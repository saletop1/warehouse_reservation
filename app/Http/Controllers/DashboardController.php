<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ReservationDocument;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Ambil data stats langsung dari database
        $stats = [
            'total_reservations' => DB::table('sap_reservations')->count(),
            'total_documents' => ReservationDocument::count(),
            'total_materials' => DB::table('sap_reservations')->distinct('matnr')->count('matnr'),
            'total_qty' => DB::table('sap_reservations')->sum('psmng'),
            'last_sync' => DB::table('sap_reservations')->orderBy('sync_date', 'desc')->value('sync_date'),
        ];

        // Get document statistics by status
        $documentStats = [
            'booked' => ReservationDocument::where('status', 'booked')->count(),
            'partial' => ReservationDocument::where('status', 'partial')->count(),
            'closed' => ReservationDocument::where('status', 'closed')->count(),
            'cancelled' => ReservationDocument::where('status', 'cancelled')->count(),
        ];

        // Get today's statistics
        $todayStats = [
            'documents_created' => ReservationDocument::whereDate('created_at', today())->count(),
            'documents_closed' => ReservationDocument::whereDate('updated_at', today())
                ->where('status', 'closed')
                ->count(),
            'transfers_created' => DB::table('reservation_transfers')
                ->whereDate('created_at', today())
                ->count(),
        ];

        // Get recent transfers
        $recentTransfers = DB::table('reservation_transfers')
            ->select('transfer_no', 'document_no', 'total_qty', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact('stats', 'documentStats', 'todayStats', 'recentTransfers'));
    }
}
