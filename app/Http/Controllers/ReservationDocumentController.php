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

            // Ambil semua transfer item IDs untuk dokumen ini
            $transferItemIds = DB::table('reservation_transfer_items')
                ->join('reservation_transfers', 'reservation_transfer_items.transfer_id', '=', 'reservation_transfers.id')
                ->where('reservation_transfers.document_id', $document->id)
                ->pluck('reservation_transfer_items.document_item_id')
                ->toArray();

            // Set flag untuk setiap item
            foreach ($document->items as $item) {
                $item->has_transfer_history = in_array($item->id, $transferItemIds);

                // Hitung status transfer per item
                $this->calculateItemTransferStatus($item);
            }

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

    /**
     * Calculate item transfer status
     */
    private function calculateItemTransferStatus($item)
    {
        // Prioritaskan force completed
        if ($item->force_completed) {
            $item->transfer_status = 'force_completed';
            $item->transfer_badge_class = 'bg-success';
            $item->transfer_icon = 'fa-check-double';
            $item->transfer_label = 'Force Completed';
            $item->has_transfer_history = true;
        } elseif ($item->remaining_qty == 0) {
            $item->transfer_status = 'completed';
            $item->transfer_badge_class = 'bg-success';
            $item->transfer_icon = 'fa-check-circle';
            $item->transfer_label = 'Completed';
        } elseif ($item->transferred_qty > 0 && $item->remaining_qty > 0) {
            $item->transfer_status = 'partial';
            $item->transfer_badge_class = 'bg-info';
            $item->transfer_icon = 'fa-tasks';
            $item->transfer_label = 'Partial';
        } else {
            $item->transfer_status = 'pending';
            $item->transfer_badge_class = 'bg-secondary';
            $item->transfer_icon = 'fa-clock';
            $item->transfer_label = 'Pending';
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

    /**
     * Get transfer history for specific item
     */
    public function getItemTransferHistory($documentId, $materialCode)
    {
        try {
            Log::info('Getting transfer history', [
                'document_id' => $documentId,
                'material_code' => $materialCode
            ]);

            // Decode material code
            $materialCode = urldecode($materialCode);

            Log::info('Decoded material code:', ['material_code' => $materialCode]);

            $document = ReservationDocument::find($documentId);

            if (!$document) {
                Log::warning('Document not found', ['document_id' => $documentId]);
                return response()->json(['error' => 'Document not found'], 404);
            }

            // Query pencarian item
            $item = ReservationDocumentItem::where('document_id', $documentId)
                ->where(function($query) use ($materialCode) {
                    $query->where('material_code', $materialCode)
                        ->orWhereRaw("TRIM(LEADING '0' FROM material_code) = ?", [ltrim($materialCode, '0')])
                        ->orWhere('material_code', 'LIKE', '%' . $materialCode . '%');
                })
                ->first();

            if (!$item) {
                Log::warning('Item not found', [
                    'document_id' => $documentId,
                    'material_code' => $materialCode
                ]);
                return response()->json(['error' => 'Item not found'], 404);
            }

            // Get transfer history dengan field tambahan
            $transferHistory = DB::table('reservation_transfer_items')
                ->leftJoin('reservation_transfers', 'reservation_transfer_items.transfer_id', '=', 'reservation_transfers.id')
                ->select(
                    'reservation_transfers.transfer_no',
                    'reservation_transfers.created_by_name',
                    'reservation_transfer_items.material_code',
                    'reservation_transfer_items.batch',
                    'reservation_transfer_items.quantity',
                    'reservation_transfer_items.unit',
                    'reservation_transfer_items.storage_location as batch_sloc',
                    'reservation_transfer_items.sloc_destination',
                    'reservation_transfer_items.created_at'
                )
                ->where('reservation_transfer_items.document_item_id', $item->id)
                ->orderBy('reservation_transfer_items.created_at', 'desc')
                ->get()
                ->map(function ($transfer) {
                    $createdAt = null;

                    if ($transfer->created_at) {
                        try {
                            $createdAt = Carbon::parse($transfer->created_at)
                                ->setTimezone('Asia/Jakarta')
                                ->format('d/m/Y H:i:s');
                        } catch (\Exception $e) {
                            $createdAt = 'Tanggal tidak valid';
                        }
                    } else {
                        $createdAt = 'Tanggal tidak tersedia';
                    }

                    // Format material code: hapus leading zero jika numerik
                    $materialCode = $transfer->material_code;
                    if (ctype_digit($materialCode)) {
                        $materialCode = ltrim($materialCode, '0');
                    }

                    return [
                        'transfer_no' => $transfer->transfer_no ?? 'N/A',
                        'created_by_name' => $transfer->created_by_name ?? 'N/A',
                        'material_code' => $materialCode,
                        'batch' => $transfer->batch ?? 'N/A',
                        'quantity' => (float) $transfer->quantity,
                        'unit' => $transfer->unit ?? 'PC',
                        'batch_sloc' => $transfer->batch_sloc ?? 'N/A',
                        'sloc_destination' => $transfer->sloc_destination ?? 'N/A',
                        'created_at' => $createdAt
                    ];
                });

            Log::info('Transfer history found:', [
                'count' => $transferHistory->count(),
                'item_id' => $item->id
            ]);

            return response()->json($transferHistory);

        } catch (\Exception $e) {
            Log::error('Error getting item transfer history: ' . $e->getMessage(), [
                'document_id' => $documentId,
                'material_code' => $materialCode,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method untuk format tanggal
     */
    private function formatDate($dateValue)
    {
        if (!$dateValue) {
            return 'Tanggal tidak tersedia';
        }

        if (is_null($dateValue) || $dateValue === '') {
            return 'Tanggal tidak tersedia';
        }

        // Coba deteksi jika sudah dalam format Indonesia
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $dateValue)) {
            $carbonDate = Carbon::createFromFormat('d/m/Y H:i:s', $dateValue);
            return $carbonDate->setTimezone('Asia/Jakarta')->format('d/m/Y, H.i.s');
        }

        // Coba format MySQL datetime
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $dateValue)) {
            $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $dateValue);
            return $carbonDate->setTimezone('Asia/Jakarta')->format('d/m/Y, H.i.s');
        }

        // Coba format MySQL date only
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
            $carbonDate = Carbon::createFromFormat('Y-m-d', $dateValue);
            return $carbonDate->setTimezone('Asia/Jakarta')->format('d/m/Y, H.i.s');
        }

        // Coba format timestamp
        if (is_numeric($dateValue)) {
            $carbonDate = Carbon::createFromTimestamp($dateValue);
            return $carbonDate->setTimezone('Asia/Jakarta')->format('d/m/Y, H.i.s');
        }

        // Coba parsing umum dengan Carbon
        try {
            $carbonDate = Carbon::parse($dateValue);
            return $carbonDate->setTimezone('Asia/Jakarta')->format('d/m/Y, H.i.s');
        } catch (\Exception $e) {
            Log::warning('Carbon parse failed for date:', [
                'date' => $dateValue,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if item is transferable
     */
    private function isItemTransferable($item)
    {
        // Jika item sudah force completed, tidak bisa ditransfer
        if ($item->force_completed ?? false) {
            return false;
        }

        // Cek remaining quantity dan stock
        $remainingQty = $item->remaining_qty ?? 0;
        $totalStock = $item->stock_info['total_stock'] ?? 0;

        return $remainingQty > 0 && $totalStock > 0;
    }

    private function hasTransferableItems($document)
    {
        foreach ($document->items as $item) {
            if ($this->isItemTransferable($item)) {
                return true;
            }
        }
        return false;
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
                $allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1', 'D21', 'D22', 'GF1', 'CH4', 'D26', 'D28', 'D23', 'DR1', 'DR2', 'WE2', 'GW2'];

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
            $newStatus = $oldStatus;

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

    /**
     * Validate batch stock availability
     */
    private function validateBatchStock($documentNo, $materialCode, $batch, $storageLocation, $requestedQty)
    {
        $stock = ReservationStock::where('document_no', $documentNo)
            ->where('matnr', $materialCode)
            ->where('charg', $batch)
            ->where('lgort', $storageLocation)
            ->first();

        if (!$stock) {
            return [
                'valid' => false,
                'message' => 'Batch not found or no stock available',
                'available' => 0
            ];
        }

        $availableStock = is_numeric($stock->clabs) ? floatval($stock->clabs) : 0;

        if ($availableStock < $requestedQty) {
            return [
                'valid' => false,
                'message' => 'Insufficient stock. Available: ' . $availableStock,
                'available' => $availableStock
            ];
        }

        return [
            'valid' => true,
            'available' => $availableStock
        ];
    }

    /**
     * Recalculate document totals
     */
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

    /**
     * Recaculate item quantities
     */
    private function recalculateItemQuantities($itemId)
    {
        try {
            Log::info("Recalculating quantities for item: {$itemId}");

            $item = ReservationDocumentItem::find($itemId);
            if (!$item) {
                Log::error("Item not found: {$itemId}");
                return;
            }

            // Jika force completed, skip recalculating quantities
            if ($item->force_completed) {
                Log::info("Item is force completed, skipping quantity recalculation", [
                    'item_id' => $itemId,
                    'force_completed' => $item->force_completed
                ]);
                return;
            }

            // Get transferred_qty dari database
            $transferredQty = DB::table('reservation_transfer_items')
                ->where('document_item_id', $itemId)
                ->sum('quantity');

            $transferredQty = $transferredQty ?? 0;

            $remainingQty = max(0, $item->requested_qty - $transferredQty);

            Log::info("Item calculations:", [
                'item_id' => $itemId,
                'requested_qty' => $item->requested_qty,
                'transferred_qty' => $transferredQty,
                'remaining_qty' => $remainingQty
            ]);

            // Direct database update
            DB::table('reservation_document_items')
                ->where('id', $itemId)
                ->update([
                    'transferred_qty' => $transferredQty,
                    'remaining_qty' => $remainingQty,
                    'updated_at' => now()
                ]);

            Log::info("✅ Item updated in database");

        } catch (\Exception $e) {
            Log::error("❌ Error in recalculateItemQuantities: " . $e->getMessage());
        }
    }
}
