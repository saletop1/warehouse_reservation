@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4">
    {{-- Welcome & Stats Section --}}
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="mb-3 mb-md-0">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-circle bg-primary bg-opacity-10 me-3 d-none d-md-flex">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <div>
                                    <h2 class="fw-bold text-dark mb-1 fs-4 fs-md-3">
                                        Good
                                        @php
                                            $hour = now()->hour;
                                            if ($hour < 12) echo 'Morning';
                                            elseif ($hour < 17) echo 'Afternoon';
                                            else echo 'Evening';
                                        @endphp,
                                        {{ Str::limit(Auth::user()->name, 20) }}!
                                    </h2>
                                    <div class="d-flex flex-wrap align-items-center text-muted small">
                                        <span class="me-3">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            {{ now()->format('d/m/Y') }}
                                        </span>
                                        <span>
                                            <i class="fas fa-clock me-1"></i>
                                            <span id="currentTime">{{ now()->format('H:i') }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                                    <i class="fas fa-user-tag me-1 fs-12"></i>{{ Auth::user()->role ?? 'User' }}
                                </span>
                                @if(Auth::user()->plant)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                    <i class="fas fa-building me-1 fs-12"></i>{{ Auth::user()->plant }}
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary px-3" id="refreshDashboard">
                                <i class="fas fa-sync-alt me-1"></i>
                                <span class="d-none d-md-inline">Refresh</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row mb-3 mb-md-4 g-2">
        <div class="col-6 col-md-3">
            <div class="stats-card card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1 fw-medium">Booked</p>
                            <h3 class="fw-bold mb-0 text-warning">{{ $documentStats['booked'] ?? 0 }}</h3>
                            <small class="text-muted d-block">Waiting</small>
                        </div>
                        <div class="stat-icon bg-warning-subtle p-2 rounded-2">
                            <i class="fas fa-file-alt text-warning fs-5"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-warning" style="width: {{ min(($documentStats['booked'] ?? 0) * 10, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stats-card card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1 fw-medium">Partial</p>
                            <h3 class="fw-bold mb-0 text-info">{{ $documentStats['partial'] ?? 0 }}</h3>
                            <small class="text-muted d-block">In Progress</small>
                        </div>
                        <div class="stat-icon bg-info-subtle p-2 rounded-2">
                            <i class="fas fa-exchange-alt text-info fs-5"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-info" style="width: {{ min(($documentStats['partial'] ?? 0) * 10, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stats-card card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1 fw-medium">Completed</p>
                            <h3 class="fw-bold mb-0 text-success">{{ $documentStats['closed'] ?? 0 }}</h3>
                            <small class="text-muted d-block">Fully Done</small>
                        </div>
                        <div class="stat-icon bg-success-subtle p-2 rounded-2">
                            <i class="fas fa-check-circle text-success fs-5"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: {{ min(($documentStats['closed'] ?? 0) * 10, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stats-card card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1 fw-medium">Today's Activity</p>
                            <h3 class="fw-bold mb-0 text-primary">{{ $todayStats['total'] ?? 0 }}</h3>
                            <div class="d-flex gap-1 mt-1">
                                <span class="badge bg-primary-subtle text-primary border-0 py-1 px-2 fs-12">
                                    {{ $todayStats['documents_created'] ?? 0 }} Docs
                                </span>
                                <span class="badge bg-success-subtle text-success border-0 py-1 px-2 fs-12">
                                    {{ $todayStats['transfers_created'] ?? 0 }} Trf
                                </span>
                            </div>
                        </div>
                        <div class="stat-icon bg-primary-subtle p-2 rounded-2">
                            <i class="fas fa-chart-line text-primary fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row">
        {{-- Recent Documents --}}
        <div class="col-lg-8 mb-3 mb-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-file-contract me-2 text-primary"></i>Recent Documents
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('reservations.create') }}" class="btn btn-sm btn-primary px-3">
                                <i class="fas fa-plus me-1"></i>
                                <span class="d-none d-md-inline">New</span>
                            </a>
                            <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                                <span class="d-none d-md-inline">View All</span>
                                <i class="fas fa-arrow-right d-md-none"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2 small fw-semibold">Doc. No</th>
                                    <th class="py-2 small fw-semibold d-none d-md-table-cell">Plant</th>
                                    <th class="py-2 small fw-semibold">Status</th>
                                    <th class="py-2 small fw-semibold d-none d-md-table-cell">Items</th>
                                    <th class="py-2 small fw-semibold d-none d-lg-table-cell">Progress</th>
                                    <th class="py-2 small fw-semibold d-none d-lg-table-cell">Created By</th>
                                    <th class="pe-3 py-2 small fw-semibold text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentDocuments as $doc)
                                <tr class="align-middle">
                                    <td class="ps-3">
                                        <div>
                                            <a href="{{ route('documents.show', $doc->id) }}" class="fw-semibold text-decoration-none text-dark">
                                                {{ Str::limit($doc->document_no, 12) }}
                                            </a>
                                            <div class="text-muted small d-md-none">
                                                {{ $doc->plant }} • {{ $doc->items_count ?? 0 }} items
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge {{ $doc->plant == '3000' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }} border px-2 py-1">
                                            {{ $doc->plant }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusConfig = [
                                                'booked' => ['class' => 'warning', 'icon' => 'clock'],
                                                'partial' => ['class' => 'info', 'icon' => 'tasks'],
                                                'closed' => ['class' => 'success', 'icon' => 'check-circle'],
                                                'cancelled' => ['class' => 'danger', 'icon' => 'times-circle']
                                            ];
                                            $config = $statusConfig[$doc->status] ?? ['class' => 'secondary', 'icon' => 'question-circle'];
                                        @endphp
                                        <span class="badge bg-{{ $config['class'] }}-subtle text-{{ $config['class'] }} border border-{{ $config['class'] }}-subtle px-2 py-1">
                                            <i class="fas fa-{{ $config['icon'] }} me-1 fs-12"></i>
                                            <span class="d-none d-sm-inline">{{ ucfirst($doc->status) }}</span>
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            {{ $doc->items_count ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 me-2">
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-success progress-bar-striped"
                                                         style="width: {{ $doc->completion_rate_calculated ?? 0 }}%"></div>
                                                </div>
                                            </div>
                                            <span class="small fw-medium">{{ $doc->completion_rate_calculated ?? 0 }}%</span>
                                        </div>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <span class="small">{{ Str::limit($doc->created_by_name ?? 'System', 15) }}</span>
                                    </td>
                                    <td class="pe-3 text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('documents.show', $doc->id) }}"
                                               class="btn btn-outline-secondary border-end-0 px-2"
                                               title="View">
                                                <i class="fas fa-eye fs-12"></i>
                                            </a>
                                            @if($doc->status == 'booked')
                                            <a href="{{ route('documents.edit', $doc->id) }}"
                                               class="btn btn-outline-secondary border-start-0 px-2"
                                               title="Edit">
                                                <i class="fas fa-edit fs-12"></i>
                                            </a>
                                            @endif
                                            <a href="{{ route('documents.print', $doc->id) }}"
                                               class="btn btn-outline-secondary border-start-0 px-2"
                                               title="Print">
                                                <i class="fas fa-print fs-12"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state py-4">
                                            <i class="fas fa-file-alt fa-3x text-muted opacity-25 mb-3"></i>
                                            <h5 class="text-muted mb-2">No documents yet</h5>
                                            <p class="text-muted small mb-3">Start by creating your first document</p>
                                            <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus me-2"></i>Create Document
                                            </a>
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

        {{-- Recent Transfers Only --}}
        <div class="col-lg-4 mb-3 mb-md-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-truck me-2 text-success"></i>Recent Transfers
                        <span class="badge bg-success-subtle text-success border ms-2">{{ $recentTransfers->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($recentTransfers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentTransfers as $transfer)
                                <a href="#" class="list-group-item list-group-item-action border-0 px-3 py-2 transfer-item"
                                   data-id="{{ $transfer->id }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-medium small">{{ Str::limit($transfer->transfer_no, 15) }}</span>
                                                <span class="badge bg-light text-dark border small">
                                                    {{ number_format($transfer->total_qty) }}
                                                </span>
                                            </div>
                                            <div class="text-muted small d-flex justify-content-between">
                                                <span>{{ $transfer->document_no }}</span>
                                                <span>{{ \Carbon\Carbon::parse($transfer->created_at)->format('H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-truck fa-2x text-muted opacity-25 mb-2"></i>
                            <p class="text-muted small mb-0">No recent transfers</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="{{ route('transfers.index') }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-list me-1"></i>All Transfers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Transfer Quick View Modal --}}
<div class="modal fade" id="quickTransferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-semibold">Transfer Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="quickTransferContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

{{-- Transfer Detail Modal (Sama dengan di index.blade.php) --}}
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

<style>
/* Mobile-first responsive design */
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stat-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stats-card {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.08) !important;
}

.transfer-item {
    transition: background-color 0.2s ease;
}

.transfer-item:hover {
    background-color: #f8f9fa;
}

/* Transfer Detail Modal Styles untuk dashboard */
.plant-supply {
    color: #28a745 !important;
    border-color: #28a745 !important;
    background-color: rgba(40, 167, 69, 0.1) !important;
}

.plant-destination {
    color: #007bff !important;
    border-color: #007bff !important;
    background-color: rgba(0, 123, 255, 0.1) !important;
}

.compact-table {
    font-size: 12.5px;
}

.compact-table td, .compact-table th {
    padding: 0.25rem 0.5rem;
}

.message-box {
    font-size: 12.5px;
    padding: 0.5rem;
    margin: 0;
    line-height: 1.4;
    word-break: break-word;
    white-space: pre-wrap;
}

.material-description {
    max-width: 200px;
    word-break: break-word;
    white-space: normal;
}

/* Transfer Detail Modal Spesifik */
#transferDetailModal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

#transferDetailModal .transfer-detail {
    min-height: 300px;
}

/* Responsive table adjustments */
@media (max-width: 768px) {
    .table td, .table th {
        padding: 0.5rem;
        font-size: 0.85rem;
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .card-body {
        padding: 1rem !important;
    }

    h2.fs-4 {
        font-size: 1.25rem !important;
    }
}

.fs-12 {
    font-size: 0.75rem;
}

/* Progress bar animation */
.progress-bar-striped {
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%,
        transparent
    );
    background-size: 0.75rem 0.75rem;
    animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
    0% { background-position-x: 0.75rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live Clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        document.getElementById('currentTime').textContent = timeString;
    }
    setInterval(updateClock, 60000);
    updateClock();

    // Refresh Dashboard
    const refreshBtn = document.getElementById('refreshDashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const btn = this;
            const icon = btn.querySelector('i');

            icon.classList.add('fa-spin');
            btn.disabled = true;

            setTimeout(() => {
                window.location.reload();
            }, 800);
        });
    }

    // Quick transfer view
    document.querySelectorAll('.transfer-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const transferId = this.dataset.id;
            loadQuickTransfer(transferId);
        });
    });

    // Fungsi untuk memuat transfer quick view
    function loadQuickTransfer(id) {
        fetch(`/transfers/${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transfer = data.data;
                    const content = `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">${transfer.transfer_no}</span>
                                <span class="badge ${transfer.status === 'COMPLETED' ? 'bg-success-subtle text-success' :
                                                    transfer.status === 'SUBMITTED' ? 'bg-warning-subtle text-warning' :
                                                    'bg-danger-subtle text-danger'} border">
                                    ${transfer.status}
                                </span>
                            </div>
                            <div class="row small mb-2">
                                <div class="col-6">
                                    <div class="text-muted">Document</div>
                                    <div>${transfer.document_no}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">Quantity</div>
                                    <div>${parseFloat(transfer.total_qty).toLocaleString()}</div>
                                </div>
                            </div>
                            <div class="row small">
                                <div class="col-6">
                                    <div class="text-muted">From</div>
                                    <div class="badge bg-info-subtle text-info border px-2">${transfer.plant_supply}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">To</div>
                                    <div class="badge bg-primary-subtle text-primary border px-2">${transfer.plant_destination}</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="loadTransferDetailsFromDashboard(${id})">Full Details</button>
                        </div>
                    `;
                    document.getElementById('quickTransferContent').innerHTML = content;

                    // Gunakan cara yang lebih aman untuk membuka modal
                    const quickModalEl = document.getElementById('quickTransferModal');
                    if (quickModalEl) {
                        const quickModal = bootstrap.Modal.getOrCreateInstance(quickModalEl);
                        quickModal.show();
                    }
                }
            })
            .catch(error => {
                console.error('Error loading transfer:', error);
            });
    }

    // Fungsi untuk memuat detail transfer lengkap
    function loadTransferDetailsFromDashboard(id) {
        // Tutup modal quick view dengan cara yang aman
        const quickModalEl = document.getElementById('quickTransferModal');
        if (quickModalEl) {
            const quickModal = bootstrap.Modal.getInstance(quickModalEl);
            if (quickModal) {
                quickModal.hide();
            }
        }

        // Buka modal detail lengkap
        const modalEl = document.getElementById('transferDetailModal');
        if (!modalEl) {
            console.error('Transfer Detail Modal element not found');
            return;
        }

        const modal = new bootstrap.Modal(modalEl);
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

        fetch(`/transfers/${id}?_details=1`)
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
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadTransferDetailsFromDashboard(${id})">
                            <i class="fas fa-redo me-1"></i>Retry
                        </button>
                    </div>
                `;
            });
    }

    // Fungsi untuk generate konten detail transfer
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
                                            <div class="badge plant-supply px-3 py-1">
                                                ${transfer.plant_supply || 'N/A'}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small mb-1">Dest Plant</div>
                                            <div class="badge plant-destination px-3 py-1">
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
                        ${transfer.document_id ? `
                        <a href="/documents/${transfer.document_id}" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-file-alt me-1"></i>View Document
                        </a>
                        ` : ''}
                        <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="copyTransferDetailsNow(${transfer.id})">
                            <i class="fas fa-copy me-1"></i>Copy Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Fungsi helper untuk generate tabel items
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

    // Fungsi helper untuk status
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
        return new Intl.NumberFormat('id-ID').format(num);
    }

    // Fungsi untuk copy transfer details
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

    // Fungsi untuk show toast notification
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

    // Export fungsi ke global scope
    window.loadTransferDetailsFromDashboard = loadTransferDetailsFromDashboard;
    window.copyTransferDetailsNow = copyTransferDetailsNow;

    // Auto refresh stats every 2 minutes
    setInterval(() => {
        console.log('Dashboard auto-refreshed at', new Date().toLocaleTimeString());
    }, 120000);
});
</script>
@endsection
