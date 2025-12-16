@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Documents</a></li>
                    <li class="breadcrumb-item active">Document Details</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Document Details</h2>
                <div>
                    <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Documents
                    </a>
                    <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('documents.print', $document->id) }}" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print
                    </a>
                    <a href="{{ route('documents.pdf', $document->id) }}" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>

            <!-- Alert Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Document Header -->
            <div class="card mb-3">
                <div class="card-header {{ $document->plant == '3000' ? 'bg-primary' : 'bg-success' }} text-white py-2">
                    <h5 class="mb-0">Document Information</h5>
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <!-- Kolom Kiri: Document No dan Created By -->
                        <div class="col-md-3">
                            <table class="table table-borderless mb-0">
                                <tr class="py-1">
                                    <th class="w-50 py-1">Document No:</th>
                                    <td class="py-1"><h5 class="mb-0 {{ $document->plant == '3000' ? 'text-primary' : 'text-success' }}">{{ $document->document_no }}</h5></td>
                                </tr>
                                <tr class="py-1">
                                    <th class="w-50 py-1">Created By:</th>
                                    <td class="py-1">{{ $document->created_by_name ?? $document->created_by }}</td>
                                </tr>
                                <!-- Remark Column -->
                                @if($document->remarks)
                                <tr class="py-1">
                                    <th class="w-50 py-1">Remark:</th>
                                    <td class="py-1">
                                        <div class="remark-text text-break">
                                            {{ $document->remarks }}
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>

                        <!-- Kolom Tengah: Plant dan SLOC Supply -->
                        <div class="col-md-3">
                            <table class="table table-borderless mb-0">
                                <tr class="py-1">
                                    <th class="w-50 py-1">Plant Request:</th>
                                    <td class="py-1"><span class="badge bg-info">{{ $document->plant }}</span></td>
                                </tr>
                                <tr class="py-1">
                                    <th class="w-50 py-1">Sloc Supply:</th>
                                    <td class="py-1">
                                        @if($document->sloc_supply)
                                            <span class="badge bg-info">{{ $document->sloc_supply }}</span>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Kolom Kanan: Created Date dan Last Updated -->
                        <div class="col-md-6">
                            <table class="table table-borderless mb-0">
                                <tr class="py-1">
                                    <th class="w-25 py-1">Created Date:</th>
                                    <td class="py-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d F Y') }}</span>
                                            <span class="badge bg-secondary">{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('H:i') }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="py-1">
                                    <th class="w-25 py-1">Last Updated:</th>
                                    <td class="py-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{ \Carbon\Carbon::parse($document->updated_at)->setTimezone('Asia/Jakarta')->format('d F Y') }}</span>
                                            <span class="badge bg-secondary">{{ \Carbon\Carbon::parse($document->updated_at)->setTimezone('Asia/Jakarta')->format('H:i') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Items with Stock Information -->
            <div class="row">
                <!-- Tabel Items (Kolom Kiri) - Lebar Diperbesar -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header text-white d-flex justify-content-between align-items-center" style="
                            background: linear-gradient(90deg, #4CAF50 0%, #2196F3 100%);
                            padding: 10px 15px;
                            min-height: 60px;
                        ">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 me-3" style="line-height: 1.2;">
                                    Document Items with Stock Availability
                                </h5>
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="checkbox" id="selectAllHeader">
                                    <label class="form-check-label text-white" for="selectAllHeader">
                                        Select All
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="selected-count text-white" id="selectedCount">0 item terpilih</span>
                                </div>
                                <form id="checkStockForm" action="{{ route('stock.fetch', $document->document_no) }}" method="POST" class="d-inline me-2">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               name="plant"
                                               placeholder="Plant Code"
                                               value="{{ request('plant', $document->plant) }}"
                                               required
                                               style="width: 100px;">
                                        <button type="submit" class="btn btn-outline-light btn-sm">
                                            <i class="fas fa-search"></i> Check Stock
                                        </button>
                                    </div>
                                </form>
                                @php
                                    $hasStockInfo = false;
                                    foreach ($document->items as $item) {
                                        if (!empty($item->stock_info)) {
                                            $hasStockInfo = true;
                                            break;
                                        }
                                    }
                                @endphp
                                @if($hasStockInfo)
                                <form id="resetStockForm" action="{{ route('stock.clear-cache', $document->document_no) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-redo"></i> Reset Stock
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive sticky-table-container">
                                <table class="table table-bordered table-striped mb-0" id="itemsTable">
                                    <thead class="sticky-header">
                                        <tr>
                                            <th class="border-0 text-center" style="width: 40px; background-color: #f8f9fa;">
                                                <input type="checkbox" id="selectAllCheckbox" class="table-checkbox">
                                            </th>
                                            <th style="background-color: #f8f9fa;">#</th>
                                            <th style="background-color: #FFF0F5;">Material</th>
                                            <th style="background-color: #F0FFF0;">Description</th>
                                            <th style="background-color: #F0F8FF;">Sales Order</th>
                                            <th style="background-color: #FFFACD;">Source PRO</th>
                                            <th class="text-center" style="background-color: #F5F0FF;">MRP</th>
                                            <th class="text-center" style="background-color: #E6FFFA;">Requested Qty</th>
                                            <th class="text-center" style="background-color: #FFF5E6;">Available Stock</th>
                                            <th class="text-center" style="background-color: #F5F0FF;">Uom</th>
                                            <th class="text-center" style="background-color: #E6F7FF;">SLOC</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortableItems">
                                        @foreach($document->items as $index => $item)
                                            @php
                                                // Format material code
                                                $materialCode = $item->material_code;
                                                if (ctype_digit($materialCode)) {
                                                    $materialCode = ltrim($materialCode, '0');
                                                }

                                                // Convert unit
                                                $unit = $item->unit == 'ST' ? 'PC' : $item->unit;

                                                // Get sources from 'sources' field in database
                                                $sources = [];
                                                if (isset($item->sources) && !empty($item->sources)) {
                                                    if (is_string($item->sources)) {
                                                        $decoded = json_decode($item->sources, true);
                                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                            $sources = $decoded;
                                                        } elseif (!empty($item->sources)) {
                                                            $sources = array_map('trim', explode(',', $item->sources));
                                                        }
                                                    } elseif (is_array($item->sources)) {
                                                        $sources = $item->sources;
                                                    }
                                                }

                                                // Sales orders
                                                $salesOrders = [];
                                                if (is_string($item->sales_orders)) {
                                                    $salesOrders = json_decode($item->sales_orders, true) ?? [];
                                                } elseif (is_array($item->sales_orders)) {
                                                    $salesOrders = $item->sales_orders;
                                                }

                                                $requestedQty = is_numeric($item->requested_qty ?? 0) ? floatval($item->requested_qty) : 0;

                                                // Stock information
                                                $stockInfo = $item->stock_info ?? null;
                                                $totalStock = $stockInfo['total_stock'] ?? 0;
                                                $storageLocations = $stockInfo['storage_locations'] ?? [];

                                                // Check if stock is available
                                                $hasStock = $totalStock > 0;
                                                $canTransfer = $hasStock && $totalStock >= $requestedQty;
                                                $transferableQty = min($requestedQty, $totalStock);
                                            @endphp
                                            <tr class="item-row draggable-row hover:bg-gray-50"
                                                draggable="true"
                                                data-item-id="{{ $item->id }}"
                                                data-material-code="{{ $materialCode }}"
                                                data-material-description="{{ $item->material_description }}"
                                                data-requested-qty="{{ $requestedQty }}"
                                                data-available-stock="{{ $totalStock }}"
                                                data-transferable-qty="{{ $transferableQty }}"
                                                data-unit="{{ $unit }}"
                                                data-sloc="{{ !empty($storageLocations) ? implode(',', $storageLocations) : '' }}"
                                                data-can-transfer="{{ $canTransfer ? 'true' : 'false' }}"
                                                style="cursor: move;">
                                                <td class="border-0 text-center">
                                                    <input type="checkbox"
                                                           class="table-checkbox row-select"
                                                           data-item-id="{{ $item->id }}"
                                                           {{ $hasStock ? '' : 'disabled' }}>
                                                </td>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td>
                                                    <code>{{ $materialCode }}</code>
                                                </td>
                                                <td>{{ $item->material_description }}</td>
                                                <td>
                                                    @if(!empty($salesOrders))
                                                        <div style="display: flex; flex-direction: column; gap: 3px;">
                                                        @foreach($salesOrders as $so)
                                                            <div>{{ $so }}</div>
                                                        @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($sources))
                                                        <div style="display: flex; flex-direction: column; gap: 3px;">
                                                        @foreach($sources as $source)
                                                            <div>{{ $source }}</div>
                                                        @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($item->dispo)
                                                        {{ $item->dispo }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <strong>{{ \App\Helpers\NumberHelper::formatQuantity($requestedQty) }}</strong>
                                                </td>
                                                <td class="text-center">
                                                    @if($totalStock > 0)
                                                        <span class="badge {{ $canTransfer ? 'bg-success' : 'bg-warning' }}">
                                                            {{ \App\Helpers\NumberHelper::formatStockNumber($totalStock) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $unit }}</td>
                                                <td class="text-center">
                                                    @if(!empty($storageLocations))
                                                        <div style="display: flex; flex-direction: column; gap: 3px;">
                                                        @foreach($storageLocations as $location)
                                                            <div>{{ $location }}</div>
                                                        @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="ms-3 text-muted" id="dragHint">
                                            <i class="fas fa-info-circle"></i> Drag items to transfer list
                                        </span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="addSelectedToTransfer">
                                            <i class="fas fa-arrow-right me-1"></i> Add Selected to Transfer
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSelection" title="Clear Selection">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transfer List Card (Kolom Kanan) - Lebar Diperkecil -->
                <div class="col-md-3">
                    <div class="card h-100 transfer-card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                            <h5 class="mb-0 fs-6">
                                <i class="fas fa-truck-loading"></i> Transfer Ready
                            </h5>
                            <span class="badge bg-light text-primary" id="transferCount">0</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="transfer-container p-2"
                                 id="transferContainer"
                                 style="min-height: 400px; max-height: 600px; overflow-y: auto;">

                                <!-- Transfer Slots -->
                                <div id="transferSlots" class="transfer-slots">
                                    <!-- Items will be dropped here -->
                                    <div class="empty-state text-center text-muted py-5">
                                        <i class="fas fa-arrow-left fa-2x mb-3"></i>
                                        <p>Drag items here</p>
                                        <small class="fs-7">Only items with available stock</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer py-2">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success btn-sm" id="generateTransferList">
                                    <i class="fas fa-file-export"></i> Generate Transfer
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="clearTransferList">
                                    <i class="fas fa-trash"></i> Clear All
                                </button>
                            </div>

                            <!-- Transfer Summary -->
                            <div class="mt-2 p-2 border rounded bg-light fs-7">
                                <h6 class="fs-7 mb-2">Transfer Summary</h6>
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td class="p-1">Total Items:</td>
                                        <td class="text-end p-1"><span id="summaryTotalItems">0</span></td>
                                    </tr>
                                    <tr>
                                        <td class="p-1">Total Quantity:</td>
                                        <td class="text-end p-1"><span id="summaryTotalQty" class="stock-number">0</span></td>
                                    </tr>
                                    <tr>
                                        <td class="p-1">SLOC Source:</td>
                                        <td class="text-end p-1">
                                            <span class="badge bg-info">{{ $document->sloc_supply ?? 'N/A' }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Preview Modal -->
<div class="modal fade" id="transferPreviewModal" tabindex="-1" aria-labelledby="transferPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="transferPreviewModalLabel">
                    <i class="fas fa-file-export"></i> Transfer Document Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> This will create a transfer document for the selected items.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="transferPreviewTable">
                        <thead class="table-light">
                            <tr>
                                <th>Material</th>
                                <th>Description</th>
                                <th class="text-center">Qty to Transfer</th>
                                <th class="text-center">Unit</th>
                                <th class="text-center">SLOC Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Preview rows will be inserted here -->
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label for="transferRemarks" class="form-label">Remarks:</label>
                    <textarea class="form-control" id="transferRemarks" rows="2" placeholder="Add remarks for this transfer..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmTransfer">
                    <i class="fas fa-paper-plane"></i> Confirm Transfer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Animation Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="background: transparent; border: none;">
            <div class="modal-body text-center">
                <div id="lottie-container" style="width: 150px; height: 150px; margin: 0 auto;"></div>
                <div class="text-white mt-2" id="loadingText">Checking Stock...</div>
            </div>
        </div>
    </div>
</div>

<!-- Add some custom CSS -->
<style>
.badge {
    font-size: 0.85em;
    padding: 0.35em 0.65em;
}
.card {
    margin-bottom: 1rem;
}
.table tbody tr:hover {
    background-color: #f5f5f5;
}
.table td, .table th {
    vertical-align: middle;
}
.modal-content {
    background: transparent !important;
    border: none !important;
}
.table thead th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

/* Sticky Table Header */
.sticky-table-container {
    max-height: 600px;
    overflow-y: auto;
    position: relative;
}

.sticky-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background-color: #f8f9fa;
}

.sticky-header th {
    position: sticky;
    top: 0;
    background-color: inherit;
    z-index: 101;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}

/* Transfer Container Styles */
.transfer-container {
    background-color: #f8f9fa;
    border-radius: 4px;
}

.transfer-slots {
    min-height: 300px;
}

.transfer-item {
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 8px;
    margin-bottom: 6px;
    transition: all 0.3s;
    position: relative;
}

.transfer-item:hover {
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.transfer-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 5px;
}

.transfer-item-code {
    font-weight: bold;
    color: #0d6efd;
    font-size: 0.85rem;
}

.transfer-item-remove {
    color: #dc3545;
    cursor: pointer;
    font-size: 12px;
}

.transfer-item-desc {
    font-size: 0.75rem;
    color: #666;
    margin-bottom: 5px;
    line-height: 1.2;
}

.transfer-item-qty {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 6px;
}

.transfer-item-qty input {
    width: 70px;
    text-align: center;
    font-size: 0.8rem;
    padding: 0.15rem 0.3rem;
}

/* Hide spinner buttons in number input */
.transfer-item-qty input::-webkit-inner-spin-button,
.transfer-item-qty input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.transfer-item-qty input[type=number] {
    -moz-appearance: textfield;
    appearance: textfield;
}

.max-stock-badge {
    font-size: 0.7rem;
    padding: 2px 5px;
    background-color: #ffc107 !important;
    color: #212529 !important;
    font-weight: 500;
    margin-left: 3px;
}

.sloc-badge {
    font-size: 0.7rem;
    padding: 1px 4px;
    margin-left: 4px;
    background-color: #0dcaf0 !important;
}

.empty-state {
    opacity: 0.5;
}

.empty-state p {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.fs-6 {
    font-size: 0.9rem !important;
}

.fs-7 {
    font-size: 0.8rem !important;
}

/* Dragging styles */
.dragging {
    opacity: 0.5;
}

.drag-over {
    border: 2px dashed #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.1) !important;
}

/* Make table rows draggable */
.draggable-row {
    cursor: move;
}

.draggable-row:hover {
    background-color: #e3f2fd !important;
}

/* Style untuk item yang sudah di drag */
.transfer-item-selected {
    border-left: 4px solid #0d6efd;
    background-color: #e3f2fd !important;
}

/* Style untuk item tanpa stock */
.zero-stock {
    opacity: 0.6;
    background-color: #f8f9fa !important;
    cursor: not-allowed !important;
}

.zero-stock:hover {
    background-color: #f8f9fa !important;
}

.zero-stock code {
    background-color: #e9ecef;
    color: #6c757d;
}

.zero-stock .badge {
    background-color: #6c757d !important;
    color: white !important;
}

/* Style untuk checkbox */
.table-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #3b82f6;
}

.table-checkbox:checked {
    background-color: #3b82f6;
}

/* Style untuk baris yang dipilih */
.row-selected {
    background-color: #f0f7ff !important;
}

.selected-count {
    font-size: 0.9rem;
    font-weight: 500;
}

/* Style untuk tombol clear selection */
#clearSelection {
    opacity: 0.5;
    cursor: not-allowed;
}

#clearSelection.active {
    opacity: 1;
    cursor: pointer;
    color: #6c757d;
    border-color: #6c757d;
}

