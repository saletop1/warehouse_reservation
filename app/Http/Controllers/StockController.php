<?php
// app/Http\Controllers/StockController.php

namespace App\Http\Controllers;

use App\Models\ReservationDocument;
use App\Models\ReservationDocumentItem;
use App\Models\ReservationStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get stock data untuk document reservation tertentu
     */
    public function getStockByDocument(Request $request, $documentNo)
    {
        try {
            // Cari document
            $document = ReservationDocument::where('document_no', $documentNo)->firstOrFail();

            // Cek apakah sudah ada data stock untuk document ini
            $existingStocks = ReservationStock::where('document_no', $documentNo)->count();

            if ($existingStocks > 0 && !$request->has('refresh')) {
                // Return existing data - hanya yang memiliki SLOC (lgort tidak kosong)
                $stocks = ReservationStock::where('document_no', $documentNo)
                    ->whereNotNull('lgort') // Hanya yang memiliki SLOC
                    ->where('lgort', '!=', '') // Pastikan bukan string kosong
                    ->orderBy('matnr')
                    ->orderBy('lgort')
                    ->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Stock data retrieved from cache (filtered to show only with SLOC)',
                    'document' => $document,
                    'stocks' => $stocks,
                    'total_stocks' => $stocks->count(),
                    'is_cached' => true
                ]);
            }

            // Jika refresh atau belum ada data, panggil SAP RFC
            return $this->fetchStockFromSAP($document);

        } catch (\Exception $e) {
            Log::error('Error getting stock data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch stock data dari SAP RFC
     */
    private function fetchStockFromSAP($document)
    {
        $startTime = microtime(true);

        try {
            // Ambil semua material dari document items
            $items = ReservationDocumentItem::where('document_id', $document->id)->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items found in document'
                ], 404);
            }

            // Ekstrak material codes yang unik
            $materialCodes = $items->pluck('material_code')->unique()->toArray();

            Log::info('Fetching stock for ' . count($materialCodes) . ' materials from document: ' . $document->document_no);

            // Hapus data stock lama untuk document ini
            ReservationStock::where('document_no', $document->document_no)->delete();

            $totalStocks = 0;
            $errors = [];

            // Panggil SAP service untuk setiap material
            foreach ($materialCodes as $matnr) {
                try {
                    $stockData = $this->callSAPStockRFC($document->plant, $matnr);

                    if (!empty($stockData)) {
                        // Filter data stock: hanya simpan yang memiliki SLOC (LGORT tidak kosong)
                        $filteredStockData = array_filter($stockData, function($stock) {
                            return !empty($stock['LGORT']) && trim($stock['LGORT']) !== '';
                        });

                        if (!empty($filteredStockData)) {
                            $saved = $this->saveStockData($document->document_no, $filteredStockData);
                            $totalStocks += $saved;
                            Log::info("Saved $saved stock records for material $matnr (after SLOC filter)");
                        } else {
                            $errors[] = "No stock data with SLOC for material $matnr";
                            Log::warning("No stock data with SLOC returned for material $matnr");
                        }
                    } else {
                        $errors[] = "No stock data for material $matnr";
                        Log::warning("No stock data returned for material $matnr");
                    }

                    // Delay kecil antara panggilan RFC untuk menghindari overload
                    usleep(100000); // 100ms

                } catch (\Exception $e) {
                    $errors[] = "Error for material $matnr: " . $e->getMessage();
                    Log::error("Error fetching stock for material $matnr: " . $e->getMessage());
                    continue;
                }
            }

            $processingTime = round(microtime(true) - $startTime, 2);

            // Ambil semua data stock yang berhasil disimpan (hanya yang memiliki SLOC)
            $stocks = ReservationStock::where('document_no', $document->document_no)
                ->whereNotNull('lgort') // Hanya yang memiliki SLOC
                ->where('lgort', '!=', '') // Pastikan bukan string kosong
                ->orderBy('matnr')
                ->orderBy('lgort')
                ->get();

            $summary = $this->generateStockSummary($stocks);

            return response()->json([
                'success' => true,
                'message' => 'Stock data fetched successfully (filtered to show only with SLOC)',
                'document' => $document,
                'stocks' => $stocks,
                'summary' => $summary,
                'statistics' => [
                    'total_materials' => count($materialCodes),
                    'total_stock_records' => $totalStocks,
                    'processing_time' => $processingTime . ' seconds',
                    'errors_count' => count($errors),
                    'errors' => $errors
                ],
                'is_cached' => false
            ]);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Panggil SAP RFC untuk mendapatkan data stock
     */
    private function callSAPStockRFC($plant, $matnr)
    {
        $sapServiceUrl = env('SAP_SERVICE_URL', 'http://localhost:5000');

        try {
            $response = Http::timeout(120)->post($sapServiceUrl . '/api/sap/stock', [
                'plant' => $plant,
                'matnr' => $matnr
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] && isset($data['data'])) {
                    return $data['data'];
                } else {
                    Log::error('SAP stock API returned error: ' . ($data['message'] ?? 'Unknown error'));
                    return [];
                }
            } else {
                Log::error('SAP stock API call failed: ' . $response->status() . ' - ' . $response->body());
                return [];
            }

        } catch (\Exception $e) {
            Log::error('Exception calling SAP stock API: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Simpan data stock ke database
     */
    private function saveStockData($documentNo, $stockData)
    {
        $savedCount = 0;

        foreach ($stockData as $stock) {
            try {
                // Pastikan LGORT tidak kosong (sudah difilter sebelumnya, tapi double-check)
                if (empty($stock['LGORT']) || trim($stock['LGORT']) === '') {
                    continue;
                }

                // Pastikan CLABS adalah numeric
                $clabsValue = isset($stock['CLABS']) ? (is_numeric($stock['CLABS']) ? floatval($stock['CLABS']) : 0) : 0;

                ReservationStock::create([
                    'document_no' => $documentNo,
                    'matnr' => $stock['MATNR'] ?? '',
                    'mtbez' => $stock['MTBEZ'] ?? '',
                    'maktx' => $stock['MAKTX'] ?? '',
                    'werk' => $stock['WERK'] ?? '',
                    'lgort' => $stock['LGORT'] ?? '',
                    'charg' => $stock['CHARG'] ?? '',
                    'clabs' => $clabsValue,
                    'meins' => $stock['MEINS'] ?? '',
                    'vbeln' => $stock['VBELN'] ?? '',
                    'posnr' => $stock['POSNR'] ?? '',
                    'stock_date' => now(),
                    'sync_at' => now(),
                    'sync_by' => auth()->id()
                ]);

                $savedCount++;

            } catch (\Exception $e) {
                Log::error('Error saving stock record: ' . $e->getMessage());
                continue;
            }
        }

        return $savedCount;
    }

    /**
     * Generate summary dari data stock
     */
    private function generateStockSummary($stocks)
    {
        // Filter hanya stock yang memiliki SLOC (seharusnya sudah difilter, tapi double-check)
        $filteredStocks = $stocks->filter(function($stock) {
            return !empty($stock->lgort) && trim($stock->lgort) !== '';
        });

        // Hitung total_quantity dengan aman
        $totalQuantity = 0;
        foreach ($filteredStocks as $stock) {
            $totalQuantity += is_numeric($stock->clabs) ? floatval($stock->clabs) : 0;
        }

        $summary = [
            'total_materials' => $filteredStocks->groupBy('matnr')->count(),
            'total_storage_locations' => $filteredStocks->groupBy('lgort')->count(),
            'total_quantity' => $totalQuantity,
            'materials' => []
        ];

        // Group by material
        $groupedByMaterial = $filteredStocks->groupBy('matnr');

        foreach ($groupedByMaterial as $matnr => $materialStocks) {
            $materialQty = 0;
            foreach ($materialStocks as $stock) {
                $materialQty += is_numeric($stock->clabs) ? floatval($stock->clabs) : 0;
            }

            $storageLocations = $materialStocks->groupBy('lgort')->count();

            $summary['materials'][] = [
                'matnr' => $matnr,
                'maktx' => $materialStocks->first()->maktx ?? '',
                'total_quantity' => $materialQty,
                'storage_locations' => $storageLocations,
                'batches' => $materialStocks->whereNotNull('charg')->groupBy('charg')->count(),
                'details' => $materialStocks->map(function($stock) {
                    return [
                        'lgort' => $stock->lgort,
                        'charg' => $stock->charg,
                        'clabs' => is_numeric($stock->clabs) ? floatval($stock->clabs) : 0,
                        'meins' => $stock->meins,
                        'vbeln' => $stock->vbeln
                    ];
                })->toArray()
            ];
        }

        return $summary;
    }

    /**
     * Clear stock cache untuk document tertentu
     */
    public function clearStockCache($documentNo)
    {
        try {
            $deleted = ReservationStock::where('document_no', $documentNo)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Stock cache cleared successfully',
                'deleted_records' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear stock cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock summary untuk document
     */
    public function getStockSummary($documentNo)
    {
        try {
            // Hanya ambil stock yang memiliki SLOC
            $stocks = ReservationStock::where('document_no', $documentNo)
                ->whereNotNull('lgort')
                ->where('lgort', '!=', '')
                ->get();

            if ($stocks->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No stock data with SLOC found for document'
                ], 404);
            }

            $summary = $this->generateStockSummary($stocks);

            return response()->json([
                'success' => true,
                'document_no' => $documentNo,
                'summary' => $summary,
                'total_records' => $stocks->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch stock data dari SAP (untuk digunakan dari show document)
     */
    public function fetchStock(Request $request, $documentNo)
    {
        try {
            $document = ReservationDocument::where('document_no', $documentNo)->firstOrFail();

            // Ambil plant dari request atau gunakan document plant
            $plant = $request->get('plant', $document->plant);

            // Ambil semua material dari document items
            $items = ReservationDocumentItem::where('document_id', $document->id)->get();

            if ($items->isEmpty()) {
                return redirect()->route('documents.show', $document->id)
                    ->with('error', 'No items found in document');
            }

            // Hapus data stock lama untuk plant yang dipilih
            ReservationStock::where('document_no', $documentNo)
                ->where('werk', $plant)
                ->delete();

            // Ekstrak material codes yang unik
            $materialCodes = $items->pluck('material_code')->unique()->toArray();

            Log::info('Fetching stock for ' . count($materialCodes) . ' materials from document: ' . $document->document_no . ' for plant: ' . $plant);

            $totalStocks = 0;
            $errors = [];

            // Panggil SAP service untuk setiap material
            foreach ($materialCodes as $matnr) {
                try {
                    $stockData = $this->callSAPStockRFC($plant, $matnr);

                    if (!empty($stockData)) {
                        // Filter data stock: hanya simpan yang memiliki SLOC (LGORT tidak kosong)
                        $filteredStockData = array_filter($stockData, function($stock) {
                            return !empty($stock['LGORT']) && trim($stock['LGORT']) !== '';
                        });

                        if (!empty($filteredStockData)) {
                            $saved = $this->saveStockDataForPlant($document->document_no, $plant, $filteredStockData);
                            $totalStocks += $saved;
                            Log::info("Saved $saved stock records for material $matnr in plant $plant (after SLOC filter)");
                        } else {
                            $errors[] = "No stock data with SLOC for material $matnr in plant $plant";
                            Log::warning("No stock data with SLOC returned for material $matnr in plant $plant");
                        }
                    } else {
                        $errors[] = "No stock data for material $matnr in plant $plant";
                        Log::warning("No stock data returned for material $matnr in plant $plant");
                    }

                    // Delay kecil antara panggilan RFC untuk menghindari overload
                    usleep(100000); // 100ms

                } catch (\Exception $e) {
                    $errors[] = "Error for material $matnr in plant $plant: " . $e->getMessage();
                    Log::error("Error fetching stock for material $matnr in plant $plant: " . $e->getMessage());
                    continue;
                }
            }

            if ($totalStocks > 0) {
                return redirect()->route('documents.show', $document->id)
                    ->with('success', 'Successfully fetched ' . $totalStocks . ' stock records (with SLOC) from SAP for plant ' . $plant);
            } else {
                return redirect()->route('documents.show', $document->id)
                    ->with('error', 'No stock data with SLOC found for any materials in plant ' . $plant . '. Please check SAP connection.');
            }

        } catch (\Exception $e) {
            Log::error('Error in fetchStock: ' . $e->getMessage());
            return redirect()->route('documents.show', $document->id ?? 1)
                ->with('error', 'Failed to fetch stock data: ' . $e->getMessage());
        }
    }

    /**
     * Simpan data stock untuk plant tertentu
     */
    private function saveStockDataForPlant($documentNo, $plant, $stockData)
    {
        $savedCount = 0;

        foreach ($stockData as $stock) {
            try {
                // Pastikan LGORT tidak kosong (sudah difilter sebelumnya, tapi double-check)
                if (empty($stock['LGORT']) || trim($stock['LGORT']) === '') {
                    continue;
                }

                // Pastikan CLABS adalah numeric
                $clabsValue = isset($stock['CLABS']) ? (is_numeric($stock['CLABS']) ? floatval($stock['CLABS']) : 0) : 0;

                ReservationStock::create([
                    'document_no' => $documentNo,
                    'matnr' => $stock['MATNR'] ?? '',
                    'mtbez' => $stock['MTBEZ'] ?? '',
                    'maktx' => $stock['MAKTX'] ?? '',
                    'werk' => $plant,
                    'lgort' => $stock['LGORT'] ?? '',
                    'charg' => $stock['CHARG'] ?? '',
                    'clabs' => $clabsValue,
                    'meins' => $stock['MEINS'] ?? '',
                    'vbeln' => $stock['VBELN'] ?? '',
                    'posnr' => $stock['POSNR'] ?? '',
                    'stock_date' => now(),
                    'sync_at' => now(),
                    'sync_by' => auth()->id()
                ]);

                $savedCount++;

            } catch (\Exception $e) {
                Log::error('Error saving stock record: ' . $e->getMessage());
                continue;
            }
        }

        return $savedCount;
    }

    /**
     * Export stock data ke Excel
     */
    public function exportStock($documentNo)
    {
        try {
            $document = ReservationDocument::where('document_no', $documentNo)->firstOrFail();
            
            // Hanya ambil stock yang memiliki SLOC
            $stocks = ReservationStock::where('document_no', $documentNo)
                ->whereNotNull('lgort')
                ->where('lgort', '!=', '')
                ->get();

            if ($stocks->isEmpty()) {
                return redirect()->back()->with('error', 'No stock data with SLOC found for export');
            }

            // Create CSV
            $filename = 'stock_data_' . $documentNo . '_' . date('Ymd_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($document, $stocks) {
                $file = fopen('php://output', 'w');

                // Header
                fputcsv($file, [
                    'Document No', 'Material Code', 'Material Description', 'Material Type',
                    'Plant', 'Storage Location', 'Batch', 'Available Stock', 'UoM',
                    'Sales Document', 'Sales Item', 'Stock Date'
                ]);

                // Data
                foreach ($stocks as $stock) {
                    fputcsv($file, [
                        $stock->document_no,
                        $stock->matnr,
                        $stock->maktx,
                        $stock->mtbez,
                        $stock->werk,
                        $stock->lgort,
                        $stock->charg,
                        $stock->clabs,
                        $stock->meins,
                        $stock->vbeln,
                        $stock->posnr,
                        $stock->stock_date ? $stock->stock_date->format('Y-m-d H:i:s') : ''
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting stock: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export stock data');
        }
    }
}