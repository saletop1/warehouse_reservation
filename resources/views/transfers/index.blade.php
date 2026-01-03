@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                <div class="mb-2 mb-md-0">
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="fas fa-exchange-alt me-2 text-primary"></i>Transfer List
                    </h2>
                    <p class="text-muted mb-0">
                        Total: {{ $transfers->count() }} transfers • Last sync: {{ now()->format('H:i') }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <button class="btn btn-outline-primary" onclick="syncTransfers()">
                        <i class="fas fa-sync-alt me-1"></i>Sync
                    </button>
                    <button class="btn btn-primary" onclick="exportTransfers()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-filter me-2 text-primary"></i>Filters
                </h6>
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="fas fa-sliders-h me-1"></i>Toggle
                </button>
            </div>

            <div class="collapse show" id="filterCollapse">
                <form method="GET" action="{{ route('transfers.index') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label text-muted mb-1">Transfer/Doc No</label>
                            <input type="text" name="search" class="form-control"
                                   value="{{ request('search') }}" placeholder="TRMG... or doc no...">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label text-muted mb-1">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                <option value="SUBMITTED" {{ request('status') == 'SUBMITTED' ? 'selected' : '' }}>Submitted</option>
                                <option value="FAILED" {{ request('status') == 'FAILED' ? 'selected' : '' }}>Failed</option>
                                <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Pending</option>
                                <option value="PROCESSING" {{ request('status') == 'PROCESSING' ? 'selected' : '' }}>Processing</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label text-muted mb-1">Plant Supply</label>
                            <input type="text" name="plant_supply" class="form-control"
                                   value="{{ request('plant_supply') }}" placeholder="e.g., 3000">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label text-muted mb-1">Plant Dest</label>
                            <input type="text" name="plant_destination" class="form-control"
                                   value="{{ request('plant_destination') }}" placeholder="e.g., 3100">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label text-muted mb-1">Date Range</label>
                            <div class="input-group">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                <span class="input-group-text">to</span>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-3 mt-2 justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                            <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Transfers Table with Sticky Header --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-container" style="max-height: 700px; overflow-y: auto;">
                <table class="table table-hover mb-0">
                    <thead class="table-light sticky-top" style="top: 0; z-index: 10;">
                        <tr>
                            <th class="ps-3 py-3 fw-semibold" style="width: 15%; min-width: 140px">
                                <i class="fas fa-hashtag me-2 text-muted"></i>Transfer No
                            </th>
                            <th class="py-3 fw-semibold" style="width: 12%; min-width: 110px">Document</th>
                            <th class="py-3 fw-semibold" style="width: 10%; min-width: 100px">Status</th>
                            <th class="py-3 fw-semibold" style="width: 18%; min-width: 140px">Plants</th>
                            <th class="py-3 fw-semibold text-center" style="width: 8%; min-width: 80px">Items</th>
                            <th class="py-3 fw-semibold text-center" style="width: 10%; min-width: 90px">Quantity</th>
                            <th class="py-3 fw-semibold" style="width: 12%; min-width: 110px">Created</th>
                            <th class="py-3 fw-semibold text-center" style="width: 15%; min-width: 120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $displayedTransfers = [];
                        @endphp

                        @forelse($transfers as $transfer)
                            @php
                                $transferKey = $transfer->transfer_no . '_' . $transfer->plant_destination;

                                if (in_array($transferKey, $displayedTransfers) ||
                                    empty($transfer->plant_destination) ||
                                    $transfer->total_items == 0 ||
                                    $transfer->total_qty == 0) {
                                    continue;
                                }

                                $displayedTransfers[] = $transferKey;
                            @endphp

                            <tr class="align-middle">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            @if($transfer->status == 'COMPLETED')
                                            <i class="fas fa-check-circle text-success"></i>
                                            @elseif($transfer->status == 'SUBMITTED')
                                            <i class="fas fa-clock text-warning"></i>
                                            @elseif($transfer->status == 'FAILED')
                                            <i class="fas fa-times-circle text-danger"></i>
                                            @else
                                            <i class="fas fa-question-circle text-secondary"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $transfer->transfer_no ?? 'N/A' }}</div>
                                            <div class="text-muted">
                                                {{ \Carbon\Carbon::parse($transfer->created_at)->format('H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($transfer->document)
                                    <a href="{{ route('documents.show', $transfer->document->id) }}"
                                       class="text-decoration-none text-dark fw-medium d-block">
                                        {{ $transfer->document_no }}
                                    </a>
                                    <div class="text-muted">
                                        Plant: {{ $transfer->document->plant ?? 'N/A' }}
                                    </div>
                                    @else
                                    <div class="text-muted">{{ $transfer->document_no }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusConfig = [
                                            'COMPLETED' => ['class' => 'success', 'icon' => 'check-circle', 'label' => 'Done'],
                                            'SUBMITTED' => ['class' => 'warning', 'icon' => 'clock', 'label' => 'Sent'],
                                            'FAILED' => ['class' => 'danger', 'icon' => 'times-circle', 'label' => 'Failed'],
                                            'PENDING' => ['class' => 'secondary', 'icon' => 'hourglass-half', 'label' => 'Pending'],
                                            'PROCESSING' => ['class' => 'info', 'icon' => 'sync-alt', 'label' => 'Processing'],
                                        ];
                                        $config = $statusConfig[$transfer->status] ?? ['class' => 'secondary', 'icon' => 'question-circle', 'label' => $transfer->status];
                                    @endphp
                                    <span class="badge bg-{{ $config['class'] }}-subtle text-{{ $config['class'] }} border border-{{ $config['class'] }}-subtle px-3 py-1">
                                        <i class="fas fa-{{ $config['icon'] }} me-1"></i>
                                        {{ $config['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="text-center me-2">
                                            <div class="badge bg-info-subtle text-info border px-3 py-1">
                                                {{ $transfer->plant_supply ?? 'N/A' }}
                                            </div>
                                            <div class="text-muted mt-1">Supply</div>
                                        </div>
                                        @if(!empty($transfer->plant_destination) && $transfer->plant_destination != $transfer->plant_supply)
                                        <div class="me-2">
                                            <i class="fas fa-arrow-right text-muted"></i>
                                        </div>
                                        <div class="text-center">
                                            <div class="badge bg-primary-subtle text-primary border px-3 py-1">
                                                {{ $transfer->plant_destination ?? 'N/A' }}
                                            </div>
                                            <div class="text-muted mt-1">Dest</div>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3 py-1">
                                        {{ $transfer->total_items ?? 0 }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold">{{ number_format($transfer->total_qty ?? 0) }}</div>
                                    <div class="text-muted">{{ $transfer->items->first()->unit ?? 'PC' }}</div>
                                </td>
                                <td>
                                    <div>
                                        <div>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/y') }}</div>
                                        <div class="text-muted">{{ $transfer->created_by_name ?? 'System' }}</div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-outline-primary view-transfer"
                                                data-id="{{ $transfer->id }}"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($transfer->document_id)
                                        <a href="{{ route('documents.show', $transfer->document_id) }}"
                                           class="btn btn-outline-info"
                                           title="View Document">
                                            <i class="fas fa-file-alt"></i>
                                        </a>
                                        @endif
                                        <button class="btn btn-outline-secondary dropdown-toggle"
                                                type="button"
                                                data-bs-toggle="dropdown">
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <li>
                                                <button class="dropdown-item" onclick="printTransfer({{ $transfer->id }})">
                                                    <i class="fas fa-print me-2 text-muted"></i>Print
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" onclick="copyTransferNo('{{ $transfer->transfer_no }}')">
                                                    <i class="fas fa-copy me-2 text-muted"></i>Copy No
                                                </button>
                                            </li>
                                            @if($transfer->status == 'FAILED')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger" onclick="retryTransfer({{ $transfer->id }})">
                                                    <i class="fas fa-redo me-2"></i>Retry
                                                </button>
                                            </li>
                                            @endif
                                            @if(empty($transfer->plant_destination))
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-warning" onclick="fixTransferData({{ $transfer->id }})">
                                                    <i class="fas fa-wrench me-2"></i>Fix Data
                                                </button>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-exchange-alt fa-3x text-muted opacity-25 mb-3"></i>
                                        <h5 class="text-muted mb-2">No transfers found</h5>
                                        <p class="text-muted mb-3">No valid transfer records available</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Row Count Summary --}}
        @if($transfers->count() > 0)
        <div class="card-footer bg-transparent border-top py-3 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Showing {{ $displayedTransfers ? count($displayedTransfers) : 0 }} transfers
                </div>
                <div class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>Scroll to see more rows
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Transfer Detail Modal (Full Data) --}}
<div class="modal fade" id="transferDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom bg-light py-3">
                <div class="d-flex align-items-center w-100">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0">
                            <i class="fas fa-exchange-alt me-2 text-primary"></i>Transfer Details
                        </h5>
                        <div class="text-muted" id="transferNoLabel">Loading...</div>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0" id="transferDetailContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

{{-- Export Modal --}}
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-semibold">
                    <i class="fas fa-download me-2 text-primary"></i>Export Transfers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label">Export Format</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary flex-fill" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                        <button class="btn btn-outline-success flex-fill" onclick="exportToCSV()">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </button>
                        <button class="btn btn-outline-danger flex-fill" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="date" id="exportDateFrom" class="form-control" value="{{ now()->subDays(7)->format('Y-m-d') }}">
                        <span class="input-group-text">to</span>
                        <input type="date" id="exportDateTo" class="form-control" value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status Filter</label>
                    <select id="exportStatus" class="form-select">
                        <option value="">All Status</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="SUBMITTED">Submitted</option>
                        <option value="FAILED">Failed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer py-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmExport()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Improved Font Sizes */
body {
    font-size: 14px;
}

.table {
    font-size: 13.5px;
}

.table th {
    font-size: 13px;
    font-weight: 600;
}

.badge {
    font-size: 12.5px;
}

.small, .text-muted {
    font-size: 12.5px;
}

.btn {
    font-size: 13px;
}

.form-control, .form-select {
    font-size: 13.5px;
}

/* Table Container */
.table-container {
    max-height: 700px;
    overflow-y: auto;
    position: relative;
}

.table-container thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    box-shadow: 0 1px 0 #dee2e6;
}

/* Table Improvements */
.table > :not(caption) > * > * {
    padding: 0.75rem 0.5rem;
}

.table td {
    vertical-align: middle;
}

/* Card Padding */
.card-body {
    padding: 1rem;
}

/* Button Groups */
.btn-group .btn {
    padding: 0.375rem 0.75rem;
}

/* Badge Padding */
.badge {
    padding: 0.35em 0.65em;
}

/* Modal */
.modal-body {
    font-size: 14px;
}

.modal-content .table {
    font-size: 13px;
}

/* Custom Scrollbar */
.table-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    body {
        font-size: 13.5px;
    }

    .table {
        font-size: 13px;
    }

    .table-container {
        max-height: 550px;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .table-container {
        max-height: 500px;
    }

    .table > :not(caption) > * > * {
        padding: 0.5rem 0.25rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View Transfer Details
    document.querySelectorAll('.view-transfer').forEach(btn => {
        btn.addEventListener('click', function() {
            const transferId = this.dataset.id;
            loadTransferDetails(transferId);
        });
    });

    // Load Transfer Details with Complete Data
    function loadTransferDetails(transferId) {
        const modal = new bootstrap.Modal(document.getElementById('transferDetailModal'));
        const contentDiv = document.getElementById('transferDetailContent');

        // Show loading state
        contentDiv.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted">Loading transfer details...</p>
            </div>
        `;

        modal.show();

        fetch(`/transfers/${transferId}?_details=1`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const transfer = data.data;
                    document.getElementById('transferNoLabel').textContent = transfer.transfer_no || 'N/A';
                    contentDiv.innerHTML = generateTransferDetailContent(transfer);
                } else {
                    contentDiv.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                            <h5 class="text-danger">Failed to load transfer details</h5>
                            <p class="text-muted">${data.message || 'Unknown error'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                        <h5 class="text-danger">Error loading transfer details</h5>
                        <p class="text-muted">${error.message}</p>
                        <button class="btn btn-outline-primary mt-3" onclick="loadTransferDetails(${transferId})">
                            <i class="fas fa-redo me-1"></i>Retry
                        </button>
                    </div>
                `;
            });
    }

    // Generate Complete Transfer Detail Content
    function generateTransferDetailContent(transfer) {
        const formattedDate = transfer.created_at ?
            new Date(transfer.created_at).toLocaleString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }) : 'N/A';

        const completedDate = transfer.completed_at ?
            new Date(transfer.completed_at).toLocaleString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }) : 'Not completed';

        return `
            <div class="transfer-detail">
                {{-- Header Info --}}
                <div class="p-4 border-bottom bg-light-subtle">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                                    <i class="fas fa-exchange-alt fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-0">${transfer.transfer_no || 'N/A'}</h4>
                                    <div class="text-muted">
                                        <i class="fas fa-file-alt me-1"></i>Doc: ${transfer.document_no || 'N/A'}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="d-inline-block">
                                <span class="badge ${getStatusClass(transfer.status)} fs-6 px-4 py-2">
                                    <i class="fas fa-${getStatusIcon(transfer.status)} me-1"></i>
                                    ${transfer.status || 'UNKNOWN'}
                                </span>
                            </div>
                            <div class="mt-2 text-muted">
                                Created: ${formattedDate}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Main Content --}}
                <div class="p-4">
                    {{-- Transfer Information --}}
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card border h-100">
                                <div class="card-header bg-transparent py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>Transfer Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="text-muted">Move Type</div>
                                            <div class="fw-semibold">${transfer.move_type || '311'}</div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-muted">Total Items</div>
                                            <div class="fw-semibold">${transfer.total_items || 0}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Total Quantity</div>
                                            <div class="fw-bold fs-5">${formatNumber(transfer.total_qty || 0)}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Completion</div>
                                            <div>
                                                <span class="badge bg-success-subtle text-success">
                                                    ${transfer.completed_at ? 'Completed' : 'In Progress'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-header bg-transparent py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-building me-2 text-primary"></i>Plant Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="text-muted mb-2">Supply Plant</div>
                                            <div class="d-flex align-items-center">
                                                <div class="badge bg-info-subtle text-info border px-4 py-2 me-2 fs-6">
                                                    ${transfer.plant_supply || 'N/A'}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-muted mb-2">Destination Plant</div>
                                            <div class="d-flex align-items-center">
                                                <div class="badge bg-primary-subtle text-primary border px-4 py-2 me-2 fs-6">
                                                    ${transfer.plant_destination || 'N/A'}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted mb-2">Created By</div>
                                            <div class="fw-semibold">${transfer.created_by_name || 'System'}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted mb-2">Completed At</div>
                                            <div class="fw-semibold">${completedDate}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Transfer Items Table --}}
                    <div class="card border">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold">
                                    <i class="fas fa-boxes me-2 text-primary"></i>Transfer Items
                                    <span class="badge bg-primary-subtle text-primary ms-2">
                                        ${transfer.items?.length || 0} items
                                    </span>
                                </h6>
                                <div class="text-muted">
                                    Total: ${formatNumber(transfer.total_qty || 0)}
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 py-2 fw-semibold">#</th>
                                            <th class="py-2 fw-semibold">Material Code</th>
                                            <th class="py-2 fw-semibold">Description</th>
                                            <th class="py-2 fw-semibold">Batch</th>
                                            <th class="py-2 fw-semibold">Storage Loc</th>
                                            <th class="py-2 fw-semibold text-end">Quantity</th>
                                            <th class="py-2 fw-semibold">Unit</th>
                                            <th class="py-2 fw-semibold">Dest. SLOC</th>
                                            <th class="pe-3 py-2 fw-semibold text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${generateItemsTable(transfer.items || [])}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    ${transfer.remarks || transfer.sap_message ? `
                    {{-- Additional Information --}}
                    <div class="row mt-4">
                        ${transfer.remarks ? `
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card border">
                                <div class="card-header bg-transparent py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-sticky-note me-2 text-warning"></i>Remarks
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">${transfer.remarks}</p>
                                </div>
                            </div>
                        </div>
                        ` : ''}

                        ${transfer.sap_message ? `
                        <div class="${transfer.remarks ? 'col-md-6' : 'col-12'}">
                            <div class="card border">
                                <div class="card-header bg-transparent py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-comment-alt me-2 text-info"></i>SAP Message
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <pre class="mb-0 bg-light p-3 rounded" style="white-space: pre-wrap;">${transfer.sap_message}</pre>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    ` : ''}

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-3 mt-4">
                        <button class="btn btn-outline-primary" onclick="printTransfer(${transfer.id})">
                            <i class="fas fa-print me-1"></i>Print Transfer
                        </button>
                        <button class="btn btn-outline-secondary" onclick="copyTransferDetails(${transfer.id})">
                            <i class="fas fa-copy me-1"></i>Copy Details
                        </button>
                        ${transfer.document_id ? `
                        <a href="/documents/${transfer.document_id}" class="btn btn-outline-info ms-auto">
                            <i class="fas fa-file-alt me-1"></i>View Document
                        </a>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    function generateItemsTable(items) {
        if (!items || items.length === 0) {
            return `
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="fas fa-box-open me-1"></i>No items found
                    </td>
                </tr>
            `;
        }

        return items.map((item, index) => {
            const materialCode = item.material_code || 'N/A';
            const formattedCode = /^\d+$/.test(materialCode) ?
                materialCode.replace(/^0+/, '') : materialCode;

            return `
                <tr>
                    <td class="ps-3">${index + 1}</td>
                    <td>
                        <code>${formattedCode}</code>
                    </td>
                    <td>${item.material_description || '-'}</td>
                    <td>${item.batch || '-'}</td>
                    <td>${item.storage_location || '-'}</td>
                    <td class="text-end fw-semibold">${formatNumber(item.quantity || 0)}</td>
                    <td>${item.unit || 'PC'}</td>
                    <td>${item.sloc_destination || '-'}</td>
                    <td class="pe-3 text-center">
                        <span class="badge ${getItemStatusClass(item.sap_status)} px-3 py-1">
                            ${item.sap_status || 'PREPARED'}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Helper Functions
    function getStatusClass(status) {
        switch(status?.toUpperCase()) {
            case 'COMPLETED': return 'bg-success-subtle text-success border-success';
            case 'SUBMITTED': return 'bg-warning-subtle text-warning border-warning';
            case 'FAILED': return 'bg-danger-subtle text-danger border-danger';
            case 'PENDING': return 'bg-secondary-subtle text-secondary border-secondary';
            case 'PROCESSING': return 'bg-info-subtle text-info border-info';
            default: return 'bg-light text-dark border';
        }
    }

    function getStatusIcon(status) {
        switch(status?.toUpperCase()) {
            case 'COMPLETED': return 'check-circle';
            case 'SUBMITTED': return 'clock';
            case 'FAILED': return 'times-circle';
            case 'PENDING': return 'hourglass-half';
            case 'PROCESSING': return 'sync-alt';
            default: return 'question-circle';
        }
    }

    function getItemStatusClass(status) {
        switch(status?.toUpperCase()) {
            case 'COMPLETED':
            case 'SUCCESS': return 'bg-success-subtle text-success border';
            case 'SUBMITTED':
            case 'PROCESSING': return 'bg-info-subtle text-info border';
            case 'FAILED': return 'bg-danger-subtle text-danger border';
            default: return 'bg-secondary-subtle text-secondary border';
        }
    }

    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        }
        return new Intl.NumberFormat().format(num);
    }

    // Fix Transfer Data
    function fixTransferData(id) {
        if (confirm('Fix this transfer data? This will try to complete missing information.')) {
            fetch(`/transfers/${id}/fix`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Transfer data fixed successfully', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('Failed to fix transfer: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error fixing transfer data', 'error');
            });
        }
    }

    // Export Functions
    function exportTransfers() {
        new bootstrap.Modal(document.getElementById('exportModal')).show();
    }

    function syncTransfers() {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;

        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Syncing...';
        btn.disabled = true;

        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }

    function printTransfer(id) {
        window.open(`/transfers/${id}/print`, '_blank');
    }

    function copyTransferNo(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Transfer number copied!', 'success');
        });
    }

    function copyTransferDetails(id) {
        fetch(`/transfers/${id}?_details=1`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transfer = data.data;
                    const text = `
Transfer No: ${transfer.transfer_no}
Document No: ${transfer.document_no}
Status: ${transfer.status}
Plant Supply: ${transfer.plant_supply}
Plant Destination: ${transfer.plant_destination}
Total Items: ${transfer.total_items}
Total Quantity: ${transfer.total_qty}
Created By: ${transfer.created_by_name}
Created At: ${new Date(transfer.created_at).toLocaleString()}
                    `.trim();

                    navigator.clipboard.writeText(text).then(() => {
                        showToast('Transfer details copied!', 'success');
                    });
                }
            });
    }

    function retryTransfer(id) {
        if (confirm('Retry this failed transfer?')) {
            fetch(`/transfers/${id}/retry`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Transfer retry initiated', 'success');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showToast('Failed to retry transfer', 'error');
                }
            });
        }
    }

    function exportToExcel() {
        const params = buildExportParams();
        window.open(`/transfers/export/excel?${params}`, '_blank');
        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
    }

    function exportToCSV() {
        const params = buildExportParams();
        window.open(`/transfers/export/csv?${params}`, '_blank');
        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
    }

    function exportToPDF() {
        const params = buildExportParams();
        window.open(`/transfers/export/pdf?${params}`, '_blank');
        bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
    }

    function confirmExport() {
        exportToExcel();
    }

    function buildExportParams() {
        const params = new URLSearchParams({
            date_from: document.getElementById('exportDateFrom').value,
            date_to: document.getElementById('exportDateTo').value,
            status: document.getElementById('exportStatus').value,
            search: '{{ request('search') }}',
            plant_supply: '{{ request('plant_supply') }}',
            plant_destination: '{{ request('plant_destination') }}'
        });
        return params.toString();
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `position-fixed bottom-0 end-0 p-3`;
        toast.innerHTML = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast.querySelector('.toast'));
        bsToast.show();

        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 3000);
    }

    // Remove page parameter from filter form
    document.getElementById('filterForm').addEventListener('submit', function() {
        const url = new URL(window.location.href);
        url.searchParams.delete('page');
        this.action = url.toString();
    });
});
</script>
@endsection
