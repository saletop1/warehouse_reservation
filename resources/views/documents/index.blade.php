@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('reservations.index') }}">Reservations</a></li>
                    <li class="breadcrumb-item active">Reservation Documents</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Reservation Documents</h2>
                <div class="text-muted" id="documentCounter">
                    <i class="fas fa-file-alt me-1"></i>
                    <span id="visibleCount">{{ $documents->count() }}</span> of {{ $documents->count() }} documents
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-lg mb-3 floating-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-lg me-3"></i>
                        <div>
                            <strong class="d-block">Success!</strong>
                            <small class="d-block mt-1">{{ session('success') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                {{-- Set session flag untuk create page --}}
                @php
                    session(['accessed_reservations_index' => true]);
                @endphp
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Documents Table --}}
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                    <div>
                        <h5 class="mb-1 fw-bold">Reservation Documents</h5>
                        <small class="text-muted">Browse and search through all reservation documents</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="input-group input-group-sm" style="width: 300px;">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control border-start-0 ps-0"
                                   id="liveSearch"
                                   placeholder="Search documents...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 position-relative">
                    <div class="table-container">
                        <table class="table table-hover mb-0" id="documentsTable">
                            <thead class="table-light sticky-header">
                                <tr>
                                    <th class="ps-4">Document No</th>
                                    <th>Plant</th>
                                    <th>Status</th>
                                    <th>Completion</th>
                                    <th>Remarks</th>
                                    <th>Created By</th>
                                    <th class="pe-4">Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $document)
                                    <tr class="align-middle document-row">
                                        <td class="ps-4">
                                            <a href="{{ route('documents.show', $document->id) }}"
                                               class="document-link {{ $document->plant == '3000' ? 'text-primary' : 'text-success' }} fw-bold d-flex align-items-center">
                                                <div class="document-icon me-2">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                                <div class="document-info">
                                                    <div class="fw-bold">{{ $document->document_no }}</div>
                                                    <small class="text-muted d-block">ID: {{ $document->id }}</small>
                                                </div>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="plant-badge d-flex align-items-center">
                                                <div class="plant-indicator me-2
                                                    {{ $document->plant == '3000' ? 'bg-primary' : 'bg-success' }}"></div>
                                                <span class="fw-medium">{{ $document->plant }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="status-container">
                                                @if($document->status == 'booked')
                                                    <span class="badge status-badge booked">
                                                        <i class="fas fa-clock me-1"></i> Booked
                                                    </span>
                                                @elseif($document->status == 'partial')
                                                    <span class="badge status-badge partial">
                                                        <i class="fas fa-sync-alt me-1"></i> Partial
                                                    </span>
                                                @elseif($document->status == 'closed')
                                                    <span class="badge status-badge closed">
                                                        <i class="fas fa-check-circle me-1"></i> Closed
                                                    </span>
                                                @else
                                                    <span class="badge status-badge cancelled">
                                                        <i class="fas fa-times-circle me-1"></i> Cancelled
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="completion-container">
                                                @php
                                                    $completionRate = $document->completion_rate ?? 0;
                                                    $color = $completionRate == 100 ? 'success' :
                                                             ($completionRate > 0 ? 'info' : 'secondary');
                                                    $icon = $completionRate == 100 ? 'fa-check-circle' :
                                                           ($completionRate > 50 ? 'fa-spinner' : 'fa-hourglass-start');
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <div class="completion-chart me-3">
                                                        <div class="chart-circle" data-percent="{{ $completionRate }}">
                                                            <svg class="chart-svg" width="40" height="40" viewBox="0 0 40 40">
                                                                <circle class="chart-bg" cx="20" cy="20" r="18"></circle>
                                                                <circle class="chart-progress chart-{{ $color }}"
                                                                        cx="20" cy="20" r="18"
                                                                        style="stroke-dasharray: {{ $completionRate * 1.13 }} 113;"></circle>
                                                                <text class="chart-text" x="20" y="23" text-anchor="middle">
                                                                    {{ round($completionRate) }}%
                                                                </text>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="completion-text">
                                                        <div class="fw-medium">{{ round($completionRate, 1) }}% Complete</div>
                                                        @if($completionRate == 100)
                                                            <small class="text-success">Fully Completed</small>
                                                        @elseif($completionRate > 0)
                                                            <small class="text-info">In Progress</small>
                                                        @else
                                                            <small class="text-secondary">Not Started</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="remarks-container">
                                                @if($document->remarks && trim($document->remarks) != '')
                                                    <div class="remarks-text"
                                                         data-bs-toggle="tooltip"
                                                         title="{{ $document->remarks }}">
                                                        <i class="fas fa-comment me-2 text-muted"></i>
                                                        {{ Str::limit($document->remarks, 40) }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        <i class="fas fa-comment-slash me-2"></i>
                                                        No remarks
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="creator-info">
                                                <div class="fw-medium">{{ $document->created_by_name }}</div>
                                                <small class="text-muted">Creator</small>
                                            </div>
                                        </td>
                                        <td class="pe-4">
                                            <div class="timestamp">
                                                <div class="date">{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                                                <div class="time text-muted">
                                                    {{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-file-alt fa-4x mb-4 text-light"></i>
                                                <h4 class="mb-2">No documents found</h4>
                                                <p class="text-muted">Start by creating your first reservation document</p>
                                                <button class="btn btn-primary mt-3">
                                                    <i class="fas fa-plus me-2"></i>Create Document
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Empty search results message --}}
                    <div id="noResults" class="d-none text-center py-5">
                        <i class="fas fa-search fa-4x mb-4 text-muted"></i>
                        <h4 class="text-muted">No matching documents</h4>
                        <p class="text-muted">Try adjusting your search terms</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table-container {
        max-height: 70vh;
        overflow-y: auto;
        position: relative;
    }

    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .document-row:hover {
        background-color: #f8f9fa;
        transform: translateY(-1px);
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .document-link {
        text-decoration: none;
        transition: color 0.2s;
    }

    .document-link:hover {
        text-decoration: underline;
    }

    .document-icon {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
    }

    .plant-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .plant-badge {
        padding: 6px 12px;
        background-color: #f8f9fa;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }

    .status-badge.booked {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.2);
    }

    .status-badge.partial {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        border: 1px solid rgba(13, 110, 253, 0.2);
    }

    .status-badge.closed {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
        border: 1px solid rgba(25, 135, 84, 0.2);
    }

    .status-badge.cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.2);
    }

    .completion-chart {
        position: relative;
        width: 40px;
        height: 40px;
    }

    .chart-svg {
        transform: rotate(-90deg);
    }

    .chart-bg {
        fill: none;
        stroke: #e9ecef;
        stroke-width: 3;
    }

    .chart-progress {
        fill: none;
        stroke-width: 3;
        stroke-linecap: round;
        transition: stroke-dasharray 0.3s ease;
    }

    .chart-success {
        stroke: #198754;
    }

    .chart-info {
        stroke: #0dcaf0;
    }

    .chart-secondary {
        stroke: #6c757d;
    }

    .chart-text {
        font-size: 8px;
        font-weight: bold;
        fill: #495057;
        transform: rotate(90deg);
        transform-origin: 50% 50%;
    }

    .remarks-container {
        max-width: 200px;
    }

    .remarks-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: help;
        padding: 4px 8px;
        background-color: #f8f9fa;
        border-radius: 4px;
        display: flex;
        align-items: center;
    }

    .timestamp {
        min-width: 100px;
    }

    .creator-info {
        min-width: 120px;
    }

    #documentCounter {
        background-color: #f8f9fa;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        border: 1px solid #e9ecef;
    }

    .empty-state {
        padding: 40px 0;
    }

    #noResults {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 5;
        background: white;
        width: 100%;
    }

    th {
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        padding-top: 16px !important;
        padding-bottom: 16px !important;
        border-bottom: 2px solid #e9ecef !important;
    }

    td {
        padding-top: 14px !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid #f8f9fa !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Live Search Functionality
    const liveSearch = document.getElementById('liveSearch');
    const clearSearch = document.getElementById('clearSearch');
    const documentsTable = document.getElementById('documentsTable');
    const rows = documentsTable.getElementsByTagName('tbody')[0].getElementsByClassName('document-row');
    const visibleCount = document.getElementById('visibleCount');
    const totalCount = {{ $documents->count() }};
    const noResults = document.getElementById('noResults');

    // Update visible count
    function updateVisibleCount() {
        let visibleRows = 0;
        for (let row of rows) {
            if (row.style.display !== 'none') {
                visibleRows++;
            }
        }
        visibleCount.textContent = visibleRows;

        // Show/hide no results message
        if (visibleRows === 0 && rows.length > 0) {
            noResults.classList.remove('d-none');
            documentsTable.classList.add('d-none');
        } else {
            noResults.classList.add('d-none');
            documentsTable.classList.remove('d-none');
        }
    }

    liveSearch.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase().trim();

        for (let row of rows) {
            const cells = row.getElementsByTagName('td');
            let found = false;

            for (let cell of cells) {
                if (cell.textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                    break;
                }
            }

            row.style.display = found ? '' : 'none';
            row.style.opacity = found ? '1' : '0.5';
        }

        updateVisibleCount();
    });

    // Clear search
    clearSearch.addEventListener('click', function() {
        liveSearch.value = '';
        for (let row of rows) {
            row.style.display = '';
            row.style.opacity = '1';
        }
        updateVisibleCount();
    });

    // Initialize with all rows visible
    updateVisibleCount();

    // Add hover effects to table rows
    document.querySelectorAll('.document-row').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.08)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)';
        });
    });

    // Animate completion charts on scroll
    function animateChartsOnScroll() {
        const charts = document.querySelectorAll('.chart-circle');
        charts.forEach(chart => {
            const rect = chart.getBoundingClientRect();
            if (rect.top < window.innerHeight - 100) {
                chart.classList.add('animated');
            }
        });
    }

    window.addEventListener('scroll', animateChartsOnScroll);
    animateChartsOnScroll(); // Initial check
});
</script>
@endsection
