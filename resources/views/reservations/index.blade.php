@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 py-2">
    {{-- Header Section --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h1 class="h3 fw-bold text-gray-800 mb-1">SAP Warehouse Reservations</h1>
                    <p class="text-muted mb-0">Manage and synchronize SAP reservation data</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reservations.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Create
                    </a>
                    <a href="{{ route('documents.index') }}" class="btn btn-info btn-sm">
                        <i class="fas fa-file me-1"></i>Documents
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card: Sync Form & Stats Combined --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header bg-light py-2 px-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-sync text-primary"></i>
                            <h6 class="mb-0 fw-bold">SAP Data Sync & Overview</h6>
                            <span class="badge bg-light text-dark border ms-2" id="pro-count-badge" style="display: none;">0 PRO</span>
                        </div>
                        <div class="d-flex gap-1">
                            <span class="badge bg-light text-dark border small">
                                <i class="fas fa-database me-1"></i>{{ $reservations->total() }} Records
                            </span>
                            <span class="badge bg-light text-dark border small">
                                <i class="fas fa-industry me-1"></i>{{ $reservations->unique('sap_plant')->count() }} Plants
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3">
                    {{-- Sync Form Row --}}
                    <form action="{{ route('reservations.sync') }}" method="POST" id="syncForm">
                        @csrf
                        <div class="row g-2 align-items-end">
                            {{-- Plant Input --}}
                            <div class="col-md-3">
                                <div class="mb-0">
                                    <label for="plant" class="form-label small fw-semibold mb-1">
                                        Plant Code <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-building text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" id="plant" name="plant"
                                               placeholder="e.g., 3000, 4000" required
                                               value="{{ old('plant', session('plant')) }}">
                                    </div>
                                    @error('plant')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Order Numbers --}}
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label for="order_number" class="form-label small fw-semibold mb-1">
                                        PRO Order Numbers <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <textarea class="form-control form-control-sm" id="order_number" name="order_number"
                                                  rows="1" placeholder="Enter multiple PRO numbers separated by commas or new lines"
                                                  required>{{ old('order_number', session('order')) }}</textarea>
                                        <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-1"
                                                type="button" id="paste-pro" title="Paste">
                                            <i class="fas fa-paste"></i>
                                        </button>
                                    </div>
                                    @error('order_number')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Sync Button --}}
                            <div class="col-md-3">
                                <div class="mb-0 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-sm btn-primary w-100" id="syncButton" disabled>
                                        <i class="fas fa-sync me-1"></i>Sync Data from SAP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Stats Row --}}
                    <div class="row g-2 mt-3">
                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="fas fa-database text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-0 small">Total Records</h6>
                                        <h5 class="mb-0 fw-bold" id="totalRecords">{{ $reservations->total() }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="fas fa-industry text-info"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-0 small">Plants</h6>
                                        <h5 class="mb-0 fw-bold" id="totalPlants">{{ $reservations->unique('sap_plant')->count() }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="fas fa-boxes text-success"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-0 small">Materials</h6>
                                        <h5 class="mb-0 fw-bold" id="totalMaterials">{{ $reservations->unique('matnr')->count() }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="fas fa-file-invoice text-danger"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-0 small">PRO Numbers</h6>
                                        <h5 class="mb-0 fw-bold" id="totalPro">{{ $reservations->unique('sap_order')->count() }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table Section --}}
    <div class="row">
        <div class="col-12">
            <div class="card border shadow-sm">
                <div class="card-header bg-white py-2 px-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h6 class="mb-0 fw-bold">Reservation Data</h6>
                            <span class="badge bg-light text-dark border ms-2" id="filteredCount">{{ $reservations->total() }}</span>
                        </div>

                        <div class="d-flex align-items-center">
                            {{-- Search Box --}}
                            <div class="input-group input-group-sm me-2" style="width: 200px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0"
                                       id="liveSearchInput" placeholder="Search...">
                                <button class="btn btn-outline-secondary border-start-0"
                                        type="button" id="clearSearchBtn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            {{-- Filter Dropdown --}}
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button" id="filterDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border shadow-sm">
                                    <li><a class="dropdown-item" href="#" data-filter="all">All Data</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    @foreach($plants as $plant)
                                    <li>
                                        <a class="dropdown-item" href="#" data-filter="plant-{{ $plant }}">
                                            Plant {{ $plant }}
                                            <span class="badge bg-secondary float-end">
                                                {{ $reservations->where('sap_plant', $plant)->count() }}
                                            </span>
                                        </a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="reservationsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Reservation No.</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Plant</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">PRO No.</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Finish Good</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Material</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Description</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase text-end">Quantity</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Unit</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Start Date</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Finish Date</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                @forelse($reservations as $reservation)
                                    @php
                                        // Format quantity
                                        $quantity = $reservation->psmng;
                                        $displayQuantity = $quantity;

                                        if (is_numeric($quantity)) {
                                            $floatValue = (float)$quantity;
                                            if (is_float($floatValue) && floor($floatValue) != $floatValue) {
                                                $displayQuantity = number_format($floatValue, 2, '.', ',');
                                            } else {
                                                $displayQuantity = number_format($floatValue, 0, '.', ',');
                                            }
                                        }

                                        // Unit conversion
                                        $unit = strtoupper(trim($reservation->meins ?? ''));
                                        $displayUnit = $unit === 'ST' ? 'PC' : $unit;

                                        // Finish Good - menggunakan kolom makhd dari database
                                        $finishGood = $reservation->makhd ?? '-';

                                        // Material number formatting
                                        $materialNumber = $reservation->matnr;
                                        $displayMaterial = preg_match('/^[0-9]+$/', $materialNumber)
                                            ? ltrim($materialNumber, '0')
                                            : $materialNumber;

                                        // Row class based on plant
                                        $rowClass = 'plant-' . ($reservation->sap_plant ?? 'unknown');

                                        // Date formatting
                                        $startDate = $reservation->gstrp ? \Carbon\Carbon::parse($reservation->gstrp) : null;
                                        $finishDate = $reservation->gltrp ? \Carbon\Carbon::parse($reservation->gltrp) : null;
                                    @endphp

                                    <tr class="searchable-row {{ $rowClass }}"
                                        data-search="{{ strtolower(implode(' ', [
                                            $reservation->rsnum,
                                            $reservation->sap_plant,
                                            $reservation->sap_order,
                                            $finishGood,
                                            $materialNumber,
                                            $reservation->maktx,
                                            $displayQuantity,
                                            $displayUnit,
                                            $reservation->gstrp,
                                            $reservation->gltrp
                                        ])) }}">
                                        <td class="py-2 px-3 border-bottom">
                                            <span class="text-mono">{{ $reservation->rsnum }}</span>
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            {{ $reservation->sap_plant ?? '-' }}
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            <span class="text-mono">{{ $reservation->sap_order ?? '-' }}</span>
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            {{ $finishGood }}
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            <span class="fw-medium">{{ $displayMaterial }}</span>
                                        </td>
                                        <td class="py-2 px-3 border-bottom" title="{{ $reservation->maktx }}">
                                            <div class="text-truncate" style="max-width: 200px;">
                                                {{ Str::limit($reservation->maktx, 50) }}
                                            </div>
                                        </td>
                                        <td class="py-2 px-3 border-bottom text-end">
                                            <span class="fw-semibold">{{ $displayQuantity }}</span>
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            {{ $displayUnit }}
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            @if($startDate)
                                                {{ $startDate->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            @if($finishDate)
                                                {{ $finishDate->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="noDataRow">
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <div class="py-3">
                                                <i class="fas fa-database fa-2x text-muted mb-3 opacity-50"></i>
                                                <p class="mb-2">No reservation data found</p>
                                                <p class="small text-muted">Please sync data using the form above</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($reservations->hasPages())
                        <div class="d-flex justify-content-between align-items-center p-2 border-top bg-white">
                            <div class="text-muted small">
                                Showing {{ $reservations->firstItem() }} to {{ $reservations->lastItem() }} of {{ $reservations->total() }} entries
                            </div>
                            <div>
                                {{ $reservations->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h6 class="mb-2">Syncing SAP Data</h6>
                <div class="progress mb-3" style="height: 4px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         id="syncProgress" style="width: 0%"></div>
                </div>
                <p class="text-muted small mb-2" id="progressDetails">Fetching your data from SAP system</p>
                <div class="d-flex justify-content-center gap-2">
                    <span class="badge bg-light text-dark border" id="progressProCount">0 PRO</span>
                    <span class="badge bg-light text-dark border" id="progressPlant">Plant: -</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title mb-0" id="resultTitle">Sync Result</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" id="resultContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="location.reload()">
                    Refresh Page
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Clean and minimal styles */
    .text-mono {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.875rem;
    }

    .table th {
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #495057;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .table td {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
        vertical-align: middle;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }

    .card {
        border-radius: 0.375rem;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .badge {
        font-weight: 500;
        padding: 0.25em 0.5em;
    }

    /* Plant-specific row styles - minimal */
    .plant-3000 {
        border-left: 2px solid #0d6efd;
    }

    .plant-4000 {
        border-left: 2px solid #198754;
    }

    .plant-5000 {
        border-left: 2px solid #fd7e14;
    }

    .searchable-row td:first-child {
        border-left-width: 2px;
        border-left-style: solid;
        border-left-color: transparent;
    }

    /* Stats card styling */
    .bg-light {
        background-color: #f8f9fa !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .table td, .table th {
            padding: 0.375rem 0.5rem;
            font-size: 0.8125rem;
        }

        .card-header .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .card-header .d-flex > div:last-child {
            margin-top: 0.5rem;
        }

        /* Adjust form layout for mobile */
        .row.g-2.align-items-end > div {
            margin-bottom: 0.5rem !important;
        }

        .row.g-2.align-items-end > .col-md-3:last-child {
            margin-bottom: 0 !important;
        }

        /* Make stats cards smaller on mobile */
        .border.rounded.p-2.bg-light {
            padding: 0.5rem !important;
        }

        .border.rounded.p-2.bg-light h5 {
            font-size: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fokuskan ke input plant saat halaman dimuat
        const plantInput = document.getElementById('plant');
        if (plantInput) {
            setTimeout(() => {
                plantInput.focus();
            }, 300);
        }

        // Elements
        const orderNumberInput = document.getElementById('order_number');
        const proCountBadge = document.getElementById('pro-count-badge');
        const pasteProBtn = document.getElementById('paste-pro');
        const syncButton = document.getElementById('syncButton');
        const syncForm = document.getElementById('syncForm');

        // Modal elements
        const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
        const resultModal = new bootstrap.Modal(document.getElementById('resultModal'));

        // Parse PRO numbers with support for multiple formats
        function parseProNumbers(inputText) {
            if (!inputText) return [];

            // Support for multiple formats: comma, newline, semicolon, space
            let normalized = inputText
                .replace(/[,;|\n\r]/g, ',')  // Replace all separators with commas
                .replace(/\s+/g, ',')        // Replace spaces with commas
                .replace(/,\s*,/g, ',')      // Remove empty entries
                .replace(/^\s+|\s+$/g, '');  // Trim whitespace

            let proNumbers = normalized.split(',')
                .map(pro => {
                    // Clean each PRO number
                    let cleaned = pro.trim();

                    // Remove any non-numeric characters (except for PRO prefixes if needed)
                    cleaned = cleaned.replace(/[^0-9]/g, '');

                    // Keep leading zeros for SAP format (12 digits)
                    return cleaned;
                })
                .filter(pro => pro.length > 0 && /^\d+$/.test(pro));

            // Remove duplicates and sort numerically
            proNumbers = [...new Set(proNumbers)];
            proNumbers.sort((a, b) => {
                // Convert to numbers for proper numerical sorting
                const numA = parseInt(a, 10);
                const numB = parseInt(b, 10);
                return numA - numB;
            });

            return proNumbers;
        }

        // Update PRO badge and sync button state
        function updateProBadge() {
            const inputText = orderNumberInput.value;
            const proNumbers = parseProNumbers(inputText);
            const count = proNumbers.length;

            // Update badge
            if (count > 0) {
                proCountBadge.textContent = `${count} PRO`;
                proCountBadge.style.display = 'inline-block';
                syncButton.disabled = false;
            } else {
                proCountBadge.style.display = 'none';
                syncButton.disabled = true;
            }

            return proNumbers;
        }

        // Event listeners for sync form
        orderNumberInput.addEventListener('input', updateProBadge);

        orderNumberInput.addEventListener('blur', function() {
            const proNumbers = parseProNumbers(this.value);
            if (proNumbers.length > 0) {
                // Format with each PRO on new line for better readability
                this.value = proNumbers.join('\n');
                updateProBadge();
            }
        });

        pasteProBtn.addEventListener('click', async function() {
            try {
                const text = await navigator.clipboard.readText();
                if (text.trim()) {
                    orderNumberInput.value = text.trim();
                    updateProBadge();
                }
            } catch (err) {
                console.error('Clipboard error:', err);
            }
        });

        // Form submission with AJAX
        if (syncForm) {
            syncForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const plant = document.getElementById('plant').value;
                const orderInput = document.getElementById('order_number').value;
                const proNumbers = parseProNumbers(orderInput);

                // Validation
                if (!plant) {
                    alert('Plant is required');
                    return false;
                }

                if (proNumbers.length === 0) {
                    alert('Enter at least one PRO number');
                    return false;
                }

                console.log('Submitting PRO numbers:', proNumbers);

                // Show progress modal
                document.getElementById('progressDetails').textContent = `Processing ${proNumbers.length} PRO numbers`;
                document.getElementById('progressProCount').textContent = `${proNumbers.length} PRO`;
                document.getElementById('progressPlant').textContent = `Plant: ${plant}`;
                document.getElementById('syncProgress').style.width = '30%';

                progressModal.show();

                // Update button state
                syncButton.disabled = true;

                // Prepare form data
                const formData = new FormData();
                formData.append('plant', plant);
                formData.append('order_number', orderInput);
                formData.append('_token', document.querySelector('input[name="_token"]').value);
                formData.append('pro_numbers_count', proNumbers.length);

                // AJAX request
                fetch('{{ route("reservations.sync") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(response => {
                    document.getElementById('syncProgress').style.width = '100%';

                    setTimeout(function() {
                        progressModal.hide();

                        if (response.success) {
                            // Show success result
                            showResultModal(
                                'Sync Successful',
                                `
                                <div class="alert alert-success mb-3">
                                    Data synced successfully
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="border p-2 text-center">
                                            <h6 class="text-primary mb-1">${response.synced_count || 0}</h6>
                                            <small class="text-muted">Records Saved</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border p-2 text-center">
                                            <h6 class="text-success mb-1">${proNumbers.length}</h6>
                                            <small class="text-muted">PRO Numbers</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-top pt-2">
                                    <small class="text-muted">Plant: ${plant}</small><br>
                                    <small class="text-muted">PROs processed: ${proNumbers.length}</small>
                                </div>
                                `
                            );

                            // Reload page after 2 seconds if successful
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            // Show error result
                            showResultModal(
                                'Sync Failed',
                                `
                                <div class="alert alert-danger mb-3">
                                    ${response.message || 'Sync failed'}
                                </div>
                                <div class="border p-2">
                                    <small class="text-muted">Error details:</small>
                                    <p class="mb-0 mt-1">${response.error || 'Unknown error'}</p>
                                </div>
                                `
                            );
                        }
                    }, 500);
                })
                .catch(error => {
                    progressModal.hide();
                    console.error('Sync error:', error);

                    showResultModal(
                        'Sync Error',
                        `
                        <div class="alert alert-danger">
                            Network error occurred: ${error.message}
                        </div>
                        `
                    );
                })
                .finally(() => {
                    // Reset button
                    syncButton.disabled = false;
                });

                return false;
            });
        }

        // Initialize
        updateProBadge();

        // Live search functionality
        const liveSearchInput = document.getElementById('liveSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const tableRows = document.querySelectorAll('.searchable-row');
        const filteredCount = document.getElementById('filteredCount');
        const allRows = Array.from(tableRows);

        if (liveSearchInput) {
            liveSearchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                let visibleCount = 0;

                allRows.forEach(row => {
                    const rowData = row.getAttribute('data-search').toLowerCase();
                    const isVisible = rowData.includes(term);
                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleCount++;
                });

                // Update count
                filteredCount.textContent = visibleCount;

                // Show/hide no data row
                const noDataRow = document.getElementById('noDataRow');
                if (noDataRow) {
                    if (term === '' || visibleCount > 0) {
                        noDataRow.style.display = 'none';
                    } else {
                        noDataRow.style.display = '';
                    }
                }
            });

            clearSearchBtn.addEventListener('click', function() {
                liveSearchInput.value = '';
                liveSearchInput.dispatchEvent(new Event('input'));
                liveSearchInput.focus();
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === '/') {
                    e.preventDefault();
                    liveSearchInput.focus();
                    liveSearchInput.select();
                }

                if (e.key === 'Escape' && document.activeElement === liveSearchInput) {
                    liveSearchInput.value = '';
                    liveSearchInput.dispatchEvent(new Event('input'));
                }
            });
        }

        // Filter by plant
        document.querySelectorAll('[data-filter]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter');

                let visibleCount = 0;
                allRows.forEach(row => {
                    if (filter === 'all' || row.classList.contains(filter)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                filteredCount.textContent = visibleCount;
                if (liveSearchInput) liveSearchInput.value = '';

                // Update dropdown text
                const filterText = this.textContent.split('\n')[0];
                document.getElementById('filterDropdown').innerHTML =
                    `<i class="fas fa-filter me-1"></i>${filterText}`;
            });
        });
    });

    // Helper functions
    function showResultModal(title, content) {
        $('#resultTitle').text(title);
        $('#resultContent').html(content);
        $('#resultModal').modal('show');
    }
</script>
@endpush
@endsection
