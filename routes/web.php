<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReservationDocumentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;

// Redirect root to login page
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes (for guests only)
Route::middleware(['guest'])->group(function () {
    // Login Routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Registration Routes
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Password reset routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Logout Route (available for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Email verification routes
Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});

// Protected Routes (Require Authentication)
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboard API Routes
    Route::prefix('api/dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
    });

    // Profile Routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::post('/update-password', [ProfileController::class, 'updatePassword'])->name('update.password');
        Route::delete('/delete-account', [ProfileController::class, 'deleteAccount'])->name('delete');
    });

    // Juga tambahkan alias untuk 'profile' (tanpa .index) jika diperlukan
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

    // Reservation Routes
    Route::prefix('reservations')->name('reservations.')->group(function () {
        // CRUD Operations
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/create', [ReservationController::class, 'create'])->name('create');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{id}', [ReservationController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ReservationController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ReservationController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReservationController::class, 'destroy'])->name('destroy');

        // Bulk Operations
        Route::post('/bulk-delete', [ReservationController::class, 'bulkDelete'])->name('bulk-delete');

        // Export
        Route::get('/export/{type?}', [ReservationController::class, 'export'])->name('export');

        // Sync Operations
        Route::post('/sync', [ReservationController::class, 'sync'])->name('sync');
        Route::post('/sync-from-sap', [ReservationController::class, 'syncFromSAP'])->name('sync.from-sap');
        Route::post('/clear-all-sync-data', [ReservationController::class, 'clearAllSyncData'])->name('clearAllSyncData');

        // Data Management
        Route::post('/clear-and-create', [ReservationController::class, 'clearAndCreate'])->name('clear-and-create');

        // Status & Health Checks
        Route::get('/check-sync-data', [ReservationController::class, 'checkSyncData'])->name('check-sync-data');
        Route::get('/check-sync-status', [ReservationController::class, 'checkSyncStatus'])->name('check-sync-status');
        Route::get('/check-flask', [ReservationController::class, 'checkFlaskService'])->name('checkFlask');
        Route::get('/test-flask-connection', [ReservationController::class, 'testFlaskConnection'])->name('testFlaskConnection');

        // Search and Autocomplete
        Route::get('/search', [ReservationController::class, 'search'])->name('search');
        Route::get('/search/autocomplete', [ReservationController::class, 'autocomplete'])->name('autocomplete');
        Route::post('/get-plants', [ReservationController::class, 'getPlants'])->name('getPlants');
        Route::get('/get-statistics', [ReservationController::class, 'getStatistics'])->name('getStatistics');
        Route::get('/debug-sync-data', [ReservationController::class, 'debugSyncData'])->name('debugSyncData');

        // AJAX Endpoints (Document Creation Workflow)
        Route::post('/get-material-types', [ReservationController::class, 'getMaterialTypes'])->name('getMaterialTypes');
        Route::post('/get-materials-by-type', [ReservationController::class, 'getMaterialsByType'])->name('getMaterialsByType');
        Route::post('/get-pro-numbers', [ReservationController::class, 'getProNumbers'])->name('getProNumbers');
        Route::post('/get-pro-numbers-for-materials', [ReservationController::class, 'getProNumbersForMaterials'])->name('getProNumbersForMaterials');
        Route::post('/load-multiple-pro', [ReservationController::class, 'loadMultiplePro'])->name('loadMultiplePro');
        Route::post('/create-document', [ReservationController::class, 'createDocument'])->name('createDocument');
    });

    // Document Routes - Consolidated (using DocumentController untuk index dengan fitur lengkap)
    Route::prefix('documents')->name('documents.')->group(function () {
        // Document Listing & Viewing - GUNAKAN DocumentController untuk fitur lengkap
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/{id}', [ReservationDocumentController::class, 'show'])->name('show');

        // Document Editing (only creator can edit)
        Route::get('/{id}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');

        // Force Complete Items
        Route::post('/{id}/items/force-complete', [DocumentController::class, 'forceCompleteItems'])->name('items.force-complete');

        // Export & Print Operations
        Route::get('/{id}/print', [ReservationDocumentController::class, 'print'])->name('print');
        Route::get('/{id}/pdf', [ReservationDocumentController::class, 'pdf'])->name('pdf');
        Route::post('/{id}/print-selected', [ReservationDocumentController::class, 'printSelected'])->name('print-selected');
        Route::post('/{id}/export-excel', [ReservationDocumentController::class, 'exportExcel'])->name('export-excel');

        // Bulk Export
        Route::post('/export-selected-excel', [ReservationDocumentController::class, 'exportSelectedExcel'])->name('exportSelectedExcel');
        Route::post('/export-selected-pdf', [ReservationDocumentController::class, 'exportSelectedPdf'])->name('exportSelectedPdf');

        // Document Export
        Route::get('/export/{type?}', [ReservationDocumentController::class, 'export'])->name('export');

        // Transfer History
        Route::get('/{document}/items/{materialCode}/transfer-history',
            [ReservationDocumentController::class, 'getItemTransferHistory'])
            ->name('item-transfer-history');

        // Document Status Management
        Route::patch('/{id}/toggle-status', [ReservationDocumentController::class, 'toggleStatus'])
            ->name('toggle-status');

        // Transfer Process
        Route::post('/{id}/transfers/process', [TransferController::class, 'createTransfer'])
            ->name('transfers.process');
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

    // Transfer Routes
    Route::prefix('transfers')->name('transfers.')->group(function () {
        // Listing & Viewing
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/{id}', [TransferController::class, 'show'])->name('show');
        Route::get('/{id}/detailed', [TransferController::class, 'showDetailed'])->name('show.detailed');
        Route::get('/{id}/print', [TransferController::class, 'print'])->name('print');

        // CRUD Operations
        Route::put('/{id}/status', [TransferController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}', [TransferController::class, 'destroy'])->name('destroy');

        // Document-specific transfers
        Route::get('/document/{documentId}', [TransferController::class, 'getTransfersByDocument'])->name('by-document');

        // Transfer Validation
        Route::get('/check-item/{documentId}/{materialCode}', [TransferController::class, 'checkItemTransferability'])->name('check-item');

        // Data Fixing & Maintenance
        Route::post('/{id}/fix', [TransferController::class, 'fixTransferData'])->name('fix');
        Route::post('/fix-all', [TransferController::class, 'fixAllTransferData'])->name('fix.all');
        Route::post('/cleanup-duplicates', [TransferController::class, 'cleanupDuplicates'])->name('cleanup-duplicates');
        Route::post('/fix-duplicate/{transferNo}', [TransferController::class, 'fixDuplicateTransfer'])->name('fix-duplicate');

        // Export Operations
        Route::get('/export/excel', [TransferController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [TransferController::class, 'exportPDF'])->name('export.pdf');

        // Statistics
        Route::get('/stats', [TransferController::class, 'getTransferStats'])->name('stats');
    });

    // Settings Route
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings');

    // Service Checking Routes
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/check-flask-endpoint', [ReservationController::class, 'checkFlaskEndpoint'])->name('checkFlaskEndpoint');
        Route::get('/check-flask-service', [ReservationController::class, 'checkFlaskService'])->name('checkFlaskService');
        Route::get('/test-flask-connection', [ReservationController::class, 'testFlaskConnection'])->name('testFlaskConnection');
    });
});

// Fallback route for 404
Route::fallback(function () {
    if (auth()->check()) {
        return redirect()->route('dashboard')->with('error', 'Halaman tidak ditemukan.');
    }
    return redirect()->route('login');
});

// Additional Utility Routes (if needed for API)
Route::middleware(['auth'])->group(function () {
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json(['status' => 'healthy', 'timestamp' => now()]);
    })->name('health');

    // System status
    Route::get('/system-status', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toDateTimeString(),
            'app' => config('app.name'),
            'env' => config('app.env'),
            'debug' => config('app.debug'),
            'php_version' => PHP_VERSION
        ]);
    })->name('system.status');
});

// Transfer routes yang terpisah (untuk menghindari konflik)
Route::middleware(['auth'])->group(function () {
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/{id}', [TransferController::class, 'show'])->name('transfers.show');
    Route::post('/transfers/{id}/fix', [TransferController::class, 'fixTransferData'])->name('transfers.fix');
    Route::post('/transfers/{id}/retry', [TransferController::class, 'retry'])->name('transfers.retry');
    Route::get('/transfers/{id}/print', [TransferController::class, 'print'])->name('transfers.print');
    Route::get('/transfers/export/{format}', [TransferController::class, 'export'])->name('transfers.export');
});
