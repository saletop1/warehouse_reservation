<?php

namespace App\Http\Controllers;

use App\Models\ReservationDocument;
use App\Models\ReservationTransfer;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Document statistics
            $documentStats = $this->getDocumentStats();

            // Today's statistics
            $todayStats = $this->getTodayStats();

            // Recent transfers
            $recentTransfers = ReservationTransfer::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Recent documents (without user relation)
            $recentDocuments = ReservationDocument::orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Calculate completion rate for each document
            foreach ($recentDocuments as $doc) {
                $totalRequested = $doc->items()->sum('requested_qty');
                $totalTransferred = $doc->items()->sum('transferred_qty');
                $doc->completion_rate_calculated = $totalRequested > 0 ?
                    round(($totalTransferred / $totalRequested) * 100, 1) : 0;
                $doc->items_count = $doc->items()->count();
                $doc->transfers_count = $doc->transfers()->count();
            }

            return view('dashboard', compact(
                'documentStats',
                'todayStats',
                'recentTransfers',
                'recentDocuments'
            ));

        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());

            return view('dashboard', [
                'documentStats' => [
                    'booked' => 0,
                    'partial' => 0,
                    'closed' => 0,
                    'cancelled' => 0,
                ],
                'todayStats' => [
                    'documents_created' => 0,
                    'transfers_created' => 0,
                    'documents_closed' => 0,
                    'total' => 0,
                ],
                'recentTransfers' => collect(),
                'recentDocuments' => collect(),
            ]);
        }
    }

    private function getDocumentStats()
    {
        return [
            'booked' => ReservationDocument::where('status', 'booked')->count(),
            'partial' => ReservationDocument::where('status', 'partial')->count(),
            'closed' => ReservationDocument::where('status', 'closed')->count(),
            'cancelled' => ReservationDocument::where('status', 'cancelled')->count(),
        ];
    }

    private function getTodayStats()
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        return [
            'documents_created' => ReservationDocument::whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'transfers_created' => ReservationTransfer::whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'documents_closed' => ReservationDocument::where('status', 'closed')
                ->whereBetween('updated_at', [$todayStart, $todayEnd])
                ->count(),
            'total' => ReservationDocument::whereBetween('created_at', [$todayStart, $todayEnd])->count() +
                      ReservationTransfer::whereBetween('created_at', [$todayStart, $todayEnd])->count(),
        ];
    }

    public function getStats()
    {
        try {
            $documentStats = $this->getDocumentStats();
            $todayStats = $this->getTodayStats();

            return response()->json([
                'success' => true,
                'booked' => $documentStats['booked'],
                'partial' => $documentStats['partial'],
                'closed' => $documentStats['closed'],
                'cancelled' => $documentStats['cancelled'],
                'today_docs' => $todayStats['documents_created'],
                'today_transfers' => $todayStats['transfers_created'],
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard stats',
            ], 500);
        }
    }
}
