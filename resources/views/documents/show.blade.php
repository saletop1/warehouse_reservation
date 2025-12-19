@extends('layouts.app')

@section('content')
                    <div class="container-fluid">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <!-- Breadcrumb -->
                                <nav aria-label="breadcrumb" class="mb-3">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="{{ route('documents.index') }}" class="text-decoration-none">Documents</a></li>
                                        <li class="breadcrumb-item active text-muted">Document Details</li>
                                    </ol>
                                </nav>

                                <!-- Header Section -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h1 class="h3 mb-0 fw-bold text-dark">Document Details</h1>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-arrow-left me-1"></i> Back
                                        </a>
                                        <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <a href="{{ route('documents.print', $document->id) }}" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-print me-1"></i> Print
                                        </a>
                                        <a href="{{ route('documents.pdf', $document->id) }}" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </a>
                                    </div>
                                </div>

                                <!-- Alert Messages -->
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <div class="flex-grow-1">{{ session('success') }}</div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    </div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            <div class="flex-grow-1">{{ session('error') }}</div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    </div>
                                @endif

                                <!-- Document Info Card -->
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-white border-bottom py-2">
                                        <h5 class="mb-0 fw-semibold text-dark">
                                            <i class="fas fa-info-circle me-2 text-primary"></i>Document Information
                                        </h5>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="document-info-compact">
                                            <!-- Column 1 -->
                                            <div class="info-column">
                                                <div class="info-item">
                        <span class="info-label">Transfer No</span>
                        <span class="info-value">
                            @php
                                // Filter transfer yang memiliki transfer_no
                                $validTransfers = $document->transfers->filter(function($transfer) {
                                    return !empty($transfer->transfer_no);
                                });

                                $transferCount = $validTransfers->count();
                            @endphp

                            @if($transferCount > 0)
                                @if($transferCount <= 2)
                                    <!-- Tampilkan langsung jika 1 atau 2 transfer -->
                                    @foreach($validTransfers as $transfer)
                                        <div class="text-primary mb-1">{{ $transfer->transfer_no }}</div>
                                    @endforeach
                                @else
                                    <!-- Tampilkan dropdown jika lebih dari 2 -->
                                    <div class="dropdown transfer-dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                            <span class="badge bg-primary">{{ $transferCount }}</span> Transfer(s)
                                        </button>
                                        <ul class="dropdown-menu transfer-dropdown-menu">
                                            @foreach($validTransfers as $transfer)
                                                <li>
                                                    <a class="dropdown-item" href="#">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="transfer-no-text">{{ $transfer->transfer_no }}</span>
                                                            <small class="text-muted">
                                                                {{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y') }}
                                                            </small>
                                                        </div>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </span>
                    </div>
                        </div>

                        <!-- Column 2 -->
                        <div class="info-column">
                            <div class="info-item">
                                <span class="info-label">Plant Request</span>
                                <span class="info-value">{{ $document->plant }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Created By</span>
                                <span class="info-value">{{ $document->created_by_name ?? $document->created_by }}</span>
                            </div>
                        </div>

                        <!-- Column 3 -->
                        <div class="info-column">
                            <div class="info-item">
                                <span class="info-label">Plant Supply</span>
                                <span class="info-value">
                                    @if($document->sloc_supply)
                                        {{ $document->sloc_supply }}
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Created Date</span>
                                <span class="info-value">{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }}</span>
                            </div>
                        </div>

                        <!-- Column 4 -->
                        <div class="info-column">
                            @if($document->remarks)
                            <div class="info-item">
                                <span class="info-label">Remarks</span>
                                <span class="info-value text-truncate" style="max-width: 200px;" title="{{ $document->remarks }}">{{ $document->remarks }}</span>
                            </div>
                            @endif
                            <div class="info-item">
                                <span class="info-label">Last Updated</span>
                                <span class="info-value">{{ \Carbon\Carbon::parse($document->updated_at)->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Items Section -->
            <div class="row">
                <!-- Items Table Column -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-semibold text-dark">
                                        <i class="fas fa-list-ul me-2 text-primary"></i>Document Items
                                    </h5>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="me-2">
                                        <span class="badge bg-light border text-dark" id="selectedCount">0 selected</span>
                                    </div>
                                    <form id="checkStockForm" action="{{ route('stock.fetch', $document->document_no) }}" method="POST" class="d-inline">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input type="text"
                                                   class="form-control form-control-sm border-end-0"
                                                   name="plant"
                                                   placeholder="Plant"
                                                   value="{{ request('plant', $document->plant) }}"
                                                   required>
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-search"></i> Stock
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
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-hover mb-0" id="itemsTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="border-end-0 text-center" style="width: 40px;">
                                                <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                            </th>
                                            <th class="border-end-0 text-center" style="width: 40px;">#</th>
                                            <th class="border-end-0">Material</th>
                                            <th class="border-end-0" style="min-width: 180px;">Description</th>
                                            <th class="border-end-0">Sales Order</th>
                                            <th class="border-end-0">Source PRO</th>
                                            <th class="border-end-0 text-center">MRP</th>
                                            <th class="border-end-0 text-center">Req Qty</th>
                                            <th class="border-end-0 text-center">Stock</th>
                                            <th class="border-end-0 text-center">Uom</th>
                                            <th class="text-center">Batch</th>
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

                                                // Get sources from 'sources' field
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
                                                $stockDetails = $stockInfo['details'] ?? [];

                                                // Prepare batch info for JavaScript
                                                $batchInfo = [];
                                                if (!empty($stockDetails)) {
                                                    foreach ($stockDetails as $detail) {
                                                        $batchInfo[] = [
                                                            'batch' => $detail['charg'] ?? '',
                                                            'sloc' => $detail['lgort'] ?? '',
                                                            'qty' => is_numeric($detail['clabs'] ?? 0) ? floatval($detail['clabs']) : 0
                                                        ];
                                                    }
                                                }

                                                // Check if stock is available
                                                $hasStock = $totalStock > 0;
                                                $canTransfer = $hasStock && $totalStock >= $requestedQty;
                                                $transferableQty = min($requestedQty, $totalStock);

                                                // Determine stock color class
                                                if ($totalStock > 0) {
                                                    if ($canTransfer) {
                                                        $stockClass = 'stock-custom-available';
                                                        $stockIcon = 'fa-check-circle';
                                                    } else {
                                                        $stockClass = 'stock-custom-low';
                                                        $stockIcon = 'fa-exclamation-triangle';
                                                    }
                                                } else {
                                                    $stockClass = 'stock-custom-unavailable';
                                                    $stockIcon = 'fa-times-circle';
                                                }
                                            @endphp
                                            <tr class="item-row draggable-row"
                                                draggable="true"
                                                data-item-id="{{ $item->id }}"
                                                data-material-code="{{ $materialCode }}"
                                                data-material-description="{{ $item->material_description }}"
                                                data-requested-qty="{{ $requestedQty }}"
                                                data-available-stock="{{ $totalStock }}"
                                                data-transferable-qty="{{ $transferableQty }}"
                                                data-unit="{{ $unit }}"
                                                data-sloc="{{ !empty($batchInfo) ? array_column($batchInfo, 'sloc')[0] ?? '' : '' }}"
                                                data-can-transfer="{{ $canTransfer ? 'true' : 'false' }}"
                                                data-batch-info="{{ htmlspecialchars(json_encode($batchInfo), ENT_QUOTES, 'UTF-8') }}"
                                                style="cursor: move;">
                                                <td class="text-center border-end-0 align-middle">
                                                    <input type="checkbox"
                                                           class="form-check-input row-select"
                                                           data-item-id="{{ $item->id }}"
                                                           {{ $hasStock ? '' : 'disabled' }}>
                                                </td>
                                                <td class="text-center border-end-0 align-middle">{{ $index + 1 }}</td>
                                                <td class="border-end-0 align-middle">
                                                    <div class="fw-medium text-dark">{{ $materialCode }}</div>
                                                </td>
                                                <td class="border-end-0">
                                                    <div class="text-truncate-2" style="max-height: 2.8em; line-height: 1.4em;" title="{{ $item->material_description }}">
                                                        {{ $item->material_description }}
                                                    </div>
                                                </td>
                                                <td class="border-end-0">
                                                    @if(!empty($salesOrders))
                                                        <div class="d-flex flex-column gap-1">
                                                        @foreach($salesOrders as $so)
                                                            <div>{{ $so }}</div>
                                                        @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="border-end-0">
                                                    @if(!empty($sources))
                                                        <div class="d-flex flex-column gap-1">
                                                        @foreach($sources as $source)
                                                            <div>{{ $source }}</div>
                                                        @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center border-end-0 align-middle">
                                                    @if($item->dispo)
                                                        {{ $item->dispo }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center border-end-0 align-middle">
                                                    <div class="fw-medium text-dark">{{ \App\Helpers\NumberHelper::formatQuantity($requestedQty) }}</div>
                                                </td>
                                                <td class="text-center border-end-0 align-middle">
                                                    @if($totalStock > 0)
                                                        @if($canTransfer)
                                                            <div class="stock-custom-available fw-bold">

                                                                {{ \App\Helpers\NumberHelper::formatStockNumber($totalStock) }}
                                                            </div>
                                                        @else
                                                            <div class="stock-custom-low fw-medium">

                                                                {{ \App\Helpers\NumberHelper::formatStockNumber($totalStock) }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="stock-custom-unavailable fw-medium">
                                                            No Stock
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="text-center border-end-0 align-middle">{{ $unit }}</td>
                                                <td class="text-center align-middle">
                                                    @if(!empty($stockDetails))
                                                        <div class="d-flex flex-column gap-1">
                                                            @foreach($stockDetails as $detail)
                                                                @php
                                                                    $batchNumber = $detail['charg'] ?? '';
                                                                    $batchQty = is_numeric($detail['clabs'] ?? 0) ? floatval($detail['clabs']) : 0;
                                                                    $batchSloc = $detail['lgort'] ?? '';

                                                                    // Determine batch color
                                                                    if ($batchQty > 0) {
                                                                        $batchClass = 'stock-custom-available';

                                                                    } else {
                                                                        $batchClass = 'stock-custom-unavailable';

                                                                    }
                                                                @endphp
                                                                    <div class="text-truncate {{ $batchClass }}" style="max-width: 150px;" title="SLOC: {{ $batchSloc }} | Batch: {{ $batchNumber }} | Qty: {{ \App\Helpers\NumberHelper::formatStockNumber($batchQty) }}">
                                                                        {{ $batchSloc }} | {{ $batchNumber }}: {{ \App\Helpers\NumberHelper::formatStockNumber($batchQty) }}
                                                                    </div>
                                                                @endforeach
                                                        </div>
                                                    @else
                                                        <div class="stock-custom-unavailable">
                                                            <i class="fas fa-ban me-1"></i> No Batch
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllHeader">
                                    <label class="form-check-label text-muted" for="selectAllHeader">
                                        Select All
                                    </label>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSelection">
                                        <i class="fas fa-times me-1"></i> Clear
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" id="addSelectedToTransfer">
                                        <i class="fas fa-arrow-right me-1"></i> Add Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transfer List Column -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom py-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h5 class="mb-0 fw-semibold text-dark">
                                    <i class="fas fa-truck me-2 text-primary"></i>Transfer List
                                </h5>
                                <span class="badge bg-primary" id="transferCount">0</span>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>Drag items here. Only items with available stock
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="transfer-container"
                                 id="transferContainer"
                                 style="min-height: 400px; max-height: 500px; overflow-y: auto;">
                                <div id="transferSlots" class="transfer-slots p-2">
                                    <div class="empty-state text-center text-muted py-4">
                                        <i class="fas fa-arrow-left fa-lg mb-2 opacity-50"></i>
                                        <p class="mb-1">No items added yet</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top py-2">
                            <div class="d-grid gap-2">
                                <button type="button"
                                        class="btn btn-primary"
                                        id="generateTransferList"
                                        @if(!$canGenerateTransfer) disabled @endif>
                                    <i class="fas fa-file-export me-1"></i> Generate Transfer
                                </button>
                                <button type="button"
                                        class="btn btn-outline-danger"
                                        id="clearTransferList"
                                        @if(!$canGenerateTransfer) disabled @endif>
                                    <i class="fas fa-trash me-1"></i> Clear All
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Loader Modal -->
<div class="modal fade" id="customLoadingModal" tabindex="-1" aria-labelledby="customLoadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body p-0">
                <!-- Loader from Uiverse.io by DevPTG - Modified -->
                <div class="loader-stock" id="loaderStock">
                    <div>S</div>
                    <div>T</div>
                    <div>O</div>
                    <div>C</div>
                    <div>K</div>
                    <div>!</div>
                </div>
                <div class="text-center text-white mt-4" id="customLoadingText">Checking Stock...</div>
            </div>
        </div>
    </div>
</div>

<!-- SAP Credentials Modal -->
<div class="modal fade" id="sapCredentialsModal" tabindex="-1" aria-labelledby="sapCredentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-semibold" id="sapCredentialsModalLabel">
                    <i class="fas fa-key me-2 text-primary"></i>SAP Credentials
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 bg-light mb-4">
                    <i class="fas fa-info-circle me-2"></i>Enter your SAP credentials to process transfer
                </div>

                <form id="sapCredsForm">
                    <div class="mb-3">
                        <label for="sap_user" class="form-label fw-semibold">SAP Username *</label>
                        <input type="text"
                               class="form-control"
                               id="sap_user"
                               placeholder="Enter SAP username"
                               required
                               autocomplete="off">
                    </div>

                    <div class="mb-4">
                        <label for="sap_password" class="form-label fw-semibold">SAP Password *</label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control"
                                   id="sap_password"
                                   placeholder="Enter SAP password"
                                   required
                                   autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveSapCredentials">
                    <i class="fas fa-paper-plane me-1"></i> Process
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Preview Modal -->
<div class="modal fade" id="transferPreviewModal" tabindex="-1" aria-labelledby="transferPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-semibold" id="transferPreviewModalLabel">
                    <i class="fas fa-file-export me-2 text-primary"></i>Transfer Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 bg-light mb-4">
                    <i class="fas fa-info-circle me-2"></i>Review and edit transfer details before confirming. All fields are required.
                </div>

                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-borderless mb-0" id="transferPreviewTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th style="width: 90px;">Material</th>
                                <th style="min-width: 150px;">Description</th>
                                <th class="text-center" style="width: 70px;">Req Qty</th>
                                <th class="text-center" style="width: 70px;">Stock</th>
                                <th class="text-center" style="width: 90px;">Transfer Qty *</th>
                                <th class="text-center" style="width: 50px;">Unit</th>
                                <th class="text-center" style="width: 80px;">Plant Dest *</th>
                                <th class="text-center" style="width: 80px;">Sloc Dest *</th>
                                <th class="text-center" style="width: 200px;">Batch Source *</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Preview rows will be inserted here -->
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="transferRemarks" class="form-label fw-semibold">
                                <i class="fas fa-sticky-note me-1 text-muted"></i>Remarks
                            </label>
                            <textarea class="form-control" id="transferRemarks" rows="2"
                                      placeholder="Add remarks for this transfer..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3">
                                    <i class="fas fa-clipboard-list me-2 text-primary"></i>Transfer Summary
                                </h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Items:</span>
                                    <span class="fw-bold" id="modalTotalItems">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Transfer Qty:</span>
                                    <span class="fw-bold" id="modalTotalQty">0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Plant Supply:</span>
                                    <span class="fw-medium">{{ $document->sloc_supply ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmTransfer">
                    <i class="fas fa-paper-plane me-1"></i> Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed top-0 start-0 p-3" style="z-index: 9999;"></div>

<style>
/* Global Styles */
/* Transfer Dropdown Styles */
.transfer-dropdown {
    display: inline-block;
}

.transfer-dropdown .btn {
    padding: 2px 8px;
    font-size: 12px;
    border-radius: 4px;
}

.transfer-dropdown .badge {
    font-size: 10px;
    padding: 2px 5px;
    margin-right: 4px;
}

.transfer-dropdown-menu {
    max-height: 200px;
    overflow-y: auto;
    min-width: 200px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: 1px solid #dee2e6;
}

.transfer-dropdown-menu .dropdown-item {
    padding: 6px 10px;
    font-size: 13px;
    border-bottom: 1px solid #f8f9fa;
}

.transfer-dropdown-menu .dropdown-item:hover {
    background-color: #f8f9fa;
}

.transfer-dropdown {
    position: relative;
    display: inline-block;
}

.transfer-dropdown .dropdown-menu {
    z-index: 99999 !important;
    position: absolute;
    min-width: 250px;
    max-height: 300px;
    overflow-y: auto;
    margin-top: 2px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175);
    border: 1px solid rgba(0, 0, 0, 0.15);
}

/* Ensure parent containers don't clip the dropdown */
.card-body {
    overflow: visible !important;
}

.info-value {
    position: relative;
    overflow: visible;
}

/* Fix for modal backdrop issue */
.modal-backdrop {
    z-index: 1040;
}

.modal {
    z-index: 1050;
}

.transfer-dropdown-menu {
    z-index: 99999;
}

/* Ensure dropdown appears above table headers */
.table-responsive {
    position: relative;
    z-index: 1;
}

.transfer-dropdown .btn {
    z-index: 2;
    position: relative;
}

.transfer-dropdown-menu .dropdown-item:last-child {
    border-bottom: none;
}

.transfer-no-text {
    font-family: monospace;
    font-weight: 500;
    color: #0d6efd;
}

.card {
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
}

.table {
    margin-bottom: 0;
    font-size: 14px;
}

.table thead th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
    background-color: #f8f9fa;
    padding: 8px 6px;
    font-size: 13px;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table tbody td {
    padding: 6px 6px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Document Information Compact */
.document-info-compact {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.info-column {
    flex: 1;
    min-width: 180px;
}

.info-item {
    display: flex;
    flex-direction: column;
    margin-bottom: 6px;
    padding-bottom: 6px;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.info-label {
    color: #6c757d;
    font-weight: 500;
    font-size: 13px;
    margin-bottom: 2px;
}

.info-value {
    color: #212529;
    font-size: 14px;
}

/* Text truncate for 2 lines */
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4em;
    max-height: 2.8em;
}

/* Transfer Container */
.transfer-container {
    background-color: #f8f9fa;
    border-radius: 6px;
    border: 2px dashed #dee2e6;
}

.transfer-slots {
    min-height: 300px;
}

.transfer-item {
    background-color: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 8px;
    margin-bottom: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.transfer-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-color: #dee2e6;
}

.transfer-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.transfer-item-code {
    font-weight: 600;
    color: #0d6efd;
    font-size: 14px;
}

.transfer-item-remove {
    color: #dc3545;
    cursor: pointer;
    font-size: 12px;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.transfer-item-remove:hover {
    opacity: 1;
}

.transfer-item-desc {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 4px;
    line-height: 1.4;
}

.transfer-item-qty {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 4px;
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

/* Drag and Drop */
.draggable-row {
    cursor: move;
    transition: background-color 0.2s;
}

.draggable-row.dragging {
    opacity: 0.5;
    background-color: #f8f9fa;
}

.drag-over {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.05) !important;
}

/* Checkbox Styles */
.form-check-input {
    width: 16px;
    height: 16px;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Toast Styles */
.toast {
    border-radius: 6px;
    border: 1px solid #e9ecef;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-size: 14px;
    min-width: 300px;
    margin-bottom: 8px;
}

/* Modal Styles */
.modal-content {
    border-radius: 8px;
}

.modal-header {
    padding: 12px 16px;
}

.modal-body {
    padding: 16px;
}

.modal-footer {
    padding: 12px 16px;
}

/* Custom Scrollbar */
.transfer-container::-webkit-scrollbar,
.table-responsive::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.transfer-container::-webkit-scrollbar-track,
.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.transfer-container::-webkit-scrollbar-thumb,
.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.transfer-container::-webkit-scrollbar-thumb:hover,
.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .col-lg-8, .col-lg-4 {
        margin-bottom: 16px;
    }

    .transfer-container {
        min-height: 300px;
    }

    .document-info-compact {
        flex-direction: column;
        gap: 10px;
    }

    .info-column {
        min-width: 100%;
    }
}

/* Selection States */
.row-selected {
    background-color: rgba(13, 110, 253, 0.05) !important;
}

.transfer-item-selected {
    border-left: 3px solid #0d6efd;
}

/* Zero Stock Items */
.zero-stock {
    opacity: 0.6;
    cursor: not-allowed !important;
}

.zero-stock .text-dark {
    color: #6c757d !important;
}

/* Sticky Table Header Fix */
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 1020;
}

/* Mandatory field indicator */
.required-field::after {
    content: " *";
    color: #dc3545;
}

/* Uppercase input */
.uppercase-input {
    text-transform: uppercase;
}

/* Custom Stock Colors */
.stock-custom-unavailable {
    color: #999999 !important;
    font-weight: 500;
}

.stock-custom-unavailable-icon {
    color: #888888 !important;
}

.stock-custom-low {
    color: #ff9800 !important;
    font-weight: 500;
}

.stock-custom-available {
    color: #4caf50 !important;
    font-weight: 500;
}

.stock-custom-partial {
    color: #2196f3 !important;
    font-weight: 500;
}

/* Custom Loader from Uiverse.io by DevPTG - Modified */
.loader-stock {
  position: relative;
  width: 600px;
  height: 36px;
  left: 50%;
  top: 40%;
  margin-left: -300px;
  overflow: visible;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  cursor: default;
}

.loader-stock div {
  position: absolute;
  width: 20px;
  height: 36px;
  opacity: 0;
  font-family: Helvetica, Arial, sans-serif;
  animation: move 2s linear infinite;
  -o-animation: move 2s linear infinite;
  -moz-animation: move 2s linear infinite;
  -webkit-animation: move 2s linear infinite;
  transform: rotate(180deg);
  -o-transform: rotate(180deg);
  -moz-transform: rotate(180deg);
  -webkit-transform: rotate(180deg);
  font-size: 28px;
  font-weight: bold;
}

/* Warna default untuk checking stock */
.loader-stock.checking-stock div {
  color: #35C4F0;
}

/* Warna untuk processing transfer */
.loader-stock.processing-transfer div {
  color: #4CAF50;
}

/* Warna untuk resetting */
.loader-stock.resetting div {
  color: #FF9800;
}

.loader-stock div:nth-child(2) {
  animation-delay: 0.2s;
  -o-animation-delay: 0.2s;
  -moz-animation-delay: 0.2s;
  -webkit-animation-delay: 0.2s;
}

.loader-stock div:nth-child(3) {
  animation-delay: 0.4s;
  -o-animation-delay: 0.4s;
  -webkit-animation-delay: 0.4s;
  -webkit-animation-delay: 0.4s;
}

.loader-stock div:nth-child(4) {
  animation-delay: 0.6s;
  -o-animation-delay: 0.6s;
  -moz-animation-delay: 0.6s;
  -webkit-animation-delay: 0.6s;
}

.loader-stock div:nth-child(5) {
  animation-delay: 0.8s;
  -o-animation-delay: 0.8s;
  -moz-animation-delay: 0.8s;
  -webkit-animation-delay: 0.8s;
}

.loader-stock div:nth-child(6) {
  animation-delay: 1s;
  -o-animation-delay: 1s;
  -moz-animation-delay: 1s;
  -webkit-animation-delay: 1s;
}

@keyframes move {
  0% {
    left: 0;
    opacity: 0;
  }

  35% {
    left: 41%;
    -moz-transform: rotate(0deg);
    -webkit-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  65% {
    left: 59%;
    -moz-transform: rotate(0deg);
    -webkit-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  100% {
    left: 100%;
    -moz-transform: rotate(-180deg);
    -webkit-transform: rotate(-180deg);
    -o-transform: rotate(-180deg);
    transform: rotate(-180deg);
    opacity: 0;
  }
}

@-moz-keyframes move {
  0% {
    left: 0;
    opacity: 0;
  }

  35% {
    left: 41%;
    -moz-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  65% {
    left: 59%;
    -moz-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  100% {
    left: 100%;
    -moz-transform: rotate(-180deg);
    transform: rotate(-180deg);
    opacity: 0;
  }
}

@-webkit-keyframes move {
  0% {
    left: 0;
    opacity: 0;
  }

  35% {
    left: 41%;
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  65% {
    left: 59%;
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  100% {
    left: 100%;
    -webkit-transform: rotate(-180deg);
    transform: rotate(-180deg);
    opacity: 0;
  }
}

@-o-keyframes move {
  0% {
    left: 0;
    opacity: 0;
  }

  35% {
    left: 41%;
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  65% {
    left: 59%;
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  100% {
    left: 100%;
    -o-transform: rotate(-180deg);
    transform: rotate(-180deg);
    opacity: 0;
  }
}

/* Adjust modal for the loader */
#customLoadingModal .modal-content {
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(10px);
  border-radius: 12px;
}

#customLoadingModal .modal-body {
  padding: 60px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

#customLoadingText {
  font-size: 16px;
  font-weight: 500;
  color: #35C4F0;
  text-shadow: 0 0 10px rgba(53, 196, 240, 0.5);
  margin-top: 20px;
  font-family: 'Segoe UI', Arial, sans-serif;
}

/* Responsive adjustments for loader */
@media (max-width: 768px) {
  .loader-stock {
    width: 400px;
    margin-left: -200px;
  }

  .loader-stock div {
    font-size: 24px;
  }
}

@media (max-width: 576px) {
  .loader-stock {
    width: 300px;
    margin-left: -150px;
  }

  .loader-stock div {
    font-size: 20px;
  }
}

/* Button disabled styles */
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    let transferItems = [];
    let selectedItems = new Set();

    // Cek apakah user memiliki izin untuk generate transfer
    const canGenerateTransfer = @json($canGenerateTransfer ?? true);

    // Helper functions
    function formatAngka(num, decimalDigits = 2) {
        if (typeof num === 'string') {
            num = parseFloat(num);
        }

        if (isNaN(num)) return '0';

        if (num % 1 === 0) {
            return num.toLocaleString('id-ID');
        } else {
            return num.toLocaleString('id-ID', {
                minimumFractionDigits: decimalDigits,
                maximumFractionDigits: decimalDigits
            });
        }
    }

    function parseAngka(str) {
        if (!str || str.trim() === '') return 0;
        let cleaned = str.replace(/\./g, '').replace(',', '.');
        return parseFloat(cleaned) || 0;
    }

    // Function to show loader with specific action
    function showLoader(action, text = '') {
        const loaderElement = document.getElementById('loaderStock');
        const loadingText = document.getElementById('customLoadingText');

        // Reset loader classes
        loaderElement.classList.remove('checking-stock', 'processing-transfer', 'resetting');

        // Set loader class based on action
        switch(action) {
            case 'checking':
                loaderElement.classList.add('checking-stock');
                loadingText.textContent = text || 'Checking Stock...';
                loadingText.style.color = '#35C4F0';
                break;
            case 'transfer':
                loaderElement.classList.add('processing-transfer');
                loadingText.textContent = text || 'Processing Transfer...';
                loadingText.style.color = '#4CAF50';
                break;
            case 'resetting':
                loaderElement.classList.add('resetting');
                loadingText.textContent = text || 'Resetting Stock...';
                loadingText.style.color = '#FF9800';
                break;
            default:
                loaderElement.classList.add('checking-stock');
                loadingText.textContent = text || 'Loading...';
                loadingText.style.color = '#35C4F0';
        }

        // Show modal
        const loadingModal = new bootstrap.Modal(document.getElementById('customLoadingModal'));
        loadingModal.show();

        return loadingModal;
    }

    // Setup drag and drop
    function setupDragAndDrop() {
        const rows = document.querySelectorAll('.draggable-row');
        const dropZone = document.getElementById('transferContainer');

        rows.forEach(function(row) {
            const availableStock = parseFloat(row.dataset.availableStock || 0);

            if (availableStock > 0 && canGenerateTransfer) {
                row.draggable = true;

                row.addEventListener('dragstart', function(e) {
                    this.classList.add('dragging');
                    const isSelected = selectedItems.has(this.dataset.itemId);

                    if (selectedItems.size > 0 && isSelected) {
                        e.dataTransfer.setData('text/plain', 'multiple');
                    } else {
                        e.dataTransfer.setData('text/plain', this.dataset.itemId);
                    }

                    e.dataTransfer.effectAllowed = 'copy';
                });

                row.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                });
            } else {
                row.draggable = false;
                row.classList.add('zero-stock');
                row.title = availableStock > 0 ? 'No permission to transfer' : 'No stock available';
            }
        });

        // Drop zone events
        if (canGenerateTransfer) {
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
                e.dataTransfer.dropEffect = 'copy';
            });

            dropZone.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                const data = e.dataTransfer.getData('text/plain');

                if (data === 'multiple') {
                    addSelectedItemsToTransfer();
                } else if (data) {
                    addItemById(data);
                }
            });
        }
    }

    // Setup checkbox selection
    function setupCheckboxSelection() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const selectAllHeader = document.getElementById('selectAllHeader');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('click', function(e) {
                const isChecked = e.target.checked;
                const checkboxes = document.querySelectorAll('.row-select:not(:disabled)');

                checkboxes.forEach(function(cb) {
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
        }

        if (selectAllHeader) {
            selectAllHeader.addEventListener('click', function(e) {
                const isChecked = e.target.checked;
                const checkboxes = document.querySelectorAll('.row-select:not(:disabled)');

                checkboxes.forEach(function(cb) {
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
        }

        // Individual checkboxes
        document.querySelectorAll('.row-select').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const itemId = this.dataset.itemId;
                if (this.checked) {
                    selectedItems.add(itemId);
                } else {
                    selectedItems.delete(itemId);
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    if (selectAllHeader) selectAllHeader.checked = false;
                }
                updateSelectionCount();
            });
        });

        // Clear selection button
        const clearSelectionBtn = document.getElementById('clearSelection');
        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function() {
                if (selectedItems.size > 0) {
                    selectedItems.clear();
                    document.querySelectorAll('.row-select').forEach(function(cb) {
                        cb.checked = false;
                    });
                    updateSelectionCount();
                    showToast('Selection cleared', 'info');
                }
            });
        }

        // Add selected to transfer button
        const addSelectedToTransferBtn = document.getElementById('addSelectedToTransfer');
        if (addSelectedToTransferBtn) {
            addSelectedToTransferBtn.addEventListener('click', function() {
                addSelectedItemsToTransfer();
            });
        }
    }

    // Update selection count
    function updateSelectionCount() {
        const count = selectedItems.size;
        document.getElementById('selectedCount').textContent = count + ' selected';

        // Update row selection style
        document.querySelectorAll('.draggable-row').forEach(function(row) {
            const itemId = row.dataset.itemId;
            if (selectedItems.has(itemId)) {
                row.classList.add('row-selected');
            } else {
                row.classList.remove('row-selected');
            }
        });

        // Update select all checkboxes
        const totalCheckboxes = document.querySelectorAll('.row-select:not(:disabled)').length;
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const selectAllHeader = document.getElementById('selectAllHeader');

        if (count === totalCheckboxes && totalCheckboxes > 0) {
            if (selectAllCheckbox) selectAllCheckbox.checked = true;
            if (selectAllHeader) selectAllHeader.checked = true;
        } else {
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            if (selectAllHeader) selectAllHeader.checked = false;
        }
    }

    // Toast notification
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
                <div class="toast-body">
                    <div class="d-flex align-items-center">
                        <i class="fas ${iconClass} me-3"></i>
                        <div class="flex-grow-1">${message}</div>
                        <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);

        // Set longer duration (8000ms = 8 seconds)
        const toast = new bootstrap.Toast(toastElement, {
            delay: 8000,
            animation: true,
            autohide: true
        });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    // Get item data from row
    function getItemDataFromRow(rowElement) {
        const row = rowElement;
        const batchInfoData = row.dataset.batchInfo;

        let batchInfo = [];
        if (batchInfoData) {
            try {
                const cleanedData = batchInfoData.replace(/&quot;/g, '"');
                batchInfo = JSON.parse(cleanedData);
            } catch (e) {
                console.warn('Error parsing batch info:', e.message);
            }
        }

        return {
            id: row.dataset.itemId,
            materialCode: row.dataset.materialCode,
            materialDesc: row.dataset.materialDescription,
            requestedQty: parseFloat(row.dataset.requestedQty || 0),
            availableStock: parseFloat(row.dataset.availableStock || 0),
            unit: row.dataset.unit || 'PC',
            sloc: row.dataset.sloc || '',
            batchInfo: batchInfo,
            canTransfer: row.dataset.canTransfer === 'true'
        };
    }

    // Add selected items to transfer
    function addSelectedItemsToTransfer() {
        if (!canGenerateTransfer) {
            showToast('You do not have permission to create transfers', 'error');
            return;
        }

        if (selectedItems.size === 0) {
            showToast('No items selected', 'warning');
            return;
        }

        let addedCount = 0;
        selectedItems.forEach(function(itemId) {
            const itemRow = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
            if (itemRow) {
                const itemData = getItemDataFromRow(itemRow);

                if (!transferItems.some(function(transferItem) { return transferItem.id === itemData.id; })) {
                    if (itemData.availableStock > 0) {
                        addItemToTransferByData(itemData);
                        addedCount++;
                    }
                }
            }
        });

        if (addedCount > 0) {
            showToast(addedCount + ' items added to transfer list', 'success');
            selectedItems.clear();
            updateSelectionCount();
        } else {
            showToast('No new items added', 'warning');
        }
    }

    // Add item by ID
    function addItemById(itemId) {
        if (!canGenerateTransfer) {
            showToast('You do not have permission to create transfers', 'error');
            return;
        }

        const itemRow = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
        if (!itemRow) {
            showToast('Item not found', 'error');
            return;
        }

        const itemData = getItemDataFromRow(itemRow);

        if (transferItems.some(function(transferItem) { return transferItem.id === itemData.id; })) {
            showToast('Item already in transfer list', 'warning');
            return;
        }

        if (itemData.availableStock <= 0) {
            showToast('Item has no available stock', 'error');
            return;
        }

        addItemToTransferByData(itemData);
    }

    // Add item to transfer by data
    function addItemToTransferByData(item) {
        const defaultPlant = "{{ $document->plant }}";

        // Get first batch if available
        let selectedBatch = '';
        let batchQty = 0;
        let batchSloc = '';

        if (item.batchInfo && item.batchInfo.length > 0) {
            const firstBatch = item.batchInfo[0];
            selectedBatch = firstBatch.batch || firstBatch.sloc || '';
            batchQty = firstBatch.qty || 0;
            batchSloc = firstBatch.sloc || selectedBatch;
        } else if (item.sloc) {
            const slocs = item.sloc.split(',');
            if (slocs.length > 0) {
                selectedBatch = slocs[0].trim();
                batchQty = item.availableStock;
                batchSloc = selectedBatch;
            }
        }

        // Add to transfer items array
        const transferItem = {
            id: item.id,
            materialCode: item.materialCode,
            materialDesc: item.materialDesc,
            maxQty: item.availableStock,
            requestedQty: item.requestedQty,
            availableStock: item.availableStock,
            qty: Math.min(item.requestedQty, item.availableStock),
            unit: item.unit,
            sloc: item.sloc,
            batchInfo: item.batchInfo,
            selectedBatch: selectedBatch,
            batchQty: batchQty,
            batchSloc: batchSloc,
            plantTujuan: defaultPlant,
            slocTujuan: '' // Kosong secara default
        };

        transferItems.push(transferItem);

        // Render transfer item
        renderTransferItem(transferItem);

        // Update transfer count
        updateTransferCount();

        // Mark row as selected
        const row = document.querySelector('.draggable-row[data-item-id="' + item.id + '"]');
        if (row) {
            row.classList.add('transfer-item-selected');
        }

        showToast('Item added to transfer list', 'success');
    }

    // Render transfer item
    function renderTransferItem(item) {
        const transferSlots = document.getElementById('transferSlots');

        // Hapus empty state jika ada
        const emptyState = transferSlots.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }

        const itemDiv = document.createElement('div');
        itemDiv.className = 'transfer-item';
        itemDiv.dataset.itemId = item.id;

        itemDiv.innerHTML = '<div class="transfer-item-header">' +
            '<div>' +
            '<span class="transfer-item-code">' + item.materialCode + '</span>' +
            '</div>' +
            '<span class="transfer-item-remove">' +
            '<i class="fas fa-times"></i>' +
            '</span>' +
            '</div>' +
            '<div class="transfer-item-desc">' + (item.materialDesc.length > 50 ? item.materialDesc.substring(0, 50) + '...' : item.materialDesc) + '</div>' +
            '<div class="transfer-item-qty">' +
            '<span class="text-muted">Req: ' + formatAngka(item.requestedQty) + '</span>' +
            '<span class="text-muted ms-2">Max: ' + formatAngka(item.maxQty) + '</span>' +
            '<span class="text-muted ms-2">' + item.unit + '</span>' +
            '</div>';

        // Add event listener for remove button
        const removeBtn = itemDiv.querySelector('.transfer-item-remove');
        removeBtn.addEventListener('click', function() {
            removeTransferItem(item.id);
        });

        transferSlots.appendChild(itemDiv);
    }

    // Remove transfer item
    function removeTransferItem(itemId) {
        // Hapus dari array
        transferItems = transferItems.filter(function(item) { return item.id !== itemId; });

        // Hapus elemen dari DOM
        const itemElement = document.querySelector('.transfer-item[data-item-id="' + itemId + '"]');
        if (itemElement) {
            itemElement.remove();
        }

        // Hapus class selected dari row
        const row = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
        if (row) {
            row.classList.remove('transfer-item-selected');
        }

        updateTransferCount();

        // Jika tidak ada item lagi, tampilkan empty state
        if (transferItems.length === 0) {
            showEmptyState();
        }

        showToast('Item removed from transfer list', 'info');
    }

    // Update transfer count
    function updateTransferCount() {
        const totalItems = transferItems.length;
        document.getElementById('transferCount').textContent = totalItems;
    }

    // Show empty state
    function showEmptyState() {
        const transferSlots = document.getElementById('transferSlots');

        // Hanya tampilkan empty state jika benar-benar kosong
        if (transferSlots.children.length === 0) {
            transferSlots.innerHTML = '<div class="empty-state text-center text-muted py-4">' +
                '<i class="fas fa-arrow-left fa-lg mb-2 opacity-50"></i>' +
                '<p class="mb-1">No items added yet</p>' +
                '</div>';
        }
    }

    // Clear transfer list
    const clearTransferListBtn = document.getElementById('clearTransferList');
    if (clearTransferListBtn) {
        clearTransferListBtn.addEventListener('click', function() {
            if (transferItems.length === 0) {
                showToast('Transfer list is already empty', 'info');
                return;
            }

            // Hapus semua item dari array
            transferItems = [];

            // Hapus semua elemen transfer item dari DOM
            const transferSlots = document.getElementById('transferSlots');
            if (transferSlots) {
                transferSlots.innerHTML = '';
            }

            // Hapus class selected dari semua row
            document.querySelectorAll('.draggable-row').forEach(function(row) {
                row.classList.remove('transfer-item-selected');
            });

            updateTransferCount();
            showEmptyState();
            showToast('Transfer list cleared', 'info');
        });
    }

    // Generate transfer list
    const generateTransferListBtn = document.getElementById('generateTransferList');
    if (generateTransferListBtn) {
        generateTransferListBtn.addEventListener('click', function() {
            if (!canGenerateTransfer) {
                showToast('You do not have permission to create transfers', 'error');
                return;
            }

            if (transferItems.length === 0) {
                showToast('Please add items to transfer list first', 'warning');
                return;
            }

            try {
                // Populate modal preview
                populateTransferPreviewModal();

                // Show modal
                const modalElement = document.getElementById('transferPreviewModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            } catch (error) {
                console.error('Error in generateTransferList:', error);
                showToast('Error: ' + error.message, 'error');
            }
        });
    }

    // Populate transfer preview modal
    function populateTransferPreviewModal() {
        const tbody = document.querySelector('#transferPreviewTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        transferItems.forEach(function(item, index) {
            // Create batch options
            const batchOptions = createBatchOptions(item.batchInfo, item.selectedBatch);

            // Create row for modal table
            const row = document.createElement('tr');
            row.innerHTML =
                '<td class="text-center align-middle">' + (index + 1) + '</td>' +
                '<td class="align-middle"><div class="fw-medium">' + item.materialCode + '</div></td>' +
                '<td class="align-middle"><div class="text-truncate-2" title="' + item.materialDesc + '">' + (item.materialDesc.length > 40 ? item.materialDesc.substring(0, 40) + '...' : item.materialDesc) + '</div></td>' +
                '<td class="text-center align-middle"><div class="fw-medium">' + formatAngka(item.requestedQty) + '</div></td>' +
                '<td class="text-center align-middle"><div class="' + (item.availableStock > 0 ? 'stock-custom-available' : 'stock-custom-unavailable') + ' fw-medium">' + formatAngka(item.availableStock) + '</div></td>' +
                '<td class="text-center align-middle">' +
                    '<input type="text" class="form-control form-control-sm qty-transfer-input uppercase-input" value="' + formatAngka(item.qty || 0) + '" placeholder="0" data-index="' + index + '" required style="width: 80px;">' +
                '</td>' +
                '<td class="text-center align-middle">' + item.unit + '</td>' +
                '<td class="text-center align-middle">' +
                    '<input type="text" class="form-control form-control-sm plant-tujuan-input uppercase-input" value="' + (item.plantTujuan || '{{ $document->plant }}') + '" data-index="' + index + '" required style="width: 70px;">' +
                '</td>' +
                '<td class="text-center align-middle">' +
                    '<input type="text" class="form-control form-control-sm sloc-tujuan-input uppercase-input" value="' + (item.slocTujuan || '') + '" placeholder="Enter SLOC" data-index="' + index + '" required style="width: 70px;">' +
                '</td>' +
                '<td class="text-center align-middle">' +
                    '<select class="form-control form-control-sm batch-source-select" data-index="' + index + '" required style="width: 180px;">' +
                        '<option value="">Select Batch *</option>' +
                        batchOptions +
                    '</select>' +
                '</td>';

            tbody.appendChild(row);
        });

        // Update totals
        updateModalTotals();

        // Setup event listeners for modal inputs
        setupModalEventListeners();
    }

    // Create batch options
    function createBatchOptions(batchInfo, selectedBatch) {
        if (!selectedBatch) selectedBatch = '';
        if (!batchInfo || batchInfo.length === 0) {
            return '';
        }

        let options = '';
        batchInfo.forEach(function(batch, index) {
            const batchValue = batch.batch || batch.sloc || 'BATCH' + (index + 1);
            const batchQty = batch.qty || 0;
            const batchSloc = batch.sloc || batchValue;
            const displayQty = formatAngka(batchQty);
            const batchLabel = batchSloc + ' | ' + batchValue + ' | ' + displayQty;
            const selected = batchValue === selectedBatch ? 'selected' : '';

            options += '<option value="' + batchValue + '" ' +
                       'data-qty="' + batchQty + '" ' +
                       'data-sloc="' + batchSloc + '" ' +
                       selected + '>' +
                       batchLabel +
                       '</option>';
        });

        return options;
    }

    // Setup modal event listeners
    function setupModalEventListeners() {
        // Quantity input change - with uppercase
        document.querySelectorAll('.qty-transfer-input').forEach(function(input) {
            // Format on blur
            input.addEventListener('blur', function() {
                const index = parseInt(this.dataset.index);
                let value = this.value.trim();

                // Parse number
                let parsedValue = parseAngka(value);

                // Validation
                const maxQty = transferItems[index].availableStock;

                if (isNaN(parsedValue) || parsedValue < 0) {
                    this.value = '';
                    transferItems[index].qty = 0;
                    showToast('Quantity for ' + transferItems[index].materialCode + ' is required', 'error');
                } else if (parsedValue > maxQty) {
                    this.value = formatAngka(maxQty);
                    transferItems[index].qty = maxQty;
                    showToast('Quantity cannot exceed ' + formatAngka(maxQty), 'warning');
                } else {
                    // Reformat with Indonesian format
                    this.value = formatAngka(parsedValue);
                    transferItems[index].qty = parsedValue;
                }

                updateModalTotals();
            });

            // Real-time validation (only numbers, dots, and commas)
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^\d.,]/g, '');
            });

            // Format on focus (show number without formatting)
            input.addEventListener('focus', function() {
                const index = parseInt(this.dataset.index);
                if (transferItems[index].qty > 0) {
                    this.value = transferItems[index].qty.toString().replace('.', ',');
                }
            });
        });

        // Plant tujuan change - with uppercase
        document.querySelectorAll('.plant-tujuan-input').forEach(function(input) {
            // Convert to uppercase on input
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });

            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                if (!this.value.trim()) {
                    showToast('Plant Destination for ' + transferItems[index].materialCode + ' is required', 'error');
                    this.focus();
                    return;
                }
                transferItems[index].plantTujuan = this.value.toUpperCase();
            });

            // Check on blur
            input.addEventListener('blur', function() {
                const index = parseInt(this.dataset.index);
                if (!this.value.trim()) {
                    showToast('Plant Destination for ' + transferItems[index].materialCode + ' is required', 'error');
                    this.focus();
                }
            });
        });

        // SLOC tujuan change - with uppercase
        document.querySelectorAll('.sloc-tujuan-input').forEach(function(input) {
            // Convert to uppercase on input
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });

            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                if (!this.value.trim()) {
                    showToast('Sloc Destination for ' + transferItems[index].materialCode + ' is required', 'error');
                    this.focus();
                    return;
                }
                transferItems[index].slocTujuan = this.value.toUpperCase();
            });

            // Check on blur
            input.addEventListener('blur', function() {
                const index = parseInt(this.dataset.index);
                if (!this.value.trim()) {
                    showToast('Sloc Destination for ' + transferItems[index].materialCode + ' is required', 'error');
                    this.focus();
                }
            });
        });

        // Batch select change
        document.querySelectorAll('.batch-source-select').forEach(function(select) {
            select.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                const selectedValue = this.value;
                const selectedOption = this.options[this.selectedIndex];

                if (!selectedValue) {
                    showToast('Batch Source for ' + transferItems[index].materialCode + ' is required', 'error');
                    this.focus();
                    return;
                }

                const batchQty = parseFloat(selectedOption.dataset.qty) || 0;
                const batchSloc = selectedOption.dataset.sloc || selectedValue;

                transferItems[index].selectedBatch = selectedValue;
                transferItems[index].batchQty = batchQty;
                transferItems[index].batchSloc = batchSloc;

                // Get related quantity input
                const qtyInput = document.querySelector('.qty-transfer-input[data-index="' + index + '"]');
                const maxQty = batchQty;

                if (qtyInput) {
                    // If quantity exceeds batch qty, adjust it
                    const currentQty = parseAngka(qtyInput.value);
                    if (currentQty > maxQty && maxQty > 0) {
                        qtyInput.value = formatAngka(maxQty);
                        transferItems[index].qty = maxQty;
                        showToast('Quantity adjusted to available batch: ' + formatAngka(maxQty), 'info');
                        updateModalTotals();
                    }
                }
            });

            // Check on blur
            select.addEventListener('blur', function() {
                if (!this.value) {
                    const index = parseInt(this.dataset.index);
                    showToast('Batch Source for ' + transferItems[index].materialCode + ' is required', 'error');
                    this.focus();
                }
            });
        });
    }

    // Update modal totals
    function updateModalTotals() {
        let totalQty = 0;
        transferItems.forEach(function(item) {
            totalQty += item.qty || 0;
        });

        const modalTotalItems = document.getElementById('modalTotalItems');
        const modalTotalQty = document.getElementById('modalTotalQty');

        if (modalTotalItems) {
            modalTotalItems.textContent = transferItems.length;
        }

        if (modalTotalQty) {
            modalTotalQty.textContent = formatAngka(totalQty);
        }
    }

    // Confirm transfer button
    const confirmTransferBtn = document.getElementById('confirmTransfer');
    if (confirmTransferBtn) {
        confirmTransferBtn.addEventListener('click', function() {
            const transferRemarks = document.getElementById('transferRemarks');
            const remarks = transferRemarks ? transferRemarks.value : '';

            // Validate: Ensure all mandatory fields are filled
            let isValid = true;
            let errorMessage = '';

            // Check all modal inputs
            document.querySelectorAll('.qty-transfer-input, .plant-tujuan-input, .sloc-tujuan-input, .batch-source-select').forEach(function(input) {
                if (!input.value || input.value.trim() === '') {
                    isValid = false;
                    const index = parseInt(input.dataset.index);
                    const materialCode = transferItems[index].materialCode;
                    const fieldName = input.classList.contains('qty-transfer-input') ? 'Transfer Quantity' :
                                     input.classList.contains('plant-tujuan-input') ? 'Plant Destination' :
                                     input.classList.contains('sloc-tujuan-input') ? 'SLOC Destination' : 'Batch Source';
                    errorMessage = fieldName + ' for ' + materialCode + ' is required';
                    input.classList.add('is-invalid');
                    input.focus();
                    return false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                showToast(errorMessage, 'error');
                return;
            }

            // Validate quantities
            transferItems.forEach(function(item, index) {
                if (!item.qty || item.qty <= 0) {
                    isValid = false;
                    errorMessage = 'Quantity for ' + item.materialCode + ' must be greater than 0';
                    return;
                }

                if (item.qty > item.availableStock) {
                    isValid = false;
                    errorMessage = 'Quantity for ' + item.materialCode + ' (' + formatAngka(item.qty) + ') exceeds available stock (' + formatAngka(item.availableStock) + ')';
                    return;
                }
            });

            if (!isValid) {
                showToast(errorMessage, 'error');
                return;
            }

            // Update transferItems with data from modal
            document.querySelectorAll('.qty-transfer-input').forEach(function(input) {
                const index = parseInt(input.dataset.index);
                let value = input.value.trim();

                if (value) {
                    let parsedValue = parseAngka(value);
                    transferItems[index].qty = parsedValue;
                }
            });

            // Update plant and sloc tujuan
            document.querySelectorAll('.plant-tujuan-input').forEach(function(input) {
                const index = parseInt(input.dataset.index);
                transferItems[index].plantTujuan = input.value.toUpperCase();
            });

            document.querySelectorAll('.sloc-tujuan-input').forEach(function(input) {
                const index = parseInt(input.dataset.index);
                transferItems[index].slocTujuan = input.value.toUpperCase();
            });

            // Update selected batch
            document.querySelectorAll('.batch-source-select').forEach(function(select) {
                const index = parseInt(select.dataset.index);
                const selectedOption = select.options[select.selectedIndex];

                if (selectedOption) {
                    transferItems[index].selectedBatch = select.value;
                    transferItems[index].batchSloc = selectedOption.dataset.sloc || '';
                }
            });

            // Save remarks to transfer items
            transferItems.forEach(function(item) {
                item.remarks = remarks;
            });

            // Close preview modal first
            const transferModalElement = document.getElementById('transferPreviewModal');
            if (transferModalElement) {
                const transferModal = bootstrap.Modal.getInstance(transferModalElement);
                if (transferModal) {
                    transferModal.hide();
                }
            }

            // Wait for modal to close, then show SAP credentials modal
            setTimeout(function() {
                const sapModalElement = document.getElementById('sapCredentialsModal');
                if (sapModalElement) {
                    const sapModal = new bootstrap.Modal(sapModalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    sapModal.show();

                    // Reset form SAP credentials
                    const sapUserInput = document.getElementById('sap_user');
                    const sapPasswordInput = document.getElementById('sap_password');
                    if (sapUserInput) sapUserInput.value = '';
                    if (sapPasswordInput) sapPasswordInput.value = '';
                    if (sapUserInput) sapUserInput.focus();
                }
            }, 300);
        });
    }

    // Toggle password visibility
    const togglePasswordBtn = document.getElementById('togglePassword');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function() {
            const passwordInput = document.getElementById('sap_password');
            if (passwordInput) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            }
        });
    }

    // Handle SAP credentials save
    const saveSapCredentialsBtn = document.getElementById('saveSapCredentials');
    if (saveSapCredentialsBtn) {
        saveSapCredentialsBtn.addEventListener('click', function() {
            const sapUser = document.getElementById('sap_user').value;
            const sapPassword = document.getElementById('sap_password').value;

            if (!sapUser || !sapPassword) {
                showToast('Please fill all required SAP credentials', 'error');
                return;
            }

            // Hide SAP credentials modal
            const sapModalElement = document.getElementById('sapCredentialsModal');
            const sapModal = bootstrap.Modal.getInstance(sapModalElement);
            if (sapModal) {
                sapModal.hide();
            }

            // Show custom loading modal with transfer action
            const loadingModal = showLoader('transfer', 'Processing Transfer to SAP...');

            // Prepare items for SAP transfer
            const sapTransferItems = transferItems.map(function(item) {
                // Parse batch_sloc
                let batchSloc = item.batchSloc || '';
                if (batchSloc && !batchSloc.startsWith('SLOC:')) {
                    batchSloc = 'SLOC:' + batchSloc;
                }

                return {
                    material_code: item.materialCode,
                    material_desc: item.materialDesc,
                    quantity: item.qty || 0,
                    unit: item.unit,
                    plant_tujuan: item.plantTujuan || '{{ $document->plant }}',
                    sloc_tujuan: item.slocTujuan || '',
                    batch: item.selectedBatch || '',
                    batch_sloc: batchSloc,
                    requested_qty: item.requestedQty,
                    available_stock: item.availableStock
                };
            });

            // Prepare transfer data
            const transferData = {
                plant: "{{ $document->plant }}",
                sloc_supply: "{{ $document->sloc_supply }}",
                items: sapTransferItems,
                header_text: document.getElementById('transferRemarks') ?
                            "Transfer from Document {{ $document->document_no }} - " +
                            document.getElementById('transferRemarks').value :
                            "Transfer from Document {{ $document->document_no }}",
                remarks: document.getElementById('transferRemarks') ?
                         document.getElementById('transferRemarks').value : '',
                sap_credentials: {
                    user: sapUser,
                    passwd: sapPassword,
                    client: "{{ env('SAP_CLIENT', '100') }}",
                    lang: "{{ env('SAP_LANG', 'EN') }}",
                    ashost: "{{ env('SAP_ASHOST', 'localhost') }}",
                    sysnr: "{{ env('SAP_SYSNR', '00') }}"
                }
            };

            // Send to Laravel controller
            fetch('{{ route("documents.create-transfer", $document->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(transferData)
            })
            .then(function(response) {
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(function(text) {
                        console.error('Non-JSON response:', text);
                        throw new Error('Expected JSON response but got: ' + text.substring(0, 100));
                    });
                }

                if (!response.ok) {
                    return response.json().then(function(errData) {
                        console.error('Server error response:', errData);
                        throw new Error(errData.message || 'Server Error: ' + response.statusText);
                    });
                }
                return response.json();
            })
            .then(function(data) {
                // Hide loading modal
                const loadingModalElement = document.getElementById('customLoadingModal');
                if (loadingModalElement) {
                    const loadingModal = bootstrap.Modal.getInstance(loadingModalElement);
                    if (loadingModal) {
                        loadingModal.hide();
                    }
                }

                if (data.success) {
                    // Show transfer number
                    const transferNo = data.transfer_no || 'PENDING';
                    showToast('Transfer successful! Material Document: ' + transferNo + ' created', 'success');

                    // Clear transfer list
                    document.getElementById('clearTransferList').click();

                    // Refresh page after 1.5 seconds
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    let errorMsg = data.message || 'Unknown error occurred';
                    if (data.errors && Array.isArray(data.errors)) {
                        errorMsg = data.errors.join(', ');
                    } else if (data.errors && typeof data.errors === 'object') {
                        errorMsg = Object.values(data.errors).flat().join(', ');
                    }
                    showToast('Transfer Error: ' + errorMsg, 'error');
                }
            })
            .catch(function(error) {
                // Hide loading modal
                const loadingModalElement = document.getElementById('customLoadingModal');
                if (loadingModalElement) {
                    const loadingModal = bootstrap.Modal.getInstance(loadingModalElement);
                    if (loadingModal) {
                        loadingModal.hide();
                    }
                }

                console.error('Error:', error);

                let errorMsg = error.message || 'Error creating transfer document';
                if (errorMsg.includes('RFC_RC') || errorMsg.includes('RFC')) {
                    errorMsg = 'SAP RFC Error: Please check SAP connection and credentials.';
                } else if (errorMsg.includes('Network') || errorMsg.includes('Failed to fetch')) {
                    errorMsg = 'Network Error: Cannot connect to server. Please check your connection.';
                } else if (errorMsg.includes('Validation error')) {
                    errorMsg = 'Validation Error: Please check all required fields are filled correctly.';
                }

                showToast(errorMsg, 'error');
            });
        });
    }

    // Custom loader for check stock form
    const checkStockForm = document.getElementById('checkStockForm');
    if (checkStockForm) {
        checkStockForm.addEventListener('submit', function(e) {
            // Show custom loading modal with checking action
            showLoader('checking', 'Checking Stock...');
        });
    }

    // Handle reset stock form with custom loader
    const resetStockForm = document.getElementById('resetStockForm');
    if (resetStockForm) {
        resetStockForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show custom loading modal with resetting action
            const loadingModal = showLoader('resetting', 'Resetting Stock...');

            // Get form data
            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function(data) {
                // Hide loading modal
                const loadingModalElement = document.getElementById('customLoadingModal');
                if (loadingModalElement) {
                    const loadingModal = bootstrap.Modal.getInstance(loadingModalElement);
                    if (loadingModal) {
                        loadingModal.hide();
                    }
                }

                if (data.success) {
                    showToast(data.message, 'success');
                    // Auto refresh page after 1 second
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(function(error) {
                // Hide loading modal
                const loadingModalElement = document.getElementById('customLoadingModal');
                if (loadingModalElement) {
                    const loadingModal = bootstrap.Modal.getInstance(loadingModalElement);
                    if (loadingModal) {
                        loadingModal.hide();
                    }
                }

                console.error('Error:', error);
                showToast('Error resetting stock data: ' + error.message, 'error');
            });
        });
    }

    // Initialize
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
