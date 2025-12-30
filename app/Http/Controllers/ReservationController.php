<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\ReservationDocument;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    /**
     * Display a listing of reservations.
     */
    public function index(Request $request)
    {
        try {
            // Get plants for filter dropdown - from sap_reservations table
            $plants = DB::table('sap_reservations')
                ->select('sap_plant as plant')
                ->whereNotNull('sap_plant')
                ->distinct()
                ->orderBy('sap_plant')
                ->pluck('plant');

            // Get statuses for filter dropdown
            $statuses = ['draft', 'approved', 'rejected', 'completed'];

            // PERUBAHAN PENTING: Query dari tabel sap_reservations, bukan reservations
            $query = DB::table('sap_reservations');

            // Apply filters if provided
            if ($request->has('plant') && $request->plant != '') {
                $query->where('sap_plant', $request->plant);
            }

            // Komen filter status karena sap_reservations tidak ada kolom status
            /*
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }
            */

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('rsnum', 'LIKE', "%{$search}%")
                      ->orWhere('matnr', 'LIKE', "%{$search}%")
                      ->orWhere('maktx', 'LIKE', "%{$search}%")
                      ->orWhere('sap_plant', 'LIKE', "%{$search}%")
                      ->orWhere('sap_order', 'LIKE', "%{$search}%")
                      ->orWhere('dispo', 'LIKE', "%{$search}%");
                });
            }

            if ($request->has('start_date') && $request->start_date != '') {
                $query->whereDate('sync_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date != '') {
                $query->whereDate('sync_at', '<=', $request->end_date);
            }

            // PERUBAHAN: Hapus pagination, gunakan all() untuk mengambil semua data
            $reservations = $query->orderBy('sync_at', 'desc')->get();

            return view('reservations.index', compact('reservations', 'plants', 'statuses'));

        } catch (\Exception $e) {
            Log::error('Error in reservations index: ' . $e->getMessage());
            return redirect()->route('reservations.index')
                ->with('error', 'Error loading reservations: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new reservation.
     */
    public function create()
    {
        // Get plants from sap_reservations table
        $plants = DB::table('sap_reservations')
            ->select('sap_plant', DB::raw('MAX(dwerk) as dwerk'))
            ->whereNotNull('sap_plant')
            ->groupBy('sap_plant')
            ->orderBy('sap_plant')
            ->get();

        return view('reservations.create', compact('plants'));
    }

    /**
     * Store a newly created reservation in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plant' => 'required|string|max:10',
            'material_code' => 'required|string|max:50',
            'material_description' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $reservation = Reservation::create([
                'plant' => $request->plant,
                'material_code' => $request->material_code,
                'material_description' => $request->material_description,
                'quantity' => $request->quantity,
                'unit' => $request->unit,
                'status' => 'created',
                'created_by' => Auth::id(),
                'created_by_name' => Auth::user()->name,
            ]);

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', 'Reservation created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create reservation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified reservation.
     */
    public function show($id)
    {
        try {
            $reservation = Reservation::findOrFail($id);
            return view('reservations.show', compact('reservation'));
        } catch (\Exception $e) {
            return redirect()->route('reservations.index')
                ->with('error', 'Reservation not found: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified reservation.
     */
    public function edit($id)
    {
        try {
            $reservation = Reservation::findOrFail($id);

            // Get plants for dropdown - from sap_reservations table
            $plants = DB::table('sap_reservations')
                ->select('sap_plant as plant')
                ->whereNotNull('sap_plant')
                ->distinct()
                ->orderBy('sap_plant')
                ->pluck('plant');

            return view('reservations.edit', compact('reservation', 'plants'));
        } catch (\Exception $e) {
            return redirect()->route('reservations.index')
                ->with('error', 'Reservation not found: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified document.
     */
    public function editDocument($id)
    {
        try {
            $document = ReservationDocument::with('items')->findOrFail($id);

            // Hanya dokumen dengan status 'created' yang bisa diedit
            if ($document->status != 'created') {
                return redirect()->route('documents.show', $document->id)
                    ->with('error', 'Only documents with status "Created" can be edited.');
            }

            // Process items untuk edit view - ambil sortf dari pro_details dan sales orders
            foreach ($document->items as $item) {
                // Decode sales orders untuk ditampilkan
                $salesOrders = json_decode($item->sales_orders, true) ?? [];
                $item->sales_orders = $salesOrders;

                // Set sortf dari kolom sortf, jika kosong gunakan default
                $item->sortf = $item->sortf ?? '-';

                // Get MRP from sap_reservations table for each item
                $sapData = DB::table('sap_reservations')
                    ->where('matnr', $item->material_code)
                    ->where('sap_plant', $document->plant)
                    ->first();

                $item->dispo = $sapData->dispo ?? $item->dispo ?? null;
                $item->is_qty_editable = $this->isQtyEditableForMRP($item->dispo);

                // Log untuk debugging
                \Log::info('Item in editDocument:', [
                    'material_code' => $item->material_code,
                    'dispo' => $item->dispo,
                    'is_qty_editable' => $item->is_qty_editable,
                    'sales_orders_count' => count($salesOrders)
                ]);
            }

            return view('documents.edit', compact('document'));
        } catch (\Exception $e) {
            return redirect()->route('documents.index')
                ->with('error', 'Document not found: ' . $e->getMessage());
        }
    }

    /**
     * Check if quantity is editable based on MRP
     */
    private function isQtyEditableForMRP($dispo)
    {
        if (!$dispo) return true;

        // MRP yang diperbolehkan untuk edit quantity
        $allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1', 'D21', 'D22', 'GF1', 'CH4', 'D26', 'D28', 'D23', 'DR1', 'DR2', 'WE2', 'GW2'];

        return in_array($dispo, $allowedMRP);
    }

    /**
     * Update the specified document in storage.
     */
    public function updateDocument(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $document = ReservationDocument::findOrFail($id);

            // Hanya dokumen dengan status 'created' yang bisa diupdate
            if ($document->status != 'created') {
                return redirect()->route('documents.show', $document->id)
                    ->with('error', 'Only documents with status "Created" can be edited.');
            }

            $request->validate([
                'remarks' => 'nullable|string|max:500',
                'items' => 'required|array',
                'items.*.id' => 'required|exists:reservation_document_items,id',
                'items.*.requested_qty' => 'required|numeric|min:0',
            ]);

            // Update remarks
            $document->remarks = $request->remarks;
            $document->save();

            // Update items qty
            $totalQty = 0;
            foreach ($request->items as $itemData) {
                $item = $document->items()->find($itemData['id']);
                if ($item) {
                    // Get MRP to check if quantity is editable
                    $sapData = DB::table('sap_reservations')
                        ->where('matnr', $item->material_code)
                        ->where('sap_plant', $document->plant)
                        ->first();

                    $isEditable = $this->isQtyEditableForMRP($sapData->dispo ?? null);

                    if (!$isEditable) {
                        // Skip quantity update for non-editable MRP
                        $itemData['requested_qty'] = $item->requested_qty;
                    }

                    $item->update([
                        'requested_qty' => $itemData['requested_qty'],
                    ]);
                    $totalQty += $itemData['requested_qty'];
                }
            }

            // Update document total qty
            $document->update([
                'total_qty' => $totalQty,
            ]);

            DB::commit();

            return redirect()->route('documents.show', $document->id)
                ->with('success', 'Document updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update document: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update the specified reservation in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'plant' => 'required|string|max:10',
            'material_code' => 'required|string|max:50',
            'material_description' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:10',
            'status' => 'required|in:draft,approved,rejected,completed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $reservation = Reservation::findOrFail($id);

            $reservation->update([
                'plant' => $request->plant,
                'material_code' => $request->material_code,
                'material_description' => $request->material_description,
                'quantity' => $request->quantity,
                'unit' => $request->unit,
                'status' => $request->status,
                'updated_by' => Auth::id(),
                'updated_by_name' => Auth::user()->name,
            ]);

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', 'Reservation updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update reservation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified reservation from storage.
     */
    public function destroy($id)
    {
        try {
            $reservation = Reservation::findOrFail($id);
            $reservation->delete();

            return redirect()->route('reservations.index')
                ->with('success', 'Reservation deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('reservations.index')
                ->with('error', 'Failed to delete reservation: ' . $e->getMessage());
        }
    }

    /**
     * Export reservations to Excel.
     */
    public function export(Request $request)
    {
        try {
            $query = Reservation::query();

            // Filter by date if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $reservations = $query->get();

            // TODO: Implement Excel export logic
            // You can use Maatwebsite\Excel package or similar

            return response()->json([
                'success' => true,
                'message' => 'Export functionality will be implemented soon',
                'count' => $reservations->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync reservations from SAP.
     */
    public function sync(Request $request)
    {
        try {
            $plant = $request->input('plant');
            $orderNumbers = $request->input('order_number');

            // Parse order numbers
            $proNumbers = $this->parseOrderNumbers($orderNumbers);

            Log::info('🚀 Starting sync process', [
                'user_id' => auth()->id(),
                'plant' => $plant,
                'order_numbers_input' => $orderNumbers,
                'pro_numbers_parsed' => $proNumbers,
                'count' => count($proNumbers)
            ]);

            // Validasi input
            if (empty($plant)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plant harus diisi!'
                ], 400);
            }

            if (empty($proNumbers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masukkan minimal satu PRO number!'
                ], 400);
            }

            // 1. Cek apakah Flask service tersedia
            $flaskStatus = $this->checkFlaskServiceInternal();
            Log::info('Flask service status:', $flaskStatus);

            if ($flaskStatus['status'] !== 'healthy') {
                return response()->json([
                    'success' => false,
                    'message' => 'Flask service tidak tersedia.',
                    'details' => $flaskStatus
                ], 503);
            }

            // 2. Panggil Flask service untuk sync data
            $flaskUrl = env('FLASK_SERVICE_URL', 'http://localhost:5000') . '/api/sap/sync';

            // Debug: Cek URL dan environment
            Log::debug('Environment check:', [
                'FLASK_SERVICE_URL' => env('FLASK_SERVICE_URL'),
                'full_url' => $flaskUrl,
                'APP_ENV' => env('APP_ENV')
            ]);

            $client = new Client([
                'verify' => env('APP_ENV') === 'production' ? true : false,
                'timeout' => env('FLASK_API_TIMEOUT', 120),
                'connect_timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Laravel-Reservation/1.0',
                    'Accept' => 'application/json',
                ]
            ]);

            $payload = [
                'plant' => $plant,
                'pro_numbers' => $proNumbers,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'sync_timestamp' => now()->toISOString(),
                'request_source' => 'laravel-reservation-system'
            ];

            Log::info('📤 Sending request to Flask service', [
                'url' => $flaskUrl,
                'payload' => $payload
            ]);

            try {
                // Coba GET request dulu untuk testing
                $testResponse = $client->get(env('FLASK_SERVICE_URL', 'http://localhost:5000') . '/api/health', [
                    'http_errors' => false,
                    'timeout' => 10
                ]);

                Log::info('Flask health check result:', [
                    'status' => $testResponse->getStatusCode(),
                    'body' => $testResponse->getBody()->getContents()
                ]);

                // Lakukan POST request untuk sync
                $response = $client->post($flaskUrl, [
                    'json' => $payload,
                    'http_errors' => false, // Jangan throw exception otomatis
                    'timeout' => env('FLASK_API_TIMEOUT', 120)
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                Log::info('📥 Flask service response:', [
                    'status_code' => $statusCode,
                    'body_preview' => substr($body, 0, 500)
                ]);

                $result = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Invalid JSON response from Flask', [
                        'error' => json_last_error_msg(),
                        'raw_body' => $body
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid response from Flask service',
                        'raw_response' => $body
                    ], 500);
                }

                if ($statusCode === 200 && isset($result['success']) && $result['success']) {
                    // 3. Process data dari Flask
                    $syncedCount = $this->processFlaskResponse($result, $plant, $proNumbers);

                    Log::info('✅ Sync completed successfully', [
                        'synced_count' => $syncedCount,
                        'plant' => $plant
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Sync completed successfully',
                        'synced_count' => $syncedCount,
                        'total_pros' => count($proNumbers),
                        'timestamp' => now()->toDateTimeString()
                    ]);
                } else {
                    $errorMessage = $result['message'] ?? 'Unknown error from Flask service';

                    Log::error('❌ Flask service returned error', [
                        'status_code' => $statusCode,
                        'error' => $errorMessage,
                        'response' => $result
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Sync failed: ' . $errorMessage,
                        'flask_response' => $result
                    ], $statusCode >= 400 ? $statusCode : 400);
                }

            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                Log::error('🔌 Cannot connect to Flask service', [
                    'url' => $flaskUrl,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot connect to Flask service. Please ensure Flask is running.',
                    'error' => $e->getMessage(),
                    'flask_url' => $flaskUrl,
                    'troubleshooting' => [
                        '1. Check if Flask service is running: ' . env('FLASK_SERVICE_URL', 'http://localhost:5000'),
                        '2. Verify network connection',
                        '3. Check firewall settings',
                        '4. Test manually: curl -v ' . env('FLASK_SERVICE_URL', 'http://localhost:5000') . '/api/health'
                    ]
                ], 503);

            } catch (\Exception $e) {
                Log::error('💥 Unexpected error during Flask API call', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unexpected error: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('🔥 Sync process error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync process failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Flask response with improved sortf handling
     */
    private function processFlaskResponse($flaskResponse, $plant, $proNumbers)
    {
        DB::beginTransaction();
        $syncedCount = 0;

        try {
            if (isset($flaskResponse['data']) && is_array($flaskResponse['data'])) {
                foreach ($flaskResponse['data'] as $item) {
                    // Validasi data minimal
                    if (empty($item['rsnum']) || empty($item['matnr']) || empty($item['sap_order'])) {
                        Log::warning('Invalid data from Flask, missing required fields', ['item' => $item]);
                        continue;
                    }

                    // Extract sortf from multiple possible keys
                    $sortf = $this->extractSortfFromItem($item);

                    // Map data dari Flask
                    $reservationData = [
                        'rsnum' => $item['rsnum'] ?? null,
                        'rspos' => $item['rspos'] ?? null,
                        'sap_plant' => $item['sap_plant'] ?? $item['plant'] ?? $plant,
                        'sap_order' => $item['sap_order'] ?? $item['order_number'] ?? null,
                        'aufnr' => $item['aufnr'] ?? $item['sap_order'] ?? null,
                        'matnr' => $item['matnr'] ?? null,
                        'maktx' => $item['maktx'] ?? $item['material_description'] ?? 'No Description',
                        'psmng' => $item['psmng'] ?? $item['quantity'] ?? 0,
                        'meins' => $item['meins'] ?? $item['unit'] ?? 'PC',
                        'gstrp' => isset($item['gstrp']) && !empty($item['gstrp']) ? Carbon::parse($item['gstrp']) : null,
                        'gltrp' => isset($item['gltrp']) && !empty($item['gltrp']) ? Carbon::parse($item['gltrp']) : null,
                        'makhd' => $item['makhd'] ?? $item['finish_good'] ?? null,
                        'mtart' => $item['mtart'] ?? $item['material_type'] ?? null,
                        'sortf' => $sortf, // Use extracted sortf value
                        'dwerk' => $item['dwerk'] ?? $plant,
                        'dispo' => $item['dispo'] ?? null,
                        'kdauf' => $item['kdauf'] ?? null,
                        'kdpos' => $item['kdpos'] ?? null,
                        'sync_by' => auth()->id(),
                        'sync_by_name' => auth()->user()->name,
                        'sync_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Debug: Log data untuk memastikan sortf ada
                    Log::debug('Processing reservation data', [
                        'matnr' => $reservationData['matnr'],
                        'sortf_value' => $reservationData['sortf'],
                        'has_sortf' => !empty($reservationData['sortf']),
                        'original_keys' => array_keys($item)
                    ]);

                    // Clean up date fields
                    if (empty($reservationData['gstrp'])) {
                        unset($reservationData['gstrp']);
                    }
                    if (empty($reservationData['gltrp'])) {
                        unset($reservationData['gltrp']);
                    }

                    // Check if record exists
                    $existing = DB::table('sap_reservations')
                        ->where('rsnum', $reservationData['rsnum'])
                        ->where('rspos', $reservationData['rspos'])
                        ->where('matnr', $reservationData['matnr'])
                        ->where('sap_plant', $reservationData['sap_plant'])
                        ->first();

                    if ($existing) {
                        DB::table('sap_reservations')
                            ->where('id', $existing->id)
                            ->update($reservationData);
                    } else {
                        DB::table('sap_reservations')->insert($reservationData);
                    }

                    $syncedCount++;
                }
            } else {
                Log::warning('No data array in Flask response', ['response' => $flaskResponse]);
            }

            DB::commit();
            Log::info('Database transaction committed', [
                'synced_count' => $syncedCount,
                'pro_numbers_count' => count($proNumbers),
                'plant' => $plant
            ]);
            return $syncedCount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Process Flask response error: ' . $e->getMessage());
            throw new \Exception('Failed to process Flask response: ' . $e->getMessage());
        }
    }

    /**
     * Extract sortf from item with multiple possible keys
     */
    private function extractSortfFromItem($item)
    {
        // Prioritize 'sortf' field
        if (isset($item['sortf']) && !empty($item['sortf'])) {
            return $item['sortf'];
        }

        // Check for alternative field names
        $alternativeKeys = ['sort_field', 'additional_info', 'additional_info_1', 'info', 'ztext'];

        foreach ($alternativeKeys as $key) {
            if (isset($item[$key]) && !empty($item[$key])) {
                Log::debug("Using alternative key for sortf: {$key}", ['value' => $item[$key]]);
                return $item[$key];
            }
        }

        // Check for any field that might contain additional info
        foreach ($item as $key => $value) {
            if (is_string($key) &&
                (stripos($key, 'sort') !== false ||
                 stripos($key, 'info') !== false ||
                 stripos($key, 'text') !== false) &&
                !empty($value) && is_string($value)) {
                Log::debug("Found potential sortf field: {$key}", ['value' => $value]);
                return $value;
            }
        }

        return null;
    }

    /**
     * Check if specific Flask endpoint exists
     */
    public function checkFlaskEndpoint()
    {
        try {
            $flaskUrl = env('FLASK_SERVICE_URL', 'http://localhost:5000') . '/api/sap/sync';

            $client = new Client([
                'verify' => false,
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $response = $client->get($flaskUrl, [
                'headers' => ['Accept' => 'application/json'],
                'http_errors' => false // Don't throw exception for 404
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            return response()->json([
                'success' => true,
                'endpoint' => $flaskUrl,
                'status_code' => $statusCode,
                'is_available' => $statusCode === 200 || $statusCode === 405,
                'message' => $statusCode === 200 ? 'Endpoint available (GET)' :
                            ($statusCode === 405 ? 'Endpoint available (POST required)' :
                            'Endpoint not available'),
                'response_body' => $body
            ]);

        } catch (\Exception $e) {
            Log::error('Cannot check Flask endpoint: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Cannot check endpoint: ' . $e->getMessage(),
                'endpoint' => env('FLASK_SERVICE_URL', 'http://localhost:5000') . '/api/sap/sync'
            ], 500);
        }
    }

    /**
     * Parse order numbers from input
     */
    private function parseOrderNumbers($input)
    {
        if (empty($input)) {
            return [];
        }

        // Replace various separators with comma
        $normalized = preg_replace('/[,;|\s]+/', ',', $input);

        // Split by comma and filter out empty values
        $numbers = array_filter(array_map('trim', explode(',', $normalized)));

        // Remove duplicates
        $numbers = array_unique($numbers);

        return array_values($numbers);
    }

    /**
     * Sync from SAP (for form submission)
     */
    public function syncFromSAP(Request $request)
    {
        try {
            $plant = $request->input('plant');
            $orderNumbers = $request->input('order_number');

            // Validasi
            if (empty($plant) || empty($orderNumbers)) {
                return back()->with('error', 'Plant dan PRO numbers harus diisi!');
            }

            // Parse order numbers
            $proNumbers = $this->parseOrderNumbers($orderNumbers);

            // Cek koneksi Flask service dulu
            $flaskStatus = $this->checkFlaskServiceInternal();
            if ($flaskStatus['status'] !== 'healthy') {
                return back()->with('error',
                    'Flask service tidak tersedia. ' .
                    'Pastikan Flask service berjalan di ' .
                    env('FLASK_SERVICE_URL', 'http://localhost:5000')
                );
            }

            // Call sync method
            $response = $this->sync($request);

            if ($response->getData()->success) {
                return back()->with('success',
                    'Sync completed successfully. Synced ' .
                    $response->getData()->synced_count .
                    ' items from ' . count($proNumbers) . ' PRO numbers.');
            } else {
                return back()->with('error',
                    'Sync failed: ' . $response->getData()->message);
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Check Flask service.
     */
    public function checkFlaskService()
    {
        return response()->json($this->checkFlaskServiceInternal());
    }

    private function checkFlaskServiceInternal()
    {
        try {
            $flaskUrl = env('FLASK_SERVICE_URL', 'http://localhost:5000') . '/api/health';

            Log::debug('Checking Flask service health at: ' . $flaskUrl);

            $client = new Client([
                'verify' => false, // Nonaktifkan SSL verification untuk development
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $response = $client->get($flaskUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'Laravel-Reservation-System/1.0'
                ],
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            Log::debug('Flask health check response', [
                'status_code' => $statusCode,
                'body' => $body
            ]);

            $result = json_decode($body, true);

            if ($statusCode === 200 && is_array($result)) {
                return [
                    'status' => 'healthy',
                    'message' => 'Flask service is running',
                    'flask_status' => $result,
                    'response_time' => $response->getHeaderLine('X-Response-Time') ?? 'N/A',
                    'url' => $flaskUrl,
                    'status_code' => $statusCode
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Flask service returned non-200 status',
                    'flask_response' => $result,
                    'status_code' => $statusCode,
                    'url' => $flaskUrl
                ];
            }

        } catch (\Exception $e) {
            Log::error('Flask service health check failed: ' . $e->getMessage());

            return [
                'status' => 'unhealthy',
                'message' => 'Flask service is not accessible',
                'error' => $e->getMessage(),
                'url' => env('FLASK_SERVICE_URL', 'http://localhost:5000') . '/api/health'
            ];
        }
    }

    /**
     * Test connection to Flask service
     */
    public function testFlaskConnection()
    {
        try {
            $flaskUrl = env('FLASK_SERVICE_URL', 'http://localhost:5000');

            Log::info('Testing Flask connection to: ' . $flaskUrl);

            $client = new Client([
                'verify' => false,
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            // Test basic connection
            $response = $client->get($flaskUrl, [
                'headers' => ['Accept' => 'text/html'],
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();

            // Test health endpoint
            $healthResponse = $client->get($flaskUrl . '/api/health', [
                'headers' => ['Accept' => 'application/json'],
                'http_errors' => false
            ]);

            $healthStatus = $healthResponse->getStatusCode();
            $healthBody = json_decode($healthResponse->getBody()->getContents(), true);

            // Test sync endpoint (GET)
            $syncResponse = $client->get($flaskUrl . '/api/sap/sync', [
                'headers' => ['Accept' => 'application/json'],
                'http_errors' => false
            ]);

            $syncStatus = $syncResponse->getStatusCode();

            return response()->json([
                'success' => true,
                'flask_base_url' => $flaskUrl,
                'connection_test' => [
                    'base_url' => $statusCode,
                    'health_endpoint' => $healthStatus,
                    'health_data' => $healthBody,
                    'sync_endpoint' => $syncStatus,
                ],
                'environment' => [
                    'FLASK_SERVICE_URL' => env('FLASK_SERVICE_URL'),
                    'APP_ENV' => env('APP_ENV'),
                    'APP_DEBUG' => env('APP_DEBUG'),
                ],
                'recommendations' => [
                    'Pastikan Flask service berjalan di: ' . $flaskUrl,
                    'Cek apakah port 5000 terbuka',
                    'Verifikasi tidak ada firewall yang memblokir',
                    'Coba akses manual: ' . $flaskUrl . '/api/health'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Flask connection test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'flask_url' => env('FLASK_SERVICE_URL', 'http://localhost:5000'),
                'error' => $e->getMessage(),
                'troubleshooting' => [
                    '1. Pastikan Flask service berjalan: python app.py atau flask run',
                    '2. Cek apakah service listening di port 5000: netstat -an | grep 5000',
                    '3. Verifikasi .env file memiliki FLASK_SERVICE_URL yang benar',
                    '4. Coba curl manual: curl -v ' . env('FLASK_SERVICE_URL', 'http://localhost:5000') . '/api/health'
                ]
            ], 500);
        }
    }

    /**
     * Clear all and create new reservation
     */
    public function clearAndCreate(Request $request)
    {
        try {
            // Clear existing data if requested
            if ($request->has('clear_existing')) {
                Reservation::truncate();
            }

            // Create new reservation logic here
            // ...

            return response()->json([
                'success' => true,
                'message' => 'Reservation created successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reservation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all sync data
     */
    public function clearAllSyncData(Request $request)
    {
        try {
            DB::beginTransaction();

            // Clear all sap_reservations data
            $deletedCount = DB::table('sap_reservations')->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All sync data has been cleared successfully',
                'deleted_count' => $deletedCount,
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear sync data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check sync data status.
     */
    public function checkSyncData()
    {
        $lastSync = DB::table('sap_reservations')
            ->whereNotNull('sync_at')
            ->orderBy('sync_at', 'desc')
            ->first();

        $totalReservations = DB::table('sap_reservations')->count();
        $syncedReservations = DB::table('sap_reservations')->whereNotNull('sync_by')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'last_sync' => $lastSync ? $lastSync->sync_at : null,
                'total_reservations' => $totalReservations,
                'synced_reservations' => $syncedReservations,
                'sync_coverage' => $totalReservations > 0 ? ($syncedReservations / $totalReservations) * 100 : 0
            ]
        ]);
    }

    /**
     * Check sync status.
     */
    public function checkSyncStatus()
    {
        // Check if Flask service is available
        $flaskStatus = $this->checkFlaskServiceInternal();

        // Get sync statistics from sap_reservations table
        $syncStats = DB::table('sap_reservations')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN sync_by IS NOT NULL THEN 1 ELSE 0 END) as synced'),
                DB::raw('MAX(sync_at) as last_sync')
            )
            ->first();

        return response()->json([
            'success' => true,
            'count' => $syncStats->total ?? 0,
            'flask_service' => $flaskStatus,
            'sync_statistics' => [
                'total' => $syncStats->total ?? 0,
                'synced' => $syncStats->synced ?? 0,
                'last_sync' => $syncStats->last_sync,
                'sync_percentage' => ($syncStats->total ?? 0) > 0 ?
                    (($syncStats->synced ?? 0) / ($syncStats->total ?? 1)) * 100 : 0
            ],
            'server_time' => now()->toDateTimeString()
        ]);
    }

    /**
     * Autocomplete search.
     */
    public function autocomplete(Request $request)
    {
        $query = $request->input('query', '');

        $results = DB::table('sap_reservations')
            ->where('maktx', 'LIKE', "%{$query}%")
            ->orWhere('matnr', 'LIKE', "%{$query}%")
            ->orWhere('rsnum', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get(['id', 'rsnum', 'matnr', 'maktx', 'psmng', 'meins']);

        return response()->json($results);
    }

    /**
     * AJAX: Get material types.
     */
    public function getMaterialTypes(Request $request)
    {
        try {
            // Debug log untuk melihat request
            \Log::info('getMaterialTypes Request:', [
                'plant' => $request->input('plant'),
                'headers' => $request->headers->all(),
                'token' => $request->input('_token'),
                'session_token' => $request->session()->token()
            ]);

            $plant = $request->input('plant');

            // Validasi plant harus diisi
            if (empty($plant)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plant parameter is required'
                ], 400);
            }

            // Get material types from sap_reservations table
            $materialTypes = DB::table('sap_reservations')
                ->select('mtart')
                ->where('sap_plant', $plant)
                ->whereNotNull('mtart')
                ->distinct()
                ->orderBy('mtart')
                ->pluck('mtart');

            \Log::info('getMaterialTypes Response:', [
                'plant' => $plant,
                'count' => $materialTypes->count(),
                'material_types' => $materialTypes->toArray()
            ]);

            return response()->json([
                'success' => true,
                'material_types' => $materialTypes
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get material types: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get material types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        // Kecualikan route API dari CSRF verification jika diperlukan
        $this->middleware('auth');

        // Jika menggunakan Laravel Sanctum atau API, tambahkan middleware berikut
        // $this->middleware('auth:sanctum')->only(['getMaterialTypes', 'getMaterialsByType', 'getProNumbers']);
    }

    /**
     * AJAX: Get materials by type - DIKOREKSI untuk mengatasi masalah sortf di step 3
     */
    public function getMaterialsByType(Request $request)
    {
        try {
            $plant = $request->input('plant');
            $materialTypes = $request->input('material_types', []);

            if (empty($materialTypes)) {
                return response()->json([
                    'success' => true,
                    'materials' => []
                ]);
            }

            // PERBAIKAN: Query yang benar untuk mengambil data di step 3
            // Mengambil data yang DISTINCT berdasarkan matnr, tapi juga menyertakan sortf
            $materials = DB::table('sap_reservations')
                ->select(
                    'matnr',
                    'maktx',
                    'mtart',
                    DB::raw('MAX(sortf) as sortf'), // Ambil sortf (gunakan MAX jika ada multiple)
                    DB::raw('MAX(dispo) as dispo'), // Ambil MRP
                    DB::raw('MAX(kdauf) as kdauf'), // Ambil Sales Order
                    DB::raw('MAX(kdpos) as kdpos'),
                    // Tambahkan mathd dan makhd
                    DB::raw('MAX(mathd) as mathd'),
                    DB::raw('MAX(makhd) as makhd')
                )
                ->where('sap_plant', $plant)
                ->whereIn('mtart', $materialTypes)
                ->whereNotNull('matnr')
                ->groupBy('matnr', 'maktx', 'mtart') // Group by yang diperlukan
                ->orderBy('matnr')
                ->get();

            // Debug logging untuk melihat data yang diambil
            Log::info('Materials by type fetched - Step 3', [
                'plant' => $plant,
                'material_types' => $materialTypes,
                'count' => $materials->count(),
                'sample_materials' => $materials->take(3)->map(function($item) {
                    return [
                        'matnr' => $item->matnr,
                        'sortf' => $item->sortf,
                        'has_sortf' => !empty($item->sortf),
                        'mathd' => $item->mathd,
                        'makhd' => $item->makhd
                    ];
                })
            ]);

            return response()->json([
                'success' => true,
                'materials' => $materials
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get materials: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get materials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: Get PRO numbers.
     */
    public function getProNumbers(Request $request)
    {
        try {
            $plant = $request->input('plant');

            // Get PRO numbers from sap_reservations table
            $proNumbers = DB::table('sap_reservations')
                ->select('sap_order as pro_number', 'aufnr', DB::raw('COUNT(*) as material_count'))
                ->where('sap_plant', $plant)
                ->whereNotNull('sap_order')
                ->groupBy('sap_order', 'aufnr')
                ->orderBy('sap_order', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'pro_numbers' => $proNumbers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get PRO numbers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: Get PRO numbers for materials.
     */
    public function getProNumbersForMaterials(Request $request)
    {
        try {
            $plant = $request->input('plant');
            $materialTypes = $request->input('material_types', []);
            $materials = $request->input('materials', []);

            if (empty($materials)) {
                return response()->json([
                    'success' => true,
                    'pro_numbers' => []
                ]);
            }

            // Get PRO numbers that have the selected materials
            $proNumbers = DB::table('sap_reservations')
                ->select('sap_order as pro_number', 'aufnr', DB::raw('COUNT(*) as material_count'))
                ->where('sap_plant', $plant)
                ->whereIn('matnr', $materials)
                ->whereNotNull('sap_order')
                ->groupBy('sap_order', 'aufnr')
                ->orderBy('sap_order', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'pro_numbers' => $proNumbers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get PRO numbers: ' . $e->getMessage()
            ], 500);
        }
    }

                /**
             * AJAX: Load multiple PRO - DIPERBAIKI untuk include data tambahan dan konsolidasi material
             */
            public function loadMultiplePro(Request $request)
            {
                try {
                    $plant = $request->input('plant');
                    $materialTypes = $request->input('material_types', []);
                    $materials = $request->input('materials', []);
                    $proNumbers = $request->input('pro_numbers', []);

                    \Log::info('🔍 DEBUG - Starting loadMultiplePro', [
                        'plant' => $plant,
                        'materials_count' => count($materials),
                        'pro_numbers_count' => count($proNumbers),
                        'materials_sample' => array_slice($materials, 0, 3),
                        'pro_numbers_sample' => array_slice($proNumbers, 0, 3)
                    ]);

                    if (empty($proNumbers) || empty($materials)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No PRO numbers or materials selected',
                            'debug' => [
                                'pro_numbers_empty' => empty($proNumbers),
                                'materials_empty' => empty($materials),
                                'pro_numbers_count' => count($proNumbers),
                                'materials_count' => count($materials)
                            ]
                        ], 400);
                    }

                    // Format material codes to 18-digit
                    $formattedMaterials = array_map(function($material) {
                        if (ctype_digit($material)) {
                            return str_pad($material, 18, '0', STR_PAD_LEFT);
                        }
                        return $material;
                    }, $materials);

                    // Format PRO numbers to 12-digit
                    $formattedProNumbers = array_map(function($pro) {
                        $pro = trim($pro);
                        if (ctype_digit($pro)) {
                            return str_pad($pro, 12, '0', STR_PAD_LEFT);
                        }
                        return $pro;
                    }, $proNumbers);

                    \Log::info('🔧 Formatted data for query', [
                        'formatted_materials_sample' => array_slice($formattedMaterials, 0, 3),
                        'formatted_pro_numbers_sample' => array_slice($formattedProNumbers, 0, 3)
                    ]);

                    // PERUBAHAN: Set group_concat_max_len untuk menangani banyak data
                    DB::statement("SET SESSION group_concat_max_len = 1000000;");

                    // Query untuk mengambil data
                    $query = DB::table('sap_reservations')
                        ->select(
                            'matnr as material_code',
                            DB::raw('MAX(maktx) as material_description'),
                            DB::raw('MAX(meins) as unit'),
                            DB::raw('MAX(sortf) as sortf'),
                            DB::raw('MAX(dispo) as dispo'),
                            DB::raw('MAX(dispc) as dispc'),
                            DB::raw('MAX(mathd) as mathd'),
                            DB::raw('MAX(makhd) as makhd'),
                            DB::raw('MAX(groes) as groes'),
                            DB::raw('MAX(ferth) as ferth'),
                            DB::raw('MAX(zeinr) as zeinr'),
                            DB::raw('SUM(psmng) as total_qty'),
                            DB::raw('GROUP_CONCAT(DISTINCT sap_order) as source_pro_numbers'),
                            DB::raw('GROUP_CONCAT(DISTINCT CONCAT(kdauf, "-", kdpos)) as sales_orders'),
                            DB::raw('COUNT(DISTINCT sap_order) as pro_count')
                        )
                        ->where('sap_plant', $plant)
                        ->whereIn('matnr', $formattedMaterials)
                        ->where(function($query) use ($formattedProNumbers) {
                            $query->whereIn('sap_order', $formattedProNumbers)
                                ->orWhereIn('aufnr', $formattedProNumbers);
                        })
                        ->groupBy('matnr')
                        ->orderBy('matnr');

                    \Log::info('📝 SQL Query being executed:', [
                        'sql' => $query->toSql(),
                        'bindings' => $query->getBindings()
                    ]);

                    $materialData = $query->get();

                    \Log::info('📊 Query results', [
                        'count' => $materialData->count(),
                        'sample' => $materialData->take(2)->map(function($item) {
                            return [
                                'material_code' => $item->material_code,
                                'dispo' => $item->dispo,
                                'dispc' => $item->dispc,
                                'total_qty' => $item->total_qty,
                                'source_pro_numbers' => $item->source_pro_numbers
                            ];
                        })
                    ]);

                    if ($materialData->isEmpty()) {
                        \Log::warning('⚠️ No data found with formatted values, trying alternative format');

                        // Try alternative format (without leading zeros)
                        $altFormattedMaterials = array_map(function($material) {
                            return ltrim($material, '0');
                        }, $formattedMaterials);

                        $altFormattedProNumbers = array_map(function($pro) {
                            return ltrim($pro, '0');
                        }, $formattedProNumbers);

                        $alternativeQuery = DB::table('sap_reservations')
                            ->select(
                                'matnr as material_code',
                                DB::raw('MAX(maktx) as material_description'),
                                DB::raw('MAX(meins) as unit'),
                                DB::raw('MAX(sortf) as sortf'),
                                DB::raw('MAX(dispo) as dispo'),
                                DB::raw('MAX(dispc) as dispc'),
                                DB::raw('MAX(mathd) as mathd'),
                                DB::raw('MAX(makhd) as makhd'),
                                DB::raw('MAX(groes) as groes'),
                                DB::raw('MAX(ferth) as ferth'),
                                DB::raw('MAX(zeinr) as zeinr'),
                                DB::raw('SUM(psmng) as total_qty'),
                                DB::raw('GROUP_CONCAT(DISTINCT sap_order) as source_pro_numbers'),
                                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(kdauf, "-", kdpos)) as sales_orders'),
                                DB::raw('COUNT(DISTINCT sap_order) as pro_count')
                            )
                            ->where('sap_plant', $plant)
                            ->where(function($query) use ($altFormattedMaterials, $formattedMaterials) {
                                $query->whereIn('matnr', $formattedMaterials)
                                    ->orWhereIn(DB::raw('TRIM(LEADING "0" FROM matnr)'), $altFormattedMaterials);
                            })
                            ->where(function($query) use ($altFormattedProNumbers, $formattedProNumbers) {
                                $query->whereIn('sap_order', $formattedProNumbers)
                                    ->orWhereIn('aufnr', $formattedProNumbers)
                                    ->orWhereIn(DB::raw('TRIM(LEADING "0" FROM sap_order)'), $altFormattedProNumbers)
                                    ->orWhereIn(DB::raw('TRIM(LEADING "0" FROM aufnr)'), $altFormattedProNumbers);
                            })
                            ->groupBy('matnr')
                            ->orderBy('matnr');

                        $alternativeData = $alternativeQuery->get();

                        \Log::info('🔍 Alternative query results', [
                            'count' => $alternativeData->count(),
                            'sample' => $alternativeData->take(2)
                        ]);

                        if ($alternativeData->isEmpty()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'The selected materials do not exist in the chosen PRO numbers',
                                'debug_info' => [
                                    'plant' => $plant,
                                    'materials_selected' => $materials,
                                    'pro_numbers_selected' => $proNumbers,
                                    'formatted_materials' => $formattedMaterials,
                                    'formatted_pro_numbers' => $formattedProNumbers,
                                    'database_check' => 'No matching records found in sap_reservations'
                                ]
                            ], 404);
                        }

                        $materialData = $alternativeData;
                    }

                    // Transform data
                    $transformedData = [];
                    foreach ($materialData as $item) {
                        $sources = $item->source_pro_numbers ? explode(',', $item->source_pro_numbers) : [];
                        $salesOrders = $item->sales_orders ? explode(',', $item->sales_orders) : [];

                        // Format material code untuk display
                        $displayMaterialCode = $item->material_code;
                        if (ctype_digit($displayMaterialCode)) {
                            $displayMaterialCode = ltrim($displayMaterialCode, '0');
                        }

                        // Format sources untuk display
                        $displaySources = array_map(function($source) {
                            if (ctype_digit($source)) {
                                return ltrim($source, '0');
                            }
                            return $source;
                        }, $sources);

                        $transformedData[] = [
                            'material_code' => $item->material_code,
                            'material_code_display' => $displayMaterialCode,
                            'material_description' => $item->material_description,
                            'unit' => $item->unit,
                            'sortf' => $item->sortf,
                            'dispo' => $item->dispo,
                            'dispc' => $item->dispc,
                            'total_qty' => $item->total_qty,
                            'sources' => $displaySources,
                            'sources_raw' => $sources,
                            'sales_orders' => $salesOrders,
                            'sales_orders_raw' => $salesOrders,
                            'source_count' => $item->pro_count,
                            'mathd' => $item->mathd,
                            'makhd' => $item->makhd,
                            'groes' => $item->groes,
                            'ferth' => $item->ferth,
                            'zeinr' => $item->zeinr,
                            'is_consolidated' => count($sources) > 1
                        ];
                    }

                    \Log::info('✅ Successfully transformed data', [
                        'transformed_count' => count($transformedData),
                        'first_item' => $transformedData[0] ?? 'No data'
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $transformedData,
                        'count' => count($transformedData),
                        'debug' => [
                            'query_matched' => $materialData->count(),
                            'transformed' => count($transformedData)
                        ]
                    ]);

                } catch (\Exception $e) {
                    \Log::error('🔥 Failed to load PRO data: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'request_data' => $request->all()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'System error: ' . $e->getMessage(),
                        'error_details' => [
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]
                    ], 500);
                }
            }

                /**
             * Create document dengan CSRF protection dan chunk processing untuk banyak data
             */
            public function createDocument(Request $request)
            {
                ini_set('max_execution_time', 300);
                ini_set('memory_limit', '1024M');

                Log::info('📝 CREATE DOCUMENT REQUEST RECEIVED', [
                    'timestamp' => now()->toISOString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'request_method' => $request->method(),
                    'content_type' => $request->header('Content-Type'),
                    'is_json' => $request->isJson(),
                    'input_data' => $request->all()
                ]);

                // Check if request is JSON
                if ($request->isJson()) {
                    $data = $request->json()->all();
                } else {
                    $data = $request->all();
                }

                Log::info('📝 CREATE DOCUMENT DATA PARSED', [
                    'data_keys' => array_keys($data),
                    'plant_supply' => $data['plant_supply'] ?? null,
                    'materials_count' => isset($data['materials']) ? count($data['materials']) : 0,
                    'pro_numbers_count' => isset($data['pro_numbers']) ? count($data['pro_numbers']) : 0,
                    'remarks' => isset($data['remarks']) ? 'Remarks provided' : 'No remarks'
                ]);

                // Validate CSRF token
                $token = $data['_token'] ?? $request->input('_token');
                if (!hash_equals($request->session()->token(), $token)) {
                    Log::error('❌ CSRF token mismatch', [
                        'session_token' => $request->session()->token(),
                        'provided_token' => $token
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'CSRF token mismatch. Please refresh the page and try again.'
                    ], 419);
                }

                DB::beginTransaction();

                try {
                    $plant = $data['plant'] ?? $request->input('plant');
                    $plantSupply = $data['plant_supply'] ?? $request->input('plant_supply');
                    $materials = $data['materials'] ?? $request->input('materials', []);
                    $proNumbers = $data['pro_numbers'] ?? $request->input('pro_numbers', []);
                    $materialTypes = $data['material_types'] ?? $request->input('material_types', []);
                    // PERUBAHAN: Ambil remarks dari data
                    $remarks = $data['remarks'] ?? $request->input('remarks', '');
                    if (empty($remarks)) {
                        throw new \Exception('Document remarks are required');
                    }
                    Log::info('🔧 Processing createDocument', [
                        'plant' => $plant,
                        'plant_supply' => $plantSupply,
                        'materials_count' => is_array($materials) ? count($materials) : 0,
                        'pro_numbers_count' => is_array($proNumbers) ? count($proNumbers) : 0,
                        'remarks_length' => strlen($remarks),
                        'materials_sample' => is_array($materials) && count($materials) > 0 ? $materials[0] : 'No materials'
                    ]);

                    // Validasi data yang diperlukan
                    if (empty($plant)) {
                        throw new \Exception('Plant request is required');
                    }

                    if (empty($plantSupply)) {
                        throw new \Exception('Plant supply is required');
                    }

                    if (empty($materials) || !is_array($materials)) {
                        throw new \Exception('No materials selected or invalid materials data');
                    }

                    // Validate materials data structure
                    foreach ($materials as $index => $material) {
                        if (!is_array($material)) {
                            throw new \Exception("Material data at index {$index} is not an array");
                        }

                        if (!isset($material['material_code']) || empty($material['material_code'])) {
                            throw new \Exception("Material code is required at index {$index}");
                        }

                        if (!isset($material['requested_qty'])) {
                            throw new \Exception("Requested quantity is required at index {$index}");
                        }

                        $requestedQty = floatval($material['requested_qty']);
                        if ($requestedQty <= 0) {
                            $materialDisplay = $material['material_code_display'] ?? $material['material_code'];
                            throw new \Exception("Requested quantity must be greater than 0 for material: {$materialDisplay}");
                        }
                    }

                    // Generate document number
                    $documentNo = $this->generateGlobalDocumentNumberWithPlantPrefix($plant);

                    Log::info('📄 Generated document number', [
                        'document_no' => $documentNo,
                        'plant' => $plant,
                        'plant_supply' => $plantSupply,
                        'remarks' => $remarks ? 'Yes' : 'No'
                    ]);

                    // Calculate totals
                    $totalItems = count($materials);
                    $totalQty = 0;

                    foreach ($materials as $material) {
                        $totalQty += floatval($material['requested_qty'] ?? 0);
                    }

                    if ($totalQty <= 0) {
                        throw new \Exception('Total requested quantity must be greater than 0');
                    }

                    // Create document dengan status 'booked' dan menyimpan plant supply dan remarks
                    $document = ReservationDocument::create([
                        'document_no' => $documentNo,
                        'plant' => $plant,
                        'sloc_supply' => $plantSupply, // Simpan plant supply
                        'remarks' => $remarks, // PERUBAHAN: Simpan remarks
                        'status' => 'booked',
                        'total_items' => $totalItems,
                        'total_qty' => $totalQty,
                        'total_transferred' => 0,
                        'completion_rate' => 0,
                        'created_by' => Auth::id(),
                        'created_by_name' => Auth::user()->name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('✅ Document created successfully', [
                        'document_id' => $document->id,
                        'document_no' => $documentNo,
                        'plant' => $plant,
                        'plant_supply' => $plantSupply,
                        'remarks' => $remarks ? 'Yes' : 'No',
                        'total_items' => $totalItems,
                        'total_qty' => $totalQty,
                        'status' => 'booked'
                    ]);

                    // Insert items in chunks to avoid memory issues
                    $itemsToCreate = [];
                    $chunkSize = 100;
                    $insertedItems = 0;

                    foreach ($materials as $index => $material) {
                        // Check if quantity is editable for MRP
                        $isQtyEditable = true;
                        $dispo = $material['dispo'] ?? null;

                        // If dispo not provided in material, check pro_details
                        if (!$dispo && isset($material['pro_details']) && is_array($material['pro_details']) && count($material['pro_details']) > 0) {
                            foreach ($material['pro_details'] as $proDetail) {
                                if (isset($proDetail['dispo']) && $proDetail['dispo']) {
                                    $dispo = $proDetail['dispo'];
                                    break;
                                }
                            }
                        }

                        $isQtyEditable = $this->isQtyEditableForMRP($dispo);

                        // Ensure arrays are properly formatted
                        $sources = isset($material['sources']) && is_array($material['sources']) ? $material['sources'] : [];
                        $salesOrders = isset($material['sales_orders']) && is_array($material['sales_orders']) ? $material['sales_orders'] : [];
                        $proDetails = isset($material['pro_details']) && is_array($material['pro_details']) ? $material['pro_details'] : [];

                        // Prepare item data
                        $itemData = [
                            'document_id' => $document->id,
                            'material_code' => $material['material_code'],
                            'material_description' => $material['material_description'] ?? 'No Description',
                            'unit' => $material['unit'] ?? 'PC',
                            'sortf' => $material['sortf'] ?? null,
                            'dispo' => $dispo,
                            'dispc' => $material['dispc'] ?? null,
                            'is_qty_editable' => $isQtyEditable,
                            'requested_qty' => floatval($material['requested_qty']),
                            'transferred_qty' => 0,
                            'remaining_qty' => floatval($material['requested_qty']),
                            'sources' => json_encode($sources),
                            'sales_orders' => json_encode($salesOrders),
                            'pro_details' => json_encode($proDetails),
                            'mathd' => $material['mathd'] ?? null,
                            'makhd' => $material['makhd'] ?? null,
                            'groes' => $material['groes'] ?? null,
                            'ferth' => $material['ferth'] ?? null,
                            'zeinr' => $material['zeinr'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $itemsToCreate[] = $itemData;

                        // Insert in chunks to avoid memory issues
                        if (count($itemsToCreate) >= $chunkSize) {
                            DB::table('reservation_document_items')->insert($itemsToCreate);
                            $insertedItems += count($itemsToCreate);
                            $itemsToCreate = [];

                            Log::info("📝 Inserted {$chunkSize} items chunk for document {$documentNo}");
                        }
                    }

                    // Insert remaining items
                    if (!empty($itemsToCreate)) {
                        DB::table('reservation_document_items')->insert($itemsToCreate);
                        $insertedItems += count($itemsToCreate);
                        Log::info("📝 Inserted final chunk of " . count($itemsToCreate) . " items");
                    }

                    Log::info("✅ Total {$insertedItems} items inserted for document {$documentNo}");

                    // Delete used sync data in smaller chunks
                    $deletedCount = $this->deleteUsedSyncDataInChunks($plant, $materials, $proNumbers);

                    DB::commit();

                    Log::info('🎉 Reservation document created successfully', [
                        'document_no' => $documentNo,
                        'document_id' => $document->id,
                        'plant_supply' => $plantSupply,
                        'remarks' => $remarks ? 'Yes' : 'No',
                        'total_items' => $totalItems,
                        'total_qty' => $totalQty,
                        'status' => 'booked',
                        'deleted_sync_data_count' => $deletedCount
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Reservation document created successfully',
                        'document_no' => $documentNo,
                        'document_id' => $document->id,
                        'plant_supply' => $plantSupply,
                        'total_items' => $totalItems,
                        'total_qty' => $totalQty,
                        'status' => 'booked',
                        'deleted_sync_data_count' => $deletedCount,
                        'redirect_url' => route('documents.show', $document->id)
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('🔥 Failed to create document: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'request_data' => $request->all(),
                        'json_data' => $data ?? []
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create document: ' . $e->getMessage()
                    ], 500);
                }
            }

    /**
     * Helper function to get allowed MRP list
     */
    private function getAllowedMRP()
    {
        return ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1', 'D21', 'D22', 'GF1', 'CH4', 'D26', 'D28', 'DR1', 'DR2','D23', 'WE2', 'GW2'];
    }

    /**
     * Generate document number dengan metode global dengan prefix plant
     */
    private function generateGlobalDocumentNumberWithPlantPrefix($plant)
    {
        try {
            // Tentukan prefix berdasarkan plant
            $prefix = ($plant == '3000') ? 'RSMG' : 'RSBY';

            // Gunakan database sequence untuk menghindari race condition
            $latestSeq = DB::table('reservation_documents')
                ->select(DB::raw('COALESCE(MAX(CAST(SUBSTRING(document_no, 5) AS UNSIGNED)), 0) as max_seq'))
                ->where('document_no', 'LIKE', $prefix . '%')
                ->value('max_seq');

            $sequence = $latestSeq + 1;
            $documentNo = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);

            // Verifikasi nomor belum digunakan
            $counter = 0;
            while (DB::table('reservation_documents')->where('document_no', $documentNo)->exists()) {
                $sequence++;
                $documentNo = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
                $counter++;
                if ($counter > 100) {
                    throw new \Exception('Failed to generate unique document number');
                }
            }

            \Log::info('Generated document number', [
                'prefix' => $prefix,
                'sequence' => $sequence,
                'document_no' => $documentNo
            ]);

            return $documentNo;
        } catch (\Exception $e) {
            \Log::error('Failed to generate document number: ' . $e->getMessage());
            throw new \Exception('Failed to generate document number: ' . $e->getMessage());
        }
    }

    /**
     * Delete used sync data in chunks to avoid memory issues
     */
    private function deleteUsedSyncDataInChunks($plant, $materials, $proNumbers)
    {
        $totalDeleted = 0;

        try {
            Log::info('🗑️ Starting sync data deletion in chunks', [
                'plant' => $plant,
                'materials_count' => is_array($materials) ? count($materials) : 0,
                'pro_numbers_count' => is_array($proNumbers) ? count($proNumbers) : 0
            ]);

            if (empty($proNumbers) || !is_array($proNumbers)) {
                Log::warning('No PRO numbers provided for deletion');
                return 0;
            }

            // Format PRO numbers to 12-digit
            $formattedProNumbers = [];
            foreach ($proNumbers as $pro) {
                $pro = trim($pro);
                if (ctype_digit($pro)) {
                    $formattedProNumbers[] = str_pad($pro, 12, '0', STR_PAD_LEFT);
                } else {
                    $formattedProNumbers[] = $pro;
                }
            }

            // Delete in chunks of 1000 records
            $chunkSize = 1000;
            $offset = 0;

            do {
                $query = DB::table('sap_reservations')
                    ->where('sap_plant', $plant)
                    ->where(function($query) use ($formattedProNumbers) {
                        $query->whereIn('sap_order', $formattedProNumbers)
                            ->orWhereIn('aufnr', $formattedProNumbers);
                    });

                $deletedCount = $query->limit($chunkSize)->delete();
                $totalDeleted += $deletedCount;

                Log::info("🗑️ Deleted chunk: {$deletedCount} records, Total: {$totalDeleted}");

                if ($deletedCount < $chunkSize) {
                    break;
                }

                // Small delay to avoid overloading the database
                usleep(100000); // 0.1 second

            } while (true);

            Log::info('✅ Deleted sync data in chunks', [
                'plant' => $plant,
                'pro_numbers_count' => count($proNumbers),
                'total_deleted' => $totalDeleted
            ]);

            return $totalDeleted;

        } catch (\Exception $e) {
            Log::error('❌ Failed to delete sync data in chunks: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Bulk delete reservations
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:reservations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reservation IDs'
            ], 400);
        }

        try {
            $count = Reservation::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} reservations deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reservations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search reservations
     */
    public function search(Request $request)
    {
        try {
            $query = Reservation::query();

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('material_code', 'LIKE', "%{$search}%")
                      ->orWhere('material_description', 'LIKE', "%{$search}%")
                      ->orWhere('plant', 'LIKE', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->input('start_date'),
                    $request->input('end_date')
                ]);
            }

            $reservations = $query->orderBy('created_at', 'desc')->paginate(20);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $reservations
                ]);
            }

            return view('reservations.index', compact('reservations'));
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search failed: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('reservations.index')
                ->with('error', 'Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Get plants for filter
     */
    public function getPlants()
    {
        try {
            $plants = DB::table('sap_reservations')
                ->select('sap_plant as plant')
                ->whereNotNull('sap_plant')
                ->distinct()
                ->orderBy('sap_plant')
                ->pluck('plant');

            return response()->json([
                'success' => true,
                'plants' => $plants
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get plants: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics()
    {
        try {
            $totalReservations = Reservation::count();
            $totalQuantity = Reservation::sum('quantity');
            $draftCount = Reservation::where('status', 'draft')->count();
            $approvedCount = Reservation::where('status', 'approved')->count();
            $rejectedCount = Reservation::where('status', 'rejected')->count();
            $completedCount = Reservation::where('status', 'completed')->count();

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_reservations' => $totalReservations,
                    'total_quantity' => $totalQuantity,
                    'draft_count' => $draftCount,
                    'approved_count' => $approvedCount,
                    'rejected_count' => $rejectedCount,
                    'completed_count' => $completedCount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug sync data untuk melihat field yang ada
     */
    public function debugSyncData(Request $request)
    {
        try {
            $plant = $request->input('plant', '3000');
            $limit = $request->input('limit', 10);

            // Get sample data with all fields
            $sampleData = DB::table('sap_reservations')
                ->where('sap_plant', $plant)
                ->whereNotNull('matnr')
                ->select('matnr', 'maktx', 'sortf', 'dispo', 'mtart', 'sap_order', 'mathd', 'makhd', 'created_at')
                ->limit($limit)
                ->get();

            // Get column structure
            $columns = DB::select('SHOW COLUMNS FROM sap_reservations');
            $columnNames = array_column($columns, 'Field');

            // Check for sortf values
            $materialsWithSortf = DB::table('sap_reservations')
                ->where('sap_plant', $plant)
                ->whereNotNull('sortf')
                ->where('sortf', '!=', '')
                ->count();

            // Get distinct sortf values
            $distinctSortf = DB::table('sap_reservations')
                ->where('sap_plant', $plant)
                ->whereNotNull('sortf')
                ->where('sortf', '!=', '')
                ->distinct()
                ->pluck('sortf')
                ->take(10);

            // Check for mathd and makhd values
            $materialsWithMathd = DB::table('sap_reservations')
                ->where('sap_plant', $plant)
                ->whereNotNull('mathd')
                ->where('mathd', '!=', '')
                ->count();

            $materialsWithMakhd = DB::table('sap_reservations')
                ->where('sap_plant', $plant)
                ->whereNotNull('makhd')
                ->where('makhd', '!=', '')
                ->count();

            return response()->json([
                'success' => true,
                'debug_info' => [
                    'table_columns' => $columnNames,
                    'total_records' => DB::table('sap_reservations')->where('sap_plant', $plant)->count(),
                    'materials_with_sortf' => $materialsWithSortf,
                    'materials_with_mathd' => $materialsWithMathd,
                    'materials_with_makhd' => $materialsWithMakhd,
                    'sample_data' => $sampleData,
                    'distinct_sortf_values' => $distinctSortf,
                    'has_mathd_field' => in_array('mathd', $columnNames),
                    'has_makhd_field' => in_array('makhd', $columnNames)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
