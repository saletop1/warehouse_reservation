@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4">
    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                <div class="mb-2 mb-md-0">
                    <h2 class="fw-bold text-dark fs-4 mb-1">
                        <i class="fas fa-exchange-alt me-2 text-primary"></i>Transfer Management
                    </h2>
                    <p class="text-muted small mb-0">
                        Total: {{ $transfers->total() }} transfers • Last sync: {{ now()->format('H:i') }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <button class="btn btn-sm btn-outline-primary" onclick="syncTransfers()">
                        <i class="fas fa-sync-alt me-1"></i>Sync
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="exportTransfers()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Summary --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <div class="row g-2 text-center">
                        @php
                            $stats = [
                                'total' => $transfers->total(),
                                'completed' => $transfers->where('status', 'COMPLETED')->count(),
                                'submitted' => $transfers->where('status', 'SUBMITTED')->count(),
                                'failed' => $transfers->where('status', 'FAILED')->count(),
                                'pending' => $transfers->whereIn('status', ['PENDING', 'PROCESSING'])->count()
                            ];
                        @endphp

                        <div class="col-4 col-md-2">
                            <div class="stat-item">
                                <div class="stat-value fw-bold text-dark">{{ $stats['total'] }}</div>
                                <div class="stat-label small text-muted">Total</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-item border-start">
                                <div class="stat-value fw-bold text-success">{{ $stats['completed'] }}</div>
                                <div class="stat-label small text-muted">Completed</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-item border-start">
                                <div class="stat-value fw-bold text-warning">{{ $stats['submitted'] }}</div>
                                <div class="stat-label small text-muted">Submitted</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-item border-start">
                                <div class="stat-value fw-bold text-danger">{{ $stats['failed'] }}</div>
                                <div class="stat-label small text-muted">Failed</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-item border-start">
                                <div class="stat-value fw-bold text-info">{{ $stats['pending'] }}</div>
                                <div class="stat-label small text-muted">Pending</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-item border-start">
                                <div class="stat-value fw-bold text-primary">
                                    {{ $transfers->sum('total_qty') > 1000 ? round($transfers->sum('total_qty')/1000, 1).'K' : $transfers->sum('total_qty') }}
                                </div>
                                <div class="stat-label small text-muted">Total Qty</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-header bg-transparent border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-filter me-2 text-primary"></i>Filters
                </h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="fas fa-sliders-h me-1"></i>Toggle
                </button>
            </div>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('transfers.index') }}" id="filterForm">
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <label class="form-label small text-muted mb-1">Transfer/Doc No</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   value="{{ request('search') }}" placeholder="TRMG... or doc no...">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                <option value="SUBMITTED" {{ request('status') == 'SUBMITTED' ? 'selected' : '' }}>Submitted</option>
                                <option value="FAILED" {{ request('status') == 'FAILED' ? 'selected' : '' }}>Failed</option>
                                <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Pending</option>
                                <option value="PROCESSING" {{ request('status') == 'PROCESSING' ? 'selected' : '' }}>Processing</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small text-muted mb-1">Plant Supply</label>
                            <input type="text" name="plant_supply" class="form-control form-control-sm"
                                   value="{{ request('plant_supply') }}" placeholder="e.g., 3000">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small text-muted mb-1">Plant Dest</label>
                            <input type="text" name="plant_destination" class="form-control form-control-sm"
                                   value="{{ request('plant_destination') }}" placeholder="e.g., 3100">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small text-muted mb-1">Date Range</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                <span class="input-group-text">to</span>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-sm btn-primary px-3">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                            <a href="{{ route('transfers.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                            <div class="ms-auto d-flex align-items-center">
                                <label class="form-label small text-muted me-2 mb-0">Show:</label>
                                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                    <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Transfers Table --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 py-2 small fw-semibold" style="width: 15%">
                                <i class="fas fa-hashtag me-1 text-muted"></i>Transfer No
                            </th>
                            <th class="py-2 small fw-semibold" style="width: 12%">Document</th>
                            <th class="py-2 small fw-semibold" style="width: 10%">Status</th>
                            <th class="py-2 small fw-semibold" style="width: 18%">Plants</th>
                            <th class="py-2 small fw-semibold text-center" style="width: 8%">Items</th>
                            <th class="py-2 small fw-semibold text-center" style="width: 10%">Quantity</th>
                            <th class="py-2 small fw-semibold" style="width: 12%">Created</th>
                            <th class="py-2 small fw-semibold text-center" style="width: 15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
    @php
        // Gunakan array untuk melacak transfer yang sudah ditampilkan
        $displayedTransfers = [];
    @endphp

    @forelse($transfers as $transfer)
        @php
            // Skip jika transfer sudah ditampilkan atau data tidak lengkap
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
                        <i class="fas fa-check-circle text-success fs-12"></i>
                        @elseif($transfer->status == 'SUBMITTED')
                        <i class="fas fa-clock text-warning fs-12"></i>
                        @elseif($transfer->status == 'FAILED')
                        <i class="fas fa-times-circle text-danger fs-12"></i>
                        @else
                        <i class="fas fa-question-circle text-secondary fs-12"></i>
                        @endif
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $transfer->transfer_no ?? 'N/A' }}</div>
                        <small class="text-muted d-block">
                            {{ \Carbon\Carbon::parse($transfer->created_at)->format('H:i') }}
                        </small>
                    </div>
                </div>
            </td>
            <td>
                @if($transfer->document)
                <a href="{{ route('documents.show', $transfer->document->id) }}"
                   class="text-decoration-none text-dark fw-medium small d-block">
                    {{ $transfer->document_no }}
                </a>
                <small class="text-muted d-block">
                    Plant: {{ $transfer->document->plant ?? 'N/A' }}
                </small>
                @else
                <span class="text-muted small">{{ $transfer->document_no }}</span>
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
                <span class="badge bg-{{ $config['class'] }}-subtle text-{{ $config['class'] }} border border-{{ $config['class'] }}-subtle px-2 py-1">
                    <i class="fas fa-{{ $config['icon'] }} me-1 fs-12"></i>
                    {{ $config['label'] }}
                </span>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="text-center me-2">
                        <div class="badge bg-info-subtle text-info border px-2 py-1">
                            {{ $transfer->plant_supply ?? 'N/A' }}
                        </div>
                        <div class="text-muted extra-small mt-1">Supply</div>
                    </div>
                    @if(!empty($transfer->plant_destination) && $transfer->plant_destination != $transfer->plant_supply)
                    <div class="me-2">
                        <i class="fas fa-arrow-right text-muted"></i>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-primary-subtle text-primary border px-2 py-1">
                            {{ $transfer->plant_destination ?? 'N/A' }}
                        </div>
                        <div class="text-muted extra-small mt-1">Dest</div>
                    </div>
                    @endif
                </div>
            </td>
            <td class="text-center">
                <span class="badge bg-light text-dark border px-2 py-1">
                    {{ $transfer->total_items ?? 0 }}
                </span>
            </td>
            <td class="text-center">
                <div class="fw-bold">{{ number_format($transfer->total_qty ?? 0) }}</div>
                <small class="text-muted">{{ $transfer->items->first()->unit ?? 'PC' }}</small>
            </td>
            <td>
                <div class="small">
                    <div>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/y') }}</div>
                    <div class="text-muted">{{ $transfer->created_by_name ?? 'System' }}</div>
                </div>
            </td>
            <td class="text-center">
                <div class="btn-group btn-group-sm" role="group">
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
                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split"
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
                <div class="empty-state py-4">
                    <i class="fas fa-exchange-alt fa-3x text-muted opacity-25 mb-3"></i>
                    <h5 class="text-muted mb-2">No transfers found</h5>
                    <p class="text-muted small mb-3">No valid transfer records available</p>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($transfers->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="text-muted small mb-2 mb-md-0">
                    Showing {{ $transfers->firstItem() ?? 0 }}-{{ $transfers->lastItem() ?? 0 }} of {{ $transfers->total() }}
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        {{ $transfers->withQueryString()->links() }}
                    </ul>
                </nav>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Transfer Detail Modal (Full Data) --}}
