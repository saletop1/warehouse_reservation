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
use App\Models\ReservationStock;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransfersExport;

class TransferController extends Controller
{
    /**
     * Create transfer - FIXED VERSION with USER SAP CREDENTIALS
     */
    public function createTransfer(Request $request, $documentId)
    {
        DB::beginTransaction();

        try {
            Log::info('=== TRANSFER PROCESS START (WITH USER SAP CREDENTIALS) ===', [
                'document_id' => $documentId,
                'user' => Auth::user()->name,
                'time' => now()->toDateTimeString(),
                'has_sap_credentials' => !empty($request->sap_credentials)
            ]);

            // 1. GET DOCUMENT
            $document = ReservationDocument::findOrFail($documentId);

            Log::info('Document data:', [
                'document_no' => $document->document_no,
                'plant' => $document->plant,
                'sloc_supply' => $document->sloc_supply,
                'has_sloc_supply' => !empty($document->sloc_supply)
            ]);

            // 2. VALIDATE REQUEST - INCLUDING SAP CREDENTIALS FROM USER
            $validated = $this->validateTransferRequest($request);

            // 3. GET SAP CREDENTIALS FROM USER INPUT (MODAL)
            $sapCredentials = $request->input('sap_credentials', []);

            // Validate SAP credentials
            if (empty($sapCredentials) ||
                empty($sapCredentials['user']) ||
                empty($sapCredentials['passwd'])) {
                throw new \Exception('SAP username and password are required from user input');
            }

            // Mask credentials for logging
            $maskedCreds = $sapCredentials;
            $maskedCreds['passwd'] = '******';
            Log::info('Using SAP credentials from user input:', $maskedCreds);

            // 4. DETERMINE PLANT SUPPLY from document sloc_supply
            $plantSupply = $document->sloc_supply;

            if (empty($plantSupply)) {
                // Fallback: use plant destination
                $plantSupply = $document->plant;
                Log::warning('Using plant destination as plant supply (sloc_supply was empty)', [
                    'plant_supply' => $plantSupply,
                    'document_no' => $document->document_no
                ]);
            }

            // Double-check plant_supply is not empty
            if (empty($plantSupply)) {
                throw new \Exception('Plant supply is required but empty. Document: ' . $document->document_no);
            }

            // 5. DETERMINE MOVE TYPE BASED ON PLANT
            // Jika plant supply sama dengan plant destination, gunakan 311 (dalam plant)
            // Jika berbeda, gunakan 301 (antar plant)
            $moveType = ($plantSupply == $document->plant) ? '311' : '301';

            Log::info('Move Type determined:', [
                'plant_supply' => $plantSupply,
                'plant_destination' => $document->plant,
                'move_type' => $moveType,
                'type_description' => $moveType == '301' ? 'Antar Plant' : 'Dalam Plant'
            ]);

            $remarks = $validated['remarks'] ?? "Transfer from Document {$document->document_no}";

            Log::info('Transfer parameters:', [
                'plant_supply' => $plantSupply,
                'move_type' => $moveType,
                'remarks' => $remarks,
                'item_count' => count($validated['items']),
                'sap_user' => substr($sapCredentials['user'], 0, 3) . '...'
            ]);

            // 6. VALIDATE EACH ITEM
            foreach ($validated['items'] as $index => $item) {
                $this->validateTransferItem($document, $item, $index);
            }

            // 7. CALL SAP API - WITH USER SAP CREDENTIALS
            $sapResponse = $this->callSapTransferServiceWithUserCredentials(
                $document,
                $plantSupply,
                $validated,
                $moveType,
                $sapCredentials
            );

            if (!$sapResponse['success']) {
                throw new \Exception('SAP transfer failed: ' . ($sapResponse['message'] ?? 'Unknown error'));
            }

            // 8. GET UNIQUE TRANSFER NUMBER FROM SAP
            $transferNo = $sapResponse['transfer_no'];

            if (empty($transferNo)) {
                throw new \Exception('No transfer number received from SAP');
            }

            Log::info('SAP transfer successful with user credentials', [
                'transfer_no' => $transferNo,
                'move_type_used' => $moveType,
                'sap_status' => $sapResponse['status'] ?? 'UNKNOWN',
                'sap_message' => $sapResponse['message'] ?? '',
                'sap_user' => substr($sapCredentials['user'], 0, 3) . '...'
            ]);

            // 9. CHECK FOR EXISTING TRANSFER NUMBER - WITH DUPLICATE HANDLING
            $existingTransfer = ReservationTransfer::where('transfer_no', $transferNo)->first();

            if ($existingTransfer) {
                Log::info('Transfer already exists in database', [
                    'existing_id' => $existingTransfer->id,
                    'existing_document_id' => $existingTransfer->document_id,
                    'existing_document_no' => $existingTransfer->document_no,
                    'new_document_id' => $document->id,
                    'new_document_no' => $document->document_no,
                    'transfer_no' => $transferNo
                ]);

                // Jika sudah ada untuk dokumen yang sama, gunakan yang sudah ada
                if ($existingTransfer->document_id == $document->id) {
                    // Update transfer dengan data terbaru jika diperlukan
                    $existingTransfer->update([
                        'status' => $sapResponse['status'] ?? 'COMPLETED',
                        'sap_message' => $sapResponse['message'] ?? 'Transfer completed',
                        'sap_response' => json_encode($sapResponse),
                        'remarks' => $remarks, // Update remarks dari input terbaru
                        'updated_at' => now()
                    ]);

                    $transfer = $existingTransfer;

                    Log::info('Using existing transfer record', [
                        'transfer_id' => $transfer->id,
                        'transfer_no' => $transfer->transfer_no
                    ]);
                } else {
                    // Jika untuk dokumen berbeda, generate transfer number baru
                    Log::warning('Transfer number exists for different document, generating new one');
                    $transferNo = $this->generateUniqueTransferNo($transferNo);
                    $transfer = $this->createNewTransferRecord($document, $plantSupply, $moveType, $transferNo, $validated, $sapResponse);
                }
            } else {
                // CREATE NEW TRANSFER RECORD
                $transfer = $this->createNewTransferRecord($document, $plantSupply, $moveType, $transferNo, $validated, $sapResponse);
            }

            // 10. CREATE TRANSFER ITEMS
            foreach ($validated['items'] as $index => $itemData) {
                $this->createTransferItem($transfer, $document, $itemData, $index + 1);
            }

            // 11. UPDATE DOCUMENT QUANTITIES
            $this->updateDocumentQuantities($document, $validated['items']);

            // 12. COMMIT TRANSACTION
            DB::commit();

            Log::info('=== TRANSFER PROCESS COMPLETED SUCCESSFULLY ===', [
                'transfer_id' => $transfer->id,
                'transfer_no' => $transfer->transfer_no,
                'document_no' => $document->document_no,
                'move_type' => $moveType,
                'item_count' => $transfer->total_items,
                'total_qty' => $transfer->total_qty,
                'sap_user' => substr($sapCredentials['user'], 0, 3) . '...'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transfer created successfully',
                'transfer_id' => $transfer->id,
                'transfer_no' => $transfer->transfer_no,
                'document_no' => $document->document_no,
                'status' => $transfer->status,
                'plant_supply' => $plantSupply,
                'plant_destination' => $document->plant,
                'move_type' => $moveType
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== TRANSFER PROCESS FAILED ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'document_id' => $documentId ?? 'unknown',
                'user' => Auth::user()->name ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call SAP transfer service with USER SAP CREDENTIALS
     */
    private function callSapTransferServiceWithUserCredentials($document, $plantSupply, $data, $moveType, $userSapCredentials)
    {
        $pythonServiceUrl = env('PYTHON_SERVICE_URL', 'http://localhost:5000');
        $user = Auth::user();

        // VALIDATE USER SAP CREDENTIALS
        if (empty($userSapCredentials['user']) || empty($userSapCredentials['passwd'])) {
            throw new \Exception('SAP username and password are required from user input');
        }

        // CRITICAL: Ensure plant_supply is not empty
        if (empty($plantSupply)) {
            throw new \Exception('Plant supply cannot be empty when calling SAP service');
        }

        // Prepare transfer data for Flask/SAP
        $transferData = [
            'document_no' => $document->document_no,
            'plant_supply' => $plantSupply,
            'plant_destination' => $document->plant,
            'move_type' => $moveType,
            'posting_date' => now()->format('Ymd'),
            'created_by' => $user->name,
            'created_at' => now()->toDateTimeString(),
            'remarks' => $data['remarks'] ?? "Transfer from Document {$document->document_no}",
            'items' => []
        ];

        foreach ($data['items'] as $item) {
            $batchSloc = $item['batch_sloc'] ?? '';
            // Remove SLOC: prefix
            if (strpos($batchSloc, 'SLOC:') === 0) {
                $batchSloc = substr($batchSloc, 5);
            }

            // Include plant_supply in EACH ITEM
            $itemData = [
                'material_code' => $item['material_code'],
                'material_desc' => $item['material_desc'],
                'quantity' => (float) $item['quantity'],
                'unit' => $item['unit'],
                'plant_tujuan' => $item['plant_tujuan'],
                'sloc_tujuan' => $item['sloc_tujuan'],
                'batch' => $item['batch'] ?? '',
                'batch_sloc' => $batchSloc,
                'plant_supply' => $plantSupply,
            ];

            $transferData['items'][] = $itemData;
        }

        Log::info('Sending to SAP service with USER SAP CREDENTIALS:', [
            'url' => $pythonServiceUrl . '/api/sap/transfer',
            'plant_supply' => $plantSupply,
            'plant_destination' => $document->plant,
            'move_type' => $moveType,
            'item_count' => count($transferData['items']),
            'sap_user' => substr($userSapCredentials['user'], 0, 3) . '...',
            'sap_client' => $userSapCredentials['client'] ?? 'Not provided'
        ]);

        try {
            // USE USER SAP CREDENTIALS, NOT ENVIRONMENT CREDENTIALS
            $response = Http::timeout(120)->post("{$pythonServiceUrl}/api/sap/transfer", [
                'transfer_data' => $transferData,
                'sap_credentials' => $userSapCredentials, // User credentials from modal
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('SAP service call failed with user credentials:', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'plant_supply' => $plantSupply,
                    'move_type' => $moveType,
                    'sap_user' => substr($userSapCredentials['user'], 0, 3) . '...'
                ]);
                throw new \Exception('SAP service call failed: ' . $errorBody);
            }

            $result = $response->json();

            if (!isset($result['success']) || !$result['success']) {
                Log::error('SAP transfer failed with user credentials:', [
                    'result' => $result,
                    'plant_supply' => $plantSupply,
                    'move_type' => $moveType,
                    'sap_user' => substr($userSapCredentials['user'], 0, 3) . '...'
                ]);
                throw new \Exception('SAP transfer failed: ' . ($result['message'] ?? 'Unknown error'));
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Exception during SAP service call with user credentials:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'python_url' => $pythonServiceUrl,
                'move_type' => $moveType,
                'sap_user' => substr($userSapCredentials['user'], 0, 3) . '...'
            ]);
            throw $e;
        }
    }

    /**
     * Call SAP for SYNC DATA ONLY - USES ENVIRONMENT CREDENTIALS
     * This is for data synchronization, not for transfers
     */
    public function syncDataFromSap(Request $request)
    {
        try {
            $pythonServiceUrl = env('PYTHON_SERVICE_URL', 'http://localhost:5000');

            // Use ENVIRONMENT credentials for sync (read-only operations)
            $envSapCredentials = [
                'ashost' => env('SAP_ASHOST'),
                'sysnr' => env('SAP_SYSNR'),
                'client' => env('SAP_CLIENT'),
                'user' => env('SAP_USERNAME'),
                'passwd' => env('SAP_PASSWORD'),
                'lang' => env('SAP_LANG', 'EN')
            ];

            Log::info('Syncing data from SAP using ENVIRONMENT credentials (read-only)', [
                'sap_user' => substr($envSapCredentials['user'], 0, 3) . '...'
            ]);

            $response = Http::timeout(120)->post("{$pythonServiceUrl}/api/sap/sync", [
                'plant' => $request->plant,
                'pro_numbers' => $request->pro_numbers,
                'user_id' => Auth::id(),
                'sap_credentials' => $envSapCredentials // Environment credentials for sync
            ]);

            // ... rest of sync logic

        } catch (\Exception $e) {
            Log::error('Sync failed:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Call SAP for STOCK CHECK ONLY - USES ENVIRONMENT CREDENTIALS
     * This is for checking stock availability, not for transfers
     */
    public function checkStockFromSap(Request $request)
    {
        try {
            $pythonServiceUrl = env('PYTHON_SERVICE_URL', 'http://localhost:5000');

            // Use ENVIRONMENT credentials for stock check (read-only operations)
            $envSapCredentials = [
                'ashost' => env('SAP_ASHOST'),
                'sysnr' => env('SAP_SYSNR'),
                'client' => env('SAP_CLIENT'),
                'user' => env('SAP_USERNAME'),
                'passwd' => env('SAP_PASSWORD'),
                'lang' => env('SAP_LANG', 'EN')
            ];

            Log::info('Checking stock from SAP using ENVIRONMENT credentials (read-only)', [
                'sap_user' => substr($envSapCredentials['user'], 0, 3) . '...'
            ]);

            $response = Http::timeout(120)->post("{$pythonServiceUrl}/api/sap/stock", [
                'plant' => $request->plant,
                'matnr' => $request->matnr,
                'sap_credentials' => $envSapCredentials // Environment credentials for stock check
            ]);

            // ... rest of stock check logic

        } catch (\Exception $e) {
            Log::error('Stock check failed:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Simple validation - UPDATED to include sap_credentials
     */
    private function validateTransferRequest(Request $request)
    {
        return $request->validate([
            'items' => 'required|array|min:1',
            'items.*.material_code' => 'required|string',
            'items.*.material_desc' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string|max:10',
            'items.*.plant_tujuan' => 'required|string|max:10',
            'items.*.sloc_tujuan' => 'required|string|max:10',
            'items.*.batch' => 'nullable|string',
            'items.*.batch_sloc' => 'required|string|max:10',
            'remarks' => 'nullable|string',
            'sap_credentials' => 'required|array',
            'sap_credentials.user' => 'required|string|min:1',
            'sap_credentials.passwd' => 'required|string|min:1',
            'sap_credentials.client' => 'nullable|string',
            'sap_credentials.lang' => 'nullable|string',
            'sap_credentials.ashost' => 'nullable|string',
            'sap_credentials.sysnr' => 'nullable|string'
        ]);
    }

    /**
     * Create new transfer record with duplicate handling
     */
    private function createNewTransferRecord($document, $plantSupply, $moveType, $transferNo, $validated, $sapResponse)
    {
        $totalQty = array_sum(array_column($validated['items'], 'quantity'));

        try {
            return ReservationTransfer::create([
                'document_id' => $document->id,
                'document_no' => $document->document_no,
                'transfer_no' => $transferNo,
                'plant_supply' => $plantSupply,
                'plant_destination' => $document->plant,
                'move_type' => $moveType,
                'total_items' => count($validated['items']),
                'total_qty' => $totalQty,
                'status' => $sapResponse['status'] ?? 'COMPLETED',
                'sap_message' => $sapResponse['message'] ?? 'Transfer created successfully',
                'remarks' => $validated['remarks'] ?? "Transfer from Document {$document->document_no}",
                'created_by' => Auth::user()->id,
                'created_by_name' => Auth::user()->name,
                'completed_at' => ($sapResponse['status'] ?? '') === 'COMPLETED' ? now() : null,
                'sap_response' => json_encode($sapResponse)
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate entry
            if ($e->errorInfo[1] == 1062) {
                Log::warning('Duplicate entry detected, trying to recover', [
                    'transfer_no' => $transferNo,
                    'error' => $e->getMessage()
                ]);

                // Try to find the existing transfer
                $existing = ReservationTransfer::where('transfer_no', $transferNo)->first();
                if ($existing) {
                    return $existing;
                }

                // If not found, generate new transfer number
                $newTransferNo = $this->generateUniqueTransferNo($transferNo);
                return $this->createNewTransferRecord($document, $plantSupply, $moveType, $newTransferNo, $validated, $sapResponse);
            }
            throw $e;
        }
    }

    /**
     * Validate each transfer item
     */
    private function validateTransferItem($document, $item, $index)
    {
        Log::info("Validating item {$index}", [
            'material_code' => $item['material_code'],
            'quantity' => $item['quantity'],
            'batch' => $item['batch'] ?? null,
            'batch_sloc' => $item['batch_sloc'] ?? null
        ]);

        // Find document item
        $docItem = ReservationDocumentItem::where('document_id', $document->id)
            ->where('material_code', $item['material_code'])
            ->first();

        if (!$docItem) {
            throw new \Exception("Item {$index}: Material {$item['material_code']} not found in document");
        }

        Log::info('Document item found:', [
            'item_id' => $docItem->id,
            'requested_qty' => $docItem->requested_qty,
            'transferred_qty' => $docItem->transferred_qty,
            'remaining_qty' => $docItem->remaining_qty,
            'force_completed' => $docItem->force_completed
        ]);

        // Check if force completed - TIDAK BOLEH TRANSFER
        if ($docItem->force_completed) {
            throw new \Exception("Item {$index}: Material {$item['material_code']} is force completed and cannot be transferred");
        }

        // Check if item is already completed (remaining_qty = 0) - TIDAK BOLEH TRANSFER
        if ($docItem->remaining_qty <= 0) {
            throw new \Exception("Item {$index}: Material {$item['material_code']} is already completed (remaining quantity: {$docItem->remaining_qty})");
        }

        // Check if document is closed - TIDAK BOLEH TRANSFER
        if ($document->status === 'closed') {
            throw new \Exception("Item {$index}: Document {$document->document_no} is closed and cannot be transferred");
        }

        // Check stock availability (PRIMARY VALIDATION)
        $batch = $item['batch'] ?? null;
        $batchSloc = $item['batch_sloc'] ?? null;

        if ($batch && $batchSloc) {
            // Remove SLOC: prefix if exists
            $storageLocation = str_replace('SLOC:', '', $batchSloc);

            Log::info('Checking stock availability:', [
                'document_no' => $document->document_no,
                'material_code' => $item['material_code'],
                'batch' => $batch,
                'storage_location' => $storageLocation,
                'requested_quantity' => $item['quantity']
            ]);

            $stock = ReservationStock::where('document_no', $document->document_no)
                ->where('matnr', $item['material_code'])
                ->where('charg', $batch)
                ->where('lgort', $storageLocation)
                ->first();

            if (!$stock) {
                throw new \Exception("Item {$index}: Stock not found for {$item['material_code']} in batch {$batch} at location {$storageLocation}");
            }

            $availableStock = is_numeric($stock->clabs) ? floatval($stock->clabs) : 0;

            Log::info('Stock details:', [
                'available_stock' => $availableStock,
                'clabs' => $stock->clabs,
                'meins' => $stock->meins
            ]);

            if ($availableStock < $item['quantity']) {
                throw new \Exception("Item {$index}: Insufficient stock for {$item['material_code']}. Available: {$availableStock}, Requested: {$item['quantity']}");
            }
        } else {
            throw new \Exception("Item {$index}: Batch and storage location are required for stock validation");
        }

        // Check if requested quantity exceeds remaining quantity - HANYA PERINGATAN, BUKAN ERROR
        if ($item['quantity'] > $docItem->remaining_qty) {
            Log::warning("Item {$index}: Requested quantity ({$item['quantity']}) exceeds remaining quantity ({$docItem->remaining_qty}) for {$item['material_code']}");
            // TIDAK throw exception, biarkan proses berlanjut
        }

        Log::info("Item {$index} validation passed", [
            'material_code' => $item['material_code'],
            'quantity' => $item['quantity'],
            'stock_available' => $availableStock ?? 'N/A'
        ]);
    }

    /**
     * Generate unique transfer number when duplicate occurs
     */
    private function generateUniqueTransferNo($baseTransferNo)
    {
        $counter = 1;
        $newTransferNo = $baseTransferNo;

        while (ReservationTransfer::where('transfer_no', $newTransferNo)->exists()) {
            $newTransferNo = $baseTransferNo . '_' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;

            if ($counter > 100) {
                throw new \Exception('Cannot generate unique transfer number after 100 attempts');
            }
        }

        Log::info('Generated unique transfer number', [
            'original' => $baseTransferNo,
            'new' => $newTransferNo
        ]);

        return $newTransferNo;
    }

    /**
     * Create transfer item
     */
    private function createTransferItem($transfer, $document, $itemData, $itemNumber)
    {
        // Find document item
        $docItem = ReservationDocumentItem::where('document_id', $document->id)
            ->where('material_code', $itemData['material_code'])
            ->first();

        if (!$docItem) {
            throw new \Exception("Document item not found for {$itemData['material_code']}");
        }

        // Parse batch_sloc for storage_location
        $batchSloc = $itemData['batch_sloc'] ?? '';
        if (strpos($batchSloc, 'SLOC:') === 0) {
            $batchSloc = substr($batchSloc, 5);
        }

        // Check if transfer item already exists
        $existingTransferItem = ReservationTransferItem::where('transfer_id', $transfer->id)
            ->where('document_item_id', $docItem->id)
            ->where('material_code', $itemData['material_code'])
            ->where('batch', $itemData['batch'] ?? null)
            ->first();

        if ($existingTransferItem) {
            Log::info('Transfer item already exists, updating quantity', [
                'transfer_item_id' => $existingTransferItem->id,
                'material_code' => $itemData['material_code']
            ]);

            $existingTransferItem->update([
                'quantity' => $existingTransferItem->quantity + $itemData['quantity'],
                'updated_at' => now()
            ]);

            return;
        }

        // Create transfer item
        ReservationTransferItem::create([
            'transfer_id' => $transfer->id,
            'document_item_id' => $docItem->id,
            'material_code' => $itemData['material_code'],
            'material_code_raw' => $itemData['material_code'],
            'material_description' => $itemData['material_desc'],
            'batch' => $itemData['batch'] ?? null,
            'storage_location' => $batchSloc,
            'plant_supply' => $transfer->plant_supply,
            'plant_destination' => $itemData['plant_tujuan'],
            'sloc_destination' => $itemData['sloc_tujuan'],
            'quantity' => (float) $itemData['quantity'],
            'unit' => $itemData['unit'],
            'item_number' => $itemNumber,
            'sap_status' => 'COMPLETED',
            'material_formatted' => ctype_digit($itemData['material_code']),
            'requested_qty' => $docItem->requested_qty,
            'available_stock' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Log::info('Transfer item created', [
            'material_code' => $itemData['material_code'],
            'quantity' => $itemData['quantity'],
            'batch' => $itemData['batch'] ?? 'N/A',
            'storage_location' => $batchSloc
        ]);
    }

    /**
     * Update document quantities
     */
    private function updateDocumentQuantities($document, $items)
    {
        $totalTransferred = 0;

        foreach ($items as $itemData) {
            $docItem = ReservationDocumentItem::where('document_id', $document->id)
                ->where('material_code', $itemData['material_code'])
                ->first();

            if ($docItem) {
                // Get current transferred quantity from database
                $currentTransferred = DB::table('reservation_transfer_items')
                    ->join('reservation_transfers', 'reservation_transfer_items.transfer_id', '=', 'reservation_transfers.id')
                    ->where('reservation_transfers.document_id', $document->id)
                    ->where('reservation_transfer_items.document_item_id', $docItem->id)
                    ->sum('reservation_transfer_items.quantity');

                $newTransferredQty = $currentTransferred;
                $newRemainingQty = max(0, $docItem->requested_qty - $newTransferredQty);

                $docItem->update([
                    'transferred_qty' => $newTransferredQty,
                    'remaining_qty' => $newRemainingQty,
                    'updated_at' => now()
                ]);

                $totalTransferred += $itemData['quantity'];

                Log::info('Document item updated', [
                    'material_code' => $itemData['material_code'],
                    'requested_qty' => $docItem->requested_qty,
                    'new_transferred_qty' => $newTransferredQty,
                    'new_remaining_qty' => $newRemainingQty
                ]);
            }
        }

        // Update document totals
        $totalRequested = ReservationDocumentItem::where('document_id', $document->id)
            ->sum('requested_qty');

        $completionRate = $totalRequested > 0 ? ($totalTransferred / $totalRequested) * 100 : 0;

        // Determine new status
        $newStatus = $document->status;
        if ($totalTransferred >= $totalRequested) {
            $newStatus = 'closed';
        } elseif ($totalTransferred > 0 && $document->status == 'booked') {
            $newStatus = 'partial';
        }

        $document->update([
            'total_transferred' => $totalTransferred,
            'completion_rate' => $completionRate,
            'status' => $newStatus,
            'updated_at' => now()
        ]);

        Log::info('Document quantities updated', [
            'document_id' => $document->id,
            'total_transferred' => $totalTransferred,
            'completion_rate' => round($completionRate, 2),
            'new_status' => $newStatus
        ]);
    }

    /**
     * CLEAN UP EXISTING DUPLICATE DATA - DIPERBAIKI
     */
    public function cleanupDuplicates()
    {
        DB::beginTransaction();

        try {
            $deletedCount = 0;
            $mergedCount = 0;

            // 1. Find and delete transfers with NULL document_id
            $nullTransfers = ReservationTransfer::whereNull('document_id')->get();

            foreach ($nullTransfers as $transfer) {
                ReservationTransferItem::where('transfer_id', $transfer->id)->delete();
                $transfer->delete();
                $deletedCount++;

                Log::info('Deleted transfer with NULL document_id', [
                    'id' => $transfer->id,
                    'transfer_no' => $transfer->transfer_no,
                    'document_no' => $transfer->document_no
                ]);
            }

            // 2. Find and merge transfers with _DUP suffix
            $dupTransfers = ReservationTransfer::where('transfer_no', 'like', '%_DUP%')->get();

            foreach ($dupTransfers as $transfer) {
                $cleanTransferNo = preg_replace('/_DUP\d+$/', '', $transfer->transfer_no);

                $cleanTransfer = ReservationTransfer::where('transfer_no', $cleanTransferNo)
                    ->where('document_id', $transfer->document_id)
                    ->first();

                if ($cleanTransfer && $cleanTransfer->id != $transfer->id) {
                    ReservationTransferItem::where('transfer_id', $transfer->id)
                        ->update(['transfer_id' => $cleanTransfer->id]);

                    $this->recalculateTransferTotals($cleanTransfer);

                    $transfer->delete();
                    $mergedCount++;

                    // PERBAIKAN: Mengubah $primaryTransfer menjadi $cleanTransfer
                    Log::info('Merged duplicate transfer', [
                        'dup_id' => $transfer->id,
                        'dup_transfer_no' => $transfer->transfer_no,
                        'clean_id' => $cleanTransfer->id,
                        'clean_transfer_no' => $cleanTransfer->transfer_no
                    ]);
                } elseif (!$cleanTransfer) {
                    $transfer->update(['transfer_no' => $cleanTransferNo]);
                    Log::info('Renamed transfer by removing _DUP suffix', [
                        'id' => $transfer->id,
                        'new_transfer_no' => $cleanTransferNo
                    ]);
                }
            }

            // 3. Fix move_type inconsistencies
            $incorrectMoveTypeCount = 0;
            $allTransfers = ReservationTransfer::whereNotNull('document_id')->get();

            foreach ($allTransfers as $transfer) {
                $document = ReservationDocument::find($transfer->document_id);
                if ($document) {
                    $correctMoveType = ($transfer->plant_supply == $document->plant) ? '311' : '301';

                    if ($transfer->move_type != $correctMoveType) {
                        $transfer->update(['move_type' => $correctMoveType]);
                        $incorrectMoveTypeCount++;

                        Log::info('Fixed move_type', [
                            'transfer_id' => $transfer->id,
                            'transfer_no' => $transfer->transfer_no,
                            'old_move_type' => $transfer->move_type,
                            'new_move_type' => $correctMoveType,
                            'plant_supply' => $transfer->plant_supply,
                            'plant_destination' => $document->plant
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cleanup completed successfully',
                'stats' => [
                    'deleted_null_transfers' => $deletedCount,
                    'merged_dup_transfers' => $mergedCount,
                    'fixed_move_type' => $incorrectMoveTypeCount,
                    'total_processed' => $deletedCount + $mergedCount + $incorrectMoveTypeCount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cleanup failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate transfer totals
     */
    private function recalculateTransferTotals($transfer)
    {
        $items = ReservationTransferItem::where('transfer_id', $transfer->id)->get();

        $transfer->update([
            'total_items' => $items->count(),
            'total_qty' => $items->sum('quantity'),
            'updated_at' => now()
        ]);
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 50);

            $query = ReservationTransfer::with(['items', 'document'])
                ->orderBy('created_at', 'desc');

            // Filter out incomplete data
            $query->whereNotNull('document_id')
                ->where('total_items', '>', 0)
                ->where('total_qty', '>', 0);

            // Apply search - PERBAIKAN: Pencarian multi-field
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('transfer_no', 'like', "%{$searchTerm}%")
                    ->orWhere('document_no', 'like', "%{$searchTerm}%")
                    ->orWhere('plant_supply', 'like', "%{$searchTerm}%")
                    ->orWhere('plant_destination', 'like', "%{$searchTerm}%")
                    ->orWhere('status', 'like', "%{$searchTerm}%")
                    ->orWhere('created_by_name', 'like', "%{$searchTerm}%")
                    ->orWhere('move_type', 'like', "%{$searchTerm}%");
                });
            }

            $transfers = $query->paginate($perPage);

            // Calculate statistics
            $stats = [
                'total' => $transfers->total(),
                'completed' => ReservationTransfer::where('status', 'COMPLETED')->count(),
                'failed' => ReservationTransfer::where('status', 'FAILED')->count(),
                'pending' => ReservationTransfer::whereIn('status', ['PENDING', 'PROCESSING'])->count(),
                'move_type_301' => ReservationTransfer::where('move_type', '301')->count(),
                'move_type_311' => ReservationTransfer::where('move_type', '311')->count()
            ];

            // If AJAX request, return JSON
            if ($request->ajax() || $request->has('_ajax')) {
                // Render views
                $tableHtml = view('transfers.partials.table', compact('transfers'))->render();
                $paginationHtml = view('transfers.partials.pagination', compact('transfers'))->render();

                return response()->json([
                    'success' => true,
                    'html' => $tableHtml,
                    'pagination' => $paginationHtml,
                    'total' => $transfers->total(),
                    'count' => $transfers->count(),
                    'message' => 'Data loaded successfully'
                ]);
            }

            // Return view for web
            return view('transfers.index', compact('transfers', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error fetching transfers: ' . $e->getMessage());

            if ($request->ajax() || $request->has('_ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error fetching transfers: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Error loading transfers: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $transfer = ReservationTransfer::with(['items', 'document'])->findOrFail($id);

            // Add remarks for API response
            $transfer->document_remarks = $transfer->document->remarks ?? '';
            $transfer->transfer_remarks = $transfer->remarks ?? '';

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
            Log::error('Error fetching transfer details: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching transfer details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transfer details with complete data (MENYERTAKAN DOCUMENT REMARKS)
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
                    // MENYERTAKAN FIELD REMARKS DARI DOCUMENT
                    $query->select(['id', 'document_no', 'plant', 'sloc_supply', 'status', 'remarks']);
                }
            ])->findOrFail($id);

            // Add document remarks to transfer object
            $transfer->document_remarks = $transfer->document->remarks ?? '';
            $transfer->transfer_remarks = $transfer->remarks ?? '';

            // Calculate totals
            $transfer->total_items = $transfer->items->count();
            $transfer->total_qty = $transfer->items->sum('quantity');

            // Format dates
            $transfer->created_at_formatted = Carbon::parse($transfer->created_at)
                ->setTimezone('Asia/Jakarta')
                ->format('d/m/Y H:i:s');

            $transfer->completed_at_formatted = $transfer->completed_at
                ? Carbon::parse($transfer->completed_at)
                    ->setTimezone('Asia/Jakarta')
                    ->format('d/m/Y H:i:s')
                : null;

            // Add move type description
            $transfer->move_type_description = $transfer->move_type == '301' ? 'Antar Plant' : 'Dalam Plant';

            return response()->json([
                'success' => true,
                'data' => $transfer,
                'message' => 'Transfer details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting detailed transfer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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

            // Add document remarks to each transfer
            foreach ($transfers as $transfer) {
                $transfer->document_remarks = $transfer->document->remarks ?? '';
                $transfer->transfer_remarks = $transfer->remarks ?? '';
            }

            return response()->json([
                'success' => true,
                'data' => $transfers,
                'message' => 'Transfers for document retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching transfers by document: ' . $e->getMessage());

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
                'transfer_no' => 'nullable|string',
                'move_type' => 'nullable|string|in:301,311',
                'remarks' => 'nullable|string' // Tambahkan field remarks untuk update
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

            if ($request->move_type) {
                $updateData['move_type'] = $request->move_type;
            }

            if ($request->has('remarks')) {
                $updateData['remarks'] = $request->remarks;
            }

            if ($request->status === 'COMPLETED') {
                $updateData['completed_at'] = now();
            }

            $transfer->update($updateData);

            Log::info('Transfer status updated', [
                'transfer_id' => $id,
                'new_status' => $request->status,
                'new_transfer_no' => $request->transfer_no ?? 'unchanged',
                'new_move_type' => $request->move_type ?? 'unchanged',
                'remarks_updated' => $request->has('remarks') ? 'Yes' : 'No'
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
            Log::error('Error updating transfer status: ' . $e->getMessage());

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

            ReservationTransferItem::where('transfer_id', $id)->delete();
            $transfer->delete();

            Log::info('Transfer deleted', [
                'transfer_id' => $id,
                'transfer_no' => $transfer->transfer_no,
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
            Log::error('Error deleting transfer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fix incomplete transfer data
     */
    public function fixTransferData($id)
    {
        try {
            $transfer = ReservationTransfer::findOrFail($id);

            if ($transfer->document_id) {
                $document = ReservationDocument::find($transfer->document_id);
                if ($document) {
                    $updates = [];

                    if (empty($transfer->plant_destination)) {
                        $updates['plant_destination'] = $document->plant;
                    }

                    if (empty($transfer->plant_supply) && !empty($document->sloc_supply)) {
                        $updates['plant_supply'] = $document->sloc_supply;
                    }

                    // Fix move_type
                    $correctMoveType = ($transfer->plant_supply == $document->plant) ? '311' : '301';
                    if ($transfer->move_type != $correctMoveType) {
                        $updates['move_type'] = $correctMoveType;
                    }

                    if ($transfer->total_items == 0) {
                        $updates['total_items'] = $transfer->items()->count();
                    }

                    if ($transfer->total_qty == 0) {
                        $updates['total_qty'] = $transfer->items()->sum('quantity');
                    }

                    if (!empty($updates)) {
                        $transfer->update($updates);

                        Log::info('Transfer data fixed', [
                            'transfer_id' => $id,
                            'updates' => $updates
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Transfer data fixed successfully',
                            'updates' => $updates
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'No fixes needed for this transfer'
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
     * Fix all transfer data with document_id null
     */
    public function fixAllTransferData()
    {
        try {
            DB::beginTransaction();

            $transfers = ReservationTransfer::whereNull('document_id')->get();
            $fixedCount = 0;
            $deletedCount = 0;

            foreach ($transfers as $transfer) {
                if ($transfer->document_no) {
                    $document = ReservationDocument::where('document_no', $transfer->document_no)->first();

                    if ($document) {
                        $transfer->document_id = $document->id;

                        if (empty($transfer->plant_destination)) {
                            $transfer->plant_destination = $document->plant;
                        }

                        if (empty($transfer->plant_supply) && !empty($document->sloc_supply)) {
                            $transfer->plant_supply = $document->sloc_supply;
                        }

                        // Fix move_type
                        $correctMoveType = ($transfer->plant_supply == $document->plant) ? '311' : '301';
                        $transfer->move_type = $correctMoveType;

                        $transfer->save();
                        $fixedCount++;

                        Log::info('Fixed transfer data', [
                            'transfer_id' => $transfer->id,
                            'document_id' => $document->id,
                            'plant_supply' => $transfer->plant_supply,
                            'plant_destination' => $transfer->plant_destination,
                            'move_type' => $transfer->move_type
                        ]);
                    } else {
                        ReservationTransferItem::where('transfer_id', $transfer->id)->delete();
                        $transfer->delete();
                        $deletedCount++;
                    }
                } else {
                    ReservationTransferItem::where('transfer_id', $transfer->id)->delete();
                    $transfer->delete();
                    $deletedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer data fixed successfully',
                'stats' => [
                    'fixed' => $fixedCount,
                    'deleted' => $deletedCount,
                    'total_processed' => $fixedCount + $deletedCount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fixing all transfer data: ' . $e->getMessage());

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

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('move_type')) {
                $query->where('move_type', $request->move_type);
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
            Log::error('Export error: ' . $e->getMessage());
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

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('move_type')) {
                $query->where('move_type', $request->move_type);
            }

            $transfers = $query->orderBy('created_at', 'desc')->get();

            $pdf = \PDF::loadView('transfers.export-pdf', compact('transfers'));

            return $pdf->download('transfers_export_' . date('Ymd_His') . '.pdf');

        } catch (\Exception $e) {
            Log::error('PDF export error: ' . $e->getMessage());
            return back()->with('error', 'PDF export failed');
        }
    }

    /**
     * Print transfer - DIPERBAIKI DENGAN MENAMBAHKAN REMARKS
     */
    public function print($id)
    {
        try {
            $transfer = ReservationTransfer::with(['items', 'document'])->findOrFail($id);

            // Calculate total quantity from items
            $totalQty = 0;
            if ($transfer->items && $transfer->items->count() > 0) {
                $totalQty = $transfer->items->sum('quantity');
            } elseif ($transfer->total_qty) {
                $totalQty = $transfer->total_qty;
            }

            $transfer->total_qty_calculated = $totalQty;

            // Tambahkan kedua remarks untuk print view
            $transfer->document_remarks = $transfer->document->remarks ?? '';
            $transfer->transfer_remarks = $transfer->remarks ?? '';

            // Format dates for view
            $transfer->created_at_formatted = $transfer->created_at
                ? \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y H:i:s')
                : 'N/A';

            $transfer->completed_at_formatted = $transfer->completed_at
                ? \Carbon\Carbon::parse($transfer->completed_at)->format('d/m/Y H:i:s')
                : 'Not completed';

            // Format material codes
            if ($transfer->items) {
                foreach ($transfer->items as $item) {
                    if (ctype_digit($item->material_code)) {
                        $item->material_code_formatted = ltrim($item->material_code, '0');
                    } else {
                        $item->material_code_formatted = $item->material_code;
                    }
                }
            }

            return view('transfers.print', compact('transfer'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Transfer not found');
        } catch (\Exception $e) {
            Log::error('Error printing transfer: ' . $e->getMessage());
            abort(500, 'Error generating printout');
        }
    }

    /**
     * Get transfer statistics
     */
    public function getTransferStats()
    {
        try {
            $totalTransfers = ReservationTransfer::count();
            $transfersWithIssues = ReservationTransfer::where(function($query) {
                $query->whereNull('document_id')
                    ->orWhere('plant_destination', '')
                    ->orWhereNull('plant_destination')
                    ->orWhere('plant_supply', '')
                    ->orWhereNull('plant_supply')
                    ->orWhere('total_items', 0)
                    ->orWhere('total_qty', 0);
            })->count();

            $transfersWithNullItems = ReservationTransferItem::whereNull('document_item_id')
                ->distinct('transfer_id')
                ->count();

            $moveTypeStats = [
                '301' => ReservationTransfer::where('move_type', '301')->count(),
                '311' => ReservationTransfer::where('move_type', '311')->count(),
                'null' => ReservationTransfer::whereNull('move_type')->count()
            ];

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_transfers' => $totalTransfers,
                    'transfers_with_issues' => $transfersWithIssues,
                    'transfers_with_null_items' => $transfersWithNullItems,
                    'move_type_stats' => $moveTypeStats,
                    'healthy_percentage' => $totalTransfers > 0
                        ? round((($totalTransfers - $transfersWithIssues) / $totalTransfers) * 100, 2)
                        : 100
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting transfer stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fix specific transfer with duplicate entry
     */
    public function fixDuplicateTransfer($transferNo)
    {
        try {
            DB::beginTransaction();

            // Find all transfers with this transfer_no
            $duplicates = ReservationTransfer::where('transfer_no', $transferNo)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($duplicates->count() <= 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'No duplicate found for transfer_no: ' . $transferNo
                ]);
            }

            // Keep the first one, merge others into it
            $primaryTransfer = $duplicates->first();
            $mergedCount = 0;

            foreach ($duplicates as $index => $duplicate) {
                if ($index === 0) continue; // Skip the first one

                // Move all items to primary transfer
                ReservationTransferItem::where('transfer_id', $duplicate->id)
                    ->update(['transfer_id' => $primaryTransfer->id]);

                // Delete the duplicate transfer
                $duplicate->delete();
                $mergedCount++;

                Log::info('Merged duplicate transfer', [
                    'primary_id' => $primaryTransfer->id,
                    'duplicate_id' => $duplicate->id,
                    'transfer_no' => $transferNo
                ]);
            }

            // Recalculate totals for primary transfer
            $this->recalculateTransferTotals($primaryTransfer);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fixed duplicate transfers for transfer_no: ' . $transferNo,
                'merged_count' => $mergedCount,
                'primary_transfer_id' => $primaryTransfer->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fixing duplicate transfer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if item can be transferred (for frontend validation)
     */
    public function checkItemTransferability($documentId, $materialCode)
    {
        try {
            $document = ReservationDocument::findOrFail($documentId);

            $item = ReservationDocumentItem::where('document_id', $documentId)
                ->where('material_code', $materialCode)
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found',
                    'transferable' => false
                ], 404);
            }

            // Check conditions
            $checks = [
                'force_completed' => $item->force_completed ? false : true,
                'remaining_quantity' => $item->remaining_qty > 0 ? true : false,
                'document_status' => $document->status !== 'closed' ? true : false,
                'has_stock' => true // This would need stock check
            ];

            $transferable = !$item->force_completed
                && $item->remaining_qty > 0
                && $document->status !== 'closed';

            return response()->json([
                'success' => true,
                'transferable' => $transferable,
                'checks' => $checks,
                'item' => [
                    'material_code' => $item->material_code,
                    'requested_qty' => $item->requested_qty,
                    'transferred_qty' => $item->transferred_qty,
                    'remaining_qty' => $item->remaining_qty,
                    'force_completed' => $item->force_completed
                ],
                'document' => [
                    'status' => $document->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking item transferability: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error checking item transferability: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transfer with full remarks data
     */
    public function getTransferWithRemarks($id)
    {
        try {
            $transfer = ReservationTransfer::with(['document'])->findOrFail($id);

            // Add remarks data
            $transfer->document_remarks = $transfer->document->remarks ?? '';
            $transfer->transfer_remarks = $transfer->remarks ?? '';

            return response()->json([
                'success' => true,
                'data' => $transfer,
                'message' => 'Transfer with remarks retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting transfer with remarks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
