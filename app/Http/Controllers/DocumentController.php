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
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $document = Document::with(['items' => function($query) {
                $query->orderBy('id', 'asc');
            }])->findOrFail($id);

            // Check if user is authorized to edit (only creator can edit)
            if (Auth::id() != $document->created_by) {
                return redirect()->route('documents.show', $id)
                    ->with('info', 'You can only view this document. Only the creator can edit.');
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Check if user is authorized to edit (only creator can edit)
        if (Auth::id() != $document->created_by) {
            return redirect()->route('documents.show', $id)
                ->with('error', 'You are not authorized to edit this document. Only the creator can edit.');
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

    /**
                     * Force complete selected items - DIPERBAIKI
                     */
                    public function forceCompleteItems(Request $request, $id)
                    {
                        DB::beginTransaction();

                        try {
                            $document = Document::findOrFail($id);

                            // Check if user is authorized to edit (only creator can edit)
                            if (Auth::id() != $document->created_by) {
                                return back()->with('error', 'You are not authorized to force complete items from this document.');
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
                                    // **PERBAIKAN: HANYA set flag force_completed, JANGAN ubah quantity**
                                    $item->force_completed = true;
                                    $item->force_complete_reason = $reason;
                                    $item->force_completed_by = Auth::id();
                                    $item->force_completed_at = now();
                                    // **JANGAN ubah transferred_qty atau remaining_qty**
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

    /**
     * Recalculate document totals after item changes
     */
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

            // Check document status - PERBAIKAN: logic baru
        $status = $document->status;

        // Cek apakah semua item sudah selesai (ditransfer atau force completed)
        $allItemsFinalized = true;
        $hasForceCompleted = false;
        $hasTransfers = false;

        foreach ($items as $item) {
            if ($item->force_completed) {
                $hasForceCompleted = true;
                // Item force completed dianggap "selesai"
                continue;
            }

            if ($item->transferred_qty > 0) {
                $hasTransfers = true;
            }

            // Jika tidak force completed dan masih ada remaining_qty > 0
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

        // Update document - JANGAN update total_transferred dari force completed
        $document->update([
            'total_qty' => $totalQty,
            'total_transferred' => $totalTransferred, // Hanya dari transfer sebenarnya
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

    /**
     * Check and update document status based on item completion
     */
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
                // If there are force completed items and document was booked, change to partial
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

    /**
     * Get item transfer history dengan JOIN ke reservation_transfers
     */
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
