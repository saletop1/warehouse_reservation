@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('reservations.index') }}">Reservations</a></li>
                    <li class="breadcrumb-item active">Create New Reservation</li>
                </ol>
            </nav>

            <!-- PERUBAHAN: Ganti panah kiri dengan home -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Create New Reservation</h4>
                <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-outline-secondary" title="Back to Home">
                    <i class="fas fa-home"></i>
                </a>
            </div>

            <!-- Notification Container -->
            <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;"></div>

            <!-- Step-by-step form -->
            <div class="card">
                <div class="card-header p-3">
                    <ul class="nav nav-pills card-header-pills">
                        <li class="nav-item">
                            <a class="nav-link active" id="step1-tab" data-bs-toggle="tab" href="#step1">
                                <span class="step-number">1</span> Plant
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" id="step2-tab" data-bs-toggle="tab" href="#step2">
                                <span class="step-number">2</span> Material Type
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" id="step3-tab" data-bs-toggle="tab" href="#step3">
                                <span class="step-number">3</span> Materials
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" id="step4-tab" data-bs-toggle="tab" href="#step4">
                                <span class="step-number">4</span> PRO Numbers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" id="step5-tab" data-bs-toggle="tab" href="#step5">
                                <span class="step-number">5</span> Review
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-3">
                    <div class="tab-content">
                        <!-- Step 1: Select Plant -->
                        <div class="tab-pane fade show active" id="step1">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label fw-bold mb-0">Select Plant *</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-next-step1" disabled>
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>

                                    <div class="mb-3">
                                        <div class="input-group">
                                            <select class="form-control form-control-sm" id="plant_select" required>
                                                <option value="">-- Select Plant --</option>
                                                @foreach($plants as $plant)
                                                    <option value="{{ $plant->sap_plant }}" data-code="{{ $plant->dwerk }}">
                                                        {{ $plant->sap_plant }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Material Type -->
                        <div class="tab-pane fade" id="step2">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <label class="form-label fw-bold mb-0">Material Type *</label>
                                            <small class="text-muted ms-2">
                                                <span class="badge bg-primary" id="selected-types-count">0</span> types selected
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-link text-decoration-none text-dark small" id="toggle_all_types">
                                                Select all
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-prev-step2">
                                                <i class="fas fa-arrow-left"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-next-step2" disabled>
                                                <i class="fas fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div id="material-types-checkbox-container" class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                                            <div class="text-center py-2">
                                                <div class="spinner-border text-primary spinner-border-sm" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 small">Loading material types...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Material Selection -->
                        <div class="tab-pane fade" id="step3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <label class="form-label fw-bold mb-0">Available Materials *</label>
                                            <small class="text-muted ms-2">
                                                <span class="badge bg-primary" id="selected-materials-count">0</span> selected
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="input-group input-group-sm" style="width: 200px;">
                                                <span class="input-group-text bg-transparent border-end-0">
                                                    <i class="fas fa-search text-muted"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0" id="material-search" placeholder="Search...">
                                            </div>
                                            <button type="button" class="btn btn-link text-decoration-none text-dark" id="toggle_all_materials">
                                                All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-prev-step3">
                                                <i class="fas fa-arrow-left"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-next-step3" disabled>
                                                <i class="fas fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div id="materials-checkbox-container" class="border rounded p-2" style="max-height: 350px; overflow-y: auto;">
                                            <div class="text-center py-3">
                                                <div class="spinner-border text-primary spinner-border-sm" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 small">Loading materials...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: PRO Selection -->
                        <div class="tab-pane fade" id="step4">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <label class="form-label fw-bold mb-0">Available PRO Numbers *</label>
                                            <small class="text-muted ms-2">
                                                <span class="badge bg-primary" id="selected-pro-count">0</span> selected
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-link text-decoration-none text-dark" id="toggle_all_pro">
                                                All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-prev-step4">
                                                <i class="fas fa-arrow-left"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-next-step4" disabled>
                                                <i class="fas fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div id="pro-numbers-checkbox-container" class="border rounded p-2" style="max-height: 350px; overflow-y: auto;">
                                            <div class="text-center py-3">
                                                <div class="spinner-border text-primary spinner-border-sm" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 small">Loading PRO numbers...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Review & Create -->
                        <div class="tab-pane fade" id="step5">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Review and Create Reservation</h5>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-prev-step5">
                                                <i class="fas fa-arrow-left"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success" id="btn-create-reservation">
                                                <i class="fas fa-file-alt me-1"></i> Create Reservation
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <!-- Materials Table -->
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered" id="materials-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="3%" class="text-center">#</th>
                                                        <th width="12%" class="text-center">Source PRO</th>
                                                        <th width="10%" class="text-center">Material PRO</th>
                                                        <th width="10%" class="text-center">Desc PRO</th>
                                                        <th width="8%" class="text-center">MRP</th>
                                                        <th width="10%" class="text-center">Sales Order</th>
                                                        <th width="10%" class="text-center">Material Req</th>
                                                        <th width="15%" class="text-center">Description</th>
                                                        <th width="8%" class="text-center">Add Info</th>
                                                        <th width="10%" class="text-center">Required Qty</th>
                                                        <th width="12%" class="text-center">Requested Qty *</th>
                                                        <th width="5%" class="text-center">Unit</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="materials-table-body">
                                                    <!-- Data will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="text-muted small mt-2">
                                            <i class="fas fa-info-circle"></i>
                                            Adjust the "Requested Qty" if needed. Used sync data will be deleted after document creation.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mt-3" id="loading-message">Processing...</h5>
                <p class="text-muted small" id="loading-details">Please wait</p>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .full-description {
        white-space: normal !important;
        word-wrap: break-word !important;
        line-height: 1.4;
        max-width: none !important;
        min-width: 200px;
    }

    /* Table responsive */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Adjust column widths for better display */
    #materials-table th,
    #materials-table td {
        padding: 0.4rem 0.5rem;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    #materials-table .text-center {
        text-align: center;
    }

    /* Description column */
    #materials-table td:nth-child(8) {
        min-width: 250px;
        max-width: 300px;
    }

    /* Step navigation - Updated with underline instead of background */
    .step-number {
        display: inline-block;
        width: 20px;
        height: 20px;
        line-height: 20px;
        text-align: center;
        background-color: #6c757d;
        color: white;
        border-radius: 50%;
        margin-right: 6px;
        font-size: 11px;
    }

    .nav-pills .nav-link {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
        color: #6c757d;
        border-radius: 0;
        position: relative;
    }

    .nav-pills .nav-link:hover {
        color: #495057;
        background-color: transparent;
    }

    .nav-pills .nav-link.active {
        color: #495057;
        background-color: transparent;
        font-weight: 500;
    }

    .nav-pills .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 2px;
        background-color: #495057;
    }

    .nav-pills .nav-link.active .step-number {
        background-color: #495057;
    }

    .card-header {
        padding: 0.5rem 1rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }

    .card-body {
        padding: 0.75rem;
    }

    /* Material item container - COMPACT NO-GRID LAYOUT */
    .material-item-container {
        display: flex;
        align-items: flex-start;
        padding: 4px 0;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }

    .material-item-container:hover {
        background-color: #f8f9fa;
    }

    .material-item-container:last-child {
        border-bottom: none;
    }

    .material-checkbox-section {
        flex: 0 0 30px;
        padding-top: 2px;
    }

    .material-content-section {
        flex: 1;
        display: flex;
        align-items: flex-start;
    }

    .material-left-section {
        flex: 0 0 200px;
    }

    .material-center-section {
        flex: 1;
        min-width: 300px;
        padding: 0 10px;
        max-width: calc(100% - 500px);
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .material-right-section {
        flex: 0 0 200px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .material-basic-info {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 2px;
    }

    .material-code {
        font-weight: bold;
        font-family: monospace;
        color: #212529;
        font-size: 0.9rem;
        background-color: #e9ecef;
        padding: 2px 6px;
        border-radius: 3px;
    }

    .material-type-badge {
        background-color: #6c757d;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 3px;
    }

    .mrp-badge-small {
        background-color: #6f42c1;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 3px;
    }

    .sales-order-badge-small {
        background-color: #20c997;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 3px;
    }

    .material-desc {
        font-size: 0.9rem;
        color: #555;
        line-height: 1.4;
        margin: 0;
        padding-top: 2px;
    }

    .material-type-description {
        font-size: 0.85rem;
        color: #666;
        font-weight: 500;
        margin-bottom: 2px;
        padding: 0;
    }

    .additional-info-section {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 2px;
        padding-top: 2px;
        border-top: 1px dashed #dee2e6;
    }

    /* Right section info - COMPACT STYLE */
    .material-pro-section {
        margin-top: 2px;
    }

    .material-pro-label {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1px;
    }

    .material-pro-value {
        font-size: 0.85rem;
        color: #495057;
        font-weight: 500;
        font-family: 'Consolas', monospace;
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
        display: inline-block;
        margin-top: 1px;
        word-break: break-all;
    }

    .desc-pro-section {
        margin-top: 4px;
    }

    .desc-pro-label {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1px;
    }

    .desc-pro-value {
        font-size: 0.85rem;
        color: #495057;
        font-weight: 500;
        font-family: 'Consolas', monospace;
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
        display: inline-block;
        margin-top: 1px;
        word-break: break-all;
    }

    .form-check {
        margin-bottom: 0;
        width: 100%;
    }

    .form-check-input {
        margin-top: 0;
    }

    .form-check-label {
        width: 100%;
        cursor: pointer;
        padding-left: 5px;
    }

    .source-pro-badge {
        margin-right: 3px;
        margin-bottom: 3px;
        font-size: 0.75rem;
    }

    .quantity-input {
        text-align: center;
        padding: 0.2rem 0.4rem;
        font-size: 0.9rem;
    }

    /* Remove spinner on number input */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
    }

    /* Quantity columns */
    .quantity-cell {
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-weight: 500;
    }

    /* Highlight consolidated rows */
    .consolidated-row {
        background-color: #fff8e1 !important;
    }

    h4, h5, h6 {
        margin-bottom: 0.5rem;
        color: #343a40;
    }

    .alert {
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.5rem;
    }

    /* Notification Styles */
    .auto-notification {
        position: relative;
        padding: 12px 15px;
        margin-bottom: 10px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        animation: slideInRight 0.3s ease;
        max-width: 350px;
        word-wrap: break-word;
    }

    .auto-notification.success {
        background-color: #d4edda;
        border-left: 4px solid #28a745;
        color: #155724;
    }

    .auto-notification.error {
        background-color: #f8d7da;
        border-left: 4px solid #dc3545;
        color: #721c24;
    }

    .auto-notification.warning {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        color: #856404;
    }

    .auto-notification.info {
        background-color: #d1ecf1;
        border-left: 4px solid #17a2b8;
        color: #0c5460;
    }

    .auto-notification .notification-content {
        font-size: 0.9rem;
        line-height: 1.4;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
            max-height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
    }

    /* PRO number with material info */
    .pro-item-info {
        font-size: 0.8rem;
        color: #666;
        margin-left: 10px;
        font-style: italic;
    }

    /* Simple list without block cell */
    .simple-list-item {
        padding: 6px 0;
        border-bottom: 1px solid #f0f0f0;
        margin: 0;
    }

    .simple-list-item:last-child {
        border-bottom: none;
    }

    /* MRP badge styles */
    .mrp-badge {
        background-color: #6f42c1;
        color: white;
    }

    .sales-order-badge {
        background-color: #20c997;
        color: white;
    }

    /* Description in table */
    .table-description {
        white-space: normal;
        word-wrap: break-word;
        line-height: 1.4;
        max-width: 200px;
    }

    /* MRP non-editable style */
    .qty-disabled {
        background-color: #f8f9fa;
        color: #6c757d;
        cursor: not-allowed;
    }

    /* Additional Info cell */
    .additional-info-cell {
        max-width: 150px;
        word-wrap: break-word;
        font-size: 0.85rem;
    }

    /* Material PRO and Desc PRO */
    .material-pro-cell, .desc-pro-cell {
        font-size: 0.85rem;
        word-wrap: break-word;
        max-width: 150px;
    }

    /* Compact header buttons */
    .compact-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Search input */
    .search-input-group {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }

    .search-input-group .input-group-text {
        background-color: transparent;
        border: none;
    }

    .search-input-group .form-control {
        border: none;
        box-shadow: none;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .material-content-section {
            flex-wrap: wrap;
        }

        .material-left-section,
        .material-center-section,
        .material-right-section {
            flex: 0 0 100%;
            padding: 0;
            margin-bottom: 6px;
        }

        .material-center-section {
            padding: 4px 0;
            max-width: 100%;
        }

        .material-right-section {
            padding-top: 4px;
            border-top: 1px dashed #e0e0e0;
        }
    }

    @media (max-width: 768px) {
        .compact-controls {
            flex-wrap: wrap;
            gap: 5px;
        }

        .nav-pills .nav-link {
            font-size: 0.8rem;
            padding: 0.3rem 0.5rem;
        }

        .nav-pills .nav-link.active::after {
            width: 30px;
        }
    }

    /* Breadcrumb styling */
    .breadcrumb {
        background-color: transparent;
        padding: 0;
        margin-bottom: 1rem;
    }

    .breadcrumb-item a {
        color: #6c757d;
        text-decoration: none;
    }

    .breadcrumb-item a:hover {
        color: #495057;
        text-decoration: underline;
    }

    .breadcrumb-item.active {
        color: #495057;
    }

    /* Button spacing */
    .gap-2 {
        gap: 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script>
    let currentStep = 1;
    let selectedPlant = '';
    let selectedMaterialTypes = [];
    let selectedMaterials = [];
    let selectedProNumbers = [];
    let materialTypes = [];
    let allMaterials = [];
    let proNumbers = [];
    let loadedMaterials = [];

    // Material Type descriptions mapping
    const materialTypeDescriptions = {
        'FERT': 'Finished Product',
        'HALB': 'Semifinished Product',
        'HALM': 'Semifinished Prod. Metal',
        'VERP': 'Packaging',
        'ZR01': 'Wood Material',
        'ZR02': 'Metal Material',
        'ZR03': 'Hardware Material',
        'ZR04': 'Accessories Material',
        'ZR05': 'Paint Material',
        'ZR06': 'Upholstery Material',
        'ZR07': 'Packaging Material',
        'ZR08': 'Glass Material',
        'ZR09': 'Chemical Material'
    };

    // MRP yang diperbolehkan untuk edit quantity
    const allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1'];

    // ============================
    // NOTIFICATION SYSTEM
    // ============================

    function showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');

        notification.className = `auto-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">${message}</div>
        `;

        container.appendChild(notification);

        // Auto remove after duration
        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.5s ease';
            setTimeout(() => {
                if (container.contains(notification)) {
                    container.removeChild(notification);
                }
            }, 500);
        }, duration);
    }

    // ============================
    // FORMATTING FUNCTIONS
    // ============================

    function formatMaterialCodeForUI(materialCode) {
        if (!materialCode) return '';
        if (/^\d+$/.test(materialCode)) {
            return materialCode.replace(/^0+/, '');
        }
        return materialCode;
    }

    function formatMaterialCodeForDB(materialCode) {
        if (!materialCode) return '';
        if (/^\d+$/.test(materialCode)) {
            return materialCode.padStart(18, '0');
        }
        return materialCode;
    }

    function formatProNumberForUI(proNumber) {
        if (!proNumber) return '';
        if (/^\d+$/.test(proNumber)) {
            return proNumber.replace(/^0+/, '');
        }
        return proNumber;
    }

    function formatProNumberForDB(proNumber) {
        if (!proNumber) return '';
        if (/^\d+$/.test(proNumber)) {
            return proNumber.padStart(12, '0');
        }
        return proNumber;
    }

    // PERUBAHAN: Fungsi khusus untuk format Material PRO (mathd) numerik tanpa leading zero
    function formatMaterialProForUI(value) {
        if (!value) return '';
        // Hanya format jika value hanya berisi angka (numerik)
        if (/^\d+$/.test(value)) {
            return value.replace(/^0+/, '');
        }
        // Jika bukan numerik (misal mengandung huruf atau karakter lain), biarkan asli
        return value;
    }

    function getMaterialTypeDescription(type) {
        return materialTypeDescriptions[type] || 'No description available';
    }

    // ============================
    // CORE FUNCTIONS
    // ============================

    // Initialize
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Step 1: Plant selection
        $('#plant_select').on('change', function() {
            selectedPlant = $(this).val();
            if (selectedPlant) {
                $('#btn-next-step1').prop('disabled', false);
                loadMaterialTypes(selectedPlant);
            } else {
                $('#btn-next-step1').prop('disabled', true);
            }
        });

        $('#btn-next-step1').on('click', function() {
            if (!selectedPlant) return;
            currentStep = 2;
            updateStepNavigation();
        });

        // Step 2: Material type selection - toggle all
        let allTypesSelected = false;
        $('#toggle_all_types').on('click', function() {
            if (allTypesSelected) {
                // Deselect all
                $('.material-type-checkbox').prop('checked', false).trigger('change');
                $(this).text('Select all');
                allTypesSelected = false;
            } else {
                // Select all
                $('.material-type-checkbox').prop('checked', true).trigger('change');
                $(this).text('Deselect all');
                allTypesSelected = true;
            }
        });

        $('#btn-prev-step2').on('click', function() {
            currentStep = 1;
            updateStepNavigation();
        });

        $('#btn-next-step2').on('click', function() {
            if (selectedMaterialTypes.length === 0) {
                showNotification('Please select at least one material type', 'warning', 3000);
                return;
            }
            currentStep = 3;
            updateStepNavigation();
            loadMaterials(selectedPlant, selectedMaterialTypes);
        });

        // Step 3: Material selection - toggle all
        let allMaterialsSelected = false;
        $('#toggle_all_materials').on('click', function() {
            if (allMaterialsSelected) {
                // Deselect all
                $('.material-checkbox').prop('checked', false).trigger('change');
                $(this).text('All');
                allMaterialsSelected = false;
            } else {
                // Select all
                $('.material-checkbox').prop('checked', true).trigger('change');
                $(this).text('None');
                allMaterialsSelected = true;
            }
        });

        $('#material-search').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            filterMaterials(searchTerm);
        });

        $('#btn-prev-step3').on('click', function() {
            currentStep = 2;
            updateStepNavigation();
        });

        $('#btn-next-step3').on('click', function() {
            if (selectedMaterials.length === 0) {
                showNotification('Please select at least one material', 'warning', 3000);
                return;
            }

            showLoading('Loading PRO numbers...', 'Please wait');

            const materialsForAPI = selectedMaterials.map(m => formatMaterialCodeForDB(m));

            $.ajax({
                url: '/reservations/get-pro-numbers-for-materials',
                method: 'POST',
                data: {
                    plant: selectedPlant,
                    material_types: selectedMaterialTypes,
                    materials: materialsForAPI,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    hideLoading();

                    if (response.success) {
                        if (response.pro_numbers && Array.isArray(response.pro_numbers)) {
                            proNumbers = response.pro_numbers.map(item => {
                                if (typeof item === 'string') return {pro_number: item, material_count: 0};
                                if (typeof item === 'object' && item !== null) {
                                    return {
                                        pro_number: item.sap_order || item.pro_number || item.aufnr || item.pro || item.value || String(item),
                                        material_count: item.material_count || 0
                                    };
                                }
                                return {pro_number: String(item), material_count: 0};
                            });

                            selectedProNumbers = [];
                            currentStep = 4;
                            updateStepNavigation();
                            populateProNumbersContainer();
                        } else {
                            showNotification('Error: PRO numbers data format is incorrect', 'error', 4000);
                        }
                    } else {
                        showNotification('Error: ' + (response.message || 'Failed to load PRO numbers'), 'error', 4000);
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    let errorMsg = 'Failed to load PRO numbers. ';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += xhr.responseJSON.message;
                    }
                    showNotification(errorMsg, 'error', 5000);
                }
            });
        });

        // Step 4: PRO selection - toggle all
        let allProSelected = false;
        $('#toggle_all_pro').on('click', function() {
            if (allProSelected) {
                // Deselect all
                $('.pro-checkbox').prop('checked', false).trigger('change');
                $(this).text('All');
                allProSelected = false;
            } else {
                // Select all
                $('.pro-checkbox').prop('checked', true).trigger('change');
                $(this).text('None');
                allProSelected = true;
            }
        });

        $('#btn-prev-step4').on('click', function() {
            currentStep = 3;
            updateStepNavigation();
        });

        $('#btn-next-step4').on('click', function() {
            if (selectedProNumbers.length === 0) {
                showNotification('Please select at least one PRO number', 'warning', 3000);
                return;
            }

            const requestData = {
                plant: selectedPlant,
                material_types: selectedMaterialTypes,
                materials: selectedMaterials,
                pro_numbers: selectedProNumbers,
                _token: '{{ csrf_token() }}'
            };

            showLoading('Loading material data...', 'Formatting data for database matching');

            $.ajax({
                url: '/reservations/load-multiple-pro',
                method: 'POST',
                data: requestData,
                success: function(response) {
                    console.log('✅ Step 5 Response:', response);

                    if (response.success && response.data && response.data.length > 0) {
                        loadedMaterials = response.data;
                        hideLoading();

                        currentStep = 5;
                        updateStepNavigation();
                        populateMaterialsTable();
                    } else {
                        hideLoading();
                        let errorMsg = response.message || 'No material data found.';
                        showNotification(errorMsg, 'warning', 4000);
                        currentStep = 4;
                        updateStepNavigation();
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    let errorMsg = 'Server error occurred.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showNotification(errorMsg, 'error', 5000);
                    currentStep = 4;
                    updateStepNavigation();
                }
            });
        });

        // Step 5: Review and create
        $('#btn-prev-step5').on('click', function() {
            currentStep = 4;
            updateStepNavigation();
        });

        $('#btn-create-reservation').on('click', function() {
            if (confirm('Create reservation document?')) {
                createReservationDocument();
            }
        });
    });

    function updateStepNavigation() {
        $('.nav-link').removeClass('active');
        $(`#step${currentStep}-tab`).addClass('active').removeClass('disabled');
        $('.tab-pane').removeClass('show active');
        $(`#step${currentStep}`).addClass('show active');

        for (let i = 1; i <= 5; i++) {
            if (i < currentStep) {
                $(`#step${i}-tab`).removeClass('disabled');
            }
        }
    }

    function loadMaterialTypes(plant) {
        $('#material-types-checkbox-container').html(`
            <div class="text-center py-2">
                <div class="spinner-border text-primary spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 small">Loading material types...</p>
            </div>
        `);

        $.ajax({
            url: '/reservations/get-material-types',
            method: 'POST',
            data: {
                plant: plant,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    materialTypes = response.material_types;

                    let containerHtml = '';
                    if (materialTypes.length > 0) {
                        materialTypes.forEach(function(type) {
                            const isChecked = selectedMaterialTypes.includes(type);
                            const description = getMaterialTypeDescription(type);

                            containerHtml += `
                                <div class="simple-list-item">
                                    <div class="form-check">
                                        <input class="form-check-input material-type-checkbox" type="checkbox"
                                               id="type_${type}" value="${type}" ${isChecked ? 'checked' : ''}>
                                        <label class="form-check-label" for="type_${type}">
                                            <span class="fw-bold">${type}</span>
                                            <small class="text-muted ms-2">${description}</small>
                                        </label>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        containerHtml = '<p class="text-muted text-center py-2 small">No material types found</p>';
                    }
                    $('#material-types-checkbox-container').html(containerHtml);

                    // Add click handlers for checkboxes
                    $('.material-type-checkbox').on('change', function() {
                        const type = $(this).val();
                        const isChecked = $(this).is(':checked');

                        if (isChecked && !selectedMaterialTypes.includes(type)) {
                            selectedMaterialTypes.push(type);
                        } else if (!isChecked) {
                            selectedMaterialTypes = selectedMaterialTypes.filter(t => t !== type);
                        }

                        $('#selected-types-count').text(selectedMaterialTypes.length);
                        $('#btn-next-step2').prop('disabled', selectedMaterialTypes.length === 0);

                        // Update toggle button text
                        let allChecked = $('.material-type-checkbox:checked').length === materialTypes.length;
                        if (allChecked) {
                            $('#toggle_all_types').text('Deselect all');
                            window.allTypesSelected = true;
                        } else {
                            $('#toggle_all_types').text('Select all');
                            window.allTypesSelected = false;
                        }
                    });

                    $('#selected-types-count').text(selectedMaterialTypes.length);
                    $('#btn-next-step2').prop('disabled', selectedMaterialTypes.length === 0);

                    // Set initial toggle button text
                    let allChecked = selectedMaterialTypes.length === materialTypes.length;
                    if (allChecked) {
                        $('#toggle_all_types').text('Deselect all');
                        window.allTypesSelected = true;
                    } else {
                        $('#toggle_all_types').text('Select all');
                        window.allTypesSelected = false;
                    }
                }
            },
            error: function(xhr) {
                console.error('Material types error:', xhr);
                showNotification('Failed to load material types', 'error', 4000);
            }
        });
    }

    // ==============================================
    // COMPACT MATERIALS DISPLAY FOR STEP 3 - NO GRID
    // ==============================================
    function loadMaterials(plant, materialTypes) {
        $('#materials-checkbox-container').html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 small">Loading materials...</p>
            </div>
        `);

        $.ajax({
            url: '/reservations/get-materials-by-type',
            method: 'POST',
            data: {
                plant: plant,
                material_types: materialTypes,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('📋 Step 3 - Materials response:', response);

                if (response.success) {
                    allMaterials = response.materials;

                    let containerHtml = '';
                    if (allMaterials.length > 0) {
                        allMaterials.forEach(function(material) {
                            const displayMatnr = formatMaterialCodeForUI(material.matnr);
                            const isChecked = selectedMaterials.includes(material.matnr);
                            const typeDescription = getMaterialTypeDescription(material.mtart);

                            // MRP information
                            const mrpBadge = material.dispo ?
                                `<span class="mrp-badge-small">${material.dispo}</span>` : '';

                            // Sales Order information
                            let salesOrderBadge = '';
                            if (material.kdauf && material.kdpos) {
                                salesOrderBadge = `<span class="sales-order-badge-small">${material.kdauf}-${material.kdpos}</span>`;
                            } else if (material.kdauf) {
                                salesOrderBadge = `<span class="sales-order-badge-small">${material.kdauf}</span>`;
                            }

                            // Additional Info (sortf)
                            const sortfValue = material.sortf || '';
                            const additionalInfo = sortfValue ?
                                `<div class="additional-info-section">
                                    <strong>Add Info:</strong> ${sortfValue}
                                </div>` : '';

                            // PERUBAHAN: Format Material PRO (mathd) numerik tanpa leading zero
                            // Desc PRO (makhd) biarkan asli
                            const mathdValue = formatMaterialProForUI(material.mathd || '');
                            const makhdValue = material.makhd || '';

                            containerHtml += `
                                <div class="material-item-container">
                                    <div class="material-checkbox-section">
                                        <input class="form-check-input material-checkbox" type="checkbox"
                                            id="mat_${material.matnr}" value="${material.matnr}" ${isChecked ? 'checked' : ''}>
                                    </div>
                                    <div class="material-content-section">
                                        <!-- LEFT: Material code, type, badges, type description -->
                                        <div class="material-left-section">
                                            <div class="material-basic-info">
                                                <span class="material-code">${displayMatnr}</span>
                                                <span class="material-type-badge">${material.mtart}</span>
                                                ${mrpBadge}
                                                ${salesOrderBadge}
                                            </div>
                                            <div class="material-type-description">
                                                ${typeDescription}
                                            </div>
                                            ${additionalInfo}
                                        </div>

                                        <!-- CENTER: Material description -->
                                        <div class="material-center-section">
                                            <div class="material-desc">
                                                ${material.maktx}
                                            </div>
                                        </div>

                                        <!-- RIGHT: Material PRO and Desc PRO -->
                                        <div class="material-right-section">
                                            ${mathdValue ? `
                                                <div class="material-pro-section">
                                                    <div class="material-pro-label">Material PRO</div>
                                                    <div class="material-pro-value">${mathdValue}</div>
                                                </div>
                                            ` : ''}
                                            ${makhdValue ? `
                                                <div class="desc-pro-section">
                                                    <div class="desc-pro-label">Desc PRO</div>
                                                    <div class="desc-pro-value">${makhdValue}</div>
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        containerHtml = '<p class="text-muted text-center py-3 small">No materials found</p>';
                    }

                    $('#materials-checkbox-container').html(containerHtml);

                    // Add click handlers for checkboxes
                    $('.material-checkbox').on('change', function() {
                        const materialCode = $(this).val();
                        const isChecked = $(this).is(':checked');

                        if (isChecked && !selectedMaterials.includes(materialCode)) {
                            selectedMaterials.push(materialCode);
                        } else if (!isChecked) {
                            selectedMaterials = selectedMaterials.filter(m => m !== materialCode);
                        }

                        $('#selected-materials-count').text(selectedMaterials.length);
                        $('#btn-next-step3').prop('disabled', selectedMaterials.length === 0);

                        // Update toggle button text
                        let allChecked = $('.material-checkbox:checked').length === allMaterials.length;
                        if (allChecked) {
                            $('#toggle_all_materials').text('None');
                            window.allMaterialsSelected = true;
                        } else {
                            $('#toggle_all_materials').text('All');
                            window.allMaterialsSelected = false;
                        }
                    });

                    $('#selected-materials-count').text(selectedMaterials.length);
                    $('#btn-next-step3').prop('disabled', selectedMaterials.length === 0);

                    // Set initial toggle button text
                    let allChecked = selectedMaterials.length === allMaterials.length;
                    if (allChecked && allMaterials.length > 0) {
                        $('#toggle_all_materials').text('None');
                        window.allMaterialsSelected = true;
                    } else {
                        $('#toggle_all_materials').text('All');
                        window.allMaterialsSelected = false;
                    }
                }
            },
            error: function(xhr) {
                console.error('Materials error:', xhr);
                showNotification('Failed to load materials', 'error', 4000);
            }
        });
    }

    function filterMaterials(searchTerm) {
        if (!searchTerm) {
            $('.material-item-container').show();
            return;
        }

        $('.material-item-container').each(function() {
            const materialCode = $(this).find('.material-code').text().toLowerCase();
            const materialDesc = $(this).find('.material-desc').text().toLowerCase();
            const typeCode = $(this).find('.material-type-badge').text().toLowerCase();
            const additionalInfo = $(this).find('.additional-info-section').text().toLowerCase();
            const typeDescription = $(this).find('.material-type-description').text().toLowerCase();
            const materialPro = $(this).find('.material-pro-value').text().toLowerCase();
            const descPro = $(this).find('.desc-pro-value').text().toLowerCase();

            if (materialCode.includes(searchTerm) ||
                materialDesc.includes(searchTerm) ||
                typeCode.includes(searchTerm) ||
                additionalInfo.includes(searchTerm) ||
                typeDescription.includes(searchTerm) ||
                materialPro.includes(searchTerm) ||
                descPro.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    function populateProNumbersContainer() {
        let containerHtml = '';
        if (proNumbers.length > 0) {
            proNumbers.forEach(function(proObj, index) {
                const proString = String(proObj.pro_number).trim();
                if (!proString) return;

                const displayPro = formatProNumberForUI(proString);
                const materialCount = proObj.material_count || 0;
                const isChecked = false;

                containerHtml += `
                    <div class="simple-list-item">
                        <div class="form-check">
                            <input class="form-check-input pro-checkbox" type="checkbox"
                                   id="pro_${index}" value="${proString}" ${isChecked ? 'checked' : ''}>
                            <label class="form-check-label" for="pro_${index}">
                                <strong>${displayPro}</strong>
                                <span class="pro-item-info">
                                    (${materialCount} material${materialCount !== 1 ? 's' : ''} available)
                                </span>
                            </label>
                        </div>
                    </div>
                `;
            });
        } else {
            containerHtml = '<p class="text-muted text-center py-3 small">No PRO numbers found</p>';
        }

        $('#pro-numbers-checkbox-container').html(containerHtml);

        // Event handlers
        $('.pro-checkbox').on('change', function() {
            const proNumber = $(this).val();
            const isChecked = $(this).is(':checked');

            if (isChecked && !selectedProNumbers.includes(proNumber)) {
                selectedProNumbers.push(proNumber);
            } else if (!isChecked) {
                selectedProNumbers = selectedProNumbers.filter(p => p !== proNumber);
            }

            $('#selected-pro-count').text(selectedProNumbers.length);
            $('#btn-next-step4').prop('disabled', selectedProNumbers.length === 0);

            // Update toggle button text
            let allChecked = $('.pro-checkbox:checked').length === proNumbers.length;
            if (allChecked) {
                $('#toggle_all_pro').text('None');
                window.allProSelected = true;
            } else {
                $('#toggle_all_pro').text('All');
                window.allProSelected = false;
            }
        });

        $('#selected-pro-count').text(selectedProNumbers.length);
        $('#btn-next-step4').prop('disabled', selectedProNumbers.length === 0);

        // Set initial toggle button text
        let allChecked = selectedProNumbers.length === proNumbers.length;
        if (allChecked && proNumbers.length > 0) {
            $('#toggle_all_pro').text('None');
            window.allProSelected = true;
        } else {
            $('#toggle_all_pro').text('All');
            window.allProSelected = false;
        }
    }

    function formatQuantity(qty) {
        if (!qty && qty !== 0) return '0';
        const num = parseFloat(qty);
        if (isNaN(num)) return '0';
        return num.toFixed(4).replace(/\.?0+$/, '');
    }

    function isMRPAllowedForEdit(dispo) {
        if (!dispo) return true;
        return allowedMRP.includes(dispo);
    }

    function populateMaterialsTable() {
        const tbody = $('#materials-table-body');
        let html = '';

        if (!loadedMaterials || loadedMaterials.length === 0) {
            html = `
                <tr>
                    <td colspan="12" class="text-center text-muted py-3">
                        <h6>No materials data available</h6>
                    </td>
                </tr>
            `;
            tbody.html(html);
            return;
        }

        loadedMaterials.forEach(function(material, index) {
            const originalQty = parseFloat(material.total_qty) || 0;
            const formattedOriginalQty = formatQuantity(originalQty);

            // Check if quantity is editable based on MRP
            const isQtyEditable = isMRPAllowedForEdit(material.dispo);

            // Format source PRO badges (display format)
            let sourceBadges = '';
            const displaySources = material.sources || [];

            if (displaySources.length > 0) {
                displaySources.forEach(function(source) {
                    const formattedSource = formatProNumberForUI(source);
                    sourceBadges += `<span class="badge bg-info source-pro-badge">${formattedSource}</span>`;
                });
            } else {
                sourceBadges = '<span class="text-muted small">No source</span>';
            }

            // Format Sales Order badges
            let salesOrderBadges = '';
            const displaySalesOrders = material.sales_orders || [];

            if (displaySalesOrders.length > 0) {
                displaySalesOrders.forEach(function(so) {
                    if (so && so !== 'null-null' && so !== 'null') {
                        salesOrderBadges += `<span class="badge sales-order-badge source-pro-badge">${so}</span>`;
                    }
                });
            }

            if (!salesOrderBadges) {
                salesOrderBadges = '<span class="text-muted small">-</span>';
            }

            // Format Additional Info (sortf)
            const additionalInfo = material.sortf || '-';

            // PERUBAHAN: Format Material PRO (mathd) numerik tanpa leading zero
            // Desc PRO (makhd) biarkan asli
            const materialPro = formatMaterialProForUI(material.mathd || '-');
            const descPro = material.makhd || '-';

            const isConsolidated = displaySources.length > 1;
            const rowClass = isConsolidated ? 'consolidated-row' : '';

            // Simpan data tambahan sebagai data attribute
            const additionalData = {};
            if (material.groes && material.groes !== 'null' && material.groes !== '0') {
                additionalData.groes = material.groes;
            }
            if (material.ferth && material.ferth !== 'null' && material.ferth !== '0') {
                additionalData.ferth = material.ferth;
            }
            if (material.zeinr && material.zeinr !== 'null' && material.zeinr !== '0') {
                additionalData.zeinr = material.zeinr;
            }

            html += `
                <tr class="${rowClass}" data-additional='${JSON.stringify(additionalData)}' data-index="${index}">
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${sourceBadges}</td>
                    <td class="text-center material-pro-cell">${materialPro}</td>
                    <td class="text-center desc-pro-cell">${descPro}</td>
                    <td class="text-center">
                        ${material.dispo ? `<span class="badge mrp-badge">${material.dispo}</span>` : '-'}
                    </td>
                    <td class="text-center">${salesOrderBadges}</td>
                    <td class="text-center"><code>${formatMaterialCodeForUI(material.material_code)}</code></td>
                    <td class="table-description full-description">${material.material_description || 'No description'}</td>
                    <td class="additional-info-cell">${additionalInfo}</td>
                    <td class="text-center quantity-cell">${formattedOriginalQty}</td>
                    <td class="text-center">
                        <input type="number" class="form-control quantity-input requested-qty text-center ${!isQtyEditable ? 'qty-disabled' : ''}"
                            value="${formattedOriginalQty}"
                            step="0.0001" min="0.0001"
                            data-index="${index}"
                            data-material="${material.material_code}"
                            data-dispo="${material.dispo || ''}"
                            ${!isQtyEditable ? 'readonly title="Quantity cannot be changed for this MRP"' : ''}>
                        ${!isQtyEditable ? '<small class="text-muted d-block">Fixed</small>' : ''}
                    </td>
                    <td class="text-center">${material.unit || '-'}</td>
                </tr>
            `;
        });

        tbody.html(html);

        // Event listeners for editable quantities only
        $('.requested-qty:not(.qty-disabled)').on('change', function() {
            const value = parseFloat($(this).val()) || 0;
            if (value < 0) {
                $(this).val(0);
                showNotification('Quantity cannot be negative', 'warning', 3000);
            }
        });
    }

    function createReservationDocument() {
        // Validate quantities
        let isValid = true;
        let materialsData = [];
        const invalidInputs = [];

        $('.requested-qty').each(function() {
            const value = parseFloat($(this).val()) || 0;
            const isEditable = !$(this).hasClass('qty-disabled');

            if (isEditable && value <= 0) {
                isValid = false;
                $(this).addClass('is-invalid');
                invalidInputs.push($(this).data('material'));
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            let errorMsg = 'Please enter valid quantities (greater than 0) for editable materials.<br><br>';
            errorMsg += '<strong>Invalid materials:</strong><br>';
            invalidInputs.forEach(matnr => {
                errorMsg += `• ${formatMaterialCodeForUI(matnr)}<br>`;
            });
            showNotification(errorMsg, 'error', 6000);
            return;
        }

        // Prepare materials data dengan semua field tambahan
        loadedMaterials.forEach(function(material, index) {
            const qtyInput = $(`.requested-qty[data-index="${index}"]`);
            const requestedQty = parseFloat(qtyInput.val()) || 0;
            const isQtyEditable = !qtyInput.hasClass('qty-disabled');

            // Ambil data tambahan dari data attribute
            const row = $(`tr[data-index="${index}"]`);
            const additionalData = row.length ? JSON.parse(row.attr('data-additional') || '{}') : {};

            materialsData.push({
                material_code: material.material_code,
                material_code_display: formatMaterialCodeForUI(material.material_code),
                material_description: material.material_description,
                unit: material.unit,
                sortf: material.sortf,
                dispo: material.dispo,
                requested_qty: requestedQty,
                is_qty_editable: isQtyEditable,
                sources: material.sources || [],
                sales_orders: material.sales_orders || [],
                pro_details: material.pro_details || [],
                // Field tambahan untuk ditampilkan di tabel
                mathd: material.mathd || null,
                makhd: material.makhd || null,
                // Field tambahan untuk disimpan (tidak ditampilkan)
                groes: additionalData.groes || null,
                ferth: additionalData.ferth || null,
                zeinr: additionalData.zeinr || null
            });
        });

        // Debug logging
        console.log('📤 Sending materials data to server:', materialsData.map(m => ({
            code: m.material_code_display,
            mathd: m.mathd,
            makhd: m.makhd,
            groes: m.groes,
            ferth: m.ferth,
            zeinr: m.zeinr,
            qty: m.requested_qty
        })));

        showLoading('Creating reservation document...', 'Used sync data will be deleted automatically');

        $.ajax({
            url: '/reservations/create-document',
            method: 'POST',
            data: {
                plant: selectedPlant,
                material_types: selectedMaterialTypes,
                materials: materialsData,
                selected_materials: selectedMaterials,
                pro_numbers: selectedProNumbers,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('✅ SUCCESS Response from server:', response);
                hideLoading();

                if (response.success) {
                    showNotification(
                        `Reservation document created successfully!<br><br>
                        <strong>Document Number:</strong> ${response.document_no}<br>
                        <small>ID: ${response.document_id} | Deleted: ${response.deleted_sync_data_count} sync records</small>`,
                        'success',
                        5000
                    );

                    // Redirect setelah delay
                    setTimeout(() => {
                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        } else {
                            window.location.href = '/documents';
                        }
                    }, 2000);
                } else {
                    let errorMsg = 'Error: ' + (response.message || 'Unknown error');
                    showNotification(errorMsg, 'error', 6000);
                }
            },
            error: function(xhr) {
                console.error('🔥 AJAX ERROR:', xhr);
                hideLoading();

                let errorMessage = 'Failed to create document. ';

                if (xhr.status === 0) {
                    errorMessage += 'Network error. Check your internet connection.';
                } else if (xhr.status === 401) {
                    errorMessage += 'Unauthorized. Please log in again.';
                } else if (xhr.status === 403) {
                    errorMessage += 'Forbidden. You don\'t have permission.';
                } else if (xhr.status === 404) {
                    errorMessage += 'Endpoint not found.';
                } else if (xhr.status === 419) {
                    errorMessage += 'Session expired. Please refresh the page.';
                } else if (xhr.status === 422) {
                    errorMessage += 'Validation error. Please check your data.';
                } else if (xhr.status === 500) {
                    errorMessage += 'Server error. Please contact administrator.';
                }

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage += '<br><strong>Details:</strong> ' + xhr.responseJSON.message;
                }

                showNotification(errorMessage, 'error', 6000);
            }
        });
    }

    function showLoading(message, details) {
        $('#loading-message').text(message);
        $('#loading-details').text(details);
        $('#loadingModal').modal('show');
    }

    function hideLoading() {
        $('#loadingModal').modal('hide');
    }

    function suggestProNumbersForMaterials() {
        showLoading('Finding PRO numbers...', 'Please wait');

        $.ajax({
            url: '/reservations/get-pro-numbers-for-materials',
            method: 'POST',
            data: {
                plant: selectedPlant,
                material_types: selectedMaterialTypes,
                materials: selectedMaterials.map(m => formatMaterialCodeForDB(m)),
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                hideLoading();

                if (response.success && response.pro_numbers.length > 0) {
                    let suggestionHtml = '<div class="mt-1" style="max-height: 150px; overflow-y: auto;">';
                    response.pro_numbers.forEach(function(pro) {
                        const displayPro = formatProNumberForUI(pro.pro_number);
                        suggestionHtml += `
                            <div class="form-check">
                                <input class="form-check-input suggested-pro-checkbox"
                                       type="checkbox" value="${pro.pro_number}"
                                       id="suggested_pro_${pro.pro_number}">
                                <label class="form-check-label small" for="suggested_pro_${pro.pro_number}">
                                    ${displayPro} (${pro.material_count || 0} materials)
                                </label>
                            </div>
                        `;
                    });
                    suggestionHtml += '</div>';

                    showSuggestionModal('PRO Suggestions', suggestionHtml, function() {
                        const selectedSuggestedPros = [];
                        $('.suggested-pro-checkbox:checked').each(function() {
                            selectedSuggestedPros.push($(this).val());
                        });

                        if (selectedSuggestedPros.length > 0) {
                            selectedProNumbers = selectedSuggestedPros;
                            $('#selected-pro-count').text(selectedProNumbers.length);
                            populateProNumbersContainer();
                            $('#btn-next-step4').prop('disabled', false);
                        }
                    });
                } else {
                    showNotification('No PRO numbers found containing the selected materials.', 'info', 4000);
                }
            },
            error: function() {
                hideLoading();
                showNotification('Failed to get PRO number suggestions.', 'error', 4000);
            }
        });
    }

    function showSuggestionModal(title, content, onConfirm) {
        const modalId = 'suggestionModal';
        $(`#${modalId}`).remove();

        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white p-2">
                            <h5 class="modal-title"><i class="fas fa-lightbulb me-2"></i>${title}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-2">
                            <div class="alert alert-info m-0">
                                ${content}
                            </div>
                        </div>
                        <div class="modal-footer p-2">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-sm btn-primary" id="apply-suggestion-btn">
                                Apply Selection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);

        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();

        $('#apply-suggestion-btn').off('click').on('click', function() {
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
            modal.hide();
        });

        if ($('.suggested-pro-checkbox').length === 1) {
            $('.suggested-pro-checkbox').prop('checked', true);
        }
    }
</script>
@endpush
@endsection
