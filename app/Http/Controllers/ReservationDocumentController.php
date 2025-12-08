<?php

namespace App\Http\Controllers;

use App\Models\ReservationDocument;
use Illuminate\Http\Request;
use App\Exports\ReservationDocumentsSelectedExport;
use Maatwebsite\Excel\Facades\Excel;

class ReservationDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = ReservationDocument::query();

        // Apply filters
        if ($request->filled('document_no')) {
            $query->where('document_no', 'like', '%' . $request->document_no . '%');
        }

        if ($request->filled('plant')) {
            $query->where('plant', $request->plant);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get total count
        $totalCount = $query->count();

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->except('page'));

        return view('documents.index', compact('documents', 'totalCount'));
    }

    public function show($id)
    {
        $document = ReservationDocument::with('items')->findOrFail($id);

        // Process items data
        $document->items->transform(function ($item) {
            // Decode JSON fields
            $sources = json_decode($item->sources, true) ?? [];
            $proDetails = json_decode($item->pro_details, true) ?? [];

            // Process sources to remove leading zeros
            $processedSources = [];
            foreach ($sources as $source) {
                $processedSources[] = \App\Helpers\NumberHelper::removeLeadingZeros($source);
            }

            // Process PRO details to remove leading zeros
            $processedProDetails = [];
            foreach ($proDetails as $detail) {
                $processedDetail = $detail;
                if (isset($detail['pro_number'])) {
                    $processedDetail['pro_number'] = \App\Helpers\NumberHelper::removeLeadingZeros($detail['pro_number']);
                }
                if (isset($detail['reservation_no'])) {
                    $processedDetail['reservation_no'] = \App\Helpers\NumberHelper::removeLeadingZeros($detail['reservation_no']);
                }
                $processedProDetails[] = $processedDetail;
            }

            // Add processed data as new properties (not modifying the original)
            $item->processed_sources = $processedSources;
            $item->processed_pro_details = $processedProDetails;

            return $item;
        });

        return view('documents.show', compact('document'));
    }

    public function export($type = 'csv')
    {
        if ($type === 'csv') {
            $filename = 'reservation_documents_' . date('Ymd_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() {
                $documents = ReservationDocument::with('items')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $file = fopen('php://output', 'w');

                // Header CSV
                fputcsv($file, [
                    'Document No', 'Plant', 'Status', 'Total Items', 'Total Qty',
                    'Created By', 'Created At', 'Material Code', 'Material Description',
                    'Unit', 'Requested Qty', 'Source PRO Numbers'
                ]);

                // Data rows
                foreach ($documents as $document) {
                    foreach ($document->items as $item) {
                        $sources = json_decode($item->sources, true) ?? [];
                        $processedSources = array_map(function($source) {
                            return \App\Helpers\NumberHelper::removeLeadingZeros($source);
                        }, $sources);

                        fputcsv($file, [
                            $document->document_no,
                            $document->plant,
                            $document->status,
                            $document->total_items,
                            \App\Helpers\NumberHelper::formatQuantity($document->total_qty),
                            $document->created_by_name,
                            \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            $item->material_code,
                            $item->material_description,
                            $item->unit,
                            \App\Helpers\NumberHelper::formatQuantity($item->requested_qty),
                            implode(', ', $processedSources)
                        ]);
                    }
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        abort(501, 'Export type not supported');
    }

    public function print($id)
    {
        $document = ReservationDocument::with('items')->findOrFail($id);

        // Process items data untuk print
        $document->items->transform(function ($item) {
            // Decode JSON fields
            $sources = json_decode($item->sources, true) ?? [];

            // Process sources untuk print - terapkan removeLeadingZeros
            $processedSources = [];
            foreach ($sources as $source) {
                $processedSources[] = \App\Helpers\NumberHelper::removeLeadingZeros($source);
            }

            // Add processed data as new properties
            $item->processed_sources = $processedSources;

            return $item;
        });

        return view('documents.print', compact('document'));
    }

    /**
     * PDF Export - redirect to print page with auto-print
     */
    public function pdf($id)
    {
        // Redirect to print page with auto-print parameter
        return redirect()->route('documents.print', ['id' => $id, 'autoPrint' => 'true']);
    }

    /**
     * Export Selected Documents to Excel
     */
    public function exportSelectedExcel(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|string'
        ]);

        // Convert comma-separated string to array
        $documentIds = explode(',', $request->document_ids);

        // Validate each ID exists
        foreach ($documentIds as $id) {
            if (!ReservationDocument::where('id', $id)->exists()) {
                return redirect()->back()->with('error', 'Invalid document ID selected.');
            }
        }

        $filename = 'selected_reservation_documents_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new ReservationDocumentsSelectedExport($documentIds), $filename);
    }

    /**
     * Export Selected Documents to PDF
     */
    public function exportSelectedPdf(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|string'
        ]);

        // Convert comma-separated string to array
        $documentIds = explode(',', $request->document_ids);

        // Validate each ID exists
        foreach ($documentIds as $id) {
            if (!ReservationDocument::where('id', $id)->exists()) {
                return redirect()->back()->with('error', 'Invalid document ID selected.');
            }
        }

        $documents = ReservationDocument::whereIn('id', $documentIds)
            ->orderBy('created_at', 'desc')
            ->get();

        // Add WIB formatted date to each document
        $documents->transform(function ($document) {
            $document->created_at_wib = \Carbon\Carbon::parse($document->created_at)
                ->setTimezone('Asia/Jakarta')
                ->format('d F Y H:i:s') . ' WIB';
            return $document;
        });

        return view('documents.export-pdf', compact('documents'));
    }
}
