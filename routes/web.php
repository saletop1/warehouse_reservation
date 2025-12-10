<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReservationDocumentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

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
        Route::post('/clear-all-sync-data', [ReservationController::class, 'clearAllSyncData'])->name('clear-all-sync-data');

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

    // Document Routes
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [ReservationDocumentController::class, 'index'])->name('index');
        Route::get('/{id}', [ReservationDocumentController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ReservationController::class, 'editDocument'])->name('edit');
        Route::put('/{id}', [ReservationController::class, 'updateDocument'])->name('update');
        Route::get('/{id}/print', [ReservationDocumentController::class, 'print'])->name('print');
        Route::get('/{id}/pdf', [ReservationDocumentController::class, 'pdf'])->name('pdf');
        Route::get('/export/{type}', [ReservationDocumentController::class, 'export'])->name('export');
        Route::post('/export/selected/excel', [ReservationDocumentController::class, 'exportSelectedExcel'])->name('export.selected.excel');
        Route::post('/export/selected/pdf', [ReservationDocumentController::class, 'exportSelectedPdf'])->name('export.selected.pdf');
        Route::get('/check-flask-endpoint', [ReservationController::class, 'checkFlaskEndpoint'])->name('checkFlaskEndpoint');
    });
});
