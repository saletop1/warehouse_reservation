<?php

namespace App\Http\Controllers;

use App\Models\ReservationDocument;
use App\Models\ReservationDocumentItem;
use App\Models\ReservationStock;
use App\Models\ReservationTransfer;
use App\Models\TransferItem;
use App\Models\Transfer;
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

        // Update document status based on transfers
        foreach ($documents as $document) {
            $totalRequested = $document->items()->sum('requested_qty');
            $totalTransferred = $document->items()->sum('transferred_qty') ?? 0;

            // Update document status if needed
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

            // PERBAIKAN: Panggil loadStockDataForDocument untuk memuat data stock
            $this->loadStockDataForDocument($document);

            // Cukup ambil data yang diperlukan untuk stock info
            $stockSummary = $this->calculateStockSummary($document); // Ini hanya untuk tampilan stock

            // Cek apakah user memiliki hak akses untuk membuat transfer
            $user = Auth::user();
            $allowedRoles = ['warehouse', 'developer', 'admin', 'supervisor'];
            $userRole = $user->role ?? 'user';
            $canGenerateTransfer = in_array($userRole, $allowedRoles);

            // Cek apakah masih ada item yang bisa ditransfer
            $hasTransferableItems = $this->hasTransferableItems($document);

            return view('documents.show', compact(
                'document',
                'stockSummary',
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

                // PERUBAHAN: Ambil data transfer dari reservation_transfer_items
                $transferItems = DB::table('reservation_transfer_items')
                    ->leftJoin('reservation_transfers', 'reservation_transfer_items.transfer_id', '=', 'reservation_transfers.id')
                    ->select(
                        'reservation_transfer_items.*',
                        'reservation_transfers.transfer_no',
                        'reservation_transfers.created_at as transfer_date',
                        'reservation_transfers.created_by_name as transfer_by'
                    )
                    ->where('reservation_transfer_items.document_item_id', $item->id)
                    ->orderBy('reservation_transfer_items.created_at', 'desc')
                    ->get();

                // Hitung total transferred dari transfer items
                $transferredQty = $transferItems->sum('quantity');
                $remainingQty = max(0, $item->requested_qty - $transferredQty);

                // Simpan data transfer untuk item ini
                $item->transfer_history = $transferItems;
                $item->transferred_qty = $transferredQty;
                $item->remaining_qty = $remainingQty;
                $item->transfer_status = $this->getItemTransferStatus($item);
            }
        }

                /**
                 * Get transfer history for specific item
                 */
                public function getItemTransferHistory($id, $materialCode)
                {
                    try {
                        Log::info('=== START getItemTransferHistory ===');
                        Log::info('Document ID: ' . $id);
                        Log::info('Material Code: ' . $materialCode);

                        // Decode material code
                        $materialCode = urldecode($materialCode);
                        Log::info('Decoded Material Code: ' . $materialCode);

                        // Cari document
                        $document = \App\Models\ReservationDocument::find($id);
                        if (!$document) {
                            Log::error('Document not found: ' . $id);
                            return response()->json([
                                'error' => 'Document not found',
                                'document_id' => $id
                            ], 404);
                        }

                        Log::info('Document found: ' . $document->document_no);

                        // PERBAIKAN: Gunakan model yang benar (ReservationTransfer dan ReservationTransferItem)
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
                                'rti.created_at',
                                'rt.status'
                            )
                            ->orderBy('rti.created_at', 'desc')
                            ->get()
                            ->map(function ($item) {
                                // Format created_at
                                if ($item->created_at) {
                                    $item->created_at = \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i:s');
                                }
                                return $item;
                            });

                        Log::info('Query results count: ' . $results->count());

                        return response()->json($results);

                    } catch (\Exception $e) {
                        Log::error('Error in getItemTransferHistory: ' . $e->getMessage());
                        Log::error('Trace: ' . $e->getTraceAsString());

                        return response()->json([
                            'error' => 'Internal Server Error',
                            'message' => $e->getMessage()
                        ], 500);
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
        $totalTransferred = 0;
        $materialsWithStock = 0;
        $materialsTransferred = 0;

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

            // Hitung total transferred
            $transferredQty = $item->transferred_qty ?? 0;
            $totalTransferred += $transferredQty;
            if ($transferredQty > 0) {
                $materialsTransferred++;
            }
        }

        $completionRate = $totalRequested > 0 ? ($totalTransferred / $totalRequested) * 100 : 0;

        return [
            'total_materials' => $totalMaterials,
            'materials_with_stock' => $materialsWithStock,
            'materials_transferred' => $materialsTransferred,
            'total_requested' => $totalRequested,
            'total_stock' => $totalStock,
            'total_transferred' => $totalTransferred,
            'balance' => $totalStock - $totalRequested,
            'completion_rate' => $completionRate
        ];
    }

    /**
     * Calculate transfer status untuk document
     */
    private function calculateTransferStatus($document)
    {
        $totalRequested = $document->items()->sum('requested_qty');
        $totalTransferred = $document->items()->sum('transferred_qty') ?? 0;

        // Update status document berdasarkan transfer
        $oldStatus = $document->status;
        $newStatus = $oldStatus;

        if ($totalTransferred >= $totalRequested) {
            $newStatus = 'closed';
        } elseif ($totalTransferred > 0) {
            $newStatus = 'partial';
        } elseif ($oldStatus == 'created') {
            $newStatus = 'booked';
        }

        // Update status jika berubah
        if ($newStatus != $oldStatus) {
            $document->status = $newStatus;
            $document->save();

            Log::info('Document status updated', [
                'document_no' => $document->document_no,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'total_transferred' => $totalTransferred,
                'total_requested' => $totalRequested
            ]);
        }
    }

    /**
     * Get item transfer status
     */
    private function getItemTransferStatus($item)
    {
        $requested = $item->requested_qty;
        $transferred = $item->transferred_qty ?? 0;

        if ($transferred >= $requested) {
            return 'completed';
        } elseif ($transferred > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }

    /**
     * Check if document has transferable items
     */
    private function hasTransferableItems($document)
    {
        foreach ($document->items as $item) {
            $stockInfo = $item->stock_info ?? null;
            $totalStock = $stockInfo['total_stock'] ?? 0;
            $transferred = $item->transferred_qty ?? 0;
            $remaining = max(0, $item->requested_qty - $transferred);

            if ($remaining > 0 && $totalStock > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create transfer document via SAP service
     */
    public function createTransfer(Request $request, $id)
    {
        try {
            $document = ReservationDocument::findOrFail($id);

            // PERBAIKAN: Tangani data yang dikirim dari frontend
            $transferData = $request->all();

            Log::info('Transfer request data:', [
                'document_id' => $id,
                'document_no' => $document->document_no,
                'request_data' => $transferData
            ]);

            // Validasi dasar
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

            // Persiapkan data untuk dikirim ke service Python
            $pythonData = [
                'transfer_data' => [
                    'transfer_info' => [
                        'document_no' => $document->document_no,
                        'document_id' => $document->id,
                        'plant_supply' => $request->sloc_supply,
                        'plant_destination' => $document->plant,
                        'move_type' => '311',
                        'posting_date' => Carbon::now()->format('Ymd'),
                        'header_text' => 'Transfer from Document ' . $document->document_no,
                        'remarks' => $transferData['transfer_data']['remarks'] ?? '',
                        'created_by' => Auth::user()->name,
                        'created_at' => Carbon::now()->format('Ymd')
                    ],
                    'items' => $request->items
                ],
                'sap_credentials' => $request->sap_credentials,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name
            ];

            Log::info('Sending data to Python service:', ['python_data' => $pythonData]);

            // Kirim ke service Python
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

            Log::info('Python service response:', ['response' => $responseData]);

            if ($responseData['success'] === true) {
                // Update document transferred quantities
                $this->updateDocumentTransferQuantities($document, $request->items);

                // Update document status
                $this->updateDocumentStatus($document);

                return response()->json([
                    'success' => true,
                    'message' => $responseData['message'],
                    'transfer_no' => $responseData['transfer_no'] ?? null,
                    'db_saved' => $responseData['db_saved'] ?? false,
                    'item_results' => $responseData['item_results'] ?? []
                ]);
            } else {
                throw new \Exception($responseData['message'] ?? 'Transfer failed');
            }

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Python service connection error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Cannot connect to transfer service. Please check if the service is running.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error creating transfer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create transfer: ' . $e->getMessage()
            ], 500);
        }
    }

            /**
         * Update document item transferred quantities after successful transfer
         * AND save transfer details to reservation_transfer_items table
         */
        private function updateDocumentTransferQuantities($document, $transferItems)
        {
            try {
                // Generate transfer number
                $transferNo = $this->generateTransferNumber($document->plant);

                // Create main transfer record
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
                    // Update document item transferred quantity
                    $item = ReservationDocumentItem::where('document_id', $document->id)
                        ->where('material_code', $transferItem['material_code'])
                        ->first();

                     if ($item) {
                // JANGAN update transferred_qty di sini
                // transferred_qty akan dihitung dari reservation_transfer_items

                // Simpan ke reservation_transfer_items table
                        DB::table('reservation_transfer_items')->insert([
                            'transfer_id' => $transfer->id,
                            'document_item_id' => $item->id,
                            'material_code' => $item->material_code,
                            'material_description' => $item->material_description,
                            'unit' => $item->unit,
                            'quantity' => $transferItem['quantity'],
                            'batch' => $transferItem['batch'] ?? null,
                            'storage_location' => $transferItem['storage_location'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Log::info('Updated item transferred quantity and saved to transfer_items', [
                            'material_code' => $item->material_code,
                            'transfer_item_qty' => $transferItem['quantity']
                        ]);
                    }
                }

                // Perbaikan: Gunakan method recalculateTotals dari model
                $document->recalculateTotals();

            } catch (\Exception $e) {
                Log::error('Error updating transfer quantities: ' . $e->getMessage());
                throw $e;
            }
        }

    /**
     * Generate transfer number
     */
    private function generateTransferNumber($plant)
    {
        $prefix = ($plant == '3000') ? 'TRMG' : 'TRBY';

        $latestSeq = DB::table('reservation_transfers')
            ->select(DB::raw('COALESCE(MAX(CAST(SUBSTRING(transfer_no, 5) AS UNSIGNED)), 0) as max_seq'))
            ->where('transfer_no', 'LIKE', $prefix . '%')
            ->value('max_seq');

        $sequence = $latestSeq + 1;
        $transferNo = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);

        // Verify unique
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

    /**
     * Update document status based on transfers
     */
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

            Log::info('Document status updated after transfer', [
                'document_no' => $document->document_no,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'total_transferred' => $totalTransferred,
                'total_requested' => $totalRequested
            ]);
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

        // Hanya dokumen dengan status 'booked' yang bisa diedit
        if ($document->status != 'booked') {
            return redirect()->route('documents.show', $document->id)
                ->with('error', 'Only documents with status "Booked" can be edited.');
        }

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

        // Hanya dokumen dengan status 'booked' yang bisa diupdate
        if ($document->status != 'booked') {
            return redirect()->route('documents.show', $document->id)
                ->with('error', 'Only documents with status "Booked" can be edited.');
        }

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
        $totalQty = 0;
        foreach ($request->items as $itemData) {
            $item = ReservationDocumentItem::find($itemData['id']);
            if ($item && $item->document_id == $document->id) {
                // Cek apakah quantity editable berdasarkan MRP
                $isQtyEditable = true;
                $allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1', 'D21', 'D22', 'GF1', 'CH4', 'MF3', 'D28', 'D23', 'WE2'];

                if ($item->dispo && !in_array($item->dispo, $allowedMRP)) {
                    $isQtyEditable = false;
                }

                // Hanya update jika quantity editable
                if ($isQtyEditable) {
                    $item->requested_qty = $itemData['requested_qty'];
                    $item->save();
                }
                $totalQty += $item->requested_qty;
            }
        }

        // Hitung ulang total quantity
        $document->total_qty = $totalQty;
        $document->total_items = count($request->items);
        $document->save();

        // Recalculate totals untuk update total_transferred dan completion_rate
        $document->recalculateTotals();

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
                    'Total Transferred', 'Completion Rate', 'Created By', 'Created At', 'Material Code',
                    'Material Description', 'Unit', 'Requested Qty', 'Transferred Qty', 'Remaining Qty',
                    'Source PRO Numbers', 'Sortf', 'MRP', 'Sales Orders'
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
                            \App\Helpers\NumberHelper::formatQuantity($document->total_transferred ?? 0),
                            round($document->completion_rate ?? 0, 2) . '%',
                            $document->created_by_name,
                            \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            $item->material_code,
                            $item->material_description,
                            $item->unit,
                            \App\Helpers\NumberHelper::formatQuantity($item->requested_qty),
                            \App\Helpers\NumberHelper::formatQuantity($item->transferred_qty ?? 0),
                            \App\Helpers\NumberHelper::formatQuantity(($item->requested_qty - ($item->transferred_qty ?? 0))),
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
        $document = ReservationDocument::with(['items', 'transfers'])->findOrFail($id);

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
     * Print selected items from a document
     */
    public function printSelected(Request $request, $id)
    {
        $document = ReservationDocument::with('items')->findOrFail($id);

        // Decode selected items
        $selectedItems = json_decode($request->input('selected_items', '[]'), true);

        if (empty($selectedItems)) {
            return redirect()->route('documents.print', $document->id)
                ->with('error', 'No items selected for printing.');
        }

        // Get selected items
        $items = $document->items()->whereIn('id', $selectedItems)->get();

        // Process items data untuk print - ambil sortf dari pro_details
        $items->transform(function ($item) {
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

        return view('documents.print-selected', compact('document', 'items'));
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
     * Export selected items to Excel
     */
    public function exportExcel(Request $request, $id)
    {
        $document = ReservationDocument::with('items')->findOrFail($id);

        // Decode selected items
        $selectedItems = json_decode($request->input('selected_items', '[]'), true);

        if (empty($selectedItems)) {
            return redirect()->route('documents.print', $document->id)
                ->with('error', 'No items selected for export.');
        }

        // Get selected items
        $items = $document->items()->whereIn('id', $selectedItems)->get();

        // Create export
        $filename = 'document_' . $document->document_no . '_selected_items_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new DocumentItemsExport($items, $document), $filename);
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

    /**
     * Delete selected items from document
     */
    public function deleteSelectedItems(Request $request, $id)
    {
        $document = ReservationDocument::findOrFail($id);

        // Hanya dokumen dengan status 'booked' yang bisa dihapus itemnya
        if ($document->status != 'booked') {
            return redirect()->route('documents.edit', $document->id)
                ->with('error', 'Only documents with status "Booked" can have items deleted.');
        }

        // Decode selected items
        $selectedItems = json_decode($request->input('selected_items', '[]'), true);

        if (empty($selectedItems)) {
            return redirect()->route('documents.edit', $document->id)
                ->with('error', 'No items selected for deletion.');
        }

        // Delete selected items
        $deletedCount = ReservationDocumentItem::where('document_id', $document->id)
            ->whereIn('id', $selectedItems)
            ->delete();

        // Update document totals menggunakan recalculateTotals
        $document->recalculateTotals();

        Log::info('Selected items deleted from document', [
            'document_id' => $document->id,
            'document_no' => $document->document_no,
            'deleted_items_count' => $deletedCount,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name
        ]);

        return redirect()->route('documents.edit', $document->id)
            ->with('success', $deletedCount . ' items deleted successfully.');
    }

    /**
     * Toggle document status (OPEN/CLOSED)
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $document = ReservationDocument::findOrFail($id);
            $user = Auth::user();

            // Cek hak akses
            if (!$user->hasPermissionTo('toggle_document_status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to toggle document status'
                ], 403);
            }

            $oldStatus = $document->status;

            // Toggle status
            if ($oldStatus == 'closed') {
                $document->status = 'booked';
            } else {
                $document->status = 'closed';
            }

            $document->save();

            Log::info('Document status toggled', [
                'document_no' => $document->document_no,
                'old_status' => $oldStatus,
                'new_status' => $document->status,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);

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

    /**
     * Log unauthorized SAP attempt
     */
    public function logUnauthorizedAttempt(Request $request, $id)
    {
        try {
            $document = ReservationDocument::findOrFail($id);

            Log::warning('Unauthorized SAP attempt', [
                'document_no' => $document->document_no,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attempted_action' => $request->input('action', 'unknown')
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