<div class="modal fade" id="transferDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom bg-light">
                <div class="d-flex align-items-center w-100">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">
                            <i class="fas fa-exchange-alt me-2 text-primary"></i>Transfer Details
                        </h6>
                        <small class="text-muted" id="transferNoLabel">Loading...</small>
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
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-semibold">
                    <i class="fas fa-download me-2 text-primary"></i>Export Transfers
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">Export Format</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary flex-fill" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                        <button class="btn btn-sm btn-outline-success flex-fill" onclick="exportToCSV()">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </button>
                        <button class="btn btn-sm btn-outline-danger flex-fill" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Date Range</label>
                    <div class="input-group input-group-sm">
                        <input type="date" id="exportDateFrom" class="form-control" value="{{ now()->subDays(7)->format('Y-m-d') }}">
                        <span class="input-group-text">to</span>
                        <input type="date" id="exportDateTo" class="form-control" value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Status Filter</label>
                    <select id="exportStatus" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="SUBMITTED">Submitted</option>
                        <option value="FAILED">Failed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="confirmExport()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Responsive Design */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .stat-item {
        padding: 0.5rem;
    }

    .modal-dialog {
        margin: 0.5rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .card-body.p-0 .table td,
    .card-body.p-0 .table th {
        padding: 0.5rem;
    }

    .btn-group .dropdown-toggle::after {
        margin-left: 0;
    }

    .d-none-mobile {
        display: none !important;
    }
}

