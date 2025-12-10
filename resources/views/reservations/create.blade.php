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

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Create New Reservation</h4>
                <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
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
                                        <button type="button" class="btn btn-sm btn-primary" id="btn-next-step1" disabled>
                                            Next <i class="fas fa-arrow-right ms-1"></i>
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
                                            <span class="input-group-text bg-light">
                                                <small id="plant-info" class="text-muted">
                                                    <i class="fas fa-info-circle"></i> Select a plant to see details
                                                </small>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Material Type -->
                        <div class="tab-pane fade" id="step2">
                            <div class="alert alert-info p-2 mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                <small>Material Type Descriptions:
                                    <strong>FERT</strong> = Finished Product,
                                    <strong>HALB</strong> = Semifinished Product,
                                    <strong>HALM</strong> = Semifinished Prod. Metal,
                                    <strong>VERP</strong> = Packaging,
                                    <strong>ZR01</strong> = Wood Material,
                                    <strong>ZR02</strong> = Metal Material,
                                    <strong>ZR03</strong> = Hardware Material,
                                    <strong>ZR04</strong> = Accessories Material,
                                    <strong>ZR05</strong> = Paint Material,
                                    <strong>ZR06</strong> = Upholstery Material,
                                    <strong>ZR07</strong> = Packaging Material,
                                    <strong>ZR08</strong> = Glass Material,
                                    <strong>ZR09</strong> = Chemical Material
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <label class="form-label fw-bold mb-0">Material Type *</label>
                                            <small class="text-muted ms-2">
                                                <span class="badge bg-primary" id="selected-types-count">0</span> types selected
                                            </small>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-secondary" id="btn-prev-step2">
                                                <i class="fas fa-arrow-left me-1"></i> Back
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" id="btn-next-step2" disabled>
                                                Next <i class="fas fa-arrow-right ms-1"></i>
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

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="select_all_types">
                                        <label class="form-check-label" for="select_all_types">Select all</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Material Selection -->
                        <div class="tab-pane fade" id="step3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <label class="form-label fw-bold mb-0">Available Materials *</label>
                                            <small class="text-muted ms-2">
                                                <span class="badge bg-primary" id="selected-materials-count">0</span> selected
                                            </small>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-secondary" id="btn-prev-step3">
                                                <i class="fas fa-arrow-left me-1"></i> Back
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" id="btn-next-step3" disabled>
                                                Next <i class="fas fa-arrow-right ms-1"></i>
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

                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="input-group input-group-sm" style="width: 300px;">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                <input type="text" class="form-control" id="material-search" placeholder="Search material code or description...">
                                                <button class="btn btn-outline-secondary" type="button" id="clear-search">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-xs btn-outline-primary me-1" id="select-all-materials">
                                                    <i class="fas fa-check-square"></i> All
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary" id="deselect-all-materials">
                                                    <i class="fas fa-times-circle"></i> None
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: PRO Selection -->
                        <div class="tab-pane fade" id="step4">
                            <div class="alert alert-info mb-2 p-2" id="pro-selection-help">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>Tip:</strong> Make sure PRO numbers contain your selected materials
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-primary" id="btn-find-suitable-pros">
                                        <i class="fas fa-search me-1"></i>Find Suitable PROs
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <label class="form-label fw-bold mb-0">Available PRO Numbers *</label>
                                            <small class="text-muted ms-2">
                                                <span class="badge bg-primary" id="selected-pro-count">0</span> selected
                                            </small>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-secondary" id="btn-prev-step4">
                                                <i class="fas fa-arrow-left me-1"></i> Back
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" id="btn-next-step4" disabled>
                                                Next <i class="fas fa-arrow-right ms-1"></i>
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

                                        <div class="d-flex justify-content-end mt-2">
                                            <button type="button" class="btn btn-xs btn-outline-primary me-1" id="select-all-pro">
                                                <i class="fas fa-check-square"></i> All
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary" id="deselect-all-pro">
                                                <i class="fas fa-times-circle"></i> None
                                            </button>
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
                                        <div>
                                            <button type="button" class="btn btn-sm btn-secondary" id="btn-prev-step5">
                                                <i class="fas fa-arrow-left me-1"></i> Back
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
                                                        <th width="12%" class="text-center">Source PRO Numbers</th>
                                                        <th width="10%" class="text-center">Material Code</th>
                                                        <th width="20%" class="text-center">Description</th>
                                                        <th width="8%" class="text-center">MRP</th>
                                                        <th width="10%" class="text-center">Sales Order</th>
                                                        <th width="10%" class="text-center">Additional Info (sortf)</th>
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

    .nav-pills .nav-link.active .step-number {
        background-color: white;
        color: #0d6efd;
    }

    .nav-pills .nav-link {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }

    .card-header {
        padding: 0.5rem 1rem;
    }

    .card-body {
        padding: 0.75rem;
    }

    .checkbox-item {
        padding: 6px 8px;
        margin: 3px 0;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        transition: all 0.2s;
        display: flex;
        align-items: flex-start;
    }

    .checkbox-item:hover {
        background-color: #f8f9fa;
    }

    .checkbox-item.selected {
        background-color: #e7f1ff;
        border-color: #0d6efd;
    }

    .checkbox-item .material-code {
        font-weight: bold;
        font-family: monospace;
        color: #212529;
        font-size: 0.9rem;
    }

    .checkbox-item .material-desc {
        font-size: 0.9rem;
        color: #666;
        white-space: normal;
        word-wrap: break-word;
        line-height: 1.4;
        margin-top: 3px;
    }

    .selected-materials-item {
        padding: 4px 6px;
        margin: 2px 0;
        background-color: #e9ecef;
        border-radius: 3px;
        font-size: 0.8rem;
    }

    .form-check {
        margin-bottom: 0;
        width: 100%;
    }

    .form-check-input {
        margin-top: 2px;
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

    .material-type-badge {
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.8rem;
    }

    .material-type-badge:hover {
        opacity: 0.8;
    }

    .quantity-input {
        text-align: center;
        padding: 0.2rem 0.4rem;
        font-size: 0.9rem;
    }

    /* Hilangkan spinner pada input number */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
    }

    /* Alignment untuk table */
    #materials-table th,
    #materials-table td {
        padding: 0.4rem 0.5rem;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    #materials-table .text-center {
        text-align: center;
    }

    /* Styling untuk quantity columns */
    .quantity-cell {
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-weight: 500;
    }

    .source-count {
        font-size: 0.8rem;
        color: #666;
    }

    /* Highlight untuk materials yang dikonsolidasi */
    .consolidated-row {
        background-color: #fff8e1 !important;
    }

    .badge-sm {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }

    h4, h5, h6 {
        margin-bottom: 0.5rem;
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

    /* Style untuk PRO number dengan material info */
    .pro-item-info {
        font-size: 0.8rem;
        color: #666;
        margin-left: 10px;
        font-style: italic;
    }

    /* Style untuk simple list tanpa block cell */
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

    /* Description full view in table */
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

    /* Additional Info cell style */
    .additional-info-cell {
        max-width: 150px;
        word-wrap: break-word;
        font-size: 0.85rem;
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
            const plantCode = $(this).find(':selected').data('code');
            const plantText = $(this).find(':selected').text();

            if (selectedPlant) {
                $('#btn-next-step1').prop('disabled', false);
                $('#plant-info').html(`<span class="text-success"><strong>${plantText}</strong> (${plantCode})</span>`);
                loadMaterialTypes(selectedPlant);
            } else {
                $('#btn-next-step1').prop('disabled', true);
                $('#plant-info').html('<i class="fas fa-info-circle"></i> Select a plant to see details');
            }
        });

        $('#btn-next-step1').on('click', function() {
            if (!selectedPlant) return;
            currentStep = 2;
            updateStepNavigation();
        });

        // Step 2: Material type selection
        $('#select_all_types').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.material-type-checkbox').prop('checked', isChecked).trigger('change');
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

        // Step 3: Material selection
        $('#select-all-materials').on('click', function() {
            $('.material-checkbox').prop('checked', true).trigger('change');
        });

        $('#deselect-all-materials').on('click', function() {
            $('.material-checkbox').prop('checked', false).trigger('change');
        });

        $('#material-search').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            filterMaterials(searchTerm);
        });

        $('#clear-search').on('click', function() {
            $('#material-search').val('');
            filterMaterials('');
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

        // Step 4: PRO selection
        $('#select-all-pro').on('click', function() {
            $('.pro-checkbox').prop('checked', true).trigger('change');
        });

        $('#deselect-all-pro').on('click', function() {
            $('.pro-checkbox').prop('checked', false).trigger('change');
        });

        $('#btn-find-suitable-pros').on('click', function() {
            if (selectedMaterials.length === 0) {
                showNotification('Please select materials first in Step 3', 'warning', 3000);
                return;
            }
            suggestProNumbersForMaterials();
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
                    if (response.success && response.data && response.data.length > 0) {
                        loadedMaterials = response.data;
                        hideLoading();

                        // Group materials by code and combine quantities
                        const materialMap = {};

                        loadedMaterials.forEach(function(material) {
                            const key = material.material_code;
                            if (!materialMap[key]) {
                                materialMap[key] = {
                                    material_code: material.material_code,
                                    material_description: material.material_description,
                                    sortf: material.sortf, // Ensure sortf is included
                                    dispo: material.dispo,
                                    unit: material.unit === 'ST' ? 'PC' : material.unit,
                                    total_qty: 0,
                                    sources: [],
                                    sales_orders: [],
                                    pro_details: []
                                };
                            }

                            // Add quantity
                            materialMap[key].total_qty += parseFloat(material.total_qty) || 0;

                            // Add sources if not already present
                            if (material.sources) {
                                material.sources.forEach(function(source) {
                                    if (!materialMap[key].sources.includes(source)) {
                                        materialMap[key].sources.push(source);
                                    }
                                });
                            }

                            // Add sales orders if not already present
                            if (material.sales_orders) {
                                material.sales_orders.forEach(function(so) {
                                    if (so && !materialMap[key].sales_orders.includes(so)) {
                                        materialMap[key].sales_orders.push(so);
                                    }
                                });
                            }

                            // Add pro details - preserve sortf from pro details
                            if (material.pro_details) {
                                materialMap[key].pro_details = materialMap[key].pro_details.concat(material.pro_details);
                            }
                        });

                        // Convert back to array
                        loadedMaterials = Object.values(materialMap);

                        // Debug: Log loaded materials with sortf
                        console.log('📋 Loaded materials with sortf:', loadedMaterials.map(m => ({
                            code: m.material_code,
                            sortf: m.sortf,
                            has_sortf: !!m.sortf
                        })));

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
                    });

                    $('#selected-types-count').text(selectedMaterialTypes.length);
                    $('#btn-next-step2').prop('disabled', selectedMaterialTypes.length === 0);
                }
            },
            error: function(xhr) {
                console.error('Material types error:', xhr);
                showNotification('Failed to load material types', 'error', 4000);
            }
        });
    }

            // Fungsi loadMaterials() yang DIPERBAIKI
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
                    console.log('📋 Step 3 - Materials response:', response); // Debug log

                    if (response.success) {
                        allMaterials = response.materials;

                        let containerHtml = '';
                        if (allMaterials.length > 0) {
                            allMaterials.forEach(function(material) {
                                const displayMatnr = formatMaterialCodeForUI(material.matnr);
                                const isChecked = selectedMaterials.includes(material.matnr);
                                const typeDescription = getMaterialTypeDescription(material.mtart);

                                // MRP information
                                const mrp = material.dispo ? `<span class="badge mrp-badge badge-sm">${material.dispo}</span>` : '';

                                // Sales Order information
                                let salesOrder = '';
                                if (material.kdauf && material.kdpos) {
                                    salesOrder = `<span class="badge sales-order-badge badge-sm">${material.kdauf}-${material.kdpos}</span>`;
                                } else if (material.kdauf) {
                                    salesOrder = `<span class="badge sales-order-badge badge-sm">${material.kdauf}</span>`;
                                }

                                // PERBAIKAN: Tampilkan sortf dengan jelas
                                const sortfValue = material.sortf || '';
                                const sortfDisplay = sortfValue ?
                                    `<div class="mt-1">
                                        <small class="text-muted">
                                            <strong>Additional Info:</strong> ${sortfValue}
                                        </small>
                                    </div>` :
                                    '';

                                containerHtml += `
                                    <div class="simple-list-item">
                                        <div class="form-check">
                                            <input class="form-check-input material-checkbox" type="checkbox"
                                                id="mat_${material.matnr}" value="${material.matnr}" ${isChecked ? 'checked' : ''}>
                                            <label class="form-check-label" for="mat_${material.matnr}">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div style="flex: 1;">
                                                        <div>
                                                            <span class="material-code">${displayMatnr}</span>
                                                            <small class="text-muted ms-2">${material.mtart}</small>
                                                            ${mrp}
                                                            ${salesOrder}
                                                        </div>
                                                        <div class="material-desc table-description">${material.maktx}</div>
                                                        ${sortfDisplay}
                                                    </div>
                                                    <div class="text-end" style="min-width: 80px;">
                                                        <small class="text-muted d-block">${typeDescription}</small>
                                                    </div>
                                                </div>
                                            </label>
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
                        });

                        $('#selected-materials-count').text(selectedMaterials.length);
                        $('#btn-next-step3').prop('disabled', selectedMaterials.length === 0);
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
            $('.simple-list-item').show();
            return;
        }

        $('.simple-list-item').each(function() {
            const materialCode = $(this).find('.material-code').text().toLowerCase();
            const materialDesc = $(this).find('.material-desc').text().toLowerCase();
            const typeCode = $(this).find('small.text-muted').first().text().toLowerCase();
            const additionalInfo = $(this).find('small.text-muted.d-block').text().toLowerCase();

            if (materialCode.includes(searchTerm) ||
                materialDesc.includes(searchTerm) ||
                typeCode.includes(searchTerm) ||
                additionalInfo.includes(searchTerm)) {
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
        });

        $('#selected-pro-count').text(selectedProNumbers.length);
        $('#btn-next-step4').prop('disabled', selectedProNumbers.length === 0);
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
                    <td colspan="10" class="text-center text-muted py-3">
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

            const isConsolidated = displaySources.length > 1;
            const rowClass = isConsolidated ? 'consolidated-row' : '';

            html += `
                <tr class="${rowClass}">
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${sourceBadges}</td>
                    <td class="text-center"><code>${formatMaterialCodeForUI(material.material_code)}</code></td>
                    <td class="table-description">${material.material_description || 'No description'}</td>
                    <td class="text-center">
                        ${material.dispo ? `<span class="badge mrp-badge">${material.dispo}</span>` : '-'}
                    </td>
                    <td class="text-center">${salesOrderBadges}</td>
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

        // Prepare materials data dengan sortf
        loadedMaterials.forEach(function(material, index) {
            const qtyInput = $(`.requested-qty[data-index="${index}"]`);
            const requestedQty = parseFloat(qtyInput.val()) || 0;
            const isQtyEditable = !qtyInput.hasClass('qty-disabled');

            materialsData.push({
                material_code: material.material_code,
                material_code_display: formatMaterialCodeForUI(material.material_code),
                material_description: material.material_description,
                unit: material.unit,
                sortf: material.sortf, // Include sortf in data
                dispo: material.dispo,
                requested_qty: requestedQty,
                is_qty_editable: isQtyEditable,
                sources: material.sources || [],
                sales_orders: material.sales_orders || [],
                pro_details: material.pro_details || []
            });
        });

        // Debug logging
        console.log('📤 Sending materials data to server:', materialsData.map(m => ({
            code: m.material_code_display,
            sortf: m.sortf,
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