#clearSelection.active:hover {
    background-color: #6c757d;
    color: white;
}

/* Drag helper untuk multiple items */
.drag-helper {
    background-color: #0d6efd;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    z-index: 10000;
    pointer-events: none;
}

.drag-helper .badge {
    background-color: white;
    color: #0d6efd;
}

/* Adjust transfer card */
.transfer-card .card-header {
    padding: 8px 12px;
}

.transfer-card .card-footer {
    padding: 8px 12px;
}

/* Format stock number style */
.stock-number {
    font-family: monospace;
    font-weight: 600;
}

/* Remark text style */
.remark-text {
    font-size: 0.9rem;
    color: #495057;
    max-height: 60px;
    overflow-y: auto;
    padding-right: 5px;
}

/* Time badge style */
.time-badge {
    font-size: 0.75rem;
    padding: 2px 6px;
}
</style>

<!-- JavaScript untuk drag-drop functionality (HTML5 Native) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    let transferItems = [];
    let isDragging = false;
    let selectedItems = new Set(); // Untuk menyimpan ID item yang terpilih

    // Helper function untuk format stock number (sama seperti available stock)
    function formatStockNumber(num) {
        // Jika sudah ada fungsi NumberHelper.formatStockNumber di frontend, gunakan itu
        // Jika tidak, buat format sederhana
        if (window.NumberHelper && window.NumberHelper.formatStockNumber) {
            return window.NumberHelper.formatStockNumber(num);
        }

        // Format sederhana: jika angka desimal, tampilkan 3 digit desimal
        // Jika integer, tampilkan tanpa desimal
        const fixedNum = parseFloat(num);

        if (isNaN(fixedNum)) {
            return '0';
        }

        if (fixedNum % 1 === 0) {
            return fixedNum.toLocaleString('id-ID');
        } else {
            return fixedNum.toLocaleString('id-ID', {
                minimumFractionDigits: 3,
                maximumFractionDigits: 3
            });
        }
    }

    // Setup drag and drop events untuk semua baris
    function setupDragAndDrop() {
        const rows = document.querySelectorAll('.draggable-row');

        rows.forEach(row => {
            const availableStock = parseFloat(row.dataset.availableStock || 0);

            // Hanya set draggable jika stock > 0
            if (availableStock > 0) {
                row.draggable = true;

                row.addEventListener('dragstart', function(e) {
                    isDragging = true;
                    this.classList.add('dragging');

                    // Cek apakah row ini terpilih
                    const isSelected = selectedItems.has(this.dataset.itemId);

                    // Jika ada item yang terpilih dan row ini termasuk yang terpilih,
                    // drag semua yang terpilih. Jika tidak, drag hanya row ini.
                    if (selectedItems.size > 0 && isSelected) {
                        // Drag multiple selected items
                        e.dataTransfer.setData('text/plain', 'multiple');
                    } else {
                        // Drag single item
                        e.dataTransfer.setData('text/plain', this.dataset.itemId);
                    }

                    e.dataTransfer.effectAllowed = 'copy';
                });

                row.addEventListener('dragend', function() {
                    isDragging = false;
                    this.classList.remove('dragging');
                });
            } else {
                // Row tanpa stock
                row.draggable = false;
                row.classList.add('zero-stock');
                row.style.cursor = 'not-allowed';
                row.title = 'Stock tidak tersedia';
            }
        });

        // Setup drop zone
        const dropZone = document.getElementById('transferContainer');

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            const data = e.dataTransfer.getData('text/plain');

            if (data === 'multiple') {
                // Add all selected items
                addSelectedItemsToTransfer();
            } else if (data) {
                // Add single item
                addItemById(data);
            }
        });
    }

    // Function untuk menambahkan item terpilih ke transfer list
    function addSelectedItemsToTransfer() {
        if (selectedItems.size === 0) {
            showToast('Tidak ada item yang dipilih', 'warning');
            return;
        }

        let addedCount = 0;
        selectedItems.forEach(itemId => {
            const itemRow = document.querySelector(`.draggable-row[data-item-id="${itemId}"]`);
            if (itemRow) {
                const itemData = getItemDataFromRow(itemRow);

                // Cek jika item sudah ada di transfer list
                if (!transferItems.some(transferItem => transferItem.id === itemData.id)) {
                    // Cek jika item memiliki stock
                    if (itemData.availableStock > 0) {
                        addItemToTransferByData(itemData);
                        addedCount++;
                    }
                }
            }
        });

        if (addedCount > 0) {
            showToast(`${addedCount} item ditambahkan ke transfer list`, 'success');
            clearSelection();
        } else {
            showToast('Tidak ada item baru yang ditambahkan', 'warning');
        }
    }

    // Function untuk menambahkan item ke transfer list
    function addItemById(itemId) {
        const itemRow = document.querySelector(`.draggable-row[data-item-id="${itemId}"]`);
        if (!itemRow) {
            showToast('Item tidak ditemukan', 'error');
            return;
        }

        const itemData = getItemDataFromRow(itemRow);

        // Cek jika item sudah ada di transfer list
        if (transferItems.some(transferItem => transferItem.id === itemData.id)) {
            showToast('Item sudah ada di transfer list', 'warning');
            return;
        }

        // Cek jika item memiliki stock
        if (itemData.availableStock <= 0) {
            showToast('Item tidak memiliki stock yang tersedia', 'error');
            return;
        }

        addItemToTransferByData(itemData);
    }

    // Function untuk mendapatkan data dari row
    function getItemDataFromRow(rowElement) {
        const row = rowElement;
        return {
            id: row.dataset.itemId,
            materialCode: row.dataset.materialCode,
            materialDesc: row.dataset.materialDescription,
            availableStock: parseFloat(row.dataset.availableStock || 0),
            transferableQty: parseFloat(row.dataset.transferableQty || 0),
            unit: row.dataset.unit || 'PC',
            sloc: row.dataset.sloc || '',
            canTransfer: row.dataset.canTransfer === 'true'
        };
    }

    // Function untuk menambahkan item ke transfer list
    function addItemToTransferByData(item) {
        // Add to transfer items array
        transferItems.push({
            id: item.id,
            materialCode: item.materialCode,
            materialDesc: item.materialDesc,
            maxQty: item.availableStock,
            transferableQty: item.transferableQty,
            qty: Math.min(item.transferableQty, item.availableStock),
            unit: item.unit,
            sloc: item.sloc,
            formattedMaxQty: formatStockNumber(item.availableStock)
        });

        // Render transfer item
        renderTransferItem(transferItems[transferItems.length - 1]);

        // Update summary
        updateTransferSummary();

        // Tandai row yang sudah dipilih
        const row = document.querySelector(`.draggable-row[data-item-id="${item.id}"]`);
        if (row) {
            row.classList.add('transfer-item-selected');
        }
    }

    // Function untuk render transfer item
    function renderTransferItem(item) {
        // Remove empty state if exists
        const emptyState = document.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }

        // Extract first SLOC for badge display
        const slocArray = item.sloc ? item.sloc.split(',') : [];
        const firstSloc = slocArray.length > 0 ? slocArray[0] : '';

        // Create transfer item element
        const itemDiv = document.createElement('div');
        itemDiv.className = 'transfer-item';
        itemDiv.dataset.itemId = item.id;
        itemDiv.innerHTML = `
            <div class="transfer-item-header">
                <div>
                    <span class="transfer-item-code">${item.materialCode}</span>
                    ${firstSloc ? `<span class="badge sloc-badge">${firstSloc}</span>` : ''}
                </div>
                <span class="transfer-item-remove" onclick="removeTransferItem('${item.id}')">
                    <i class="fas fa-times"></i>
                </span>
            </div>
            <div class="transfer-item-desc">${item.materialDesc}</div>
            <div class="transfer-item-qty">
                <small class="text-muted">Qty:</small>
                <input type="number"
                       class="form-control form-control-sm qty-input"
                       value="${item.qty.toFixed(3)}"
                       min="0.001"
                       max="${item.maxQty}"
                       step="0.001"
                       data-item-id="${item.id}">
                <span class="text-muted">${item.unit}</span>
                <span class="badge max-stock-badge">Max: ${item.formattedMaxQty}</span>
            </div>
        `;

        // Add event listener untuk perubahan quantity
        const qtyInput = itemDiv.querySelector('.qty-input');
        qtyInput.addEventListener('change', function() {
            const newQty = parseFloat(this.value);
            const maxQty = parseFloat(this.max);

            if (isNaN(newQty) || newQty <= 0) {
                this.value = 0.001;
                showToast('Quantity harus lebih dari 0', 'warning');
            } else if (newQty > maxQty) {
                this.value = maxQty.toFixed(3);
                showToast(`Quantity tidak boleh melebihi ${formatStockNumber(maxQty)}`, 'warning');
            }

            updateItemQty(item.id, parseFloat(this.value));
        });

        // Add to transfer slots
        document.getElementById('transferSlots').appendChild(itemDiv);
    }

    // Function untuk menghapus transfer item (global function)
    window.removeTransferItem = function(itemId) {
        // Remove from array
        transferItems = transferItems.filter(item => item.id !== itemId);

        // Remove from DOM
        const itemElement = document.querySelector(`.transfer-item[data-item-id="${itemId}"]`);
        if (itemElement) {
            itemElement.remove();
        }

        // Hapus tanda seleksi dari row
        const row = document.querySelector(`.draggable-row[data-item-id="${itemId}"]`);
        if (row) {
            row.classList.remove('transfer-item-selected');
        }

        // Update summary
        updateTransferSummary();

        // Show empty state jika tidak ada item
        if (transferItems.length === 0) {
            showEmptyState();
        }

        showToast('Item dihapus dari transfer list', 'info');
    };

    // Function untuk update quantity
    function updateItemQty(itemId, newQty) {
        const item = transferItems.find(item => item.id === itemId);
        if (item) {
            item.qty = newQty;
            updateTransferSummary();
        }
    }

    // Function untuk update transfer summary
    function updateTransferSummary() {
        const totalItems = transferItems.length;
        const totalQty = transferItems.reduce((sum, item) => sum + (item.qty || 0), 0);

        // Update counters
        document.getElementById('transferCount').textContent = totalItems;
        document.getElementById('summaryTotalItems').textContent = totalItems;
        document.getElementById('summaryTotalQty').textContent = formatStockNumber(totalQty);
    }

    // Function untuk show empty state
    function showEmptyState() {
        const transferSlots = document.getElementById('transferSlots');
        transferSlots.innerHTML = `
            <div class="empty-state text-center text-muted py-5">
                <i class="fas fa-arrow-left fa-2x mb-3"></i>
                <p>Drag items here</p>
                <small class="fs-7">Only items with available stock</small>
            </div>
        `;
    }

    // Function untuk update seleksi count
    function updateSelectionCount() {
        const count = selectedItems.size;
        document.getElementById('selectedCount').textContent = `${count} item terpilih`;

        const clearBtn = document.getElementById('clearSelection');
        if (count > 0) {
            clearBtn.classList.add('active');
            clearBtn.title = `Clear ${count} selected items`;
            // Tambahkan class row-selected pada baris yang terpilih
            document.querySelectorAll('.draggable-row').forEach(row => {
                const itemId = row.dataset.itemId;
                if (selectedItems.has(itemId)) {
                    row.classList.add('row-selected');
                } else {
                    row.classList.remove('row-selected');
                }
            });
        } else {
            clearBtn.classList.remove('active');
            clearBtn.title = 'Clear Selection';
            // Hapus class row-selected dari semua baris
            document.querySelectorAll('.draggable-row').forEach(row => {
                row.classList.remove('row-selected');
            });
        }

        // Update select all checkbox
        const totalCheckboxes = document.querySelectorAll('.row-select:not(:disabled)').length;
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const selectAllHeader = document.getElementById('selectAllHeader');

        if (count === totalCheckboxes && totalCheckboxes > 0) {
            selectAllCheckbox.checked = true;
            selectAllHeader.checked = true;
        } else {
            selectAllCheckbox.checked = false;
            selectAllHeader.checked = false;
        }
    }

    // Function untuk clear selection
    function clearSelection() {
        selectedItems.clear();
        document.querySelectorAll('.row-select').forEach(cb => {
            cb.checked = false;
        });
        updateSelectionCount();
        showToast('Pilihan berhasil dihapus', 'success');
    }

    // Function untuk show toast
    function showToast(message, type = 'info') {
        // Create toast container jika belum ada
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        const iconClass = type === 'success' ? 'fa-check-circle' :
                         type === 'warning' ? 'fa-exclamation-triangle' :
                         type === 'error' ? 'fa-times-circle' : 'fa-info-circle';

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconClass} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Initialize and show toast
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();

        // Remove setelah hide
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }

    // Setup checkbox selection
    function setupCheckboxSelection() {
        // Select all checkbox (header)
        document.getElementById('selectAllCheckbox').addEventListener('click', function(e) {
            const isChecked = e.target.checked;
            const checkboxes = document.querySelectorAll('.row-select:not(:disabled)');

            checkboxes.forEach(cb => {
                cb.checked = isChecked;
                const itemId = cb.dataset.itemId;
                if (isChecked) {
                    selectedItems.add(itemId);
                } else {
                    selectedItems.delete(itemId);
                }
            });

            updateSelectionCount();
        });

        // Select all checkbox (header label)
        document.getElementById('selectAllHeader').addEventListener('click', function(e) {
            const isChecked = e.target.checked;
            const checkboxes = document.querySelectorAll('.row-select:not(:disabled)');

            checkboxes.forEach(cb => {
                cb.checked = isChecked;
                const itemId = cb.dataset.itemId;
                if (isChecked) {
                    selectedItems.add(itemId);
                } else {
                    selectedItems.delete(itemId);
                }
            });

            updateSelectionCount();
        });

        // Individual checkbox handling
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-select')) {
                const itemId = e.target.dataset.itemId;
                if (e.target.checked) {
                    selectedItems.add(itemId);
                } else {
                    selectedItems.delete(itemId);
                    // Uncheck select all jika ada checkbox yang diuncheck
                    document.getElementById('selectAllCheckbox').checked = false;
                    document.getElementById('selectAllHeader').checked = false;
                }
                updateSelectionCount();
            }
        });

        // Clear selection button
        document.getElementById('clearSelection').addEventListener('click', function() {
            if (selectedItems.size > 0) {
                clearSelection();
            }
        });

        // Add selected to transfer button
        document.getElementById('addSelectedToTransfer').addEventListener('click', function() {
            addSelectedItemsToTransfer();
        });
    }

    // Clear transfer list button
    document.getElementById('clearTransferList').addEventListener('click', function() {
        if (transferItems.length === 0) {
            showToast('Transfer list sudah kosong', 'info');
            return;
        }

        if (confirm('Apakah Anda yakin ingin menghapus semua item dari transfer list?')) {
            // Clear all transfer items
            transferItems.forEach(item => {
                // Hapus tanda seleksi dari row
                const row = document.querySelector(`.draggable-row[data-item-id="${item.id}"]`);
                if (row) {
                    row.classList.remove('transfer-item-selected');
                }
            });

            // Clear array
            transferItems = [];
            document.getElementById('transferSlots').innerHTML = '';

            // Reset counters
            updateTransferSummary();

            // Show empty state
            showEmptyState();

            showToast('Transfer list berhasil dibersihkan', 'info');
        }
    });

    // Generate transfer list button
    document.getElementById('generateTransferList').addEventListener('click', function() {
        if (transferItems.length === 0) {
            showToast('Silakan tambahkan item ke transfer list terlebih dahulu', 'warning');
            return;
        }

        // Populate preview table
        const tbody = document.querySelector('#transferPreviewTable tbody');
        tbody.innerHTML = '';

        transferItems.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><code>${item.materialCode}</code></td>
                <td>${item.materialDesc}</td>
                <td class="text-center">${item.qty.toFixed(3)}</td>
                <td class="text-center">${item.unit}</td>
                <td class="text-center">${item.sloc || 'N/A'}</td>
            `;
            tbody.appendChild(row);
        });

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('transferPreviewModal'));
        modal.show();
    });

    // Confirm transfer button
    document.getElementById('confirmTransfer').addEventListener('click', function() {
        // Get remarks
        const remarks = document.getElementById('transferRemarks').value;

        // Prepare data untuk API call
        const transferData = {
            document_id: {{ $document->id }},
            document_no: "{{ $document->document_no }}",
            plant: "{{ $document->plant }}",
            sloc_supply: "{{ $document->sloc_supply }}",
            items: transferItems,
            remarks: remarks,
            created_by: {{ auth()->id() }}
        };

        // Show loading
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
        document.getElementById('loadingText').textContent = 'Membuat Transfer Document...';

        // Send to server
        fetch('{{ route("documents.create-transfer") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(transferData)
        })
        .then(response => response.json())
        .then(data => {
            loadingModal.hide();

            if (data.success) {
                showToast('Transfer document berhasil dibuat!', 'success');

                // Close modal
                const transferModal = bootstrap.Modal.getInstance(document.getElementById('transferPreviewModal'));
                transferModal.hide();

                // Clear transfer list
                document.getElementById('clearTransferList').click();

                // Optionally redirect atau refresh
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            loadingModal.hide();
            console.error('Error:', error);
            showToast('Error creating transfer document', 'error');
        });
    });

    // Initialize Lottie animation
    let animation;

    function loadLottieAnimation() {
        if (animation) {
            animation.destroy();
        }

        animation = lottie.loadAnimation({
            container: document.getElementById('lottie-container'),
            renderer: 'svg',
            loop: true,
            autoplay: false,
            path: '{{ asset("json/Floating_Duck.json") }}'
        });
    }

    // Show loading animation pada form submit
    document.getElementById('checkStockForm').addEventListener('submit', function(e) {
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
        document.getElementById('loadingText').textContent = 'Checking Stock...';

        setTimeout(function() {
            loadLottieAnimation();
            animation.play();
        }, 100);
    });

    // Handle reset stock form submission
    const resetStockForm = document.getElementById('resetStockForm');
    if (resetStockForm) {
        resetStockForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!confirm('Apakah Anda yakin ingin mereset data stock?')) {
                return;
            }

            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();
            document.getElementById('loadingText').textContent = 'Resetting Stock...';

            setTimeout(function() {
                if (!animation) {
                    loadLottieAnimation();
                }
                animation.play();
            }, 100);

            const token = resetStockForm.querySelector('input[name="_token"]').value;

            fetch(resetStockForm.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingModal.hide();

                if (data.success) {
                    alert(data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                loadingModal.hide();
                console.error('Error:', error);
                alert('Error resetting stock data.');
            });
        });
    }

    // Setup drag and drop
    setupDragAndDrop();
    setupCheckboxSelection();

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
@endsection
