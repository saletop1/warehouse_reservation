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
                        Total: <span id="totalCount">{{ $transfers->total() }}</span> transfers
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-primary" id="clearSearchBtn" style="display: none;">
                        <i class="fas fa-times me-1"></i>Clear Search
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Live Search --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-search me-2 text-primary"></i>
                <input type="text"
                    id="liveSearch"
                    class="form-control border-0 shadow-none"
                    placeholder="Search transfers (Transfer No, Document No, Plant, Status, etc)..."
                    autocomplete="off"
                    value="{{ request('search') }}">
                <div class="spinner-border spinner-border-sm text-primary ms-2 d-none" id="searchSpinner"></div>
                <span id="searchResultCount" class="badge bg-primary ms-2 d-none">0 found</span>
            </div>
        </div>
    </div>

    {{-- Transfers Table --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-container" style="max-height: 700px; overflow-y: auto;">
                <table class="table table-hover mb-0" id="transfersTable">
                    <thead class="table-light sticky-top" style="top: 0; z-index: 10;">
                        <tr>
                            <th class="ps-3 py-3 fw-semibold" style="width: 20%; min-width: 160px">
                                <i class="fas fa-hashtag me-2 text-muted"></i>Transfer No
                            </th>
                            <th class="py-3 fw-semibold" style="width: 15%; min-width: 130px">Document</th>
                            <th class="py-3 fw-semibold" style="width: 12%; min-width: 110px">Status</th>
                            <th class="py-3 fw-semibold" style="width: 20%; min-width: 160px">Plants</th>
                            <th class="py-3 fw-semibold text-center" style="width: 10%; min-width: 90px">Items</th>
                            <th class="py-3 fw-semibold text-center" style="width: 12%; min-width: 100px">Quantity</th>
                            <th class="py-3 fw-semibold" style="width: 16%; min-width: 140px">Created</th>
                        </tr>
                    </thead>
                    <tbody id="transfersTableBody">
                        @include('transfers.partials.table', ['transfers' => $transfers])
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Debug Info --}}
        <div class="card-footer bg-info bg-opacity-10 border-top py-2 px-3">
            <div class="text-info small">
                <i class="fas fa-info-circle me-1"></i>
                <span id="debugInfo">Showing {{ $transfers->firstItem() ?? 0 }}-{{ $transfers->lastItem() ?? 0 }} of {{ $transfers->total() }} transfers</span>
            </div>
        </div>

        {{-- Pagination --}}
        @if($transfers->hasPages() && $transfers->lastPage() > 1)
        <div class="card-footer bg-transparent border-top py-3 px-4" id="paginationContainer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Page {{ $transfers->currentPage() }} of {{ $transfers->lastPage() }}
                </div>
                <div class="d-flex justify-content-end">
                    {{ $transfers->onEachSide(1)->links('pagination::bootstrap-5') }}
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

/* Clickable Transfer No Styling */
.view-transfer-clickable:hover {
    background-color: rgba(0, 123, 255, 0.05);
    border-radius: 4px;
    padding: 4px;
    margin: -4px;
}

.view-transfer-clickable:hover .transfer-no {
    text-decoration: underline;
}

/* Pagination Styling - FIXED */
.pagination {
    margin-bottom: 0;
    --bs-pagination-padding-x: 0.5rem;
    --bs-pagination-padding-y: 0.25rem;
    --bs-pagination-font-size: 0.875rem;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.pagination .page-link {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    color: #007bff;
    border: 1px solid #dee2e6;
    min-width: 32px;
    text-align: center;
}

.pagination .page-link:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #0056b3;
}

/* Style khusus untuk tombol Previous/Next */
.pagination .page-item:first-child .page-link,
.pagination .page-item:last-child .page-link {
    padding: 0.25rem 0.75rem;
    font-weight: 500;
}

/* Hilangkan border radius berlebihan */
.pagination .page-link {
    border-radius: 0.25rem;
}

/* Style untuk disabled state */
.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

