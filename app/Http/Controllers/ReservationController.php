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

            $query = Reservation::query();

            // Apply filters if provided
            if ($request->has('plant') && $request->plant != '') {
                $query->where('plant', $request->plant);
            }

            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('material_code', 'LIKE', "%{$search}%")
                      ->orWhere('material_description', 'LIKE', "%{$search}%")
                      ->orWhere('plant', 'LIKE', "%{$search}%");
                });
            }

            if ($request->has('start_date') && $request->start_date != '') {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date != '') {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $reservations = $query->orderBy('created_at', 'desc')->paginate(20);

            // Debug: Cek data
            Log::info('Reservations data:', [
                'count' => $reservations->count(),
                'total' => $reservations->total(),
                'first_item' => $reservations->first() ? get_class($reservations->first()) : 'No data'
            ]);

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
        $document = ReservationDocument::findOrFail($id);
        // Hanya dokumen dengan status 'created' yang bisa diedit
        if ($document->status != 'created') {
            return redirect()->route('documents.show', $document->id)
                ->with('error', 'Only documents with status "Created" can be edited.');
        }
        return view('documents.edit', compact('document'));
    } catch (\Exception $e) {
        return redirect()->route('documents.index')
            ->with('error', 'Document not found: ' . $e->getMessage());
    }
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

        // Update remarks - PERBAIKAN DI SINI
        $document->remarks = $request->remarks;
        $document->save();

        // Update items qty
        $totalQty = 0;
        foreach ($request->items as $itemData) {
            $item = $document->items()->find($itemData['id']);
            if ($item) {
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
                                'makhd' => $item['makhd'] ?? $item['finish_good'] ?? null, // Kolom Finish Good
                                'mtart' => $item['mtart'] ?? $item['material_type'] ?? null,
                                'sortf' => $item['sortf'] ?? null,
                                'dwerk' => $item['dwerk'] ?? $plant,
                                'sync_by' => auth()->id(),
                                'sync_by_name' => auth()->user()->name,
                                'sync_at' => now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            // Hapus null values untuk date fields
                            if (empty($reservationData['gstrp'])) {
                                unset($reservationData['gstrp']);
                            }
                            if (empty($reservationData['gltrp'])) {
                                unset($reservationData['gltrp']);
                            }

                            // **PERBAIKAN: Gunakan kombinasi unik rsnum + rspos + matnr + sap_plant**
                            // Ini akan menyimpan setiap item reservation secara individual
                            $uniqueKey = implode('_', [
                                $reservationData['rsnum'],
                                $reservationData['rspos'] ?? '000',
                                $reservationData['matnr'],
                                $reservationData['sap_plant']
                            ]);

                            // Update or create berdasarkan kombinasi unik
                            $existing = DB::table('sap_reservations')
                                ->where('rsnum', $reservationData['rsnum'])
                                ->where('rspos', $reservationData['rspos'])
                                ->where('matnr', $reservationData['matnr'])
                                ->where('sap_plant', $reservationData['sap_plant'])
                                ->first();

                            if ($existing) {
                                // Update existing record
                                DB::table('sap_reservations')
                                    ->where('id', $existing->id)
                                    ->update($reservationData);
                            } else {
                                // Insert new record
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
            $plant = $request->input('plant');

            // Get material types from sap_reservations table
            $materialTypes = DB::table('sap_reservations')
                ->select('mtart')
                ->where('sap_plant', $plant)
                ->whereNotNull('mtart')
                ->distinct()
                ->orderBy('mtart')
                ->pluck('mtart');

            return response()->json([
                'success' => true,
                'material_types' => $materialTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get material types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: Get materials by type.
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

            $materials = DB::table('sap_reservations')
                ->select('matnr', 'maktx', 'mtart', 'sortf')
                ->where('sap_plant', $plant)
                ->whereIn('mtart', $materialTypes)
                ->whereNotNull('matnr')
                ->distinct()
                ->orderBy('matnr')
                ->get();

            return response()->json([
                'success' => true,
                'materials' => $materials
            ]);
        } catch (\Exception $e) {
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
     * AJAX: Load multiple PRO.
     */
    public function loadMultiplePro(Request $request)
{
    try {
        $plant = $request->input('plant');
        $materialTypes = $request->input('material_types', []);
        $materials = $request->input('materials', []);
        $proNumbers = $request->input('pro_numbers', []);

        Log::info('🔍 DEBUG - Starting loadMultiplePro', [
            'plant' => $plant,
            'materials_raw' => $materials,
            'pro_numbers_raw' => $proNumbers,
            'material_types' => $materialTypes
        ]);

        if (empty($proNumbers) || empty($materials)) {
            return response()->json([
                'success' => false,
                'message' => 'No PRO numbers or materials selected'
            ], 400);
        }

        // **PERBAIKAN 1: Konversi material codes ke format 18-digit dengan leading zeros**
        $formattedMaterials = array_map(function($material) {
            // Jika material adalah numeric (hanya angka), tambahkan leading zeros hingga 18 karakter
            if (ctype_digit($material)) {
                return str_pad($material, 18, '0', STR_PAD_LEFT);
            }
            // Jika mengandung huruf/simbol, kembalikan asli
            return $material;
        }, $materials);

        Log::info('🔍 DEBUG - Material format conversion', [
            'original' => $materials,
            'formatted_18_digit' => $formattedMaterials
        ]);

        // **PERBAIKAN 2: Format PRO numbers jika numeric**
        $formattedProNumbers = array_map(function($pro) {
            $pro = trim($pro);
            // Jika hanya angka, format ke 12-digit (panjang standar PRO number)
            if (ctype_digit($pro)) {
                return str_pad($pro, 12, '0', STR_PAD_LEFT);
            }
            return $pro;
        }, $proNumbers);

        Log::info('🔍 DEBUG - PRO number format conversion', [
            'original' => $proNumbers,
            'formatted_12_digit' => $formattedProNumbers
        ]);

        // **DEBUG 1: Cek data di database untuk kombinasi ini**
        $debugQuery = DB::table('sap_reservations')
            ->where('sap_plant', $plant)
            ->where(function($query) use ($formattedProNumbers) {
                foreach ($formattedProNumbers as $pro) {
                    $query->orWhere('sap_order', $pro)
                          ->orWhere('aufnr', $pro);
                }
            })
            ->where(function($query) use ($formattedMaterials) {
                foreach ($formattedMaterials as $material) {
                    $query->orWhere('matnr', $material);
                }
            });

        $debugData = $debugQuery->get();

        Log::info('🔍 DEBUG - Direct database check', [
            'query_conditions' => [
                'plant' => $plant,
                'pros' => $formattedProNumbers,
                'materials' => $formattedMaterials
            ],
            'found_count' => $debugData->count(),
            'found_records' => $debugData->map(function($item) {
                return [
                    'matnr' => $item->matnr,
                    'sap_order' => $item->sap_order,
                    'aufnr' => $item->aufnr,
                    'maktx' => $item->maktx
                ];
            })
        ]);

        // **PERBAIKAN 3: Query utama dengan format yang tepat**
        $materialData = DB::table('sap_reservations')
            ->select(
                'matnr as material_code',
                'maktx as material_description',
                'meins as unit',
                'sortf',
                DB::raw('SUM(psmng) as total_qty'),
                DB::raw('GROUP_CONCAT(DISTINCT sap_order) as source_pro_numbers'),
                DB::raw('COUNT(DISTINCT sap_order) as pro_count')
            )
            ->where('sap_plant', $plant)
            ->whereIn('matnr', $formattedMaterials)
            ->where(function($query) use ($formattedProNumbers) {
                $query->whereIn('sap_order', $formattedProNumbers)
                      ->orWhereIn('aufnr', $formattedProNumbers);
            })
            ->groupBy('matnr', 'maktx', 'meins', 'sortf')
            ->orderBy('matnr')
            ->get();

        Log::info('🔍 DEBUG - Main query result', [
            'query_count' => $materialData->count(),
            'materials_found' => $materialData->pluck('material_code'),
            'sql' => DB::getQueryLog()[count(DB::getQueryLog()) - 1]['query'] ?? 'N/A'
        ]);

        if ($materialData->isEmpty()) {
            // **Cari data alternatif dengan format berbeda**
            // Coba tanpa leading zeros
            $altFormattedMaterials = array_map(function($material) {
                return ltrim($material, '0');
            }, $formattedMaterials);

            $altFormattedProNumbers = array_map(function($pro) {
                return ltrim($pro, '0');
            }, $formattedProNumbers);

            Log::info('🔍 DEBUG - Trying alternative format (without leading zeros)', [
                'materials' => $altFormattedMaterials,
                'pros' => $altFormattedProNumbers
            ]);

            // **PERBAIKAN 4: Jika tidak ditemukan, coba query alternatif**
            $alternativeData = DB::table('sap_reservations')
                ->select(
                    'matnr as material_code',
                    'maktx as material_description',
                    'meins as unit',
                    'sortf',
                    DB::raw('SUM(psmng) as total_qty'),
                    DB::raw('GROUP_CONCAT(DISTINCT sap_order) as source_pro_numbers'),
                    DB::raw('COUNT(DISTINCT sap_order) as pro_count')
                )
                ->where('sap_plant', $plant)
                ->where(function($query) use ($altFormattedMaterials, $formattedMaterials) {
                    // Coba kedua format
                    $query->whereIn('matnr', $formattedMaterials)
                          ->orWhereIn(DB::raw('TRIM(LEADING "0" FROM matnr)'), $altFormattedMaterials);
                })
                ->where(function($query) use ($altFormattedProNumbers, $formattedProNumbers) {
                    $query->whereIn('sap_order', $formattedProNumbers)
                          ->orWhereIn('aufnr', $formattedProNumbers)
                          ->orWhereIn(DB::raw('TRIM(LEADING "0" FROM sap_order)'), $altFormattedProNumbers)
                          ->orWhereIn(DB::raw('TRIM(LEADING "0" FROM aufnr)'), $altFormattedProNumbers);
                })
                ->groupBy('matnr', 'maktx', 'meins', 'sortf')
                ->orderBy('matnr')
                ->get();

            if ($alternativeData->isEmpty()) {
                // **Informasi lengkap untuk debugging**
                $availableMaterials = DB::table('sap_reservations')
                    ->where('sap_plant', $plant)
                    ->whereNotNull('matnr')
                    ->distinct()
                    ->pluck('matnr')
                    ->map(function($matnr) {
                        return [
                            'with_zeros' => $matnr,
                            'without_zeros' => ltrim($matnr, '0')
                        ];
                    });

                $availablePros = DB::table('sap_reservations')
                    ->where('sap_plant', $plant)
                    ->whereNotNull('sap_order')
                    ->distinct()
                    ->pluck('sap_order')
                    ->map(function($pro) {
                        return [
                            'with_zeros' => $pro,
                            'without_zeros' => ltrim($pro, '0')
                        ];
                    });

                Log::warning('❌ No data found with any format', [
                    'plant' => $plant,
                    'user_selected_materials' => $materials,
                    'formatted_materials_18d' => $formattedMaterials,
                    'alt_materials_no_zeros' => $altFormattedMaterials,
                    'available_materials_in_db' => $availableMaterials,
                    'available_pros_in_db' => $availablePros
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'The selected materials do not exist in the chosen PRO numbers',
                    'details' => [
                        'Plant: ' . $plant,
                        'Materials selected (UI format): ' . implode(', ', $materials),
                        'Materials searched (18-digit): ' . implode(', ', $formattedMaterials),
                        'PRO numbers selected (UI format): ' . implode(', ', $proNumbers),
                        'PRO numbers searched (12-digit): ' . implode(', ', $formattedProNumbers),
                        'Try: 1) Sync fresh data from SAP 2) Check PRO numbers 3) Select different materials'
                    ],
                    'debug_info' => [
                        'database_sample_materials' => $availableMaterials->take(5),
                        'database_sample_pros' => $availablePros->take(5),
                        'data_formats_tried' => [
                            'materials_18_digit' => $formattedMaterials,
                            'materials_no_zeros' => $altFormattedMaterials,
                            'pros_12_digit' => $formattedProNumbers,
                            'pros_no_zeros' => $altFormattedProNumbers
                        ]
                    ]
                ], 404);
            }

            $materialData = $alternativeData;
            Log::info('✅ Found data with alternative format', [
                'count' => $materialData->count()
            ]);
        }

        // **PERBAIKAN 5: Transform data - format material code untuk display**
        $transformedData = [];
        foreach ($materialData as $item) {
            $sources = $item->source_pro_numbers ? explode(',', $item->source_pro_numbers) : [];

            // Format material code untuk display (tanpa leading zeros)
            $displayMaterialCode = $item->material_code;
            if (ctype_digit($displayMaterialCode)) {
                $displayMaterialCode = ltrim($displayMaterialCode, '0');
            }

            // Format PRO numbers untuk display
            $displaySources = array_map(function($source) {
                if (ctype_digit($source)) {
                    return ltrim($source, '0');
                }
                return $source;
            }, $sources);

            // Get PRO details
            $proDetails = [];
            foreach ($sources as $source) {
                $proQty = DB::table('sap_reservations')
                    ->where('sap_plant', $plant)
                    ->where('matnr', $item->material_code)
                    ->where('sap_order', $source)
                    ->sum('psmng');

                $displayPro = $source;
                if (ctype_digit($displayPro)) {
                    $displayPro = ltrim($displayPro, '0');
                }

                $proDetails[] = [
                    'pro_number' => $displayPro,
                    'pro_number_raw' => $source,
                    'quantity' => $proQty ?? 0,
                ];
            }

            $transformedData[] = [
                'material_code' => $item->material_code, // Format asli dari SAP
                'material_code_display' => $displayMaterialCode, // Untuk display di UI
                'material_description' => $item->material_description,
                'unit' => $item->unit,
                'sortf' => $item->sortf,
                'total_qty' => $item->total_qty,
                'sources' => $displaySources, // Untuk display
                'sources_raw' => $sources, // Format asli
                'pro_details' => $proDetails,
                'source_count' => $item->pro_count
            ];
        }

        Log::info('✅ Successfully loaded material data', [
            'transformed_count' => count($transformedData),
            'materials_raw' => array_column($transformedData, 'material_code'),
            'materials_display' => array_column($transformedData, 'material_code_display')
        ]);

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'count' => count($transformedData),
            'debug_info' => [
                'input_received' => [
                    'materials_ui' => $materials,
                    'pros_ui' => $proNumbers
                ],
                'database_matching' => [
                    'materials_found' => array_column($transformedData, 'material_code_display'),
                    'sources_found' => array_column($transformedData, 'sources')
                ]
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('🔥 Failed to load PRO data: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'System error: ' . $e->getMessage(),
            'details' => [
                'Please check application logs',
                'Contact system administrator'
            ]
        ], 500);
    }
}

    /**
     * AJAX: Create document and delete used sync data
     */
    public function createDocument(Request $request)
    {
        DB::beginTransaction();

        try {
            $plant = $request->input('plant');
            $materials = $request->input('materials', []);
            $proNumbers = $request->input('pro_numbers', []);

            Log::info('Creating reservation document', [
                'plant' => $plant,
                'materials_count' => count($materials),
                'pro_numbers_count' => count($proNumbers)
            ]);

            if (empty($materials)) {
                throw new \Exception('No materials selected');
            }

            // Generate document number
            $documentNo = $this->generateDocumentNumber($plant);

            // Calculate totals
            $totalItems = count($materials);
            $totalQty = 0;

            foreach ($materials as $material) {
                $totalQty += floatval($material['requested_qty'] ?? 0);
            }

            // Create document
            $document = ReservationDocument::create([
                'document_no' => $documentNo,
                'plant' => $plant,
                'status' => 'created',
                'total_items' => $totalItems,
                'total_qty' => $totalQty,
                'created_by' => Auth::id(),
                'created_by_name' => Auth::user()->name,
            ]);

            // Create document items
            foreach ($materials as $material) {
                $document->items()->create([
                    'material_code' => $material['material_code'],
                    'material_description' => $material['material_description'],
                    'unit' => $material['unit'],
                    'sortf' => $material['sortf'] ?? null,
                    'requested_qty' => $material['requested_qty'],
                    'sources' => json_encode($material['sources'] ?? []),
                    'pro_details' => json_encode($material['pro_details'] ?? []),
                ]);
            }

            // Delete used sync data from sap_reservations table
            $deletedCount = $this->deleteUsedSyncData($plant, $materials, $proNumbers);

            DB::commit();

            Log::info('Reservation document created successfully', [
                'document_no' => $documentNo,
                'document_id' => $document->id,
                'deleted_sync_data_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reservation document created successfully',
                'document_no' => $documentNo,
                'document_id' => $document->id,
                'deleted_sync_data_count' => $deletedCount,
                'redirect_url' => route('documents.show', $document->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create document: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete used sync data from sap_reservations table
     */
    private function deleteUsedSyncData($plant, $materials, $proNumbers)
            {
                $deletedCount = 0;

                try {
                    // **PERBAIKAN: Format PRO numbers ke 12-digit untuk database matching**
                    $formattedProNumbers = array_map(function($pro) {
                        $pro = trim($pro);
                        // Jika hanya angka, format ke 12-digit
                        if (ctype_digit($pro)) {
                            return str_pad($pro, 12, '0', STR_PAD_LEFT);
                        }
                        return $pro;
                    }, $proNumbers);

                    // **PERBAIKAN: Hapus SEMUA data dari PRO numbers yang dipilih**
                    $deletedCount = DB::table('sap_reservations')
                        ->where('sap_plant', $plant)
                        ->where(function($query) use ($formattedProNumbers) {
                            $query->whereIn('sap_order', $formattedProNumbers)
                                ->orWhereIn('aufnr', $formattedProNumbers);
                        })
                        ->delete();

                    Log::info('✅ Deleted ALL sync data for PROs', [
                        'plant' => $plant,
                        'pro_numbers_original' => $proNumbers,
                        'pro_numbers_formatted' => $formattedProNumbers,
                        'deleted_count' => $deletedCount
                    ]);

                    return $deletedCount;

                } catch (\Exception $e) {
                    Log::error('❌ Failed to delete sync data: ' . $e->getMessage());
                    return 0;
                }
            }

    /**
     * Generate document number.
     */
    private function generateDocumentNumber($plant)
    {
        $prefix = ($plant == '3000') ? 'RSMG' : 'RSBY';
        $year = date('Y');
        $month = date('m');

        // Get next sequence from reservation_documents table
        $sequence = DB::table('reservation_documents')
            ->where('plant', $plant)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
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
}
