@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    {{-- Welcome Section with Quick Stats --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="fw-bold text-primary mb-2">Good
                                @php
                                    $hour = now()->hour;
                                    if ($hour < 12) {
                                        echo 'Morning';
                                    } elseif ($hour < 17) {
                                        echo 'Afternoon';
                                    } else {
                                        echo 'Evening';
                                    }
                                @endphp, {{ Auth::user()->name }}!
                            </h2>
                            <p class="text-muted mb-0">Here's what's happening with your SAP reservations today</p>
                            <div class="d-flex gap-3 mt-3">
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-calendar-day me-1"></i>{{ now()->format('l, F j, Y') }}
                                </span>
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-clock me-1"></i>{{ now()->format('H:i') }} WIB
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-outline-primary btn-sm" id="refreshDashboard">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Document Status Summary --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-semibold mb-1">Booked Documents</h6>
                            <h2 class="fw-bold mb-1">{{ $documentStats['booked'] ?? 0 }}</h2>
                            <span class="text-warning small">
                                <i class="fas fa-clock me-1"></i>Waiting for Transfer
                            </span>
                        </div>
                        <div class="icon-wrapper bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-file-alt fa-lg text-warning"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Documents ready for processing</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-semibold mb-1">Partial Transfers</h6>
                            <h2 class="fw-bold mb-1">{{ $documentStats['partial'] ?? 0 }}</h2>
                            <span class="text-info small">
                                <i class="fas fa-tasks me-1"></i>In Progress
                            </span>
                        </div>
                        <div class="icon-wrapper bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-exchange-alt fa-lg text-info"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Partially transferred documents</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-semibold mb-1">Closed Documents</h6>
                            <h2 class="fw-bold mb-1">{{ $documentStats['closed'] ?? 0 }}</h2>
                            <span class="text-success small">
                                <i class="fas fa-check-circle me-1"></i>Completed
                            </span>
                        </div>
                        <div class="icon-wrapper bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-check fa-lg text-success"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Fully transferred documents</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-semibold mb-1">Today's Activity</h6>
                            <h2 class="fw-bold mb-1">{{ $todayStats['documents_created'] ?? 0 }}</h2>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary border-0 py-1 px-2">
                                    {{ $todayStats['transfers_created'] ?? 0 }} Transfers
                                </span>
                                <span class="badge bg-success bg-opacity-10 text-success border-0 py-1 px-2">
                                    {{ $todayStats['documents_closed'] ?? 0 }} Closed
                                </span>
                            </div>
                        </div>
                        <div class="icon-wrapper bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-chart-line fa-lg text-primary"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Activities for {{ now()->format('d M Y') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-bolt me-2 text-primary"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <a href="{{ route('reservations.index') }}?sync=true" class="card border-0 shadow-sm h-100 text-decoration-none hover-lift">
                                <div class="card-body text-center py-4">
                                    <div class="icon-wrapper bg-primary bg-opacity-10 rounded-circle p-3 mb-3 mx-auto">
                                        <i class="fas fa-sync fa-2x text-primary"></i>
                                    </div>
                                    <h6 class="mb-2 fw-semibold">Sync from SAP</h6>
                                    <p class="text-muted small mb-0">Refresh SAP reservation data</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-6 col-md-3">
                            <a href="{{ route('reservations.create') }}" class="card border-0 shadow-sm h-100 text-decoration-none hover-lift">
                                <div class="card-body text-center py-4">
                                    <div class="icon-wrapper bg-success bg-opacity-10 rounded-circle p-3 mb-3 mx-auto">
                                        <i class="fas fa-plus-circle fa-2x text-success"></i>
                                    </div>
                                    <h6 class="mb-2 fw-semibold">Create Document</h6>
                                    <p class="text-muted small mb-0">Create new reservation document</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-6 col-md-3">
                            <a href="{{ route('documents.index') }}" class="card border-0 shadow-sm h-100 text-decoration-none hover-lift">
                                <div class="card-body text-center py-4">
                                    <div class="icon-wrapper bg-info bg-opacity-10 rounded-circle p-3 mb-3 mx-auto">
                                        <i class="fas fa-list fa-2x text-info"></i>
                                    </div>
                                    <h6 class="mb-2 fw-semibold">View Documents</h6>
                                    <p class="text-muted small mb-0">Browse all reservation documents</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-6 col-md-3">
                            <a href="{{ route('transfers.index') }}" class="card border-0 shadow-sm h-100 text-decoration-none hover-lift">
                                <div class="card-body text-center py-4">
                                    <div class="icon-wrapper bg-warning bg-opacity-10 rounded-circle p-3 mb-3 mx-auto">
                                        <i class="fas fa-truck fa-2x text-warning"></i>
                                    </div>
                                    <h6 class="mb-2 fw-semibold">View Transfers</h6>
                                    <p class="text-muted small mb-0">Monitor transfer activities</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Documents & Activity Section --}}
    <div class="row">
        {{-- Recent Documents Column --}}
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-history me-2 text-primary"></i>Recent Documents
                        </h5>
                        <div class="d-flex gap-2">
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <input type="text"
                                       class="form-control"
                                       placeholder="Search documents..."
                                       id="searchDocuments">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <select class="form-select form-select-sm" id="statusFilter" style="width: 120px;">
                                <option value="">All Status</option>
                                <option value="booked">Booked</option>
                                <option value="partial">Partial</option>
                                <option value="closed">Closed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @php
                        $recentDocuments = \App\Models\ReservationDocument::withCount(['transfers', 'items'])
                            ->orderBy('created_at', 'desc')
                            ->limit(10)
                            ->get();
                    @endphp

                    @if($recentDocuments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="documentsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-2">Document No</th>
                                        <th class="py-2">Plant</th>
                                        <th class="py-2">Status</th>
                                        <th class="py-2">Items</th>
                                        <th class="py-2">Transfers</th>
                                        <th class="py-2">Created By</th>
                                        <th class="py-2">Created At</th>
                                        <th class="pe-3 py-2 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentDocuments as $doc)
                                        @php
                                            $itemsCount = $doc->items_count ?? $doc->items()->count();
                                            $transfersCount = $doc->transfers_count ?? $doc->transfers()->count();
                                            $bgColor = $doc->plant == '3000' ? 'bg-primary bg-opacity-10' :
                                                      ($doc->plant == '4000' ? 'bg-success bg-opacity-10' : 'bg-info bg-opacity-10');
                                        @endphp
                                        <tr class="document-row"
                                            data-status="{{ $doc->status }}"
                                            data-search="{{ strtolower($doc->document_no . ' ' . $doc->plant . ' ' . $doc->created_by_name) }}">
                                            <td class="ps-3">
                                                <a href="{{ route('documents.show', $doc->id) }}" class="fw-bold text-decoration-none">
                                                    {{ $doc->document_no }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge {{ $bgColor }} text-dark border-0">{{ $doc->plant }}</span>
                                            </td>
                                            <td>
                                                @if($doc->status == 'booked')
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                                        <i class="fas fa-clock me-1"></i>Booked
                                                    </span>
                                                @elseif($doc->status == 'partial')
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                                        <i class="fas fa-tasks me-1"></i>Partial
                                                    </span>
                                                @elseif($doc->status == 'closed')
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                        <i class="fas fa-check-circle me-1"></i>Closed
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                                        <i class="fas fa-times-circle me-1"></i>Cancelled
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $itemsCount }} items</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $transfersCount }} transfers</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-2">
                                                        <div class="avatar-title bg-light text-dark rounded-circle">
                                                            {{ substr($doc->created_by_name, 0, 1) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium">{{ $doc->created_by_name }}</div>
                                                        <small class="text-muted">{{ $doc->created_by }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-nowrap">
                                                    {{ \Carbon\Carbon::parse($doc->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y') }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($doc->created_at)->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                                                </small>
                                            </td>
                                            <td class="pe-3 text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('documents.show', $doc->id) }}"
                                                       class="btn btn-outline-primary"
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($doc->status == 'booked')
                                                    <a href="{{ route('documents.edit', $doc->id) }}"
                                                       class="btn btn-outline-warning"
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endif
                                                    <a href="{{ route('documents.print', $doc->id) }}"
                                                       class="btn btn-outline-success"
                                                       title="Print">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="empty-state-icon mb-3">
                                <i class="fas fa-file-alt fa-4x text-muted opacity-25"></i>
                            </div>
                            <h5 class="text-muted">No documents yet</h5>
                            <p class="text-muted mb-4">Start by creating your first document</p>
                            <a href="{{ route('reservations.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Document
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white border-top py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small">Showing {{ min($recentDocuments->count(), 10) }} of {{ \App\Models\ReservationDocument::count() }} documents</span>
                        </div>
                        <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-primary">
                            View All Documents <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Transfers & Today's Summary Column --}}
        <div class="col-lg-4 mb-4">
            {{-- Today's Summary --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar me-2 text-success"></i>Today's Summary
                    </h5>
                    <small class="text-muted">{{ now()->format('d M Y') }}</small>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4 mb-3">
                            <div class="p-2">
                                <h3 class="text-primary mb-1 fw-bold">
                                    {{ $todayStats['documents_created'] ?? 0 }}
                                </h3>
                                <small class="text-muted">Documents</small>
                            </div>
                        </div>
                        <div class="col-4 mb-3">
                            <div class="p-2">
                                <h3 class="text-success mb-1 fw-bold">
                                    {{ $todayStats['transfers_created'] ?? 0 }}
                                </h3>
                                <small class="text-muted">Transfers</small>
                            </div>
                        </div>
                        <div class="col-4 mb-3">
                            <div class="p-2">
                                <h3 class="text-warning mb-1 fw-bold">
                                    {{ $todayStats['documents_closed'] ?? 0 }}
                                </h3>
                                <small class="text-muted">Closed</small>
                            </div>
                        </div>
                    </div>

                    {{-- Document Status Distribution --}}
                    <div class="mt-3">
                        <h6 class="small text-uppercase text-muted mb-2">Document Status Distribution</h6>
                        <div class="d-flex gap-2">
                            <div class="flex-grow-1">
                                <div class="progress" style="height: 8px;">
                                    @php
                                        $totalDocs = \App\Models\ReservationDocument::count();
                                        $bookedDocs = \App\Models\ReservationDocument::where('status', 'booked')->count();
                                        $partialDocs = \App\Models\ReservationDocument::where('status', 'partial')->count();
                                        $closedDocs = \App\Models\ReservationDocument::where('status', 'closed')->count();
                                        $cancelledDocs = \App\Models\ReservationDocument::where('status', 'cancelled')->count();

                                        $bookedPercent = $totalDocs > 0 ? ($bookedDocs / $totalDocs) * 100 : 0;
                                        $partialPercent = $totalDocs > 0 ? ($partialDocs / $totalDocs) * 100 : 0;
                                        $closedPercent = $totalDocs > 0 ? ($closedDocs / $totalDocs) * 100 : 0;
                                        $cancelledPercent = $totalDocs > 0 ? ($cancelledDocs / $totalDocs) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar bg-warning" style="width: {{ $bookedPercent }}%" title="Booked"></div>
                                    <div class="progress-bar bg-info" style="width: {{ $partialPercent }}%" title="Partial"></div>
                                    <div class="progress-bar bg-success" style="width: {{ $closedPercent }}%" title="Closed"></div>
                                    <div class="progress-bar bg-danger" style="width: {{ $cancelledPercent }}%" title="Cancelled"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span>Booked: {{ $bookedDocs }}</span>
                                    <span>Partial: {{ $partialDocs }}</span>
                                    <span>Closed: {{ $closedDocs }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Transfers --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-truck me-2 text-primary"></i>Recent Transfers
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if(isset($recentTransfers) && $recentTransfers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentTransfers as $transfer)
                                <div class="list-group-item px-3 py-2 border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium small">{{ $transfer->transfer_no }}</div>
                                            <small class="text-muted">Doc: {{ $transfer->document_no }}</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold">{{ number_format($transfer->total_qty) }}</div>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($transfer->created_at)->format('H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-truck fa-2x text-muted opacity-25 mb-2"></i>
                            <p class="text-muted small mb-0">No recent transfers</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white border-top py-2">
                    <a href="{{ route('transfers.index') }}" class="btn btn-sm btn-outline-primary w-100">
                        View All Transfers <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;"></div>

<style>
/* Custom Styles */
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.icon-wrapper {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.empty-state-icon {
    opacity: 0.5;
}

/* Progress bar */
.progress {
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}

/* Table styles */
.table th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.document-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Badge styles */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .card-header .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }

    .card-header .d-flex > div:last-child {
        margin-top: 0.5rem;
        width: 100%;
    }

    .input-group, .form-select {
        width: 100% !important;
    }

    .icon-wrapper {
        width: 50px;
        height: 50px;
    }

    .icon-wrapper i {
        font-size: 1.25rem;
    }
}

/* Animation for refresh */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.spin {
    animation: spin 0.5s linear;
}

/* Toast styles */
.toast {
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.toast-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.toast-body {
    padding: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh Dashboard Button
    const refreshBtn = document.getElementById('refreshDashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('spin');

            // Simulate loading
            setTimeout(() => {
                icon.classList.remove('spin');
                showToast('Dashboard refreshed successfully', 'success');
                // In real implementation, you might want to reload specific data via AJAX
                window.location.reload();
            }, 500);
        });
    }

    // Search Documents
    const searchInput = document.getElementById('searchDocuments');
    const statusFilter = document.getElementById('statusFilter');
    const documentRows = document.querySelectorAll('.document-row');

    function filterDocuments() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusTerm = statusFilter.value;

        documentRows.forEach(row => {
            const rowText = row.getAttribute('data-search');
            const rowStatus = row.getAttribute('data-status');

            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = !statusTerm || rowStatus === statusTerm;

            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterDocuments);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', filterDocuments);
    }

    // Toast Notification Function
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast-' + Date.now();

        const bgClass = {
            'success': 'bg-success',
            'error': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-info'
        }[type] || 'bg-info';

        const iconClass = {
            'success': 'fa-check-circle',
            'error': 'fa-times-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        }[type] || 'fa-info-circle';

        const toastHTML = `
            <div id="${toastId}" class="toast ${bgClass} text-white border-0" role="alert">
                <div class="toast-header ${bgClass} text-white border-0">
                    <i class="fas ${iconClass} me-2"></i>
                    <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);

        const toast = new bootstrap.Toast(toastElement, {
            delay: 5000,
            animation: true,
            autohide: true
        });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Welcome message based on time
    function updateWelcomeMessage() {
        const hour = new Date().getHours();
        let greeting = 'Good ';

        if (hour < 12) {
            greeting += 'Morning';
        } else if (hour < 17) {
            greeting += 'Afternoon';
        } else {
            greeting += 'Evening';
        }

        const welcomeElement = document.querySelector('.text-primary');
        if (welcomeElement && welcomeElement.textContent.includes('Good')) {
            welcomeElement.textContent = greeting + ', ' + "{{ Auth::user()->name }}!";
        }
    }

    // Update time every minute
    function updateTime() {
        const now = new Date();
        const timeElements = document.querySelectorAll('.fa-clock').forEach(icon => {
            const parent = icon.closest('.badge');
            if (parent) {
                const timeString = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
                parent.innerHTML = `<i class="fas fa-clock me-1"></i>${timeString} WIB`;
            }
        });
    }

    // Initialize time updates
    setInterval(updateTime, 60000);
    updateTime();
    updateWelcomeMessage();
});
</script>
@endsection
