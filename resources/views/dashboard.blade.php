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

        {{-- Quick Actions & Transfers --}}
        <div class="col-lg-4 mb-3 mb-md-4">
            {{-- Quick Actions --}}
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('reservations.create') }}" class="quick-action-btn d-flex flex-column align-items-center p-2 rounded-2">
                                <div class="icon-wrapper bg-primary-subtle p-2 rounded-2 mb-2">
                                    <i class="fas fa-plus text-primary fs-5"></i>
                                </div>
                                <span class="small fw-medium text-center">New Doc</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('reservations.index') }}" class="quick-action-btn d-flex flex-column align-items-center p-2 rounded-2">
                                <div class="icon-wrapper bg-success-subtle p-2 rounded-2 mb-2">
                                    <i class="fas fa-sync text-success fs-5"></i>
                                </div>
                                <span class="small fw-medium text-center">Sync SAP</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('documents.index') }}" class="quick-action-btn d-flex flex-column align-items-center p-2 rounded-2">
                                <div class="icon-wrapper bg-info-subtle p-2 rounded-2 mb-2">
                                    <i class="fas fa-list text-info fs-5"></i>
                                </div>
                                <span class="small fw-medium text-center">All Docs</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('transfers.index') }}" class="quick-action-btn d-flex flex-column align-items-center p-2 rounded-2">
                                <div class="icon-wrapper bg-warning-subtle p-2 rounded-2 mb-2">
                                    <i class="fas fa-truck text-warning fs-5"></i>
                                </div>
                                <span class="small fw-medium text-center">Transfers</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Transfers --}}
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

    {{-- Activity Summary --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar me-2 text-info"></i>Today's Summary
                        <small class="text-muted ms-2">{{ now()->format('d M') }}</small>
                    </h5>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <div class="activity-card bg-primary-subtle border border-primary-subtle rounded-2 p-3 text-center">
                                <h3 class="fw-bold text-primary mb-1">{{ $todayStats['documents_created'] ?? 0 }}</h3>
                                <small class="text-muted d-block">Docs Created</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="activity-card bg-success-subtle border border-success-subtle rounded-2 p-3 text-center">
                                <h3 class="fw-bold text-success mb-1">{{ $todayStats['transfers_created'] ?? 0 }}</h3>
                                <small class="text-muted d-block">Transfers</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="activity-card bg-warning-subtle border border-warning-subtle rounded-2 p-3 text-center">
                                <h3 class="fw-bold text-warning mb-1">{{ $todayStats['documents_closed'] ?? 0 }}</h3>
                                <small class="text-muted d-block">Docs Closed</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="activity-card bg-info-subtle border border-info-subtle rounded-2 p-3 text-center">
                                <h3 class="fw-bold text-info mb-1">{{ $todayStats['total'] ?? 0 }}</h3>
                                <small class="text-muted d-block">Total Activity</small>
                            </div>
                        </div>
                    </div>
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

.quick-action-btn {
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.quick-action-btn:hover {
    background-color: var(--bs-light);
    border-color: var(--bs-border-color);
    transform: translateY(-2px);
}

.icon-wrapper {
    width: 40px;
    height: 40px;
}

.transfer-item {
    transition: background-color 0.2s ease;
}

.transfer-item:hover {
    background-color: #f8f9fa;
}

.activity-card {
    transition: transform 0.2s ease;
}

.activity-card:hover {
    transform: scale(1.02);
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
                            <a href="/transfers/${id}/view" class="btn btn-sm btn-outline-primary">Full Details</a>
                        </div>
                    `;
                    document.getElementById('quickTransferContent').innerHTML = content;
                    new bootstrap.Modal(document.getElementById('quickTransferModal')).show();
                }
            })
            .catch(error => {
                console.error('Error loading transfer:', error);
            });
    }

    // Auto refresh stats every 2 minutes
    setInterval(() => {
        console.log('Dashboard auto-refreshed at', new Date().toLocaleTimeString());
    }, 120000);
});
</script>
@endsection
