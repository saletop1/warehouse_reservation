<?php

namespace App\Http\Controllers;

use App\Models\ReservationDocument;
use App\Models\ReservationDocumentItem;
use App\Models\ReservationStock;
use App\Models\ReservationTransfer;
use App\Models\ReservationTransferItem;
use Illuminate\Http\Request;
use App\Exports\ReservationDocumentsSelectedExport;
use App\Exports\DocumentItemsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Document;

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

                // Hitung status transfer per item (dipindahkan dari view ke controller)
                if ($item->remaining_qty == 0) {
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

                        /**
                 * Get transfer history for specific item - DIPERBAIKI
                 */
                public function getItemTransferHistory($documentId, $materialCode)
                {
                    try {
                        Log::info('Getting transfer history', [
                            'document_id' => $documentId,
                            'material_code' => $materialCode
                        ]);

                        // Decode material code (jika ada encoding di URL)
                        $materialCode = urldecode($materialCode);

                        Log::info('Decoded material code:', ['material_code' => $materialCode]);

                        // PERBAIKAN: Gunakan model yang benar - ReservationDocument
                        $document = ReservationDocument::find($documentId);

                        if (!$document) {
                            Log::warning('Document not found', ['document_id' => $documentId]);
                            return response()->json(['error' => 'Document not found'], 404);
                        }

                        Log::info('Document found:', [
                            'document_no' => $document->document_no,
                            'total_items' => $document->items()->count()
                        ]);

                        // PERBAIKAN: Query pencarian item yang lebih akurat
                        $item = ReservationDocumentItem::where('document_id', $documentId)
                            ->where(function($query) use ($materialCode) {
                                // Exact match
                                $query->where('material_code', $materialCode)
                                    // Match dengan atau tanpa leading zeros
                                    ->orWhereRaw("TRIM(LEADING '0' FROM material_code) = ?", [ltrim($materialCode, '0')])
                                    // Match jika material code mengandung kode
                                    ->orWhere('material_code', 'LIKE', '%' . $materialCode . '%');
                            })
                            ->first();

                        if (!$item) {
                            Log::warning('Item not found', [
                                'document_id' => $documentId,
                                'material_code' => $materialCode,
                                'search_patterns' => [
                                    'exact' => $materialCode,
                                    'without_zeros' => ltrim($materialCode, '0'),
                                    'like' => '%' . $materialCode . '%'
                                ]
                            ]);
                            return response()->json(['error' => 'Item not found'], 404);
                        }

                        Log::info('Found item:', [
                            'item_id' => $item->id,
                            'material_code' => $item->material_code,
                            'material_description' => $item->material_description
                        ]);

                        // Get transfer history
                        $transferHistory = DB::table('reservation_transfer_items')
                            ->leftJoin('reservation_transfers', 'reservation_transfer_items.transfer_id', '=', 'reservation_transfers.id')
                            ->select(
                                'reservation_transfers.transfer_no',
                                'reservation_transfer_items.material_code',
                                'reservation_transfer_items.batch',
                                'reservation_transfer_items.quantity',
                                'reservation_transfer_items.unit',
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

                                return [
                                    'transfer_no' => $transfer->transfer_no ?? 'N/A',
                                    'material_code' => $transfer->material_code,
                                    'batch' => $transfer->batch ?? 'N/A',
                                    'quantity' => (float) $transfer->quantity,
                                    'unit' => $transfer->unit ?? 'PC',
                                    'created_at' => $createdAt
                                ];
                            });

                        Log::info('Transfer history found:', [
                            'count' => $transferHistory->count(),
                            'item_id' => $item->id,
                            'data' => $transferHistory->toArray()
                        ]);

                        // Jika tidak ada history, cek apakah ada transfer items dengan material code yang sama
                        if ($transferHistory->isEmpty()) {
                            Log::info('No transfer history via document_item_id, trying material_code match...');

                            $transferHistory = DB::table('reservation_transfer_items')
                                ->leftJoin('reservation_transfers', 'reservation_transfer_items.transfer_id', '=', 'reservation_transfers.id')
                                ->where('reservation_transfers.document_id', $documentId)
                                ->where(function($query) use ($item) {
                                    $query->where('reservation_transfer_items.material_code', $item->material_code)
                                        ->orWhere('reservation_transfer_items.material_code', 'LIKE', '%' . ltrim($item->material_code, '0') . '%');
                                })
                                ->select(
                                    'reservation_transfers.transfer_no',
                                    'reservation_transfer_items.material_code',
                                    'reservation_transfer_items.batch',
                                    'reservation_transfer_items.quantity',
                                    'reservation_transfer_items.unit',
                                    'reservation_transfer_items.created_at'
                                )
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

                                    return [
                                        'transfer_no' => $transfer->transfer_no ?? 'N/A',
                                        'material_code' => $transfer->material_code,
                                        'batch' => $transfer->batch ?? 'N/A',
                                        'quantity' => (float) $transfer->quantity,
                                        'unit' => $transfer->unit ?? 'PC',
                                        'created_at' => $createdAt
                                    ];
                                });

                            Log::info('Transfer history via material_code match:', [
                                'count' => $transferHistory->count()
                            ]);
                        }

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
             * Helper method untuk format tanggal dari berbagai format input
             */
            private function formatDate($dateValue)
            {
                if (!$dateValue) {
                    return 'Tanggal tidak tersedia';
                }

                // Jika sudah string kosong atau null
                if (is_null($dateValue) || $dateValue === '') {
                    return 'Tanggal tidak tersedia';
                }

                // Coba deteksi jika sudah dalam format Indonesia
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $dateValue)) {
                    // Format: dd/mm/YYYY
                    $carbonDate = Carbon::createFromFormat('d/m/Y H:i:s', $dateValue);
                    return $carbonDate->setTimezone('Asia/Jakarta')->format('d/m/Y, H.i.s');
                }

                // Coba format MySQL datetime
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $dateValue)) {
                    // Format: YYYY-MM-DD HH:MM:SS
                    $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $dateValue);
                    return $carbonDate->setTimezone('Asia/Jakarta')->format('d/m/Y, H.i.s');
                }

                // Coba format MySQL date only
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                    // Format: YYYY-MM-DD
                    $carbonDate = Carbon::createFromFormat('Y-m-d', $dateValue);
                    return $carbonDate->setTimezone('Asia/Jakarta')->format('d/m/Y, H.i.s');
                }

                // Coba format timestamp
                if (is_numeric($dateValue)) {
                    // Unix timestamp
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

            // **PERBAIKAN: Log data yang masuk**
            Log::info('Create transfer request received:', [
                'document_id' => $id,
                'document_no' => $document->document_no,
                'transfer_items_count' => count($request->items ?? []),
                'transfer_items' => $request->items
            ]);

            $validated = $request->validate([
                'plant' => 'required|string',
                'sloc_supply' => 'required|string', // Ini sebenarnya plant_supply
                'items' => 'required|array|min:1',
                'items.*.material_code' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.001',
                'sap_credentials' => 'required|array',
                'sap_credentials.user' => 'required|string',
                'sap_credentials.passwd' => 'required|string',
            ]);

            // **PERBAIKAN: Validasi setiap item sebelum diproses**
            $validItems = [];
            foreach ($request->items as $index => $item) {
                $materialCode = $item['material_code'];

                // Format material code
                if (ctype_digit($materialCode)) {
                    $materialCode = ltrim($materialCode, '0');
                }

                // Cari item di database
                $dbItem = ReservationDocumentItem::where('document_id', $document->id)
                    ->where(function($query) use ($materialCode) {
                        $query->where('material_code', $materialCode)
                            ->orWhere('material_code', 'like', '%' . $materialCode . '%')
                            ->orWhereRaw("TRIM(LEADING '0' FROM material_code) = ?", [$materialCode]);
                    })
                    ->first();

                if (!$dbItem) {
                    Log::error('Item validation failed:', [
                        'index' => $index,
                        'requested_material_code' => $item['material_code'],
                        'formatted_material_code' => $materialCode
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Item not found: ' . $item['material_code']
                    ], 400);
                }

                $validItems[] = array_merge($item, [
                    'db_item_id' => $dbItem->id,
                    'db_material_code' => $dbItem->material_code
                ]);
            }

            // **PERBAIKAN: Update document transfer quantities dengan valid items**
            $this->updateDocumentTransferQuantities($document, $validItems);

            return response()->json([
                'success' => true,
                'message' => 'Transfer created successfully'
            ]);

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
        Log::info('=== START TRANSFER QUANTITIES UPDATE ===');

        // Set timezone ke Asia/Jakarta
        date_default_timezone_set('Asia/Jakarta');

        // **PERBAIKAN: Generate transfer number dengan cek duplikasi**
        $transferNo = $this->generateUniqueTransferNumber($document->plant);

        if (!$transferNo) {
            throw new \Exception('Failed to generate unique transfer number');
        }

        // **PERBAIKAN: Cek apakah transfer sudah ada sebelumnya**
        $existingTransfer = ReservationTransfer::where('transfer_no', $transferNo)->first();
        if ($existingTransfer) {
            Log::warning('Transfer number already exists, skipping duplicate', [
                'transfer_no' => $transferNo,
                'existing_id' => $existingTransfer->id
            ]);

            // Update items ke existing transfer
            $transfer = $existingTransfer;
        } else {
            // Create transfer record dengan data lengkap
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
                'remarks' => request()->input('remarks', ''),
                'sap_message' => 'Transfer created from document',
            ]);
        }

            Log::info('Transfer created', [
                'transfer_id' => $transfer->id,
                'transfer_no' => $transferNo,
                'document_id' => $document->id
            ]);

            // PERBAIKAN: Tambahkan definisi $now
            $now = Carbon::now('Asia/Jakarta');

            foreach ($transferItems as $index => $transferItem) {
                Log::info("Processing item {$index}:", [
                    'material_code' => $transferItem['material_code'],
                    'quantity' => $transferItem['quantity']
                ]);

                // **CRITICAL FIX: Normalize material code**
                $requestedMaterialCode = $transferItem['material_code'];
                $normalizedCode = $this->normalizeMaterialCode($requestedMaterialCode);

                // **CRITICAL FIX: Find item with multiple matching strategies**
                $item = $this->findDocumentItem($document->id, $requestedMaterialCode, $normalizedCode);

                if (!$item) {
                    Log::error('ITEM NOT FOUND!', [
                        'document_id' => $document->id,
                        'requested_code' => $requestedMaterialCode,
                        'normalized_code' => $normalizedCode
                    ]);
                    continue;
                }

                Log::info('Item found:', [
                    'item_id' => $item->id,
                    'db_material_code' => $item->material_code,
                    'requested_qty' => $item->requested_qty
                ]);

                // Parse batch_sloc untuk storage_location
                $batchSloc = $transferItem['batch_sloc'] ?? '';
                if ($batchSloc && strpos($batchSloc, 'SLOC:') === 0) {
                    $batchSloc = substr($batchSloc, 5);
                }

                // Format material code
                $materialCodeFormatted = 0;
                if (ctype_digit($item->material_code)) {
                    $materialCodeFormatted = 1;
                }

                // Di dalam method updateDocumentTransferQuantities():
                DB::table('reservation_transfer_items')->insert([
                    'transfer_id' => $transfer->id,
                    'document_item_id' => $item->id,
                    'material_code' => $item->material_code,
                    'material_code_raw' => $item->material_code,
                    'material_description' => $item->material_description,
                    'unit' => $item->unit,
                    'quantity' => $transferItem['quantity'],
                    'batch' => $transferItem['batch'] ?? null,
                    'storage_location' => $batchSloc,
                    'plant_supply' => request()->sloc_supply,
                    'plant_destination' => $transferItem['plant_dest'] ?? $document->plant,
                    'sloc_destination' => $transferItem['sloc_dest'] ?? null,
                    'item_number' => $index + 1,
                    'sap_status' => 'SUBMITTED',
                    'sap_message' => '',
                    'material_formatted' => $materialCodeFormatted,
                    'created_at' => $now, // PASTIKAN INI DIISI
                    'updated_at' => $now  // PASTIKAN INI DIISI
                ]);

                Log::info('Transfer item inserted:', [
                    'document_item_id' => $item->id,
                    'quantity' => $transferItem['quantity'],
                    'plant_supply' => request()->sloc_supply,
                    'storage_location' => $batchSloc
                ]);

                // **IMMEDIATELY update the item's transferred_qty**
                $this->recalculateItemQuantities($item->id);
            }

            // **CRITICAL: Force recalculation of ALL document totals**
            $this->recalculateDocumentTotals($document->id);

            // **CRITICAL: Update document status**
            $this->updateDocumentStatus($document);

            Log::info('=== END TRANSFER QUANTITIES UPDATE ===');

        } catch (\Exception $e) {
        Log::error('ERROR in updateDocumentTransferQuantities: ' . $e->getMessage());
        throw $e;
        }
    }

            /**
         * Generate unique transfer number dengan cek duplikasi
         */
        private function generateUniqueTransferNumber($plant)
        {
            $prefix = ($plant == '3000') ? 'TRMG' : 'TRBY';

            // Cari sequence terakhir yang unik
            $latestSeq = DB::table('reservation_transfers')
                ->select(DB::raw('COALESCE(MAX(CAST(SUBSTRING(transfer_no, 5) AS UNSIGNED)), 0) as max_seq'))
                ->where('transfer_no', 'LIKE', $prefix . '%')
                ->where('transfer_no', 'NOT LIKE', '%DUP%') // Abaikan yang sudah ditandai duplicate
                ->value('max_seq');

            $sequence = $latestSeq + 1;
            $transferNo = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);

            // Cek duplikasi
            $counter = 0;
            while (ReservationTransfer::where('transfer_no', $transferNo)->exists()) {
                // Jika ditemukan duplikasi, generate nomor baru
                $sequence++;
                $transferNo = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
                $counter++;

                if ($counter > 100) {
                    // Fallback: tambahkan timestamp
                    $timestamp = date('His');
                    $transferNo = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT) . $timestamp;

                    // Cek lagi
                    if (ReservationTransfer::where('transfer_no', $transferNo)->exists()) {
                        throw new \Exception('Failed to generate unique transfer number after multiple attempts');
                    }
                }
            }

            return $transferNo;
        }

    // **ADD THESE HELPER METHODS TO THE CONTROLLER:**

    private function normalizeMaterialCode($materialCode)
    {
        if (ctype_digit($materialCode)) {
            return ltrim($materialCode, '0');
        }
        return $materialCode;
    }

    private function findDocumentItem($documentId, $requestedCode, $normalizedCode)
    {
        // Try multiple matching strategies
        $item = ReservationDocumentItem::where('document_id', $documentId)
            ->where(function($query) use ($requestedCode, $normalizedCode) {
                // Exact match
                $query->where('material_code', $requestedCode)
                    // Match with normalized code
                    ->orWhere('material_code', $normalizedCode)
                    // Match ignoring leading zeros
                    ->orWhereRaw("TRIM(LEADING '0' FROM material_code) = ?", [$normalizedCode])
                    // Partial match
                    ->orWhere('material_code', 'LIKE', "%{$normalizedCode}%");
            })
            ->first();

        if (!$item) {
            Log::warning('Item not found with standard matching, trying fuzzy search...');

            // Fuzzy search - remove all non-alphanumeric
            $cleanCode = preg_replace('/[^A-Za-z0-9]/', '', $normalizedCode);

            $item = ReservationDocumentItem::where('document_id', $documentId)
                ->whereRaw("REGEXP_REPLACE(material_code, '[^A-Za-z0-9]', '') = ?", [$cleanCode])
                ->first();
        }

        return $item;
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
        try {
            Log::info("Recalculating quantities for item: {$itemId}");

            $item = ReservationDocumentItem::find($itemId);
            if (!$item) {
                Log::error("Item not found: {$itemId}");
                return;
            }

            // **CRITICAL: Get transferred_qty dari database**
            $transferredQty = DB::table('reservation_transfer_items')
                ->where('document_item_id', $itemId)
                ->sum('quantity');

            // **CRITICAL: Ensure not null**
            $transferredQty = $transferredQty ?? 0;

            $remainingQty = max(0, $item->requested_qty - $transferredQty);

            Log::info("Item calculations:", [
                'item_id' => $itemId,
                'requested_qty' => $item->requested_qty,
                'transferred_qty' => $transferredQty,
                'remaining_qty' => $remainingQty
            ]);

            // **CRITICAL: Direct database update (bypass model events if any)**
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

    public function fixTransferData($documentId)
    {
        try {
            $document = ReservationDocument::findOrFail($documentId);

            Log::info('Fixing transfer data for document:', [
                'document_id' => $documentId,
                'document_no' => $document->document_no
            ]);

            // 1. Cek semua transfer items dengan document_item_id NULL
            $nullTransferItems = DB::table('reservation_transfer_items as rti')
                ->join('reservation_transfers as rt', 'rti.transfer_id', '=', 'rt.id')
                ->where('rt.document_id', $documentId)
                ->whereNull('rti.document_item_id')
                ->select('rti.*')
                ->get();

            Log::info('Found ' . $nullTransferItems->count() . ' transfer items with NULL document_item_id');

            $fixedCount = 0;
            foreach ($nullTransferItems as $transferItem) {
                // Cari item berdasarkan material_code
                $item = ReservationDocumentItem::where('document_id', $documentId)
                    ->where(function($query) use ($transferItem) {
                        $materialCode = $transferItem->material_code;
                        if (ctype_digit($materialCode)) {
                            $materialCode = ltrim($materialCode, '0');
                        }

                        $query->where('material_code', $transferItem->material_code)
                            ->orWhere('material_code', 'like', '%' . $materialCode . '%')
                            ->orWhereRaw("TRIM(LEADING '0' FROM material_code) = ?", [$materialCode]);
                    })
                    ->first();

                if ($item) {
                    DB::table('reservation_transfer_items')
                        ->where('id', $transferItem->id)
                        ->update(['document_item_id' => $item->id]);

                    $fixedCount++;
                    Log::info('Fixed transfer item:', [
                        'transfer_item_id' => $transferItem->id,
                        'document_item_id' => $item->id,
                        'material_code' => $transferItem->material_code
                    ]);
                } else {
                    Log::warning('Cannot find item for transfer:', [
                        'transfer_item_id' => $transferItem->id,
                        'material_code' => $transferItem->material_code
                    ]);
                }
            }

            // 2. Recalculate semua item quantities
            foreach ($document->items as $item) {
                $this->recalculateItemQuantities($item->id);
            }

            // 3. Recalculate document totals
            $this->recalculateDocumentTotals($documentId);

            // 4. Update document status
            $this->updateDocumentStatus($document);

            return response()->json([
                'success' => true,
                'message' => 'Fixed ' . $fixedCount . ' transfer items and recalculated quantities',
                'fixed_count' => $fixedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error fixing transfer data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
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
                $allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1', 'D21', 'D22', 'GF1', 'CH4', 'D26', 'D28', 'D23', 'WE2', 'GW2'];

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
