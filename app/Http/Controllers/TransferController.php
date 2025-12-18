<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ReservationDocument;
use App\Models\Transfer;
use App\Models\TransferItem;

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

            // Validasi input - disederhanakan dan sesuai dengan data frontend
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
                'items.*.batch_sloc' => 'nullable|string',
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
            $slocSupply = $validated['sloc_supply'] ?? $document->sloc_supply;
            $remarks = $validated['remarks'] ?? "Transfer from Document {$documentNo}";
            $headerText = $validated['header_text'] ?? "Transfer from Document {$documentNo}";

            // Prepare data for Python service
            $transferData = [
                'transfer_info' => [
                    'document_no' => $documentNo,
                    'plant_supply' => $slocSupply,
                    'move_type' => '311',
                    'posting_date' => now()->format('Ymd'),
                    'header_text' => $headerText,
                    'created_by' => $user->name,
                    'created_at' => now()->format('Y-m-d H:i:s')
                ],
                'items' => []
            ];

            // Map items to SAP RFC format
            foreach ($validated['items'] as $item) {
                // Parse batch_sloc - format: "SLOC:XXXX" or just "XXXX"
                $batchSloc = $item['batch_sloc'] ?? '';
                if ($batchSloc && strpos($batchSloc, 'SLOC:') === 0) {
                    $batchSloc = substr($batchSloc, 5); // Remove 'SLOC:'
                }

                $transferData['items'][] = [
                    'material_code' => $item['material_code'],
                    'material_desc' => $item['material_desc'],
                    'quantity' => (float) $item['quantity'],
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
                'user' => $user->name,
                'sap_host' => $sapCredentials['ashost'] ?? 'N/A',
                'sap_client' => $sapCredentials['client'] ?? 'N/A'
            ]);

            Log::debug('Transfer data details:', [
                'transfer_info' => $transferData['transfer_info'],
                'first_item' => $transferData['items'][0] ?? 'No items'
            ]);

            $response = Http::timeout(120)->post("{$pythonServiceUrl}/api/sap/transfer", [
                'transfer_data' => $transferData,
                'sap_credentials' => $sapCredentials,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Python service response:', $result);

                if (isset($result['success']) && $result['success']) {
                    // Save transfer record to database
                    $transfer = Transfer::create([
                        'document_id' => $document->id,
                        'document_no' => $documentNo,
                        'transfer_no' => $result['transfer_no'] ?? 'PENDING',
                        'plant_supply' => $slocSupply,
                        'plant_destination' => $plant,
                        'move_type' => '311',
                        'total_items' => count($transferData['items']),
                        'total_quantity' => array_sum(array_column($transferData['items'], 'quantity')),
                        'status' => $result['status'] ?? 'SUBMITTED',
                        'sap_message' => $result['message'] ?? '',
                        'remarks' => $remarks,
                        'created_by' => $user->id,
                        'created_by_name' => $user->name,
                        'completed_at' => $result['status'] === 'COMPLETED' ? now() : null,
                        'sap_response' => json_encode($result)
                    ]);

                    // Save transfer items
                    foreach ($validated['items'] as $index => $item) {
                        TransferItem::create([
                            'transfer_id' => $transfer->id,
                            'material_code' => $item['material_code'],
                            'material_description' => $item['material_desc'],
                            'batch' => $item['batch'] ?? '',
                            'batch_sloc' => $item['batch_sloc'] ?? '',
                            'quantity' => (float) $item['quantity'],
                            'unit' => $item['unit'],
                            'plant_supply' => $slocSupply,
                            'plant_destination' => $item['plant_tujuan'],
                            'sloc_destination' => $item['sloc_tujuan'],
                            'requested_qty' => (float) ($item['requested_qty'] ?? 0),
                            'available_stock' => (float) ($item['available_stock'] ?? 0),
                            'sap_status' => $result['item_results'][$index]['status'] ?? 'SUBMITTED',
                            'sap_message' => $result['item_results'][$index]['message'] ?? '',
                            'item_number' => $index + 1
                        ]);
                    }

                    // Log success
                    Log::info('Transfer created successfully', [
                        'transfer_id' => $transfer->id,
                        'transfer_no' => $result['transfer_no'] ?? 'PENDING',
                        'document_no' => $documentNo,
                        'status' => $result['status'] ?? 'SUBMITTED'
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Transfer document created successfully',
                        'transfer_no' => $result['transfer_no'] ?? 'PENDING',
                        'transfer_id' => $transfer->id,
                        'status' => $result['status'] ?? 'SUBMITTED',
                        'data' => $result
                    ]);
                } else {
                    // Log SAP transfer failure
                    Log::error('SAP transfer failed', [
                        'document_no' => $documentNo,
                        'errors' => $result['errors'] ?? [],
                        'message' => $result['message'] ?? 'Unknown error',
                        'error_type' => $result['error_type'] ?? 'UNKNOWN'
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'SAP transfer failed: ' . ($result['message'] ?? 'Unknown error'),
                        'errors' => $result['errors'] ?? [],
                        'error_type' => $result['error_type'] ?? 'UNKNOWN'
                    ], 400);
                }
            } else {
                // Log connection failure
                $errorBody = $response->body();
                $errorStatus = $response->status();

                Log::error('Failed to connect to Python service', [
                    'status' => $errorStatus,
                    'body' => $errorBody,
                    'document_no' => $documentNo,
                    'python_url' => $pythonServiceUrl
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to SAP service. HTTP Status: ' . $errorStatus,
                    'error_details' => $errorBody
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation error
            Log::error('Transfer validation error', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['sap_credentials.passwd']) // Exclude password for security
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Document not found', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Transfer creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'document_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'System error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SAP credentials for user
     * Priority: 1. Request credentials, 2. Environment variables
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
        try {
            $perPage = $request->get('per_page', 20);

            $transfers = Transfer::with(['items', 'document'])
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
     * Get transfer details
     */
    public function show($id)
    {
        try {
            $transfer = Transfer::with(['items', 'document', 'creator'])->findOrFail($id);

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
            $transfers = Transfer::with(['items'])
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

            $transfer = Transfer::findOrFail($id);

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
     * Delete a transfer (with items)
     */
    public function destroy($id)
    {
        try {
            $transfer = Transfer::findOrFail($id);

            // Delete transfer items first
            TransferItem::where('transfer_id', $id)->delete();

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
