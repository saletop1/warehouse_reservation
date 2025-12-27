<?php

namespace App\Http\Controllers;

use App\Models\ReservationDocument;
use App\Models\ReservationDocumentItem;
use App\Models\ReservationStock;
use App\Models\ReservationTransfer;
use Illuminate\Http\Request;
use App\Exports\ReservationDocumentsSelectedExport;
use App\Exports\DocumentItemsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReservationDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = ReservationDocument::withCount(['transfers', 'items']);

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

        $totalCount = $query->count();
        $perPage = $request->get('per_page', 20);
        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->except('page'));

        foreach ($documents as $document) {
            $totalRequested = $document->items()->sum('requested_qty');
            $totalTransferred = $document->items()->sum('transferred_qty') ?? 0;

            if ($totalTransferred >= $totalRequested && $document->status != 'closed') {
                $document->status = 'closed';
                $document->save();
            } elseif ($totalTransferred > 0 && $document->status == 'booked') {
                $document->status = 'partial';
                $document->save();
            }
        }

        return view('documents.index', compact('documents', 'totalCount'));
    }

    public function show($id)
    {
        try {
            $document = ReservationDocument::with(['items', 'transfers'])->findOrFail($id);

            $this->loadStockDataForDocument($document);

            $user = Auth::user();
            $allowedRoles = ['warehouse', 'developer', 'admin', 'supervisor'];
            $userRole = $user->role ?? 'user';
            $canGenerateTransfer = in_array($userRole, $allowedRoles);

            $hasTransferableItems = $this->hasTransferableItems($document);

            return view('documents.show', compact(
                'document',
                'canGenerateTransfer',
                'hasTransferableItems'
            ));

        } catch (\Exception $e) {
            Log::error('Error in show document: ' . $e->getMessage());
            return redirect()->route('documents.index')
                ->with('error', 'Document not found: ' . $e->getMessage());
        }
    }

    private function loadStockDataForDocument($document)
    {
        foreach ($document->items as $item) {
            // Load stock data
            $stockData = ReservationStock::where('document_no', $document->document_no)
                ->where('matnr', $item->material_code)
                ->get();

            if ($stockData->isNotEmpty()) {
                $totalStock = $stockData->sum(function($stock) {
                    return is_numeric($stock->clabs) ? floatval($stock->clabs) : 0;
                });

                $item->stock_info = [
                    'total_stock' => $totalStock,
                    'details' => $stockData->map(function($stock) {
                        return [
                            'lgort' => $stock->lgort,
                            'charg' => $stock->charg,
                            'clabs' => is_numeric($stock->clabs) ? floatval($stock->clabs) : 0,
                            'meins' => $stock->meins
                        ];
                    })->toArray()
                ];
            } else {
                $item->stock_info = [
                    'total_stock' => 0,
                    'details' => []
                ];
            }

            // Calculate transferred quantity
            $transferItems = DB::table('reservation_transfer_items')
                ->where('document_item_id', $item->id)
                ->get();

            $transferredQty = $transferItems->sum('quantity');

            // Calculate remaining quantity
            $remainingQty = max(0, $item->requested_qty - $transferredQty);

            // Prepare arrays for view
            $sources = is_string($item->sources) ? json_decode($item->sources, true) ?? [] : ($item->sources ?? []);
            $salesOrders = is_string($item->sales_orders) ? json_decode($item->sales_orders, true) ?? [] : ($item->sales_orders ?? []);

            $item->sources_array = $sources;
            $item->sales_orders_array = $salesOrders;
            $item->transferred_qty = $transferredQty;
            $item->remaining_qty = $remainingQty;
        }
    }

    public function getItemTransferHistory($id, $materialCode)
    {
        try {
            $materialCode = urldecode($materialCode);
            $document = ReservationDocument::find($id);

            if (!$document) {
                return response()->json([
                    'error' => 'Document not found',
                    'document_id' => $id
                ], 404);
            }

            $results = DB::table('reservation_transfer_items as rti')
                ->join('reservation_transfers as rt', 'rti.transfer_id', '=', 'rt.id')
                ->where('rt.document_no', $document->document_no)
                ->where('rti.material_code', 'LIKE', '%' . $materialCode . '%')
                ->select(
                    'rt.transfer_no',
                    'rti.material_code',
                    'rti.batch',
                    'rti.quantity',
                    'rti.unit',
                    'rti.created_at'
                )
                ->orderBy('rti.created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    if ($item->created_at) {
                        $item->created_at = Carbon::parse($item->created_at)->format('Y-m-d H:i:s');
                    }
                    return $item;
                });

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('Error in getItemTransferHistory: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function hasTransferableItems($document)
    {
        foreach ($document->items as $item) {
            $totalStock = $item->stock_info['total_stock'] ?? 0;
            $remainingQty = $item->remaining_qty ?? 0;

            if ($remainingQty > 0 && $totalStock > 0) {
                return true;
            }
        }
        return false;
    }

    public function createTransfer(Request $request, $id)
    {
        try {
            $document = ReservationDocument::findOrFail($id);

            $validated = $request->validate([
                'plant' => 'required|string',
                'sloc_supply' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.material_code' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.001',
                'sap_credentials' => 'required|array',
                'sap_credentials.user' => 'required|string',
                'sap_credentials.passwd' => 'required|string',
            ]);

            $pythonData = [
                'transfer_data' => [
                    'transfer_info' => [
                        'document_no' => $document->document_no,
                        'plant_supply' => $request->sloc_supply,
                        'plant_destination' => $document->plant,
                        'move_type' => '311',
                        'posting_date' => Carbon::now()->format('Ymd'),
                        'header_text' => 'Transfer from Document ' . $document->document_no,
                        'remarks' => $request->input('transfer_data.remarks', ''),
                        'created_by' => Auth::user()->name,
                        'created_at' => Carbon::now()->format('Ymd')
                    ],
                    'items' => $request->items
                ],
                'sap_credentials' => $request->sap_credentials,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name
            ];

            $pythonServiceUrl = env('PYTHON_SERVICE_URL', 'http://localhost:5000/api/sap/transfer');

            $client = new \GuzzleHttp\Client();
            $response = $client->post($pythonServiceUrl, [
                'json' => $pythonData,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'timeout' => 120
            ]);

            $responseData = json_decode($response->getBody(), true);

            if ($responseData['success'] === true) {
                $this->updateDocumentTransferQuantities($document, $request->items);
                $this->updateDocumentStatus($document);

                return response()->json([
                    'success' => true,
                    'message' => $responseData['message'],
                    'transfer_no' => $responseData['transfer_no'] ?? null
                ]);
            } else {
                throw new \Exception($responseData['message'] ?? 'Transfer failed');
            }

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Python service connection error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Cannot connect to transfer service.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error creating transfer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateDocumentTransferQuantities($document, $transferItems)
    {
        try {
            $transferNo = $this->generateTransferNumber($document->plant);

            $transfer = ReservationTransfer::create([
                'transfer_no' => $transferNo,
                'document_id' => $document->id,
                'document_no' => $document->document_no,
                'plant_supply' => request()->sloc_supply,
                'plant_destination' => $document->plant,
                'move_type' => '311',
                'status' => 'completed',
                'created_by' => Auth::user()->id,
                'created_by_name' => Auth::user()->name,
                'total_qty' => array_sum(array_column($transferItems, 'quantity')),
                'total_items' => count($transferItems),
                'remarks' => request()->input('transfer_data.remarks', ''),
            ]);

            foreach ($transferItems as $transferItem) {
                $item = ReservationDocumentItem::where('document_id', $document->id)
                    ->where('material_code', $transferItem['material_code'])
                    ->first();

                if ($item) {
                    DB::table('reservation_transfer_items')->insert([
                        'transfer_id' => $transfer->id,
                        'document_item_id' => $item->id,
                        'material_code' => $item->material_code,
                        'material_description' => $item->material_description,
                        'unit' => $item->unit,
                        'quantity' => $transferItem['quantity'],
                        'batch' => $transferItem['batch'] ?? null,
                        'storage_location' => $transferItem['batch_sloc'] ?? null,
                        'plant_supply' => request()->sloc_supply,
                        'plant_destination' => $transferItem['plant_dest'] ?? $document->plant,
                        'sloc_destination' => $transferItem['sloc_dest'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->recalculateItemQuantities($item->id);
                }
            }

            $this->recalculateDocumentTotals($document->id);

        } catch (\Exception $e) {
            Log::error('Error updating transfer quantities: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateTransferNumber($plant)
    {
        $prefix = ($plant == '3000') ? 'TRMG' : 'TRBY';

        $latestSeq = DB::table('reservation_transfers')
            ->select(DB::raw('COALESCE(MAX(CAST(SUBSTRING(transfer_no, 5) AS UNSIGNED)), 0) as max_seq'))
            ->where('transfer_no', 'LIKE', $prefix . '%')
            ->value('max_seq');

        $sequence = $latestSeq + 1;
        $transferNo = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);

        $counter = 0;
        while (DB::table('reservation_transfers')->where('transfer_no', $transferNo)->exists()) {
            $sequence++;
            $transferNo = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
            $counter++;
            if ($counter > 100) {
                throw new \Exception('Failed to generate unique transfer number');
            }
        }

        return $transferNo;
    }

    private function updateDocumentStatus($document)
    {
        $totalRequested = $document->items()->sum('requested_qty');
        $totalTransferred = $document->items()->sum('transferred_qty');

        $oldStatus = $document->status;
        $newStatus = $oldStatus;

        if ($totalTransferred >= $totalRequested) {
            $newStatus = 'closed';
        } elseif ($totalTransferred > 0 && $oldStatus == 'booked') {
            $newStatus = 'partial';
        } elseif ($totalTransferred == 0 && $oldStatus == 'created') {
            $newStatus = 'booked';
        }

        if ($newStatus != $oldStatus) {
            $document->status = $newStatus;
            $document->save();
        }
    }

    private function recalculateItemQuantities($itemId)
    {
        $item = ReservationDocumentItem::find($itemId);
        if (!$item) return;

        $transferredQty = DB::table('reservation_transfer_items')
            ->where('document_item_id', $itemId)
            ->sum('quantity');

        $remainingQty = max(0, $item->requested_qty - $transferredQty);

        $item->update([
            'transferred_qty' => $transferredQty,
            'remaining_qty' => $remainingQty
        ]);
    }

    private function recalculateDocumentTotals($documentId)
    {
        $document = ReservationDocument::find($documentId);
        if (!$document) return;

        $totalTransferred = ReservationDocumentItem::where('document_id', $documentId)
            ->sum('transferred_qty');

        $totalRequested = ReservationDocumentItem::where('document_id', $documentId)
            ->sum('requested_qty');

        $completionRate = $totalRequested > 0 ? ($totalTransferred / $totalRequested) * 100 : 0;

        $document->update([
            'total_transferred' => $totalTransferred,
            'completion_rate' => $completionRate
        ]);
    }

    public function edit($id)
    {
        $document = ReservationDocument::with('items')->findOrFail($id);

        if ($document->status != 'booked') {
            return redirect()->route('documents.show', $document->id)
                ->with('error', 'Only documents with status "Booked" can be edited.');
        }

        return view('documents.edit', compact('document'));
    }

    public function update(Request $request, $id)
    {
        $document = ReservationDocument::findOrFail($id);

        if ($document->status != 'booked') {
            return redirect()->route('documents.show', $document->id)
                ->with('error', 'Only documents with status "Booked" can be edited.');
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
            'sloc_supply' => 'required|string|max:20',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:reservation_document_items,id',
            'items.*.requested_qty' => 'required|numeric|min:0',
        ]);

        $document->remarks = $request->remarks;
        $document->sloc_supply = $request->sloc_supply;
        $document->save();

        $totalQty = 0;
        foreach ($request->items as $itemData) {
            $item = ReservationDocumentItem::find($itemData['id']);
            if ($item && $item->document_id == $document->id) {
                $isQtyEditable = true;
                $allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1', 'D21', 'D22', 'GF1', 'CH4', 'MF3', 'D28', 'D23', 'WE2'];

                if ($item->dispo && !in_array($item->dispo, $allowedMRP)) {
                    $isQtyEditable = false;
                }

                if ($isQtyEditable) {
                    $item->requested_qty = $itemData['requested_qty'];
                    $item->save();
                }
                $totalQty += $item->requested_qty;
            }
        }

        $document->total_qty = $totalQty;
        $document->total_items = count($request->items);
        $document->save();

        $this->recalculateDocumentTotals($document->id);

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

                fputcsv($file, [
                    'Document No', 'Plant Request', 'Plant Supply', 'Status', 'Total Items', 'Total Qty',
                    'Total Transferred', 'Completion Rate', 'Created By', 'Created At', 'Material Code',
                    'Material Description', 'Unit', 'Requested Qty', 'Transferred Qty', 'Remaining Qty',
                    'Source PRO Numbers', 'Sortf', 'MRP', 'Sales Orders'
                ]);

                foreach ($documents as $document) {
                    foreach ($document->items as $item) {
                        $sources = is_string($item->sources) ? json_decode($item->sources, true) ?? [] : ($item->sources ?? []);
                        $salesOrders = is_string($item->sales_orders) ? json_decode($item->sales_orders, true) ?? [] : ($item->sales_orders ?? []);

                        $processedSources = array_map(function($source) {
                            return \App\Helpers\NumberHelper::removeLeadingZeros($source);
                        }, $sources);

                        fputcsv($file, [
                            $document->document_no,
                            $document->plant,
                            $document->sloc_supply ?? '',
                            $document->status,
                            $document->total_items,
                            \App\Helpers\NumberHelper::formatQuantity($document->total_qty),
                            \App\Helpers\NumberHelper::formatQuantity($document->total_transferred ?? 0),
                            round($document->completion_rate ?? 0, 2) . '%',
                            $document->created_by_name,
                            Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            $item->material_code,
                            $item->material_description,
                            $item->unit,
                            \App\Helpers\NumberHelper::formatQuantity($item->requested_qty),
                            \App\Helpers\NumberHelper::formatQuantity($item->transferred_qty ?? 0),
                            \App\Helpers\NumberHelper::formatQuantity(max(0, $item->requested_qty - ($item->transferred_qty ?? 0))),
                            implode(', ', $processedSources),
                            $item->sortf ?? '',
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
        $document = ReservationDocument::with(['items', 'transfers'])->findOrFail($id);

        return view('documents.print', compact('document'));
    }

    public function printSelected(Request $request, $id)
    {
        $document = ReservationDocument::with('items')->findOrFail($id);

        $selectedItems = json_decode($request->input('selected_items', '[]'), true);

        if (empty($selectedItems)) {
            return redirect()->route('documents.print', $document->id)
                ->with('error', 'No items selected for printing.');
        }

        $items = $document->items()->whereIn('id', $selectedItems)->get();

        return view('documents.print-selected', compact('document', 'items'));
    }

    public function pdf($id)
    {
        return redirect()->route('documents.print', ['id' => $id, 'autoPrint' => 'true']);
    }

    public function exportExcel(Request $request, $id)
    {
        $document = ReservationDocument::with('items')->findOrFail($id);

        $selectedItems = json_decode($request->input('selected_items', '[]'), true);

        if (empty($selectedItems)) {
            return redirect()->route('documents.print', $document->id)
                ->with('error', 'No items selected for export.');
        }

        $items = $document->items()->whereIn('id', $selectedItems)->get();

        $filename = 'document_' . $document->document_no . '_selected_items_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new DocumentItemsExport($items, $document), $filename);
    }

    public function exportSelectedExcel(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|string'
        ]);

        $documentIds = explode(',', $request->document_ids);

        foreach ($documentIds as $id) {
            if (!ReservationDocument::where('id', $id)->exists()) {
                return redirect()->back()->with('error', 'Invalid document ID selected.');
            }
        }

        $filename = 'selected_reservation_documents_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new ReservationDocumentsSelectedExport($documentIds), $filename);
    }

    public function exportSelectedPdf(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|string'
        ]);

        $documentIds = explode(',', $request->document_ids);

        foreach ($documentIds as $id) {
            if (!ReservationDocument::where('id', $id)->exists()) {
                return redirect()->back()->with('error', 'Invalid document ID selected.');
            }
        }

        $documents = ReservationDocument::whereIn('id', $documentIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $documents->transform(function ($document) {
            $document->created_at_wib = Carbon::parse($document->created_at)
                ->setTimezone('Asia/Jakarta')
                ->format('d F Y H:i:s') . ' WIB';
            return $document;
        });

        return view('documents.export-pdf', compact('documents'));
    }

    public function deleteSelectedItems(Request $request, $id)
    {
        $document = ReservationDocument::findOrFail($id);

        if ($document->status != 'booked') {
            return redirect()->route('documents.edit', $document->id)
                ->with('error', 'Only documents with status "Booked" can have items deleted.');
        }

        $selectedItems = json_decode($request->input('selected_items', '[]'), true);

        if (empty($selectedItems)) {
            return redirect()->route('documents.edit', $document->id)
                ->with('error', 'No items selected for deletion.');
        }

        $deletedCount = ReservationDocumentItem::where('document_id', $document->id)
            ->whereIn('id', $selectedItems)
            ->delete();

        $this->recalculateDocumentTotals($document->id);

        return redirect()->route('documents.edit', $document->id)
            ->with('success', $deletedCount . ' items deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $document = ReservationDocument::findOrFail($id);
            $user = Auth::user();

            if (!$user->hasPermissionTo('toggle_document_status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to toggle document status'
                ], 403);
            }

            $oldStatus = $document->status;

            if ($oldStatus == 'closed') {
                $document->status = 'booked';
            } else {
                $document->status = 'closed';
            }

            $document->save();

            return response()->json([
                'success' => true,
                'message' => 'Document status updated successfully',
                'new_status' => $document->status
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling document status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle document status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logUnauthorizedAttempt(Request $request, $id)
    {
        try {
            $document = ReservationDocument::findOrFail($id);

            Log::warning('Unauthorized SAP attempt', [
                'document_no' => $document->document_no,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unauthorized attempt logged'
            ]);

        } catch (\Exception $e) {
            Log::error('Error logging unauthorized attempt: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to log unauthorized attempt'
            ], 500);
        }
    }
}