/* Search Highlight */
.search-highlight {
    background-color: #fff3cd;
    padding: 0 2px;
    border-radius: 2px;
    font-weight: 600;
}

/* Loading State */
.loading {
    opacity: 0.7;
    pointer-events: none;
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

    /* Compact pagination untuk mobile */
    .pagination {
        --bs-pagination-padding-x: 0.375rem;
        --bs-pagination-padding-y: 0.125rem;
        --bs-pagination-font-size: 0.75rem;
    }

    .pagination .page-link {
        padding: 0.125rem 0.375rem;
        min-width: 28px;
        font-size: 0.75rem;
    }

    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        padding: 0.125rem 0.5rem;
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

    .pagination {
        flex-wrap: wrap;
    }

    .pagination .page-item .page-link {
        padding: 0.2rem 0.4rem;
        font-size: 11px;
        min-width: 24px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const liveSearch = document.getElementById('liveSearch');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const searchResultCount = document.getElementById('searchResultCount');
    const transfersTableBody = document.getElementById('transfersTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const debugInfo = document.getElementById('debugInfo');
    const totalCount = document.getElementById('totalCount');

    let searchTimeout;
    let currentSearch = '';

    // Initialize from URL
    if (liveSearch.value) {
        currentSearch = liveSearch.value;
        updateClearButton();
    }

    // Live Search with Debounce
    liveSearch.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        currentSearch = searchTerm;

        clearTimeout(searchTimeout);

        updateClearButton();

        // Search immediately when typing
        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });

    // Clear Search
    clearSearchBtn.addEventListener('click', function() {
        liveSearch.value = '';
        currentSearch = '';
        performSearch('');
        updateClearButton();
    });

    function updateClearButton() {
        clearSearchBtn.style.display = currentSearch ? 'block' : 'none';
    }

    // Perform Search
    function performSearch(searchTerm) {
        searchSpinner.classList.remove('d-none');

        // Build URL with search parameter
        let url = '{{ route("transfers.index") }}';
        const params = new URLSearchParams();

        if (searchTerm) {
            params.append('search', searchTerm);
        }

        params.append('_ajax', '1');

        const fullUrl = `${url}?${params.toString()}`;

        // Make AJAX request
        fetch(fullUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update table body
                transfersTableBody.innerHTML = data.html;

                // Update pagination
                if (paginationContainer) {
                    if (data.pagination) {
                        paginationContainer.innerHTML = data.pagination;
                        attachPaginationEvents();
                    } else {
                        paginationContainer.style.display = 'none';
                    }
                }

                // Update counts
                totalCount.textContent = data.total;

                if (searchTerm) {
                    searchResultCount.textContent = `${data.count} results`;
                    searchResultCount.classList.remove('d-none');
                } else {
                    searchResultCount.classList.add('d-none');
                }

                // Update debug info
                debugInfo.textContent = `Showing ${data.count} of ${data.total} transfers`;

                // Update URL without reload
                updateUrl(searchTerm);

                // Reattach event listeners
                attachTransferClickEvents();
            } else {
                console.error('Error:', data.message);
                showToast(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToast('Error loading data', 'danger');
        })
        .finally(() => {
            searchSpinner.classList.add('d-none');
        });
    }

    // Update URL
    function updateUrl(searchTerm) {
        const url = new URL(window.location);

        if (searchTerm) {
            url.searchParams.set('search', searchTerm);
        } else {
            url.searchParams.delete('search');
        }

        history.replaceState({}, '', url);
    }

    // Attach transfer click events
    function attachTransferClickEvents() {
        document.querySelectorAll('.view-transfer-clickable').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const transferId = this.dataset.id;
                loadTransferDetails(transferId);
            });
        });
    }

    // Attach pagination events
    function attachPaginationEvents() {
        if (!paginationContainer) return;

        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                if (url && url !== '#') {
                    loadPage(url);
                }
            });
        });
    }

    // Load specific page
    function loadPage(url) {
        searchSpinner.classList.remove('d-none');

        // Add AJAX parameter to URL
        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.append('_ajax', '1');

        if (currentSearch) {
            urlObj.searchParams.append('search', currentSearch);
        }

        fetch(urlObj.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                transfersTableBody.innerHTML = data.html;

                if (paginationContainer && data.pagination) {
                    paginationContainer.innerHTML = data.pagination;
                    attachPaginationEvents();
                }

                // Update URL
                history.pushState({}, '', urlObj.toString());

                // Reattach events
                attachTransferClickEvents();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error loading page', 'danger');
        })
        .finally(() => {
            searchSpinner.classList.add('d-none');
        });
    }

    // Handle browser back/forward
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('search') || '';
        liveSearch.value = searchTerm;
        currentSearch = searchTerm;
        updateClearButton();
        performSearch(searchTerm);
    });

    // Load Transfer Details
    async function loadTransferDetails(transferId) {
        const modal = new bootstrap.Modal(document.getElementById('transferDetailModal'));
        const contentDiv = document.getElementById('transferDetailContent');

        contentDiv.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted">Loading transfer details...</p>
            </div>
        `;

        modal.show();

        try {
            const response = await fetch(`/transfers/${transferId}?_details=1`);
            const data = await response.json();

            if (data.success) {
                const transfer = data.data;
                document.getElementById('transferNoLabel').textContent = transfer.transfer_no || 'N/A';
                contentDiv.innerHTML = generateTransferDetailContent(transfer);
            } else {
                contentDiv.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h6 class="text-danger mb-2">Failed to load transfer details</h6>
                        <p class="text-muted small">${data.message}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
            contentDiv.innerHTML = `
                <div class="text-center py-3">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                    <h6 class="text-danger mb-2">Error loading transfer details</h6>
                    <p class="text-muted small">${error.message}</p>
                </div>
            `;
        }
    }

    // Generate Compact Transfer Detail Content - DESAIN ASLI YANG BAGUS
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
                        ${transfer.status === 'FAILED' ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="retryTransfer(${transfer.id})">
                            <i class="fas fa-redo me-1"></i>Retry
                        </button>
                        ` : ''}
                        ${!transfer.plant_destination ? `
                        <button class="btn btn-sm btn-outline-warning" onclick="fixTransferData(${transfer.id})">
                            <i class="fas fa-wrench me-1"></i>Fix Data
                        </button>
                        ` : ''}
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
        return new Intl.NumberFormat('id-ID').format(num);
    }

    // Toast notification
    function showToast(message, type = 'info') {
        const toast = `
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div class="toast align-items-center text-white bg-${type} border-0">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', toast);
        const toastEl = document.querySelector('.toast-container .toast');
        const bsToast = new bootstrap.Toast(toastEl, { delay: 3000 });
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', function() {
            this.closest('.toast-container').remove();
        });
    }

    // Global functions for modal actions
    window.printTransferNow = function(id) {
        if (id) {
            const url = `/transfers/${id}/print`;
            window.open(url, '_blank');
            showToast('Opening print preview...', 'info');
        }
    };

    window.copyTransferDetailsNow = function(id) {
        if (!id) {
            showToast('Transfer ID is required', 'error');
            return;
        }

        fetch(`/transfers/${id}?_details=1`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transfer = data.data;
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
    };

    window.fixTransferData = function(id) {
        if (confirm('Fix this transfer data?')) {
            fetch(`/transfers/${id}/fix`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Transfer data fixed successfully', 'success');
                    setTimeout(() => performSearch(), 1000);
                } else {
                    showToast('Failed to fix transfer: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error fixing transfer data', 'error');
            });
        }
    };

    window.retryTransfer = function(id) {
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
                    setTimeout(() => performSearch(), 2000);
                } else {
                    showToast('Failed to retry transfer', 'error');
                }
            });
        }
    };

    // Initialize events
    attachTransferClickEvents();
    attachPaginationEvents();
});
</script>
@endsection
