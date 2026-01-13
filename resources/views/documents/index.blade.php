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
                    <div class="d-flex flex-column align-items-end">
                        <!-- Search and Filter Controls -->
                        <div class="d-flex align-items-center mb-2">
                            <!-- Search Box -->
                            <form id="searchForm" method="GET" action="{{ route('documents.index') }}" class="d-flex align-items-center">
                                <div class="input-group input-group-sm me-2" style="width: 250px;">
                                    <span class="input-group-text bg-transparent border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control border-start-0 ps-0"
                                           id="liveSearch"
                                           name="search"
                                           value="{{ request('search') }}"
                                           placeholder="Search documents..."
                                           autocomplete="off">
                                    @if(request('search'))
                                        <button type="button" class="btn btn-outline-secondary border-start-0" id="clearSearch">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>

                                <!-- Status Filter Dropdown -->
                                <div class="dropdown me-2">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-filter me-1"></i>
                                        @if(request('status') && in_array(request('status'), ['booked', 'partial', 'closed', 'cancelled']))
                                            {{ ucfirst(request('status')) }}
                                        @else
                                            All Status
                                        @endif
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="statusFilterDropdown">
                                        <li>
                                            <a class="dropdown-item status-filter {{ !request('status') || request('status') == 'all' || !in_array(request('status'), ['booked', 'partial', 'closed', 'cancelled']) ? 'active' : '' }}"
                                               href="#"
                                               data-status="all">
                                                All Status
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item status-filter {{ request('status') == 'booked' ? 'active' : '' }}"
                                               href="#"
                                               data-status="booked">
                                                Booked
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item status-filter {{ request('status') == 'partial' ? 'active' : '' }}"
                                               href="#"
                                               data-status="partial">
                                                Partial
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item status-filter {{ request('status') == 'closed' ? 'active' : '' }}"
                                               href="#"
                                               data-status="closed">
                                                Closed
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item status-filter {{ request('status') == 'cancelled' ? 'active' : '' }}"
                                               href="#"
                                               data-status="cancelled">
                                                Cancelled
                                            </a>
                                        </li>
                                    </ul>
                                    <input type="hidden" name="status" id="statusFilter" value="{{ request('status', 'all') }}">
                                </div>

                                <!-- Per Page Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="perPageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-list-ol me-1"></i>
                                        {{ request('per_page', 50) }} per page
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="perPageDropdown">
                                        <li><a class="dropdown-item per-page-filter {{ request('per_page', 50) == 25 ? 'active' : '' }}" href="#" data-perpage="25">25 per page</a></li>
                                        <li><a class="dropdown-item per-page-filter {{ request('per_page', 50) == 50 ? 'active' : '' }}" href="#" data-perpage="50">50 per page</a></li>
                                        <li><a class="dropdown-item per-page-filter {{ request('per_page', 50) == 100 ? 'active' : '' }}" href="#" data-perpage="100">100 per page</a></li>
                                        <li><a class="dropdown-item per-page-filter {{ request('per_page', 50) == 200 ? 'active' : '' }}" href="#" data-perpage="200">200 per page</a></li>
                                    </ul>
                                    <input type="hidden" name="per_page" id="perPageFilter" value="{{ request('per_page', 50) }}">
                                </div>

                                <!-- Hidden submit button for form -->
                                <button type="submit" class="d-none" id="formSubmit"></button>
                            </form>
                        </div>
                        <!-- Document Counter -->
                        <div class="text-muted small" id="documentCounter">
                            <i class="fas fa-file-alt me-1"></i>
                            Showing {{ $documents->firstItem() ?? 0 }}-{{ $documents->lastItem() ?? 0 }} of {{ $documents->total() }} documents
                            @if(request('search'))
                                <span class="text-primary ms-2">
                                    <i class="fas fa-search me-1"></i>Search: "{{ request('search') }}"
                                </span>
                            @endif
                            @if(request('status') && in_array(request('status'), ['booked', 'partial', 'closed', 'cancelled']))
                                <span class="text-info ms-2">
                                    <i class="fas fa-filter me-1"></i>Status: {{ ucfirst(request('status')) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 position-relative">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="documentsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'document_no', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                            Document No
                                            @if(request('sort') == 'document_no')
                                                <i class="fas fa-sort-{{ request('direction', 'asc') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'plant', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                            Plant
                                            @if(request('sort') == 'plant')
                                                <i class="fas fa-sort-{{ request('direction', 'asc') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                            Status
                                            @if(request('sort') == 'status')
                                                <i class="fas fa-sort-{{ request('direction', 'asc') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'completion_rate', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                            Completion
                                            @if(request('sort') == 'completion_rate')
                                                <i class="fas fa-sort-{{ request('direction', 'asc') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Remarks</th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_by_name', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                            Created By
                                            @if(request('sort') == 'created_by_name')
                                                <i class="fas fa-sort-{{ request('direction', 'asc') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="pe-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                            Created At
                                            @if(request('sort') == 'created_at')
                                                <i class="fas fa-sort-{{ request('direction', 'asc') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="documentsTableBody">
                                @include('documents.partials.table', ['documents' => $documents])
                            </tbody>
                        </table>
                    </div>

                    <div id="paginationContainer">
                        @include('documents.partials.pagination', ['documents' => $documents])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table-responsive {
        max-height: 70vh;
        overflow-y: auto;
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

    .status-badge.secondary {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
        border: 1px solid rgba(108, 117, 125, 0.2);
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
        font-size: 0.85rem;
        color: #6c757d;
    }

    .empty-state {
        padding: 40px 0;
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
        position: sticky;
        top: 0;
        background-color: white;
        z-index: 10;
    }

    td {
        padding-top: 14px !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid #f8f9fa !important;
    }

    .dropdown-item.active {
        background-color: #0d6efd;
        color: white;
    }

    .page-link {
        color: #0d6efd;
    }

    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Elements
    const searchForm = document.getElementById('searchForm');
    const liveSearch = document.getElementById('liveSearch');
    const statusFilter = document.getElementById('statusFilter');
    const perPageFilter = document.getElementById('perPageFilter');
    const clearSearchBtn = document.getElementById('clearSearch');
    const statusFilterItems = document.querySelectorAll('.status-filter');
    const perPageItems = document.querySelectorAll('.per-page-filter');
    const statusFilterButton = document.getElementById('statusFilterDropdown');
    const perPageButton = document.getElementById('perPageDropdown');

    // Debounce function untuk delay search
    let searchTimeout;
    let isSearching = false;

    // Clear search button
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            liveSearch.value = '';
            submitForm();
        });
    }

    // Status filter event
    statusFilterItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();

            // Update active state
            statusFilterItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            // Update filter value
            const statusValue = this.getAttribute('data-status');
            statusFilter.value = statusValue;

            // Update button text
            const filterText = this.textContent;
            statusFilterButton.innerHTML = `<i class="fas fa-filter me-1"></i> ${filterText}`;

            submitForm();
        });
    });

    // Per page filter event
    perPageItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();

            // Update active state
            perPageItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            // Update filter value
            const perPageValue = this.getAttribute('data-perpage');
            perPageFilter.value = perPageValue;

            // Update button text
            perPageButton.innerHTML = `<i class="fas fa-list-ol me-1"></i> ${perPageValue} per page`;

            submitForm();
        });
    });

    // Live search dengan debounce
    liveSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);

        // Show/hide clear button
        if (this.value.trim() !== '') {
            if (!clearSearchBtn) {
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'btn btn-outline-secondary border-start-0';
                clearBtn.id = 'clearSearch';
                clearBtn.innerHTML = '<i class="fas fa-times"></i>';
                clearBtn.addEventListener('click', function() {
                    liveSearch.value = '';
                    submitForm();
                });
                liveSearch.parentNode.appendChild(clearBtn);
            }
        } else if (clearSearchBtn) {
            clearSearchBtn.remove();
        }

        // Debounce search untuk mengurangi request ke server
        searchTimeout = setTimeout(() => {
            submitForm();
        }, 500); // 500ms delay
    });

    // Submit form function
    function submitForm() {
        // Show loading indicator
        showLoading();

        // Build URL with all parameters
        const url = new URL(searchForm.action);
        const params = new URLSearchParams();

        params.append('search', liveSearch.value);
        params.append('status', statusFilter.value);
        params.append('per_page', perPageFilter.value);
        params.append('sort', '{{ request("sort", "created_at") }}');
        params.append('direction', '{{ request("direction", "desc") }}');
        params.append('page', 1); // Reset to page 1 when filtering

        url.search = params.toString();

        // AJAX request untuk server-side search
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update table body
                if (data.table) {
                    document.getElementById('documentsTableBody').innerHTML = data.table;
                }

                // Update pagination
                if (data.pagination) {
                    document.getElementById('paginationContainer').innerHTML = data.pagination;
                } else {
                    document.getElementById('paginationContainer').innerHTML = '';
                }

                // Update document counter
                if (data.counter) {
                    document.getElementById('documentCounter').innerHTML = data.counter;
                }

                // Update URL without page reload
                window.history.pushState({}, '', url);

                // Reinitialize tooltips
                tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Hide loading indicator
                hideLoading();
            } else {
                throw new Error('Server returned error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoading();
            // Fallback to normal form submission
            searchForm.submit();
        });
    }

    // Loading indicator functions
    function showLoading() {
        if (isSearching) return;

        isSearching = true;
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading-overlay';
        loadingDiv.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Searching...</p>
            </div>
        `;
        document.querySelector('.card-body').style.position = 'relative';
        document.querySelector('.card-body').appendChild(loadingDiv);
    }

    function hideLoading() {
        isSearching = false;
        const loadingOverlay = document.querySelector('.loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        location.reload();
    });

    // Click event untuk pagination links (menangani AJAX)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.page-link') && !e.target.closest('.page-item.disabled')) {
            e.preventDefault();
            const pageUrl = e.target.closest('.page-link').href;
            if (pageUrl) {
                showLoading();
                fetch(pageUrl + '&ajax=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('documentsTableBody').innerHTML = data.table;
                        document.getElementById('paginationContainer').innerHTML = data.pagination;
                        document.getElementById('documentCounter').innerHTML = data.counter;
                        window.history.pushState({}, '', pageUrl);
                        hideLoading();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideLoading();
                    window.location.href = pageUrl;
                });
            }
        }
    });
});
</script>
@endsection
