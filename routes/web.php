<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReservationDocumentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TransferController;

// Redirect root to login page
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes (for guests only)
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Logout Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Require Authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile');

    // Reservation Routes
    Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/create', [ReservationController::class, 'create'])->name('create');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{id}', [ReservationController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ReservationController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ReservationController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReservationController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [ReservationController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/export/{type?}', [ReservationController::class, 'export'])->name('export');

        // Sync operations
        Route::post('/sync', [ReservationController::class, 'sync'])->name('sync');
        Route::post('/sync-from-sap', [ReservationController::class, 'syncFromSAP'])->name('sync.from-sap');
        Route::post('/clear-and-create', [ReservationController::class, 'clearAndCreate'])->name('clear-and-create');
        Route::post('/clear-all-sync-data', [ReservationController::class, 'clearAllSyncData'])->name('clearAllSyncData'); // DITAMBAHKAN

        // Sync status checking
        Route::get('/check-sync-data', [ReservationController::class, 'checkSyncData'])->name('check-sync-data');
        Route::get('/check-sync-status', [ReservationController::class, 'checkSyncStatus'])->name('check-sync-status');

        // Search and autocomplete
        Route::get('/search/autocomplete', [ReservationController::class, 'autocomplete'])->name('autocomplete');

        // Service checking
        Route::get('/check-flask', [ReservationController::class, 'checkFlaskService'])->name('checkFlask');

        // AJAX endpoints
        Route::post('/get-material-types', [ReservationController::class, 'getMaterialTypes'])->name('getMaterialTypes');
        Route::post('/get-materials-by-type', [ReservationController::class, 'getMaterialsByType'])->name('getMaterialsByType');
        Route::post('/get-pro-numbers', [ReservationController::class, 'getProNumbers'])->name('getProNumbers');
        Route::post('/get-pro-numbers-for-materials', [ReservationController::class, 'getProNumbersForMaterials'])->name('getProNumbersForMaterials');
        Route::post('/load-multiple-pro', [ReservationController::class, 'loadMultiplePro'])->name('loadMultiplePro');
        Route::post('/create-document', [ReservationController::class, 'createDocument'])->name('createDocument');
    });

    // Document Routes - Hanya routes yang benar-benar ada
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [ReservationDocumentController::class, 'index'])->name('index');
        Route::get('/{id}', [ReservationDocumentController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ReservationDocumentController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ReservationDocumentController::class, 'update'])->name('update');
        Route::get('/{id}/print', [ReservationDocumentController::class, 'print'])->name('print');
        Route::get('/{id}/pdf', [ReservationDocumentController::class, 'pdf'])->name('pdf');

        // Transfer from document - HANYA SATU ROUTE
        Route::post('/{id}/create-transfer', [TransferController::class, 'createTransfer'])->name('create-transfer');
    });

    // Stock Routes
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/document/{documentNo}', [StockController::class, 'getStockByDocument'])
            ->name('by-document');
        Route::delete('/document/{documentNo}/clear', [StockController::class, 'clearStockCache'])
            ->name('clear-cache');
        Route::post('/document/{documentNo}/fetch', [StockController::class, 'fetchStock'])
            ->name('fetch');
    });

    // Transfer Routes (standalone) - HANYA SATU SET ROUTES
    Route::prefix('transfers')->name('transfers.')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/{id}', [TransferController::class, 'show'])->name('show');
        Route::put('/{id}/status', [TransferController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}', [TransferController::class, 'destroy'])->name('destroy');
    });

    // Single route untuk create transfer (bisa dari mana saja)
    Route::post('/transfer/create', [TransferController::class, 'createTransfer'])->name('transfer.create');
});
