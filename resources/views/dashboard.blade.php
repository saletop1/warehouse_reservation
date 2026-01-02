@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    {{-- Welcome Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="fw-bold text-primary mb-2">
                                Good
                                @php
                                    $hour = now()->hour;
                                    if ($hour < 12) {
                                        echo 'Morning';
                                    } elseif ($hour < 17) {
                                        echo 'Afternoon';
                                    } else {
                                        echo 'Evening';
                                    }
                                @endphp,
                                {{ Auth::user()->name }}!
                            </h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-calendar-alt me-1"></i>
                                {{ now()->format('l, F j, Y') }} •
                                <i class="fas fa-clock me-1 ms-2"></i>
                                <span id="currentTime">{{ now()->format('H:i:s') }}</span> WIB
                            </p>
                            <div class="d-flex gap-2 mt-3">
                                <span class="badge bg-primary bg-opacity-25 text-primary border-0 py-2 px-3 rounded-pill">
                                    <i class="fas fa-user-tag me-1"></i>{{ Auth::user()->role ?? 'User' }}
                                </span>
                                @if(Auth::user()->plant)
                                <span class="badge bg-success bg-opacity-25 text-success border-0 py-2 px-3 rounded-pill">
                                    <i class="fas fa-building me-1"></i>{{ Auth::user()->plant }}
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-primary btn-lg px-4" id="refreshDashboard">
                                <i class="fas fa-sync-alt me-2"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-semibold mb-1">Booked Documents</h6>
                            <h2 class="fw-bold mb-1 text-warning">{{ $documentStats['booked'] ?? 0 }}</h2>
                            <small class="text-muted">Waiting for processing</small>
                        </div>
                        <div class="icon-wrapper bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-file-alt fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-semibold mb-1">Partial Transfers</h6>
                            <h2 class="fw-bold mb-1 text-info">{{ $documentStats['partial'] ?? 0 }}</h2>
                            <small class="text-muted">In progress</small>
                        </div>
                        <div class="icon-wrapper bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-exchange-alt fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-semibold mb-1">Completed</h6>
                            <h2 class="fw-bold mb-1 text-success">{{ $documentStats['closed'] ?? 0 }}</h2>
                            <small class="text-muted">Fully transferred</small>
                        </div>
                        <div class="icon-wrapper bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-semibold mb-1">Today's Activity</h6>
                            <h2 class="fw-bold mb-1 text-primary">{{ $todayStats['total'] ?? 0 }}</h2>
                            <div class="d-flex gap-1 mt-2">
                                <span class="badge bg-primary bg-opacity-25 text-primary border-0">
                                    {{ $todayStats['documents_created'] ?? 0 }} Docs
                                </span>
                                <span class="badge bg-success bg-opacity-25 text-success border-0">
                                    {{ $todayStats['transfers_created'] ?? 0 }} Transfers
                                </span>
                            </div>
                        </div>
                        <div class="icon-wrapper bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-chart-line fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Documents & Quick Actions --}}
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-file-contract me-2 text-primary"></i>Recent Documents
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('reservations.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>New
                            </a>
                            <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-primary">
                                View All
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2">Document No</th>
                                    <th class="py-2">Plant</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2">Items</th>
                                    <th class="py-2">Completion</th>
                                    <th class="py-2">Created By</th>
                                    <th class="pe-3 py-2 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentDocuments as $doc)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('documents.show', $doc->id) }}" class="fw-bold text-decoration-none">
                                            {{ $doc->document_no }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge {{ $doc->plant == '3000' ? 'bg-primary' : 'bg-success' }} bg-opacity-10 text-dark border-0">
                                            {{ $doc->plant }}
                                        </span>
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
                                        <span class="badge bg-light text-dark border">
                                            {{ $doc->items_count ?? 0 }} items
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                <div class="progress-bar bg-success progress-bar-striped"
                                                     style="width: {{ $doc->completion_rate_calculated ?? 0 }}%"></div>
                                            </div>
                                            <span class="fw-medium">{{ $doc->completion_rate_calculated ?? 0 }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $doc->created_by_name ?? 'System' }}</div>
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
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-file-alt fa-3x text-muted opacity-25 mb-3"></i>
                                            <h5 class="text-muted">No documents yet</h5>
                                            <p class="text-muted mb-4">Start by creating your first document</p>
                                            <a href="{{ route('reservations.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Create First Document
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

        <div class="col-lg-4 mb-4">
            {{-- Quick Actions --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-lg text-start">
                            <i class="fas fa-plus-circle me-2"></i>Create New Document
                        </a>
                        <a href="{{ route('reservations.index') }}" class="btn btn-success btn-lg text-start">
                            <i class="fas fa-sync me-2"></i>Sync from SAP
                        </a>
                        <a href="{{ route('documents.index') }}" class="btn btn-info btn-lg text-start">
                            <i class="fas fa-list me-2"></i>View All Documents
                        </a>
                        <a href="{{ route('transfers.index') }}" class="btn btn-warning btn-lg text-start">
                            <i class="fas fa-truck me-2"></i>View Transfers
                        </a>
                    </div>
                </div>
            </div>

            {{-- Recent Transfers --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-truck me-2 text-success"></i>Recent Transfers
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($recentTransfers->count() > 0)
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

    {{-- Today's Summary --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar me-2 text-info"></i>Today's Summary
                        <small class="text-muted ms-2">{{ now()->format('d M Y') }}</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <h2 class="text-primary fw-bold">{{ $todayStats['documents_created'] ?? 0 }}</h2>
                                <small class="text-muted">Documents Created</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <h2 class="text-success fw-bold">{{ $todayStats['transfers_created'] ?? 0 }}</h2>
                                <small class="text-muted">Transfers Created</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <h2 class="text-warning fw-bold">{{ $todayStats['documents_closed'] ?? 0 }}</h2>
                                <small class="text-muted">Documents Closed</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <h2 class="text-info fw-bold">{{ $todayStats['total'] ?? 0 }}</h2>
                                <small class="text-muted">Total Activities</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}
.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}
.icon-wrapper {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.empty-state {
    opacity: 0.5;
}
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
    background-size: 1rem 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update clock every second
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
        document.getElementById('currentTime').textContent = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Refresh Dashboard
    document.getElementById('refreshDashboard').addEventListener('click', function() {
        const btn = this;
        const icon = btn.querySelector('i');

        // Add spinning animation
        icon.classList.add('fa-spin');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...';

        // Refresh page after 1 second
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    });

    // Add hover effects to stats cards
    document.querySelectorAll('.stats-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Auto-refresh dashboard every 5 minutes
    setInterval(() => {
        console.log('Auto-refreshing dashboard...');
        // In production, you would make an AJAX call to update stats
    }, 300000); // 5 minutes
});
</script>
@endsection
