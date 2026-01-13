<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function __construct()
    {
        // Middleware untuk edit dokumen - HANYA creator atau role tertentu
        $this->middleware(function ($request, $next) {
            if (in_array($request->route()->getName(), ['documents.edit', 'documents.update', 'documents.items.force-complete'])) {
                $documentId = $request->route('id') ?? $request->route('document');
                $document = Document::find($documentId);

                if ($document) {
                    $user = Auth::user();
                    $isCreator = Auth::id() == $document->created_by;
                    $allowedRoles = ['admin', 'supervisor', 'warehouse'];
                    $userRole = $user->role ?? 'user';

                    // Jika bukan creator DAN bukan role yang diizinkan
                    if (!$isCreator && !in_array($userRole, $allowedRoles)) {
                        return redirect()->route('documents.show', $document->id)
                            ->with('info', 'Anda tidak memiliki izin untuk mengedit dokumen ini. Hanya creator atau admin/supervisor yang dapat mengedit.');
                    }
                }
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // Log request untuk debugging
        Log::info('Document index request:', [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'sort' => $request->get('sort'),
            'direction' => $request->get('direction'),
            'per_page' => $request->get('per_page')
        ]);

        $perPage = $request->get('per_page', 50);
        $search = $request->get('search');
        $status = $request->get('status');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        // Validasi sort column untuk mencegah SQL injection
        $allowedSorts = ['document_no', 'plant', 'status', 'completion_rate', 'created_by_name', 'created_at'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        // Validasi direction
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'desc';

        $query = Document::query();

        // Log total dokumen sebelum filter
        Log::info('Total documents before filter:', ['count' => $query->count()]);

        // Filter berdasarkan status - PERBAIKAN DI SINI
        if ($status && $status !== 'all') {
            // Jika status bukan 'all', filter berdasarkan status
            if (in_array($status, ['booked', 'partial', 'closed', 'cancelled'])) {
                $query->where('status', $status);
                Log::info('Filtering by status:', ['status' => $status]);
            }
        }
        // Jika status = 'all' atau null, tampilkan semua (tidak ada where clause tambahan)

        // Search di semua kolom yang relevan
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('document_no', 'like', "%{$search}%")
                  ->orWhere('plant', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhere('created_by_name', 'like', "%{$search}%")
                  ->orWhere(DB::raw('CAST(id AS CHAR)'), 'like', "%{$search}%");
            });
            Log::info('Search applied:', ['term' => $search]);
        }

        // Sorting
        $query->orderBy($sort, $direction);

        // Pagination dengan 50 item per halaman default
        $documents = $query->paginate($perPage);

        // Append query parameters to pagination links
        $documents->appends([
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
            'per_page' => $perPage
        ]);

        // Log hasil akhir
        Log::info('Document index results:', [
            'total' => $documents->total(),
            'per_page' => $documents->perPage(),
            'current_page' => $documents->currentPage(),
            'last_page' => $documents->lastPage()
        ]);

        // Log beberapa dokumen untuk debugging
        if ($documents->count() > 0) {
            $sampleDocs = $documents->take(3)->pluck('document_no', 'status')->toArray();
            Log::info('Sample documents:', $sampleDocs);
        }

        if ($request->ajax()) {
            // Untuk AJAX request, kembalikan view partials
            $view = view('documents.partials.table', compact('documents'))->render();
            $pagination = view('documents.partials.pagination', compact('documents'))->render();
            $counter = view('documents.partials.counter', compact('documents'))->render();

            return response()->json([
                'success' => true,
                'table' => $view,
                'pagination' => $pagination,
                'counter' => $counter
            ]);
        }

        return view('documents.index', compact('documents'));
    }

    public function edit($id)
    {
        try {
            $document = Document::with(['items' => function($query) {
                $query->orderBy('id', 'asc');
            }])->findOrFail($id);

            // Check if user is authorized to edit (only creator can edit)
            if (Auth::id() != $document->created_by) {
                return redirect()->route('documents.show', $id)
                    ->with('info', 'Anda bukan creator dokumen ini. Hanya creator yang dapat melakukan edit.');
            }

            // Tambahkan pengecekan role/tambahan jika diperlukan
            $user = Auth::user();
            $allowedRoles = ['admin', 'supervisor', 'warehouse'];
            $userRole = $user->role ?? 'user';

            if (!in_array($userRole, $allowedRoles) && Auth::id() != $document->created_by) {
                return redirect()->route('documents.show', $id)
                    ->with('info', 'Anda tidak memiliki izin untuk mengedit dokumen ini. Hanya creator atau admin/supervisor yang dapat mengedit.');
            }

            return view('documents.edit', compact('document'));

        } catch (\Exception $e) {
            Log::error('Error in DocumentController@edit: ' . $e->getMessage(), [
                'document_id' => $id,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('documents.index')
                ->with('error', 'Document not found or you do not have permission to edit.');
        }
    }

    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Check if user is authorized to edit (only creator can edit)
        if (Auth::id() != $document->created_by) {
            return redirect()->route('documents.show', $id)
                ->with('info', 'Anda bukan creator dokumen ini. Hanya creator yang dapat melakukan edit.');
        }

        // Check if document is editable (only booked or partial status)
        if (!in_array($document->status, ['booked', 'partial'])) {
            return redirect()->route('documents.show', $id)
                ->with('error', 'Cannot edit a document with status: ' . $document->status);
        }

        DB::beginTransaction();

        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'sloc_supply' => 'required|string|max:50',
                'remarks' => 'nullable|string|max:500',
                'items' => 'required|array',
                'items.*.id' => 'required|exists:reservation_document_items,id',
                'items.*.requested_qty' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Update document details
            $document->update([
                'sloc_supply' => $request->sloc_supply,
                'plant_supply' => $request->sloc_supply,
                'remarks' => $request->remarks
            ]);

            // Update items quantities
            $totalQty = 0;
            $updatedItems = 0;

            foreach ($request->items as $itemData) {
                $item = DocumentItem::find($itemData['id']);

                if ($item && $item->document_id == $document->id) {
                    // Check if item is editable (not force completed and MRP allows)
                    if ($item->is_qty_editable) {
                        $oldQty = $item->requested_qty;
                        $newQty = (float)$itemData['requested_qty'];

                        if ($oldQty != $newQty) {
                            $item->requested_qty = $newQty;

                            // Recalculate remaining quantity
                            $item->remaining_qty = max(0, $newQty - $item->transferred_qty);
                            $item->save();
                            $updatedItems++;
                        }
                    }

                    $totalQty += $item->requested_qty;
                }
            }

            // Recalculate document totals
            $this->recalculateDocumentTotals($document);

            DB::commit();

            Log::info('Document updated', [
                'document_no' => $document->document_no,
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'updated_items' => $updatedItems,
                'total_qty' => $totalQty
            ]);

            return redirect()->route('documents.edit', $document->id)
                ->with('success', 'Document updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating document: ' . $e->getMessage(), [
                'document_id' => $id,
                'user_id' => auth()->id(),
                'request_data' => $request->except(['items'])
            ]);

            return redirect()->back()
                ->with('error', 'Error updating document: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function forceCompleteItems(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $document = Document::findOrFail($id);

            // Check if user is authorized to edit (only creator can edit)
            if (Auth::id() != $document->created_by) {
                return back()->with('info', 'Anda bukan creator dokumen ini. Hanya creator yang dapat melakukan force complete items.');
            }

            // Check if document is editable (only booked or partial status)
            if (!in_array($document->status, ['booked', 'partial'])) {
                return back()->with('error', 'Cannot force complete items from a document with status: ' . $document->status);
            }

            // Get selected items from request
            $selectedItems = json_decode($request->input('selected_items', '[]'), true);

            if (empty($selectedItems)) {
                return back()->with('error', 'Please select items to force complete.');
            }

            // Get reason
            $reason = $request->input('reason');
            if (empty($reason)) {
                return back()->with('error', 'Please provide a reason for force completing items.');
            }

            // Log before force complete
            Log::info('Attempting to force complete items', [
                'document_id' => $document->id,
                'document_no' => $document->document_no,
                'selected_items' => $selectedItems,
                'user_id' => auth()->id(),
                'reason' => $reason
            ]);

            // Force complete selected items
            $forceCompletedCount = 0;
            foreach ($selectedItems as $itemId) {
                $item = DocumentItem::where('id', $itemId)
                    ->where('document_id', $document->id)
                    ->first();

                if ($item && !$item->force_completed) {
                    $item->force_completed = true;
                    $item->force_complete_reason = $reason;
                    $item->force_completed_by = Auth::id();
                    $item->force_completed_at = now();
                    $item->save();

                    $forceCompletedCount++;

                    Log::info('Item force completed', [
                        'item_id' => $item->id,
                        'material_code' => $item->material_code,
                        'requested_qty' => $item->requested_qty,
                        'transferred_qty' => $item->transferred_qty,
                        'remaining_qty' => $item->remaining_qty
                    ]);
                }
            }

            // Recalculate document totals
            $this->recalculateDocumentTotals($document);

            // Update document status if all items are force completed
            $this->checkAndUpdateDocumentStatus($document);

            DB::commit();

            // Log the force complete action
            Log::info('Document items force completed successfully', [
                'document_no' => $document->document_no,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'force_completed_items' => $forceCompletedCount,
                'item_ids' => $selectedItems,
                'reason' => $reason
            ]);

            return redirect()->route('documents.edit', $document->id)
                ->with('success', $forceCompletedCount . ' item(s) force completed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error force completing document items: ' . $e->getMessage(), [
                'document_id' => $id,
                'user_id' => auth()->id(),
                'selected_items' => $request->input('selected_items'),
                'reason' => $request->input('reason')
            ]);

            return back()->with('error', 'Error force completing items: ' . $e->getMessage());
        }
    }

    private function recalculateDocumentTotals(Document $document)
    {
        try {
            // Get all items for the document
            $items = $document->items()->get();

            // Calculate totals
            $totalQty = $items->sum('requested_qty');
            $totalTransferred = $items->sum('transferred_qty');
            $totalItems = $items->count();

            // Calculate completion rate
            $completionRate = 0;
            if ($totalQty > 0) {
                $completionRate = ($totalTransferred / $totalQty) * 100;
            }

            // Check document status
            $status = $document->status;

            // Cek apakah semua item sudah selesai (ditransfer atau force completed)
            $allItemsFinalized = true;
            $hasForceCompleted = false;
            $hasTransfers = false;

            foreach ($items as $item) {
                if ($item->force_completed) {
                    $hasForceCompleted = true;
                    continue;
                }

                if ($item->transferred_qty > 0) {
                    $hasTransfers = true;
                }

                if (!$item->force_completed && $item->remaining_qty > 0) {
                    $allItemsFinalized = false;
                }
            }

            // Update status
            if ($allItemsFinalized) {
                $status = 'closed';
            } elseif ($hasForceCompleted && $document->status == 'booked') {
                $status = 'partial';
            } elseif ($hasTransfers && $document->status == 'booked') {
                $status = 'partial';
            }

            // Update document
            $document->update([
                'total_qty' => $totalQty,
                'total_transferred' => $totalTransferred,
                'completion_rate' => round($completionRate, 2),
                'status' => $status
            ]);

            Log::info('Document totals recalculated', [
                'document_id' => $document->id,
                'document_no' => $document->document_no,
                'total_qty' => $totalQty,
                'total_transferred' => $totalTransferred,
                'completion_rate' => $completionRate,
                'status' => $status,
                'all_items_finalized' => $allItemsFinalized
            ]);

        } catch (\Exception $e) {
            Log::error('Error recalculating document totals: ' . $e->getMessage(), [
                'document_id' => $document->id
            ]);
            throw $e;
        }
    }

    private function checkAndUpdateDocumentStatus(Document $document)
    {
        try {
            // Get all items for the document
            $items = $document->items()->get();

            if ($items->isEmpty()) {
                return;
            }

            // Check if all items are either fully transferred or force completed
            $allItemsCompleted = true;
            $hasForceCompletedItems = false;

            foreach ($items as $item) {
                if ($item->force_completed) {
                    $hasForceCompletedItems = true;
                }

                if (!$item->force_completed && $item->transferred_qty < $item->requested_qty) {
                    $allItemsCompleted = false;
                }
            }

            // Update document status
            if ($allItemsCompleted) {
                $document->status = 'closed';
                $document->completion_rate = 100;
                $document->save();

                Log::info('Document status updated to closed', [
                    'document_id' => $document->id,
                    'document_no' => $document->document_no
                ]);
            } elseif ($hasForceCompletedItems && $document->status == 'booked') {
                $document->status = 'partial';
                $document->save();

                Log::info('Document status updated to partial due to force completed items', [
                    'document_id' => $document->id,
                    'document_no' => $document->document_no
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error checking document status: ' . $e->getMessage(), [
                'document_id' => $document->id
            ]);
        }
    }

    private function getItemTransferHistory($itemId)
    {
        try {
            return DB::table('reservation_transfer_items')
                ->join('reservation_transfers', 'reservation_transfer_items.transfer_id', '=', 'reservation_transfers.id')
                ->select(
                    'reservation_transfers.transfer_no',
                    'reservation_transfer_items.material_code',
                    'reservation_transfer_items.batch',
                    'reservation_transfer_items.quantity',
                    'reservation_transfer_items.unit',
                    'reservation_transfer_items.created_at'
                )
                ->where('reservation_transfer_items.document_item_id', $itemId)
                ->orderBy('reservation_transfer_items.created_at', 'desc')
                ->get()
                ->map(function ($transfer) {
                    return [
                        'transfer_no' => $transfer->transfer_no,
                        'material_code' => $transfer->material_code,
                        'batch' => $transfer->batch,
                        'quantity' => $transfer->quantity,
                        'unit' => $transfer->unit,
                        'created_at' => $transfer->created_at ?
                            \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y H:i:s') :
                            'Tanggal tidak tersedia'
                    ];
                });
        } catch (\Exception $e) {
            Log::error('Error getting item transfer history: ' . $e->getMessage(), [
                'item_id' => $itemId
            ]);
            return collect();
        }
    }
}
