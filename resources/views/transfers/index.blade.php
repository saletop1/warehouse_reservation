@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="fw-bold text-primary mb-2">
                                <i class="fas fa-truck me-2"></i>Transfer Management
                            </h2>
                            <p class="text-muted mb-0">
                                View and manage all material transfers
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('transfers.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Transfer No</label>
                                <input type="text"
                                       name="transfer_no"
                                       class="form-control form-control-sm"
                                       value="{{ request('transfer_no') }}"
                                       placeholder="Search transfer number...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Document No</label>
                                <input type="text"
                                       name="document_no"
                                       class="form-control form-control-sm"
                                       value="{{ request('document_no') }}"
                                       placeholder="Search document number...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                    <option value="SUBMITTED" {{ request('status') == 'SUBMITTED' ? 'selected' : '' }}>Submitted</option>
                                    <option value="FAILED" {{ request('status') == 'FAILED' ? 'selected' : '' }}>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Date From</label>
                                <input type="date"
                                       name="date_from"
                                       class="form-control form-control-sm"
                                       value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Date To</label>
                                <input type="date"
                                       name="date_to"
                                       class="form-control form-control-sm"
                                       value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                                <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Transfers Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-list me-2 text-primary"></i>Transfers List
                            <span class="badge bg-primary ms-2">{{ $transfers->total() }} transfers</span>
                        </h5>
                        <div class="text-muted small">
                            Showing {{ $transfers->firstItem() ?? 0 }}-{{ $transfers->lastItem() ?? 0 }} of {{ $transfers->total() }}
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2">Transfer No</th>
                                    <th class="py-2">Document No</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2">Plant Supply</th>
                                    <th class="py-2">Plant Dest</th>
                                    <th class="py-2">Items</th>
                                    <th class="py-2">Total Qty</th>
                                    <th class="py-2">Created By</th>
                                    <th class="py-2">Created At</th>
                                    <th class="pe-3 py-2 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ $transfer->transfer_no }}</div>
                                        <small class="text-muted">ID: {{ $transfer->id }}</small>
                                    </td>
                                    <td>
                                        @if($transfer->document)
                                        <a href="{{ route('documents.show', $transfer->document->id) }}"
                                           class="text-decoration-none">
                                            {{ $transfer->document_no }}
                                        </a>
                                        @else
                                        <span class="text-muted">{{ $transfer->document_no }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transfer->status == 'COMPLETED')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                            <i class="fas fa-check-circle me-1"></i>Completed
                                        </span>
                                        @elseif($transfer->status == 'SUBMITTED')
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                            <i class="fas fa-clock me-1"></i>Submitted
                                        </span>
                                        @elseif($transfer->status == 'FAILED')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                            <i class="fas fa-times-circle me-1"></i>Failed
                                        </span>
                                        @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                            <i class="fas fa-question-circle me-1"></i>{{ $transfer->status }}
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info border-0">
                                            {{ $transfer->plant_supply }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border-0">
                                            {{ $transfer->plant_destination }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $transfer->total_items }} items
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ number_format($transfer->total_qty) }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-medium small">{{ $transfer->created_by_name }}</div>
                                    </td>
                                    <td>
                                        <div class="text-nowrap small">
                                            {{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($transfer->created_at)->format('H:i') }}
                                        </small>
                                    </td>
                                    <td class="pe-3 text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary view-transfer"
                                                    data-id="{{ $transfer->id }}"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($transfer->document_id && $transfer->document)
                                            <a href="{{ route('documents.show', $transfer->document_id) }}"
                                               class="btn btn-outline-info"
                                               title="View Document">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-truck fa-3x text-muted opacity-25 mb-3"></i>
                                            <h5 class="text-muted">No transfers found</h5>
                                            <p class="text-muted mb-4">No transfer records available</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($transfers->hasPages())
                <div class="card-footer bg-white border-top py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $transfers->firstItem() ?? 0 }}-{{ $transfers->lastItem() ?? 0 }} of {{ $transfers->total() }}
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                {{ $transfers->links() }}
                            </ul>
                        </nav>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Transfer Detail Modal --}}
