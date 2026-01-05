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

    {{-- Live Search --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <div class="d-flex align-items-center mb-0">
                <i class="fas fa-search me-2 text-primary"></i>
                <input type="text"
                       id="liveSearch"
                       class="form-control border-0 shadow-none"
                       placeholder="Search anything in transfer list... (transfer no, document no, plant, status, etc.)"
                       autocomplete="off">
                <span id="searchResultCount" class="badge bg-primary ms-2 d-none">0 found</span>
            </div>
        </div>
    </div>

    {{-- Transfers Table with Sticky Header --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-container" style="max-height: 700px; overflow-y: auto;">
                <table class="table table-hover mb-0" id="transfersTable">
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

                            <tr class="align-middle transfer-row">
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
                                            <div class="fw-semibold transfer-no">{{ $transfer->transfer_no ?? 'N/A' }}</div>
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
                                    <div class="text-muted document-no">{{ $transfer->document_no }}</div>
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
                                    <span class="badge bg-{{ $config['class'] }}-subtle text-{{ $config['class'] }} border border-{{ $config['class'] }}-subtle px-3 py-1 transfer-status">
                                        <i class="fas fa-{{ $config['icon'] }} me-1"></i>
                                        {{ $config['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="text-center me-2">
                                            <div class="badge plant-supply border px-3 py-1" style="color: #28a745 !important; border-color: #28a745 !important; background-color: rgba(40, 167, 69, 0.1) !important;">
                                                {{ $transfer->plant_supply ?? 'N/A' }}
                                            </div>
                                            <div class="text-muted mt-1">Supply</div>
                                        </div>
                                        @if(!empty($transfer->plant_destination) && $transfer->plant_destination != $transfer->plant_supply)
                                        <div class="me-2">
                                            <i class="fas fa-arrow-right text-muted"></i>
                                        </div>
                                        <div class="text-center">
                                            <div class="badge plant-destination border px-3 py-1" style="color: #007bff !important; border-color: #007bff !important; background-color: rgba(0, 123, 255, 0.1) !important;">
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
                                    <div class="fw-bold">
                                        @php
                                            // Calculate total quantity from all transfer items
                                            $totalQty = 0;
                                            if($transfer->items && $transfer->items->count() > 0) {
                                                $totalQty = $transfer->items->sum('quantity');
                                            } elseif($transfer->total_qty) {
                                                $totalQty = $transfer->total_qty;
                                            }
                                        @endphp
                                        {{ number_format($totalQty, 0, ',', '.') }}
                                    </div>
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
                                                <button class="dropdown-item" onclick="printTransferNow({{ $transfer->id }})">
                                                    <i class="fas fa-print me-2 text-muted"></i>Print
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" onclick="copyTransferDetailsNow({{ $transfer->id }})">
                                                    <i class="fas fa-copy me-2 text-muted"></i>Copy Details
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
                    Showing <span id="visibleRowCount">{{ $displayedTransfers ? count($displayedTransfers) : 0 }}</span> transfers
                </div>
                <div class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>Scroll to see more rows
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Transfer Detail Modal (Compact Design) --}}
<div class="modal fade" id="transferDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom bg-light py-2 px-3">
                <div class="d-flex align-items-center w-100">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0" style="font-size: 1.1rem;">
                            <i class="fas fa-exchange-alt me-2 text-primary"></i>Transfer Details
                        </h5>
                        <div class="text-muted small" id="transferNoLabel">Loading...</div>
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

/* Modal - Compact Design */
.modal-body {
    font-size: 14px;
}

.modal-content .table {
    font-size: 13px;
}

/* Plant Supply Color */
.plant-supply {
    color: #28a745 !important;
    border-color: #28a745 !important;
    background-color: rgba(40, 167, 69, 0.1) !important;
}

/* Plant Destination Color */
.plant-destination {
    color: #007bff !important;
    border-color: #007bff !important;
    background-color: rgba(0, 123, 255, 0.1) !important;
}

/* Compact Modal Tables */
.compact-table {
    font-size: 12.5px;
}

.compact-table td, .compact-table th {
    padding: 0.25rem 0.5rem;
}

/* Message Box Styling */
.message-box {
    font-size: 12.5px;
    padding: 0.5rem;
    margin: 0;
    line-height: 1.4;
    word-break: break-word;
    white-space: pre-wrap;
}

/* Material Description Styling */
.material-description {
    max-width: 200px;
    word-break: break-word;
    white-space: normal;
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
    // Live Search Functionality
    const liveSearch = document.getElementById('liveSearch');
    const searchResultCount = document.getElementById('searchResultCount');
    const visibleRowCount = document.getElementById('visibleRowCount');
    const transferRows = document.querySelectorAll('.transfer-row');

    liveSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;

        transferRows.forEach(row => {
            const textContent = row.textContent.toLowerCase();
            const transferNo = row.querySelector('.transfer-no')?.textContent.toLowerCase() || '';
            const documentNo = row.querySelector('.document-no')?.textContent.toLowerCase() || '';
            const transferStatus = row.querySelector('.transfer-status')?.textContent.toLowerCase() || '';
            const plantSupply = row.querySelector('.plant-supply')?.textContent.toLowerCase() || '';
            const plantDestination = row.querySelector('.plant-destination')?.textContent.toLowerCase() || '';

            const matches = searchTerm === '' ||
                textContent.includes(searchTerm) ||
                transferNo.includes(searchTerm) ||
                documentNo.includes(searchTerm) ||
                transferStatus.includes(searchTerm) ||
                plantSupply.includes(searchTerm) ||
                plantDestination.includes(searchTerm);

            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update counters
        if (searchTerm) {
            searchResultCount.textContent = `${visibleCount} found`;
            searchResultCount.classList.remove('d-none');
        } else {
            searchResultCount.classList.add('d-none');
        }

        visibleRowCount.textContent = visibleCount;
    });

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
                        <div class="text-center py-3">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                            <h6 class="text-danger mb-2">Failed to load transfer details</h6>
                            <p class="text-muted small">${data.message || 'Unknown error'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h6 class="text-danger mb-2">Error loading transfer details</h6>
                        <p class="text-muted small">${error.message}</p>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadTransferDetails(${transferId})">
                            <i class="fas fa-redo me-1"></i>Retry
                        </button>
                    </div>
                `;
            });
    }

    // Generate Compact Transfer Detail Content
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

        // Calculate total quantity from items
        let totalQty = 0;
        if (transfer.items && transfer.items.length > 0) {
            totalQty = transfer.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
        } else if (transfer.total_qty) {
            totalQty = parseFloat(transfer.total_qty);
        }

        return `
            <div class="transfer-detail">
                {{-- Compact Header --}}
                <div class="p-3 border-bottom bg-light-subtle">
                    <div class="row align-items-center g-2">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-2">
                                    <i class="fas fa-exchange-alt text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">${transfer.transfer_no || 'N/A'}</h6>
                                    <div class="text-muted small">
                                        <i class="fas fa-file-alt me-1"></i>Doc: ${transfer.document_no || 'N/A'}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge ${getStatusClass(transfer.status)} px-3 py-1">
                                <i class="fas fa-${getStatusIcon(transfer.status)} me-1"></i>
                                ${transfer.status || 'UNKNOWN'}
                            </span>
                            <div class="text-muted small mt-1">
                                Created: ${formattedDate}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Compact Main Content --}}
                <div class="p-3">
                    {{-- Compact Information Row --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="card border h-100">
                                <div class="card-body p-2 text-center">
                                    <div class="text-muted small mb-1">Move Type</div>
                                    <div class="fw-semibold">${transfer.move_type || '311'}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border h-100">
                                <div class="card-body p-2 text-center">
                                    <div class="text-muted small mb-1">Total Items</div>
                                    <div class="fw-semibold">${transfer.total_items || 0}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border h-100">
                                <div class="card-body p-2 text-center">
                                    <div class="text-muted small mb-1">Total Quantity</div>
                                    <div class="fw-bold">${formatFullNumber(totalQty)}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border h-100">
                                <div class="card-body p-2 text-center">
                                    <div class="text-muted small mb-1">Completion</div>
                                    <span class="badge ${transfer.completed_at ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'}">
                                        ${transfer.completed_at ? 'Completed' : 'In Progress'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Plant Information --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-header bg-transparent py-2 px-3">
                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">
                                        <i class="fas fa-building me-2 text-primary"></i>Plant Information
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="text-muted small mb-1">Supply Plant</div>
                                            <div class="badge plant-supply px-3 py-1" style="color: #28a745 !important; border-color: #28a745 !important; background-color: rgba(40, 167, 69, 0.1) !important;">
                                                ${transfer.plant_supply || 'N/A'}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small mb-1">Dest Plant</div>
                                            <div class="badge plant-destination px-3 py-1" style="color: #007bff !important; border-color: #007bff !important; background-color: rgba(0, 123, 255, 0.1) !important;">
                                                ${transfer.plant_destination || 'N/A'}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small mb-1">Created By</div>
                                            <div class="fw-semibold small">${transfer.created_by_name || 'System'}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small mb-1">Completed At</div>
                                            <div class="fw-semibold small">${completedDate}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Remarks Section --}}
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-header bg-transparent py-2 px-3">
                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">
                                        <i class="fas fa-sticky-note me-2 text-warning"></i>Remarks & SAP Message
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    ${transfer.remarks ? `
                                    <div class="mb-2">
                                        <div class="text-muted small mb-1">Remarks:</div>
                                        <div class="message-box bg-light p-2 rounded">${transfer.remarks}</div>
                                    </div>
                                    ` : ''}

                                    ${transfer.sap_message ? `
                                    <div>
                                        <div class="text-muted small mb-1">SAP Message:</div>
                                        <div class="message-box bg-light p-2 rounded">${transfer.sap_message}</div>
                                    </div>
                                    ` : ''}

                                    ${!transfer.remarks && !transfer.sap_message ? `
                                    <div class="text-center py-3">
                                        <i class="fas fa-comment-slash text-muted fa-lg mb-2"></i>
                                        <p class="text-muted small mb-0">No remarks or messages</p>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Transfer Items Table --}}
                    <div class="card border">
                        <div class="card-header bg-transparent border-bottom py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">
                                    <i class="fas fa-boxes me-2 text-primary"></i>Transfer Items
                                    <span class="badge bg-primary-subtle text-primary ms-1">
                                        ${transfer.items?.length || 0} items
                                    </span>
                                </h6>
                                <div class="text-muted small">
                                    Total: ${formatFullNumber(totalQty)}
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm mb-0 compact-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 py-1 fw-semibold">#</th>
                                            <th class="py-1 fw-semibold">Material Code</th>
                                            <th class="py-1 fw-semibold">Description</th>
                                            <th class="py-1 fw-semibold">Batch</th>
                                            <th class="py-1 fw-semibold text-end">Quantity</th>
                                            <th class="pe-3 py-1 fw-semibold">Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${generateCompactItemsTable(transfer.items || [])}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-sm btn-outline-primary" onclick="printTransferNow(${transfer.id})">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyTransferDetailsNow(${transfer.id})">
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

    function generateCompactItemsTable(items) {
        if (!items || items.length === 0) {
            return `
                <tr>
                    <td colspan="6" class="text-center py-3 text-muted small">
                        <i class="fas fa-box-open me-1"></i>No items found
                    </td>
                </tr>
            `;
        }

        return items.slice(0, 10).map((item, index) => {
            const materialCode = item.material_code || 'N/A';
            const formattedCode = /^\d+$/.test(materialCode) ?
                materialCode.replace(/^0+/, '') : materialCode;
            const description = item.material_description || '-';

            return `
                <tr>
                    <td class="ps-3">${index + 1}</td>
                    <td>
                        <div class="fw-semibold" style="font-size: 11.5px;">${formattedCode}</div>
                    </td>
                    <td class="material-description">
                        <div class="text-muted small" style="font-size: 11.5px; line-height: 1.3;">${description}</div>
                    </td>
                    <td>${item.batch || '-'}</td>
                    <td class="text-end fw-semibold">${formatFullNumber(item.quantity || 0)}</td>
                    <td class="pe-3">${item.unit || 'PC'}</td>
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

    function formatFullNumber(num) {
        // Format number without abbreviation (no K, M)
        return new Intl.NumberFormat('id-ID').format(num);
    }

    // Print Transfer Function
    function printTransferNow(id) {
        if (id) {
            const url = `/transfers/${id}/print`;
            console.log('Opening print URL:', url);
            window.open(url, '_blank');
            showToast('Opening print preview...', 'info');
        } else {
            showToast('Transfer ID is required', 'error');
        }
    }

    // Copy Transfer Details Function
    function copyTransferDetailsNow(id) {
        if (!id) {
            showToast('Transfer ID is required', 'error');
            return;
        }

        fetch(`/transfers/${id}?_details=1`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transfer = data.data;

                    // Calculate total quantity
                    let totalQty = 0;
                    if (transfer.items && transfer.items.length > 0) {
                        totalQty = transfer.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
                    } else if (transfer.total_qty) {
                        totalQty = parseFloat(transfer.total_qty);
                    }

                    const text = `
TRANSFER DETAILS
────────────────
Transfer No: ${transfer.transfer_no || 'N/A'}
Document No: ${transfer.document_no || 'N/A'}
Status: ${transfer.status || 'N/A'}
Move Type: ${transfer.move_type || '311'}
Plant Supply: ${transfer.plant_supply || 'N/A'}
Plant Destination: ${transfer.plant_destination || 'N/A'}
Total Items: ${transfer.total_items || 0}
Total Quantity: ${formatFullNumber(totalQty)}
Created By: ${transfer.created_by_name || 'System'}
Created At: ${transfer.created_at ? new Date(transfer.created_at).toLocaleString('id-ID') : 'N/A'}
Completed At: ${transfer.completed_at ? new Date(transfer.completed_at).toLocaleString('id-ID') : 'Not completed'}
                    `.trim();

                    navigator.clipboard.writeText(text).then(() => {
                        showToast('Transfer details copied to clipboard!', 'success');
                    }).catch(err => {
                        console.error('Copy failed:', err);
                        // Fallback method
                        const textArea = document.createElement('textarea');
                        textArea.value = text;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                        showToast('Transfer details copied!', 'success');
                    });
                } else {
                    showToast('Failed to load transfer details', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error loading transfer details', 'error');
            });
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

    function copyTransferNo(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Transfer number copied to clipboard!', 'success');
        }).catch(err => {
            console.error('Copy failed:', err);
            showToast('Failed to copy transfer number', 'error');
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
            search: document.getElementById('liveSearch').value
        });
        return params.toString();
    }

    function showToast(message, type = 'info') {
        // Remove existing toasts
        document.querySelectorAll('.toast-container').forEach(el => el.remove());

        const toastHtml = `
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', toastHtml);

        const toastElement = document.querySelector('.toast-container .toast');
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();

        // Remove after hide
        toastElement.addEventListener('hidden.bs.toast', function () {
            this.closest('.toast-container').remove();
        });
    }

    // Global functions for dropdown menu
    window.printTransferNow = printTransferNow;
    window.copyTransferDetailsNow = copyTransferDetailsNow;
    window.fixTransferData = fixTransferData;
    window.retryTransfer = retryTransfer;
});
</script>
@endsection