/* Custom Styles */
.stat-item {
    padding: 0.75rem;
    border-right: 1px solid #dee2e6;
}

.stat-item:last-child {
    border-right: none;
}

.stat-value {
    font-size: 1.25rem;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.75rem;
}

.extra-small {
    font-size: 0.7rem;
}

.fs-12 {
    font-size: 0.75rem;
}

/* Table improvements */
.table > :not(caption) > * > * {
    padding: 0.75rem 0.5rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}

/* Badge styles */
.badge {
    font-weight: 500;
    letter-spacing: 0.3px;
}

/* Modal custom */
.modal-header {
    padding: 1rem 1.5rem;
}

.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

/* Loading animation */
.loading-spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Custom scrollbar */
.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
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
                <div class="loading-spinner mb-3"></div>
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

                    // Initialize tooltips
                    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
                } else {
                    contentDiv.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                            <h6 class="text-danger">Failed to load transfer details</h6>
                            <p class="text-muted small">${data.message || 'Unknown error'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                        <h6 class="text-danger">Error loading transfer details</h6>
                        <p class="text-muted small">${error.message}</p>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadTransferDetails(${transferId})">
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
                minute: '2-digit',
                second: '2-digit'
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
                                <div class="bg-primary bg-opacity-10 p-2 rounded-2 me-3">
                                    <i class="fas fa-exchange-alt fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0">${transfer.transfer_no || 'N/A'}</h5>
                                    <div class="text-muted small">
                                        <i class="fas fa-file-alt me-1"></i>Doc: ${transfer.document_no || 'N/A'}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="d-inline-block">
                                <span class="badge ${getStatusClass(transfer.status)} fs-12 px-3 py-2">
                                    <i class="fas fa-${getStatusIcon(transfer.status)} me-1"></i>
                                    ${transfer.status || 'UNKNOWN'}
                                </span>
                            </div>
                            <div class="mt-2 small text-muted">
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
                                <div class="card-header bg-transparent py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>Transfer Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row small">
                                        <div class="col-6 mb-2">
                                            <div class="text-muted">Move Type</div>
                                            <div class="fw-medium">${transfer.move_type || '311'}</div>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <div class="text-muted">Total Items</div>
                                            <div class="fw-medium">${transfer.total_items || 0}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Total Quantity</div>
                                            <div class="fw-bold">${formatNumber(transfer.total_qty || 0)}</div>
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
                                <div class="card-header bg-transparent py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-building me-2 text-primary"></i>Plant Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row small">
                                        <div class="col-6 mb-3">
                                            <div class="text-muted mb-1">Supply Plant</div>
                                            <div class="d-flex align-items-center">
                                                <div class="badge bg-info-subtle text-info border px-3 py-1 me-2">
                                                    ${transfer.plant_supply || 'N/A'}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-muted mb-1">Destination Plant</div>
                                            <div class="d-flex align-items-center">
                                                <div class="badge bg-primary-subtle text-primary border px-3 py-1 me-2">
                                                    ${transfer.plant_destination || 'N/A'}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted mb-1">Created By</div>
                                            <div class="fw-medium">${transfer.created_by_name || 'System'}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted mb-1">Completed At</div>
                                            <div class="fw-medium">${completedDate}</div>
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
                                <div class="text-muted small">
                                    Total: ${formatNumber(transfer.total_qty || 0)}
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 py-2 small fw-semibold">#</th>
                                            <th class="py-2 small fw-semibold">Material Code</th>
                                            <th class="py-2 small fw-semibold">Description</th>
                                            <th class="py-2 small fw-semibold">Batch</th>
                                            <th class="py-2 small fw-semibold">Storage Loc</th>
                                            <th class="py-2 small fw-semibold text-end">Quantity</th>
                                            <th class="py-2 small fw-semibold">Unit</th>
                                            <th class="py-2 small fw-semibold">Dest. SLOC</th>
                                            <th class="pe-3 py-2 small fw-semibold text-center">Status</th>
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
                                <div class="card-header bg-transparent py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-sticky-note me-2 text-warning"></i>Remarks
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0 small">${transfer.remarks}</p>
                                </div>
                            </div>
                        </div>
                        ` : ''}

                        ${transfer.sap_message ? `
                        <div class="${transfer.remarks ? 'col-md-6' : 'col-12'}">
                            <div class="card border">
                                <div class="card-header bg-transparent py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-comment-alt me-2 text-info"></i>SAP Message
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <pre class="mb-0 small bg-light p-2 rounded" style="white-space: pre-wrap;">${transfer.sap_message}</pre>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    ` : ''}

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-sm btn-outline-primary" onclick="printTransfer(${transfer.id})">
                            <i class="fas fa-print me-1"></i>Print Transfer
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyTransferDetails(${transfer.id})">
                            <i class="fas fa-copy me-1"></i>Copy Details
                        </button>
                        ${transfer.document_id ? `
                        <a href="/documents/${transfer.document_id}" class="btn btn-sm btn-outline-info ms-auto">
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
                        <code class="small">${formattedCode}</code>
                    </td>
                    <td>${item.material_description || '-'}</td>
                    <td>${item.batch || '-'}</td>
                    <td>${item.storage_location || '-'}</td>
                    <td class="text-end fw-semibold">${formatNumber(item.quantity || 0)}</td>
                    <td>${item.unit || 'PC'}</td>
                    <td>${item.sloc_destination || '-'}</td>
                    <td class="pe-3 text-center">
                        <span class="badge ${getItemStatusClass(item.sap_status)} px-2 py-1">
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

        // Tambahkan di bagian script
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

    // Fungsi untuk membersihkan data duplicate di client side
    function cleanDuplicateTransfers() {
        const transfers = @json($transfers->items());
        const uniqueTransfers = [];
        const seen = new Set();

        transfers.forEach(transfer => {
            const key = transfer.transfer_no + '_' + transfer.plant_destination;
            if (!seen.has(key) &&
                transfer.plant_destination &&
                transfer.plant_destination.trim() !== '' &&
                transfer.total_items > 0) {
                seen.add(key);
                uniqueTransfers.push(transfer);
            }
        });

        return uniqueTransfers;
    }
    // Export Functions
    function exportTransfers() {
        new bootstrap.Modal(document.getElementById('exportModal')).show();
    }

    function syncTransfers() {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;

        btn.innerHTML = '<span class="loading-spinner me-1"></span>Syncing...';
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
            showToast('Retrying transfer...', 'info');
            // Implement retry logic
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
        const format = document.querySelector('.modal-body .btn-primary').dataset.format;
        if (format === 'excel') exportToExcel();
        else if (format === 'csv') exportToCSV();
        else if (format === 'pdf') exportToPDF();
    }

    function buildExportParams() {
        const params = new URLSearchParams({
            date_from: document.getElementById('exportDateFrom').value,
            date_to: document.getElementById('exportDateTo').value,
            status: document.getElementById('exportStatus').value
        });
        return params.toString();
    }

    function showToast(message, type = 'info') {
        // Simple toast notification
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
            document.body.removeChild(toast);
        }, 3000);
    }

    // Auto refresh every 2 minutes if on page for more than 5 minutes
    let autoRefreshTimer;
    setTimeout(() => {
        autoRefreshTimer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, 120000); // 2 minutes
    }, 300000); // Start after 5 minutes
});
</script>
@endsection
