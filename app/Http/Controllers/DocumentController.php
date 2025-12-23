<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use Illuminate\Http\Request;
use App\Exports\DocumentExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    // ... method lainnya yang sudah ada ...

    /**
     * Export selected items to Excel
     */
    public function exportExcel(Request $request, $id)
    {
        try {
            $document = Document::with('items')->findOrFail($id);

            // Get selected items from request
            $selectedItems = json_decode($request->input('selected_items', '[]'), true);

            if (empty($selectedItems)) {
                return back()->with('error', 'Please select items to export.');
            }

            // Get only selected items
            $items = $document->items->whereIn('id', $selectedItems);

            if ($items->isEmpty()) {
                return back()->with('error', 'No items found to export.');
            }

            // Create export filename
            $filename = 'document_' . $document->document_no . '_export_' . date('Ymd_His') . '.xlsx';

            // Log the export activity
            \Log::info('Document exported to Excel', [
                'document_no' => $document->document_no,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'exported_items' => count($items)
            ]);

            // Return Excel download
            return Excel::download(new DocumentExport($document, $items), $filename);

        } catch (\Exception $e) {
            \Log::error('Error exporting document to Excel: ' . $e->getMessage(), [
                'document_id' => $id,
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Error exporting to Excel: ' . $e->getMessage());
        }
    }

    /**
     * Delete selected items from document
     */
    public function deleteSelectedItems(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $document = Document::findOrFail($id);

            // Check if document is editable (only booked status)
            if ($document->status !== 'booked') {
                return back()->with('error', 'Cannot delete items from a document with status: ' . $document->status);
            }

            // Get selected items from request
            $selectedItems = json_decode($request->input('selected_items', '[]'), true);

            if (empty($selectedItems)) {
                return back()->with('error', 'Please select items to delete.');
            }

            // Delete selected items
            $deletedCount = DocumentItem::where('document_id', $document->id)
                ->whereIn('id', $selectedItems)
                ->delete();

            // Recalculate document totals
            $this->recalculateDocumentTotals($document);

            DB::commit();

            // Log the deletion
            \Log::info('Document items deleted', [
                'document_no' => $document->document_no,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'deleted_items' => $deletedCount,
                'item_ids' => $selectedItems
            ]);

            return redirect()->route('documents.edit', $document->id)
                ->with('success', $deletedCount . ' item(s) deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error deleting document items: ' . $e->getMessage(), [
                'document_id' => $id,
                'user_id' => auth()->id(),
                'selected_items' => $request->input('selected_items')
            ]);

            return back()->with('error', 'Error deleting items: ' . $e->getMessage());
        }
    }

    /**
     * Recalculate document totals after item deletion
     */
    private function recalculateDocumentTotals(Document $document)
    {
        try {
            // Get remaining items
            $items = $document->items()->get();

            // Calculate totals
            $totalQty = $items->sum('requested_qty');
            $totalTransferred = $items->sum('transferred_qty');

            // Calculate completion rate
            $completionRate = 0;
            if ($totalQty > 0) {
                $completionRate = ($totalTransferred / $totalQty) * 100;
            }

            // Update document
            $document->update([
                'total_qty' => $totalQty,
                'total_transferred' => $totalTransferred,
                'completion_rate' => round($completionRate, 2)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error recalculating document totals: ' . $e->getMessage(), [
                'document_id' => $document->id
            ]);
            throw $e;
        }
    }

    // ... method lainnya yang sudah ada ...
}
