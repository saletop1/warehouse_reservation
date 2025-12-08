<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Ambil data stats langsung dari database dengan tabel sap_reservations
        $stats = [
            'total_reservations' => DB::table('sap_reservations')->count(),
            'total_documents' => DB::table('reservation_documents')->count(),
            'total_materials' => DB::table('sap_reservations')->distinct('matnr')->count('matnr'),
            'total_qty' => DB::table('sap_reservations')->sum('psmng'),
            'last_sync' => DB::table('sap_reservations')->orderBy('sync_date', 'desc')->value('sync_date'),
        ];

        return view('dashboard', compact('stats'));
    }
}