<div class="modal fade" id="transferDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transfer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transferDetailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.view-transfer:hover {
    background-color: #0d6efd;
    color: white;
}
.empty-state {
    opacity: 0.5;
}
.table th {
    font-weight: 600;
    font-size: 0.875rem;
}
.table td {
    vertical-align: middle;
}
.pagination {
    margin-bottom: 0;
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

    // Load Transfer Details via AJAX
    function loadTransferDetails(transferId) {
        fetch(`/transfers/${transferId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transfer = data.data;
                    const content = generateTransferDetailContent(transfer);
                    document.getElementById('transferDetailContent').innerHTML = content;
                    const modal = new bootstrap.Modal(document.getElementById('transferDetailModal'));
                    modal.show();
                } else {
                    alert('Failed to load transfer details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading transfer details');
            });
    }

    // Generate Transfer Detail HTML
    function generateTransferDetailContent(transfer) {
        return `
            <div class="transfer-detail">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Transfer Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Transfer No:</strong><br>
                                        <span class="fw-bold">${transfer.transfer_no || 'N/A'}</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Document No:</strong><br>
                                        <span>${transfer.document_no || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <strong>Status:</strong><br>
                                        <span class="badge ${getStatusBadgeClass(transfer.status)}">
                                            ${transfer.status}
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Move Type:</strong><br>
                                        <span>${transfer.move_type || '311'}</span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <strong>Plant Supply:</strong><br>
                                        <span class="badge bg-info">${transfer.plant_supply}</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Plant Destination:</strong><br>
                                        <span class="badge bg-primary">${transfer.plant_destination}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Quantities</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Total Items:</strong><br>
                                        <h4 class="fw-bold">${transfer.total_items}</h4>
                                    </div>
                                    <div class="col-6">
                                        <strong>Total Quantity:</strong><br>
                                        <h4 class="fw-bold">${parseFloat(transfer.total_qty).toLocaleString()}</h4>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <strong>Created By:</strong><br>
                                        <span>${transfer.created_by_name}</span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <strong>Created At:</strong><br>
                                        <small>${new Date(transfer.created_at).toLocaleString()}</small>
                                    </div>
                                    <div class="col-6">
                                        <strong>Completed At:</strong><br>
                                        <small>${transfer.completed_at ? new Date(transfer.completed_at).toLocaleString() : 'N/A'}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Transfer Items (${transfer.items?.length || 0})</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Material Code</th>
                                        <th>Description</th>
                                        <th>Batch</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Storage Location</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${generateItemsTable(transfer.items || [])}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                ${transfer.remarks ? `
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Remarks</h6>
                    </div>
                    <div class="card-body">
                        <p>${transfer.remarks}</p>
                    </div>
                </div>` : ''}

                ${transfer.sap_message ? `
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">SAP Response</h6>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-2 small">${transfer.sap_message}</pre>
                    </div>
                </div>` : ''}
            </div>
        `;
    }

    function generateItemsTable(items) {
        if (!items || items.length === 0) {
            return '<tr><td colspan="8" class="text-center">No items found</td></tr>';
        }

        return items.map((item, index) => `
            <tr>
                <td>${index + 1}</td>
                <td><code>${item.material_code || 'N/A'}</code></td>
                <td>${item.material_description || '-'}</td>
                <td>${item.batch || '-'}</td>
                <td class="fw-bold">${parseFloat(item.quantity).toLocaleString()}</td>
                <td>${item.unit || 'PC'}</td>
                <td>${item.storage_location || '-'}</td>
                <td>
                    <span class="badge ${getStatusBadgeClass(item.sap_status || 'PREPARED')}">
                        ${item.sap_status || 'PREPARED'}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function getStatusBadgeClass(status) {
        switch(status.toUpperCase()) {
            case 'COMPLETED':
            case 'SUCCESS':
                return 'bg-success';
            case 'SUBMITTED':
            case 'PREPARED':
                return 'bg-warning';
            case 'FAILED':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }
});
</script>
@endsection
