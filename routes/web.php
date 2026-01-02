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
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Password reset routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Logout Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('/profile/index', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');
    Route::delete('/profile/delete-account', [ProfileController::class, 'deleteAccount'])->name('profile.delete');

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
        Route::post('/clear-all-sync-data', [ReservationController::class, 'clearAllSyncData'])->name('clearAllSyncData');

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

    // Document Routes - PERBAIKAN: Consolidate all document routes
    Route::prefix('documents')->name('documents.')->group(function () {
        // Document CRUD Routes (using ReservationDocumentController for most operations)
        Route::get('/', [ReservationDocumentController::class, 'index'])->name('index');
        Route::get('/{id}', [ReservationDocumentController::class, 'show'])->name('show');
        Route::get('/{id}/print', [ReservationDocumentController::class, 'print'])->name('print');
        Route::get('/{id}/pdf', [ReservationDocumentController::class, 'pdf'])->name('pdf');

        // Item Management Routes (using DocumentController for edit/update/force-complete)
        Route::get('/{id}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');

        // Item Selection Operations
        Route::post('/{id}/print-selected', [ReservationDocumentController::class, 'printSelected'])->name('print-selected');
        Route::post('/{id}/export-excel', [ReservationDocumentController::class, 'exportExcel'])->name('export-excel');

        // Force Complete Items
        Route::post('/{id}/items/force-complete', [DocumentController::class, 'forceCompleteItems'])->name('items.force-complete');

        // Transfer Process Route
        Route::post('/{id}/transfers/process', [TransferController::class, 'createTransfer'])
            ->name('transfers.process');

        // Transfer History
        Route::get('/{document}/items/{materialCode}/transfer-history',
            [ReservationDocumentController::class, 'getItemTransferHistory'])
            ->name('item-transfer-history');

        // Document Status Management
        Route::patch('/{id}/toggle-status', [ReservationDocumentController::class, 'toggleStatus'])
            ->name('toggle-status')
            ->middleware('can:toggle_document_status');

        // System Maintenance Routes
        Route::post('/{id}/fix-transfer-data', [ReservationDocumentController::class, 'fixTransferData'])
            ->name('fix-transfer-data');
        Route::post('/{id}/fix-status', [ReservationDocumentController::class, 'fixItemStatuses'])
            ->name('fix-status');
        Route::post('/{id}/log-unauthorized-attempt', [ReservationDocumentController::class, 'logUnauthorizedAttempt'])
            ->name('log-unauthorized-attempt');
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

    // Transfer Routes (standalone)
    Route::prefix('transfers')->name('transfers.')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/{id}', [TransferController::class, 'show'])->name('show');
        Route::put('/{id}/status', [TransferController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}', [TransferController::class, 'destroy'])->name('destroy');
    });

    // Additional utility routes
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings');
});

// Fallback route for 404
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

// Dashboard API routes
Route::prefix('api/dashboard')->name('api.dashboard.')->middleware(['auth'])->group(function () {
    Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
    Route::get('/activities', [DashboardController::class, 'getActivities'])->name('activities');
});
