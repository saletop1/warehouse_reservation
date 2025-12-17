<?php

namespace App\Http\Controllers;

use App\Models\ReservationDocument;
use App\Models\ReservationDocumentItem;
use App\Models\ReservationStock;
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
        try {
            $document = ReservationDocument::with(['items'])->findOrFail($id);

            // Ambil data stock untuk semua material di document
            $this->loadStockDataForDocument($document);

            // Hitung summary stock
            $stockSummary = $this->calculateStockSummary($document);

            return view('documents.show', compact('document', 'stockSummary'));

        } catch (\Exception $e) {
            Log::error('Error in show document: ' . $e->getMessage());
            return redirect()->route('documents.index')
                ->with('error', 'Document not found: ' . $e->getMessage());
        }
    }

            /**
     * Load stock data untuk document
     */
    private function loadStockDataForDocument($document)
    {
        foreach ($document->items as $item) {
            // Ambil stock data dari database untuk material ini
            $stockData = ReservationStock::where('document_no', $document->document_no)
                ->where('matnr', $item->material_code)
                ->get();

            if ($stockData->isNotEmpty()) {
                $totalStock = $stockData->sum(function($stock) {
                    return is_numeric($stock->clabs) ? floatval($stock->clabs) : 0;
                });

                $storageLocations = $stockData->pluck('lgort')->unique()->toArray();

                $item->stock_info = [
                    'total_stock' => $totalStock,
                    'storage_locations' => $storageLocations,
                    'details' => $stockData->map(function($stock) {
                        return [
                            'lgort' => $stock->lgort,
                            'charg' => $stock->charg,
                            'clabs' => is_numeric($stock->clabs) ? floatval($stock->clabs) : 0,
                            'meins' => $stock->meins,
                            'vbeln' => $stock->vbeln,
                            'posnr' => $stock->posnr
                        ];
                    })->toArray()
                ];
            } else {
                $item->stock_info = [
                    'total_stock' => 0,
                    'storage_locations' => [],
                    'details' => []
                ];
            }
        }
    }

     /**
     * Calculate stock summary untuk document
     */
    private function calculateStockSummary($document)
    {
        $totalMaterials = $document->items->count();
        $totalRequested = 0;
        $totalStock = 0;
        $materialsWithStock = 0;

        foreach ($document->items as $item) {
            // Hitung total requested
            $requestedQty = $item->requested_qty;
            if (is_numeric($requestedQty)) {
                $totalRequested += floatval($requestedQty);
            }

            // Hitung total stock
            $stockInfo = $item->stock_info ?? null;
            if ($stockInfo && isset($stockInfo['total_stock'])) {
                $itemStock = $stockInfo['total_stock'];
                if (is_numeric($itemStock)) {
                    $totalStock += floatval($itemStock);
                    if ($itemStock > 0) {
                        $materialsWithStock++;
                    }
                }
            }
        }

        return [
            'total_materials' => $totalMaterials,
            'materials_with_stock' => $materialsWithStock,
            'total_requested' => $totalRequested,
            'total_stock' => $totalStock,
            'balance' => $totalStock - $totalRequested
        ];
    }

            /**
         * Create transfer document from selected items
         */
        public function createTransfer(Request $request)
        {
            try {
                $request->validate([
                    'document_id' => 'required|exists:reservation_documents,id',
                    'document_no' => 'required|string',
                    'items' => 'required|array',
                    'items.*.id' => 'required|exists:reservation_document_items,id',
                    'items.*.qty' => 'required|numeric|min:0.001',
                ]);

                $document = ReservationDocument::findOrFail($request->document_id);

                // Log the transfer creation
                Log::info('Creating transfer document', [
                    'document_id' => $document->id,
                    'document_no' => $document->document_no,
                    'user_id' => auth()->id(),
                    'items_count' => count($request->items)
                ]);

                // Here you would typically:
                // 1. Create a new transfer document
                // 2. Update stock records
                // 3. Generate transfer slip
                // 4. Send notification, etc.

                // For now, return success response
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer document created successfully',
                    'data' => [
                        'document_no' => $document->document_no,
                        'transfer_id' => 'TRF-' . time(),
                        'items_count' => count($request->items),
                        'total_qty' => array_sum(array_column($request->items, 'qty')),
                        'created_at' => now()->format('Y-m-d H:i:s')
                    ]
                ]);

            } catch (\Exception $e) {
                Log::error('Error creating transfer document: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create transfer document: ' . $e->getMessage()
                ], 500);
            }
        }
    /**
     * Get stock data for a specific material and plant
     */
    private function getStockDataForMaterial($materialCode, $plant, $documentNo)
    {
        // Try to get stock data from database
        $stocks = ReservationStock::where('document_no', $documentNo)
            ->where('werk', $plant)
            ->where('matnr', $materialCode)
            ->get();

        if ($stocks->isEmpty()) {
            return [
                'total_stock' => 0,
                'storage_locations' => [],
                'details' => [],
                'has_stock' => false
            ];
        }

        // Calculate total stock and group by storage location
        $totalStock = $stocks->sum('clabs');
        $storageLocations = $stocks->pluck('lgort')->unique()->toArray();

        return [
            'total_stock' => $totalStock,
            'storage_locations' => $storageLocations,
            'details' => $stocks->map(function($stock) {
                return [
                    'lgort' => $stock->lgort,
                    'charg' => $stock->charg,
                    'clabs' => $stock->clabs,
                    'meins' => $stock->meins,
                    'vbeln' => $stock->vbeln,
                    'posnr' => $stock->posnr
                ];
            })->toArray(),
            'has_stock' => $totalStock > 0
        ];
    }

    /**
     * Format number with Indonesian format (dot for thousands, comma for decimal)
     */
    private function formatNumberIndonesian($number, $decimalPlaces = 3)
    {
        return number_format($number, $decimalPlaces, ',', '.');
    }

    public function edit($id)
    {
        $document = ReservationDocument::with('items')->findOrFail($id);

        // Process items data for edit view
        $document->items->transform(function ($item) {
            // Pastikan kita memiliki data yang konsisten
            $sources = [];
            $proDetails = [];

            // Handle jika data masih string JSON atau sudah array
            if (is_string($item->sources)) {
                $sources = json_decode($item->sources, true) ?? [];
            } elseif (is_array($item->sources)) {
                $sources = $item->sources;
            }

            if (is_string($item->pro_details)) {
                $proDetails = json_decode($item->pro_details, true) ?? [];
            } elseif (is_array($item->pro_details)) {
                $proDetails = $item->pro_details;
            }

            // Process sources to remove leading zeros
            $processedSources = [];
            foreach ($sources as $source) {
                $processedSources[] = \App\Helpers\NumberHelper::removeLeadingZeros($source);
            }

            // Ambil sortf dari pro_details pertama
            $sortf = null;
            if (!empty($proDetails) && isset($proDetails[0]['sortf'])) {
                $sortf = $proDetails[0]['sortf'];
            }

            // Add processed data
            $item->processed_sources = $processedSources;
            $item->sortf = $sortf ?? $item->sortf ?? '-';

            return $item;
        });

        return view('documents.edit', compact('document'));
    }

    public function update(Request $request, $id)
    {
        $document = ReservationDocument::findOrFail($id);

        // Validasi data
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
            'sloc_supply' => 'required|string|max:20',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:reservation_document_items,id',
            'items.*.requested_qty' => 'required|numeric|min:0',
        ]);

        // Update document
        $document->remarks = $request->remarks;
        $document->sloc_supply = $request->sloc_supply;
        $document->save();

        // Update items quantities
        foreach ($request->items as $itemData) {
            $item = ReservationDocumentItem::find($itemData['id']);
            if ($item && $item->document_id == $document->id) {
                // Cek apakah quantity editable berdasarkan MRP
                $isQtyEditable = true;
                $allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1'];

                if ($item->dispo && !in_array($item->dispo, $allowedMRP)) {
                    $isQtyEditable = false;
                }

                // Hanya update jika quantity editable
                if ($isQtyEditable) {
                    $item->requested_qty = $itemData['requested_qty'];
                    $item->save();
                }
            }
        }

        // Hitung ulang total quantity
        $totalQty = $document->items()->sum('requested_qty');
        $document->total_qty = $totalQty;
        $document->save();

        return redirect()->route('documents.show', $document->id)
            ->with('success', 'Document updated successfully.');
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
                    'Document No', 'Plant Request', 'Plant Supply', 'Status', 'Total Items', 'Total Qty',
                    'Created By', 'Created At', 'Material Code', 'Material Description',
                    'Unit', 'Requested Qty', 'Source PRO Numbers', 'Sortf', 'MRP', 'Sales Orders'
                ]);

                // Data rows
                foreach ($documents as $document) {
                    foreach ($document->items as $item) {
                        // Process sources
                        $sources = [];
                        if (is_string($item->sources)) {
                            $sources = json_decode($item->sources, true) ?? [];
                        } elseif (is_array($item->sources)) {
                            $sources = $item->sources;
                        }

                        $processedSources = array_map(function($source) {
                            return \App\Helpers\NumberHelper::removeLeadingZeros($source);
                        }, $sources);

                        // Process sales orders
                        $salesOrders = [];
                        if (is_string($item->sales_orders)) {
                            $salesOrders = json_decode($item->sales_orders, true) ?? [];
                        } elseif (is_array($item->sales_orders)) {
                            $salesOrders = $item->sales_orders;
                        }

                        // Get sortf from item
                        $sortf = $item->sortf;
                        if (empty($sortf) && is_string($item->pro_details)) {
                            $proDetails = json_decode($item->pro_details, true) ?? [];
                            if (!empty($proDetails) && isset($proDetails[0]['sortf'])) {
                                $sortf = $proDetails[0]['sortf'];
                            }
                        }

                        fputcsv($file, [
                            $document->document_no,
                            $document->plant,
                            $document->sloc_supply ?? '',
                            $document->status,
                            $document->total_items,
                            \App\Helpers\NumberHelper::formatQuantity($document->total_qty),
                            $document->created_by_name,
                            \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            $item->material_code,
                            $item->material_description,
                            $item->unit,
                            \App\Helpers\NumberHelper::formatQuantity($item->requested_qty),
                            implode(', ', $processedSources),
                            $sortf ?? '',
                            $item->dispo ?? '',
                            implode(', ', $salesOrders)
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

        // Process items data untuk print - ambil sortf dari pro_details
        $document->items->transform(function ($item) {
            // Pastikan data konsisten
            $sources = [];
            $proDetails = [];

            if (is_string($item->sources)) {
                $sources = json_decode($item->sources, true) ?? [];
            } elseif (is_array($item->sources)) {
                $sources = $item->sources;
            }

            if (is_string($item->pro_details)) {
                $proDetails = json_decode($item->pro_details, true) ?? [];
            } elseif (is_array($item->pro_details)) {
                $proDetails = $item->pro_details;
            }

            // Process sources untuk print
            $processedSources = [];
            foreach ($sources as $source) {
                $processedSources[] = \App\Helpers\NumberHelper::removeLeadingZeros($source);
            }

            // Ambil sortf dari pro_details pertama
            $sortf = null;
            if (!empty($proDetails) && isset($proDetails[0]['sortf'])) {
                $sortf = $proDetails[0]['sortf'];
            }

            // Add processed data
            $item->processed_sources = $processedSources;
            $item->sortf = $sortf ?? $item->sortf ?? '-';

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
