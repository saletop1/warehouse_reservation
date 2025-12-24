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
                            <h6 class="mb-0 fw-bold">SAP Data Sync</h6>
                            <span class="badge bg-light text-dark border ms-2" id="pro-count-badge" style="display: none;">0 PRO</span>
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
                                    <div class="d-flex">
                                        <textarea class="form-control form-control-sm flex-grow-1" id="order_number" name="order_number"
                                                  rows="1" placeholder="Enter multiple PRO numbers separated by commas or new lines"
                                                  required>{{ old('order_number', session('order')) }}</textarea>
                                        <button class="btn btn-sm btn-outline-secondary ms-1"
                                                type="button" id="paste-pro" title="Paste PRO numbers">
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
                            {{-- Clear Sync Button --}}
                            <button class="btn btn-warning btn-sm ms-3" id="clearSyncBtn" title="Clear all synced data">
                                <i class="fas fa-broom me-1"></i>Clear Sync
                            </button>
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
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive sticky-table-container">
                        <table class="table table-sm table-hover mb-0" id="reservationsTable">
                            <thead class="table-light sticky-header">
                                <tr>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Reservation No.</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Plant</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">PRO No.</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">MRP Pro</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">MRP Comp</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Sales Order</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Material PRO</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Material Comp</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase">Description Comp</th>
                                    <th class="py-2 px-3 border-bottom small fw-semibold text-uppercase text-end">QTY REQ</th>
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

                                        // Material PRO - menggunakan kolom makhd dari database
                                        $finishGood = $reservation->makhd ?? '-';

                                        // Material number formatting
                                        $materialNumber = $reservation->matnr;
                                        $displayMaterial = preg_match('/^[0-9]+$/', $materialNumber)
                                            ? ltrim($materialNumber, '0')
                                            : $materialNumber;

                                        // MRP dari kolom dispo
                                        $mrp = $reservation->dispo ?? '-';
                                        // DISPC (MRP Comp) - tambah kolom ini
                                        $mrpc = $reservation->dispc ?? '-';

                                        // Sales Order - combine kdauf dan kdpos
                                        $salesOrder = '';
                                        if ($reservation->kdauf && $reservation->kdpos) {
                                            $salesOrder = $reservation->kdauf . '-' . $reservation->kdpos;
                                        } elseif ($reservation->kdauf) {
                                            $salesOrder = $reservation->kdauf;
                                        } elseif ($reservation->kdpos) {
                                            $salesOrder = $reservation->kdpos;
                                        } else {
                                            $salesOrder = '-';
                                        }

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
                                            $mrp,
                                            $mrpc, // TAMBAHAN: Tambahkan mrpc ke data search
                                            $salesOrder,
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
                                            <span class="badge bg-info">{{ $mrp }}</span>
                                        </td>
                                        <!-- TAMBAHAN: Kolom DISPC (MRP Comp) -->
                                        <td class="py-2 px-3 border-bottom">
                                            @if($mrpc && $mrpc != '-')
                                                <span class="badge bg-warning text-dark">{{ $mrpc }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        <td class="py-2 px-3 border-bottom">
                                            <span class="text-mono">{{ $salesOrder }}</span>
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            {{ $finishGood }}
                                        </td>
                                        <td class="py-2 px-3 border-bottom">
                                            <span class="fw-medium">{{ $displayMaterial }}</span>
                                        </td>
                                        <td class="py-2 px-3 border-bottom" title="{{ $reservation->maktx }}">
                                            <div class="description-full" style="white-space: normal; max-width: 300px;">
                                                {{ $reservation->maktx }}
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
                                    <!-- Ubah colspan dari 12 menjadi 13 karena kita menambah 1 kolom -->
                                    <tr id="noDataRow">
                                        <td colspan="13" class="text-center text-muted py-4">
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
                <div id="lottie-container"></div>
                <div id="fallback-animation" style="display: none;">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <h6 class="mb-2 mt-3">Syncing SAP Data</h6>
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

    /* Description full view */
    .description-full {
        word-wrap: break-word;
        white-space: normal;
        line-height: 1.4;
    }

    /* Lottie animation container */
    #lottie-container {
        width: 120px;
        height: 120px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Sticky table styles */
    .sticky-table-container {
        max-height: 60vh;
        overflow: auto;
        position: relative;
    }

    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8f9fa;
    }

    .sticky-header th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
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

        /* Adjust Lottie container for mobile */
        #lottie-container {
            width: 100px;
            height: 100px;
        }

        .description-full {
            max-width: 150px !important;
        }

        /* Adjust sticky table height for mobile */
        .sticky-table-container {
            max-height: 50vh;
        }
    }

    /* Ensure table is responsive on small screens */
    @media (max-width: 576px) {
        .sticky-table-container {
            max-height: 45vh;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
    }
</style>
@endpush

@push('scripts')
{{-- Load Lottie library --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fokuskan ke input plant saat halaman dimuat
    const plantInput = document.getElementById('plant');
    if (plantInput) {
        plantInput.focus();
    }

    // Elements
    const orderNumberInput = document.getElementById('order_number');
    const proCountBadge = document.getElementById('pro-count-badge');
    const pasteProBtn = document.getElementById('paste-pro');
    const syncButton = document.getElementById('syncButton');
    const syncForm = document.getElementById('syncForm');
    const clearSyncBtn = document.getElementById('clearSyncBtn');

    // Modal elements
    const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
    const progressModalElement = document.getElementById('progressModal');

    // Variable untuk menyimpan instance Lottie
    let lottieAnimation = null;

    // Fungsi untuk memuat animasi Lottie
    function loadLottieAnimation() {
        const container = document.getElementById('lottie-container');
        const fallback = document.getElementById('fallback-animation');

        // Kosongkan container
        container.innerHTML = '';

        // Cek apakah file animasi ada di server
        const animationUrl = "{{ asset('animation/Pacman.json') }}";

        // Coba load animasi Lottie
        try {
            lottieAnimation = lottie.loadAnimation({
                container: container,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: animationUrl,
                rendererSettings: {
                    progressiveLoad: true,
                    hideOnTransparent: true
                }
            });

            // Sembunyikan fallback spinner
            fallback.style.display = 'none';
            container.style.display = 'block';

            // Event listener untuk error animasi
            lottieAnimation.addEventListener('error', function(error) {
                showFallbackAnimation();
            });

            // Event listener ketika data gagal dimuat
            lottieAnimation.addEventListener('data_failed', function() {
                showFallbackAnimation();
            });

        } catch (error) {
            showFallbackAnimation();
        }
    }

    // Fungsi untuk menampilkan fallback spinner
    function showFallbackAnimation() {
        const container = document.getElementById('lottie-container');
        const fallback = document.getElementById('fallback-animation');

        container.style.display = 'none';
        fallback.style.display = 'block';
    }

    // Event untuk menampilkan modal progress
    progressModalElement.addEventListener('show.bs.modal', function() {
        loadLottieAnimation();
    });

    // Event untuk menyembunyikan modal progress
    progressModalElement.addEventListener('hidden.bs.modal', function() {
        // Hentikan animasi Lottie
        if (lottieAnimation) {
            lottieAnimation.destroy();
            lottieAnimation = null;
        }
    });

    // Parse PRO numbers with support for multiple formats
    function parseProNumbers(inputText) {
        if (!inputText) return [];

        // Support for multiple formats: comma, newline, semicolon, space
        let normalized = inputText
            .replace(/[,;|\n\r]/g, ',')
            .replace(/\s+/g, ',')
            .replace(/,\s*,/g, ',')
            .replace(/^\s+|\s+$/g, '');

        let proNumbers = normalized.split(',')
            .map(pro => {
                let cleaned = pro.trim();
                cleaned = cleaned.replace(/[^0-9]/g, '');
                return cleaned;
            })
            .filter(pro => pro.length > 0 && /^\d+$/.test(pro));

        proNumbers = [...new Set(proNumbers)];
        proNumbers.sort((a, b) => {
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
            this.value = proNumbers.join('\n');
            updateProBadge();
        }
    });

    // Paste button click handler
    pasteProBtn.addEventListener('click', async function() {
        try {
            const text = await navigator.clipboard.readText();
            if (text.trim()) {
                const proNumbers = parseProNumbers(text);
                if (proNumbers.length > 0) {
                    orderNumberInput.value = proNumbers.join('\n');
                    updateProBadge();

                    const originalTitle = this.getAttribute('title');
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    this.setAttribute('title', `Pasted ${proNumbers.length} PRO numbers`);

                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-paste"></i>';
                        this.setAttribute('title', originalTitle);
                    }, 1500);
                }
            }
        } catch (err) {
            // Tidak menampilkan pesan apa-apa
        }
    });

    // Clear Sync Button handler
    if (clearSyncBtn) {
        clearSyncBtn.addEventListener('click', function() {
            const originalText = clearSyncBtn.innerHTML;
            clearSyncBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Clearing...';
            clearSyncBtn.disabled = true;

            fetch('{{ route("reservations.clearAllSyncData") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    location.reload();
                }
            })
            .catch(error => {
                // Tidak menampilkan pesan error
            })
            .finally(() => {
                clearSyncBtn.innerHTML = originalText;
                clearSyncBtn.disabled = false;
            });
        });
    }

    // Form submission with AJAX
    if (syncForm) {
        syncForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const plant = document.getElementById('plant').value.trim();
            const orderInput = document.getElementById('order_number').value;
            const proNumbers = parseProNumbers(orderInput);

            // Validation for empty plant
            if (!plant) {
                document.getElementById('plant').focus();
                return false;
            }

            if (proNumbers.length === 0) {
                document.getElementById('order_number').focus();
                return false;
            }

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
                progressModal.hide();
                location.reload();
            })
            .catch(error => {
                progressModal.hide();
                // Tidak menampilkan pesan error
            })
            .finally(() => {
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
});
</script>
@endpush
@endsection
