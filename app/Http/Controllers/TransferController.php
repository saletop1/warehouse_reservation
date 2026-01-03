<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ReservationDocument;
use App\Models\ReservationTransfer;
use App\Models\ReservationTransferItem;
use App\Models\ReservationDocumentItem;
use Carbon\Carbon;

class TransferController extends Controller
{
    /**
     * Create a new transfer document
     */
    public function createTransfer(Request $request, $id)
    {
        try {
            // Dapatkan document dari route parameter
            $document = ReservationDocument::findOrFail($id);

            // **PERBAIKAN: Set timezone ke Asia/Jakarta**
            date_default_timezone_set('Asia/Jakarta');

            // Validasi input
            $validated = $request->validate([
                'plant' => 'required|string|max:10',
                'sloc_supply' => 'required|string|max:10',
                'items' => 'required|array|min:1',
                'items.*.material_code' => 'required|string',
                'items.*.material_desc' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit' => 'required|string|max:10',
                'items.*.plant_tujuan' => 'required|string|max:10',
                'items.*.sloc_tujuan' => 'required|string|max:10',
                'items.*.batch' => 'nullable|string',
                'items.*.batch_sloc' => 'required|string|max:10',
                'items.*.requested_qty' => 'nullable|numeric',
                'items.*.available_stock' => 'nullable|numeric',
                'sap_credentials' => 'required|array',
                'sap_credentials.user' => 'required|string',
                'sap_credentials.passwd' => 'required|string',
                'sap_credentials.client' => 'nullable|string',
                'sap_credentials.lang' => 'nullable|string',
                'sap_credentials.ashost' => 'nullable|string',
                'sap_credentials.sysnr' => 'nullable|string',
                'remarks' => 'nullable|string',
                'header_text' => 'nullable|string'
            ]);

            $user = Auth::user();

            // Gunakan data dari document jika tidak ada di request
            $documentNo = $document->document_no;
            $plant = $validated['plant'] ?? $document->plant;
            $plantSupply = $validated['sloc_supply'] ?? $document->sloc_supply;
            $remarks = $validated['remarks'] ?? "Transfer from Document {$documentNo}";
            $headerText = $validated['header_text'] ?? "Transfer from Document {$documentNo}";

            // **PERBAIKAN: Gunakan Carbon dengan timezone Jakarta**
            $now = Carbon::now('Asia/Jakarta');

            // Log untuk debugging
            Log::info('Starting transfer process', [
                'document_id' => $document->id,
                'document_no' => $documentNo,
                'user' => $user->name,
                'item_count' => count($validated['items']),
                'total_quantity' => array_sum(array_column($validated['items'], 'quantity')),
                'plant_supply' => $plantSupply,
                'plant' => $plant,
                'timezone' => date_default_timezone_get(),
                'current_time' => $now->toDateTimeString()
            ]);

            // Prepare data for Python service
            $transferData = [
                'transfer_info' => [
                    'document_no' => $documentNo,
                    'plant_supply' => $plantSupply,
                    'move_type' => '311',
                    'posting_date' => $now->format('Ymd'),
                    'header_text' => $headerText,
                    'created_by' => $user->name,
                    'created_at' => $now->format('Y-m-d H:i:s')
                ],
                'items' => []
            ];

            // Map items to SAP RFC format
            foreach ($validated['items'] as $index => $item) {
                // Parse batch_sloc - format: "SLOC:XXXX" or just "XXXX"
                $batchSloc = $item['batch_sloc'] ?? '';
                if ($batchSloc && strpos($batchSloc, 'SLOC:') === 0) {
                    $batchSloc = substr($batchSloc, 5);
                }

                $quantity = (float) $item['quantity'];

                $transferData['items'][] = [
                    'material_code' => $item['material_code'],
                    'material_desc' => $item['material_desc'],
                    'quantity' => $quantity,
                    'unit' => $item['unit'],
                    'plant_tujuan' => $item['plant_tujuan'],
                    'sloc_tujuan' => $item['sloc_tujuan'],
                    'batch' => $item['batch'] ?? '',
                    'batch_sloc' => $batchSloc,
                    'requested_qty' => (float) ($item['requested_qty'] ?? 0),
                    'available_stock' => (float) ($item['available_stock'] ?? 0)
                ];
            }

            // Get SAP credentials
            $sapCredentials = $this->getSapCredentials($user, $validated['sap_credentials']);

            // Send to Python service
            $pythonServiceUrl = env('PYTHON_SERVICE_URL', 'http://localhost:5000');

            Log::info('Sending transfer to SAP service', [
                'url' => $pythonServiceUrl,
                'document_no' => $documentNo,
                'item_count' => count($transferData['items']),
                'total_quantity' => array_sum(array_column($transferData['items'], 'quantity')),
                'user' => $user->name,
                'time_sent' => $now->toDateTimeString()
            ]);

            $response = Http::timeout(120)->post("{$pythonServiceUrl}/api/sap/transfer", [
                'transfer_data' => $transferData,
                'sap_credentials' => $sapCredentials,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Python service response received', [
                    'success' => $result['success'] ?? false,
                    'transfer_no' => $result['transfer_no'] ?? null,
                    'status' => $result['status'] ?? null,
                    'message' => $result['message'] ?? '',
                    'received_at' => $now->toDateTimeString()
                ]);

                if (isset($result['success']) && $result['success']) {
                    // Cek apakah transfer sudah ada sebelumnya
                    $existingTransfer = ReservationTransfer::where('document_id', $document->id)
                        ->where('transfer_no', $result['transfer_no'] ?? '')
                        ->first();

                    if ($existingTransfer) {
                        Log::warning('Duplicate transfer attempt detected', [
                            'transfer_no' => $result['transfer_no'],
                            'document_id' => $document->id,
                            'existing_created_at' => $existingTransfer->created_at
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Transfer already exists. Please refresh the page.',
                            'transfer_no' => $result['transfer_no']
                        ], 409);
                    }

                    // **PERBAIKAN: Save transfer dengan timezone Jakarta**
                    $transfer = ReservationTransfer::create([
                        'document_id' => $document->id,
                        'document_no' => $documentNo,
                        'transfer_no' => $result['transfer_no'] ?? 'PENDING',
                        'plant_supply' => $plantSupply,
                        'plant_destination' => $plant,
                        'move_type' => '311',
                        'total_items' => count($transferData['items']),
                        'total_qty' => array_sum(array_column($transferData['items'], 'quantity')),
                        'status' => $result['status'] ?? 'SUBMITTED',
                        'sap_message' => $result['message'] ?? '',
                        'remarks' => $remarks,
                        'created_by' => $user->id,
                        'created_by_name' => $user->name,
                        'completed_at' => $result['status'] === 'COMPLETED' ? $now : null,
                        'sap_response' => json_encode($result)
                    ]);

                    // Save transfer items
                    foreach ($validated['items'] as $index => $item) {
                        // Cari document_item_id berdasarkan material_code
                        $documentItem = ReservationDocumentItem::where('document_id', $document->id)
                            ->where('material_code', $item['material_code'])
                            ->first();

                        if (!$documentItem) {
                            Log::warning('Document item not found for transfer', [
                                'material_code' => $item['material_code'],
                                'document_id' => $document->id
                            ]);
                            continue;
                        }

                        // Parse batch_sloc untuk storage_location
                        $batchSloc = $item['batch_sloc'] ?? '';
                        if ($batchSloc && strpos($batchSloc, 'SLOC:') === 0) {
                            $batchSloc = substr($batchSloc, 5);
                        }

                        // Format material code
                        $materialCodeRaw = $item['material_code'];
                        $materialCodeFormatted = false;

                        if (ctype_digit($materialCodeRaw)) {
                            $materialCodeFormatted = true;
                        }
                        // Di dalam method createTransfer(), bagian save transfer items:
                        ReservationTransferItem::create([
                            'transfer_id' => $transfer->id,
                            'document_item_id' => $documentItem->id,
                            'material_code' => $item['material_code'],
                            'material_code_raw' => $item['material_code'],
                            'material_description' => $item['material_desc'],
                            'batch' => $item['batch'] ?? '',
                            'storage_location' => $batchSloc,
                            'plant_supply' => $plantSupply,
                            'plant_destination' => $item['plant_tujuan'],
                            'sloc_destination' => $item['sloc_tujuan'],
                            'quantity' => (float) $item['quantity'],
                            'unit' => $item['unit'],
                            'item_number' => $index + 1,
                            'sap_status' => $result['item_results'][$index]['status'] ?? 'SUBMITTED',
                            'sap_message' => $result['item_results'][$index]['message'] ?? '',
                            'material_formatted' => $materialCodeFormatted,
                            'requested_qty' => (float) ($item['requested_qty'] ?? 0),
                            'available_stock' => (float) ($item['available_stock'] ?? 0),
                            'created_at' => $now, // Pastikan ini diisi
                            'updated_at' => $now  // Pastikan ini diisi
                        ]);
                    }

                    // Update document transferred quantities
                    $this->updateDocumentTransferredQuantities($document, $validated['items']);

                    // Log success
                    Log::info('Transfer created successfully', [
                        'transfer_id' => $transfer->id,
                        'transfer_no' => $result['transfer_no'] ?? 'PENDING',
                        'document_no' => $documentNo,
                        'status' => $result['status'] ?? 'SUBMITTED',
                        'created_at' => $now->toDateTimeString()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Transfer document created successfully',
                        'transfer_no' => $result['transfer_no'] ?? 'PENDING',
                        'transfer_id' => $transfer->id,
                        'status' => $result['status'] ?? 'SUBMITTED',
                        'created_at' => $now->toDateTimeString(),
                        'data' => $result
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Transfer creation error', [
                'error' => $e->getMessage(),
                'document_id' => $id,
                'timezone' => date_default_timezone_get()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'System error: ' . $e->getMessage()
            ], 500);
        }
    }

            /**
         * Get transfer details with complete data
         */
        public function showDetailed($id)
        {
            try {
                $transfer = ReservationTransfer::with([
                    'items' => function($query) {
                        $query->select([
                            'id', 'transfer_id', 'document_item_id', 'material_code',
                            'material_code_raw', 'material_description', 'batch',
                            'storage_location', 'plant_supply', 'plant_destination',
                            'sloc_destination', 'quantity', 'unit', 'item_number',
                            'sap_status', 'sap_message', 'material_formatted',
                            'requested_qty', 'available_stock', 'created_at'
                        ]);
                    },
                    'document' => function($query) {
                        $query->select(['id', 'document_no', 'plant', 'sloc_supply', 'status']);
                    }
                ])->findOrFail($id);

                // Add additional calculated fields
                $transfer->total_items = $transfer->items->count();
                $transfer->total_qty = $transfer->items->sum('quantity');

                // Format dates
                $transfer->created_at_formatted = Carbon::parse($transfer->created_at)
                    ->setTimezone('Asia/Jakarta')
                    ->format('d/m/Y H:i:s');

                $transfer->completed_at_formatted = $transfer->completed_at ?
                    Carbon::parse($transfer->completed_at)
                        ->setTimezone('Asia/Jakarta')
                        ->format('d/m/Y H:i:s') : null;

                return response()->json([
                    'success' => true,
                    'data' => $transfer,
                    'message' => 'Transfer details retrieved successfully'
                ]);

            } catch (\Exception $e) {
                \Log::error('Error getting detailed transfer: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        }

        /**
         * Export transfers to Excel
         */
        public function exportExcel(Request $request)
        {
            try {
                $query = ReservationTransfer::with(['items', 'document']);

                // Apply filters
                if ($request->filled('date_from')) {
                    $query->whereDate('created_at', '>=', $request->date_from);
                }

                if ($request->filled('date_to')) {
                    $query->whereDate('created_at', '<=', $request->date_to);
                }

                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }

                if ($request->filled('plant_supply')) {
                    $query->where('plant_supply', $request->plant_supply);
                }

                if ($request->filled('plant_destination')) {
                    $query->where('plant_destination', $request->plant_destination);
                }

                $transfers = $query->orderBy('created_at', 'desc')->get();

                $filename = 'transfers_export_' . date('Ymd_His') . '.xlsx';

                return Excel::download(new TransfersExport($transfers), $filename);

            } catch (\Exception $e) {
                \Log::error('Export error: ' . $e->getMessage());
                return back()->with('error', 'Export failed: ' . $e->getMessage());
            }
        }

        /**
         * Export transfers to PDF
         */
        public function exportPDF(Request $request)
        {
            try {
                $query = ReservationTransfer::with(['items', 'document']);

                // Apply filters (same as above)
                if ($request->filled('date_from')) {
                    $query->whereDate('created_at', '>=', $request->date_from);
                }

                if ($request->filled('date_to')) {
                    $query->whereDate('created_at', '<=', $request->date_to);
                }

                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }

                $transfers = $query->orderBy('created_at', 'desc')->get();

                $pdf = \PDF::loadView('transfers.export-pdf', compact('transfers'));

                return $pdf->download('transfers_export_' . date('Ymd_His') . '.pdf');

            } catch (\Exception $e) {
                \Log::error('PDF export error: ' . $e->getMessage());
                return back()->with('error', 'PDF export failed');
            }
        }

        /**
         * Print transfer
         */
        public function print($id)
        {
            try {
                $transfer = ReservationTransfer::with(['items', 'document'])->findOrFail($id);

                return view('transfers.print', compact('transfer'));

            } catch (\Exception $e) {
                \Log::error('Print error: ' . $e->getMessage());
                return back()->with('error', 'Cannot print transfer');
            }
        }

    /**
     * Update document transferred quantities after successful transfer
     */
    private function updateDocumentTransferredQuantities($document, $transferItems)
    {
        try {
            // Group items by material code and sum quantities
            $groupedItems = [];
            foreach ($transferItems as $item) {
                $materialCode = $item['material_code'];
                if (!isset($groupedItems[$materialCode])) {
                    $groupedItems[$materialCode] = 0;
                }
                $groupedItems[$materialCode] += (float) $item['quantity'];
            }

            // Update document items
            foreach ($groupedItems as $materialCode => $transferredQty) {
                DB::table('reservation_document_items')
                    ->where('document_id', $document->id)
                    ->where('material_code', $materialCode)
                    ->increment('transferred_qty', $transferredQty);
            }

            // Recalculate document totals
            $totalTransferred = DB::table('reservation_document_items')
                ->where('document_id', $document->id)
                ->sum('transferred_qty');

            $totalRequested = DB::table('reservation_document_items')
                ->where('document_id', $document->id)
                ->sum('requested_qty');

            $completionRate = $totalRequested > 0 ? ($totalTransferred / $totalRequested) * 100 : 0;

            // Update document
            $document->total_transferred = $totalTransferred;
            $document->completion_rate = $completionRate;

            // Update status if needed
            if ($totalTransferred >= $totalRequested && $document->status != 'closed') {
                $document->status = 'closed';
            } elseif ($totalTransferred > 0 && $document->status == 'booked') {
                $document->status = 'partial';
            }

            $document->save();

            Log::info('Document quantities updated', [
                'document_id' => $document->id,
                'total_transferred' => $totalTransferred,
                'completion_rate' => $completionRate,
                'new_status' => $document->status
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating document quantities', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);
        }
    }

        /**
     * Cek duplikasi transfer dengan validasi ketat
     */
    private function checkForDuplicateTransfer($documentId, $transferNo)
    {
        // Cari transfer dengan nomor yang sama
        $existingTransfers = ReservationTransfer::where('transfer_no', $transferNo)->get();

        if ($existingTransfers->isEmpty()) {
            return ['is_duplicate' => false];
        }

        // Cek jika ada transfer untuk document yang sama
        foreach ($existingTransfers as $existing) {
            if ($existing->document_id == $documentId) {
                return [
                    'is_duplicate' => true,
                    'existing_id' => $existing->id,
                    'transfer_no' => $existing->transfer_no,
                    'document_id' => $existing->document_id
                ];
            }
        }

        // Jika transfer_no sama tapi untuk document berbeda, masih dianggap duplicate
        // karena transfer_no harus unik di seluruh sistem
        return [
            'is_duplicate' => true,
            'existing_id' => $existingTransfers->first()->id,
            'transfer_no' => $existingTransfers->first()->transfer_no
        ];
    }
    /**
     * Get SAP credentials for user
     */
    private function getSapCredentials($user, $requestCredentials = null)
    {
        // Priority 1: Credentials from request (user input)
        if ($requestCredentials && is_array($requestCredentials)) {
            $creds = [
                'ashost' => $requestCredentials['ashost'] ?? env('SAP_ASHOST'),
                'sysnr' => $requestCredentials['sysnr'] ?? env('SAP_SYSNR'),
                'client' => $requestCredentials['client'] ?? env('SAP_CLIENT'),
                'user' => $requestCredentials['user'] ?? env('SAP_USERNAME'),
                'passwd' => $requestCredentials['passwd'] ?? env('SAP_PASSWORD'),
                'lang' => $requestCredentials['lang'] ?? env('SAP_LANG', 'EN')
            ];

            // Log credentials (mask password for security)
            $maskedCreds = $creds;
            $maskedCreds['passwd'] = '******';
            Log::info('Using request SAP credentials', $maskedCreds);

            return $creds;
        }

        // Priority 2: Environment variables (service account)
        $creds = [
            'ashost' => env('SAP_ASHOST'),
            'sysnr' => env('SAP_SYSNR'),
            'client' => env('SAP_CLIENT'),
            'user' => env('SAP_USERNAME'),
            'passwd' => env('SAP_PASSWORD'),
            'lang' => env('SAP_LANG', 'EN')
        ];

        // Log credentials (mask password for security)
        $maskedCreds = $creds;
        $maskedCreds['passwd'] = '******';
        Log::info('Using environment SAP credentials', $maskedCreds);

        return $creds;
    }

    /**
     * Get transfer history
     */
    public function index(Request $request)
    {
        // Check if request wants JSON (API call)
        if ($request->wantsJson() || $request->ajax()) {
            return $this->getTransfersJson($request);
        }

        // For web view
        return $this->getTransfersView($request);
    }

    /**
     * Get transfers as JSON (for API)
     */
    private function getTransfersJson(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 20);

            $transfers = ReservationTransfer::with(['items', 'document'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $transfers,
                'message' => 'Transfers retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching transfers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching transfers: ' . $e->getMessage()
            ], 500);
        }
    }

            /**
         * Get transfers as HTML view
         */
        private function getTransfersView(Request $request)
        {
            try {
                $perPage = $request->get('per_page', 20);

                // Query dengan filter untuk menghilangkan data tidak lengkap
                $query = ReservationTransfer::with(['items', 'document'])
                    ->orderBy('created_at', 'desc');

                // Filter data tidak lengkap: plant_destination harus ada dan tidak kosong
                $query->whereNotNull('plant_destination')
                    ->where('plant_destination', '!=', '')
                    ->where('total_items', '>', 0)
                    ->where('total_qty', '>', 0);

                // Terapkan filter pencarian dari user
                if ($request->filled('transfer_no')) {
                    $query->where('transfer_no', 'like', '%' . $request->transfer_no . '%');
                }

                if ($request->filled('document_no')) {
                    $query->where('document_no', 'like', '%' . $request->document_no . '%');
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

                if ($request->filled('plant_supply')) {
                    $query->where('plant_supply', $request->plant_supply);
                }

                if ($request->filled('plant_destination')) {
                    $query->where('plant_destination', $request->plant_destination);
                }

                $transfers = $query->paginate($perPage);

                // Tambahkan stats untuk tampilan
                $stats = [
                    'total' => $transfers->total(),
                    'completed' => $transfers->where('status', 'COMPLETED')->count(),
                    'submitted' => $transfers->where('status', 'SUBMITTED')->count(),
                    'failed' => $transfers->where('status', 'FAILED')->count(),
                    'pending' => $transfers->whereIn('status', ['PENDING', 'PROCESSING'])->count()
                ];

                return view('transfers.index', compact('transfers', 'stats'));

            } catch (\Exception $e) {
                Log::error('Error fetching transfers view: ' . $e->getMessage());

                return redirect()->route('dashboard')
                    ->with('error', 'Error loading transfers: ' . $e->getMessage());
            }
        }

                /**
         * Fix incomplete transfer data
         */
        public function fixTransferData($id)
        {
            try {
                $transfer = ReservationTransfer::findOrFail($id);

                // Coba ambil data dari document jika ada
                if ($transfer->document_id) {
                    $document = ReservationDocument::find($transfer->document_id);
                    if ($document) {
                        // Update missing data
                        if (empty($transfer->plant_destination) || $transfer->plant_destination == '') {
                            $transfer->plant_destination = $document->plant;
                        }

                        if ($transfer->total_items == 0) {
                            $transfer->total_items = $transfer->items()->count();
                        }

                        if ($transfer->total_qty == 0) {
                            $transfer->total_qty = $transfer->items()->sum('quantity');
                        }

                        $transfer->save();

                        Log::info('Transfer data fixed', [
                            'transfer_id' => $id,
                            'plant_destination_set' => $document->plant,
                            'total_items_updated' => $transfer->total_items,
                            'total_qty_updated' => $transfer->total_qty
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Transfer data fixed successfully'
                        ]);
                    }
                }

                // Jika tidak ada document, coba hapus transfer yang tidak lengkap
                if (empty($transfer->plant_destination) || $transfer->total_items == 0) {
                    $transfer->delete();

                    Log::info('Incomplete transfer deleted', [
                        'transfer_id' => $id,
                        'transfer_no' => $transfer->transfer_no
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Incomplete transfer deleted'
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'No fixes needed'
                ]);

            } catch (\Exception $e) {
                Log::error('Error fixing transfer data: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        }
    /**
     * Get transfer details
     */
    public function show($id)
    {
        try {
            $transfer = ReservationTransfer::with(['items', 'document'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transfer,
                'message' => 'Transfer details retrieved successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error fetching transfer details', [
                'transfer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching transfer details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transfers for a specific document
     */
    public function getTransfersByDocument($documentId)
    {
        try {
            $transfers = ReservationTransfer::with(['items'])
                ->where('document_id', $documentId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $transfers,
                'message' => 'Transfers for document retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching transfers by document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching transfers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update transfer status (for manual updates)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string',
                'sap_message' => 'nullable|string',
                'transfer_no' => 'nullable|string'
            ]);

            $transfer = ReservationTransfer::findOrFail($id);

            $updateData = [
                'status' => $request->status,
                'sap_message' => $request->sap_message,
                'updated_at' => now()
            ];

            if ($request->transfer_no) {
                $updateData['transfer_no'] = $request->transfer_no;
            }

            if ($request->status === 'COMPLETED') {
                $updateData['completed_at'] = now();
            }

            $transfer->update($updateData);

            Log::info('Transfer status updated', [
                'transfer_id' => $id,
                'status' => $request->status,
                'transfer_no' => $request->transfer_no
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transfer status updated successfully',
                'data' => $transfer
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error updating transfer status', [
                'transfer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating transfer status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete transfer
     */
    public function destroy($id)
    {
        try {
            $transfer = ReservationTransfer::findOrFail($id);

            // Delete transfer items first
            ReservationTransferItem::where('transfer_id', $id)->delete();

            // Delete the transfer
            $transfer->delete();

            Log::info('Transfer deleted', [
                'transfer_id' => $id,
                'document_no' => $transfer->document_no
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transfer deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error deleting transfer', [
                'transfer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting transfer: ' . $e->getMessage()
            ], 500);
        }
    }
}
