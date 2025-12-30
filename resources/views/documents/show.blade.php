@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="page-title-box">
            <h4 class="page-title mb-0">
                <i class="fas fa-file-alt me-2 text-primary"></i>
                Document Details
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('documents.index') }}" class="text-muted">
                            <i class="fas fa-home me-1"></i> Documents
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Document Details</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            @if(in_array($document->status, ['booked', 'partial']))
            <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            @endif
            <a href="{{ route('documents.print', $document->id) }}" class="btn btn-outline-secondary btn-sm">
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

    <!-- Document Overview Cards -->
    <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card card-animate border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Document No</p>
                            <h5 class="mb-0 fw-semibold text-primary">{{ $document->document_no }}</h5>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-primary rounded fs-4">
                                <i class="fas fa-barcode text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card card-animate border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Status</p>
                            <h5 class="mb-0 fw-semibold">
                                @if($document->status == 'booked')
                                <span class="badge bg-warning">
                                    <i class="fas fa-clock me-1"></i>Booked
                                </span>
                                @elseif($document->status == 'partial')
                                <span class="badge bg-info">
                                    <i class="fas fa-tasks me-1"></i>Partial
                                </span>
                                @elseif($document->status == 'closed')
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Closed
                                </span>
                                @else
                                <span class="badge bg-danger">
                                    <i class="fas fa-times-circle me-1"></i>Cancelled
                                </span>
                                @endif
                            </h5>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-warning rounded fs-4">
                                <i class="fas fa-chart-line text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card card-animate border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Items</p>
                            <h5 class="mb-0 fw-semibold">{{ $document->items->count() }}</h5>
                            <p class="text-muted mb-0 small">{{ number_format($document->total_qty) }} total quantity</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-success rounded fs-4">
                                <i class="fas fa-boxes text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card card-animate border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Completion Rate</p>
                            <h5 class="mb-0 fw-semibold">{{ round($document->completion_rate ?? 0, 2) }}%</h5>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ round($document->completion_rate ?? 0, 2) }}%;"
                                     aria-valuenow="{{ round($document->completion_rate ?? 0, 2) }}"></div>
                            </div>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-info rounded fs-4">
                                <i class="fas fa-chart-pie text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Details -->
    <div class="row mb-4">
        <!-- Document Information -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom bg-light py-2 px-3">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Document Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1">Plant Request</label>
                                <p class="fw-medium mb-0">{{ $document->plant }}</p>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1">Plant Supply</label>
                                <p class="fw-medium mb-0">
                                    @if(isset($document->plant_supply) && !empty($document->plant_supply))
                                        {{ $document->plant_supply }}
                                    @elseif(isset($document->sloc_supply) && !empty($document->sloc_supply))
                                        {{ $document->sloc_supply }}
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1">Total Quantity</label>
                                <p class="fw-medium mb-0">{{ number_format($document->total_qty) }}</p>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1">Created By</label>
                                <p class="fw-medium mb-0">
                                    <i class="fas fa-user me-1 text-muted small"></i>
                                    {{ $document->created_by_name ?? $document->user->name ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1">Created Date</label>
                                <p class="fw-medium mb-0">
                                    <i class="fas fa-calendar me-1 text-muted small"></i>
                                    {{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i:s') }}
                                </p>
                            </div>
                            @if($document->remarks)
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1">Remarks</label>
                                <p class="fw-medium mb-0 small">{{ Str::limit($document->remarks, 100) }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Information -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom bg-light py-2 px-3">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-exchange-alt me-2 text-primary"></i>Transfer Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label text-muted small mb-1">Transfer Numbers</label>
                        @php
                            $validTransfers = $document->transfers->filter(function($transfer) {
                                return !empty($transfer->transfer_no);
                            });
                            $transferCount = $validTransfers->count();
                        @endphp

                        @if($transferCount > 0)
                            @if($transferCount <= 2)
                                @foreach($validTransfers as $transfer)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-soft-primary text-primary fw-medium py-1 px-2 small">
                                        <i class="fas fa-truck me-1 small"></i>{{ $transfer->transfer_no }}
                                    </span>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y') }}
                                    </small>
                                </div>
                                @endforeach
                            @else
                            <div class="dropdown">
                                <button class="btn btn-soft-primary btn-sm dropdown-toggle w-100 py-1" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="badge bg-primary me-1">{{ $transferCount }}</span> Transfer(s)
                                </button>
                                <ul class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;">
                                    @foreach($validTransfers as $transfer)
                                    <li>
                                        <a class="dropdown-item small py-1" href="#">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-medium">{{ $transfer->transfer_no }}</span>
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
                            <p class="text-muted mb-0 small"><i class="fas fa-info-circle me-1"></i>No transfers yet</p>
                        @endif
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-muted small mb-1">Total Transferred</label>
                        <p class="fw-medium mb-0 small">
                            {{ number_format($document->total_transferred ?? 0) }} / {{ number_format($document->total_qty) }}
                            ({{ round($document->completion_rate ?? 0, 2) }}%)
                        </p>
                    </div>

                    @if($canGenerateTransfer)
                    <div class="alert alert-info border-0 py-1 small mt-2 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        You have permission to create transfers
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Document Items and Transfer -->
    <div class="row">
        <!-- Items Table -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom bg-light py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list-ul me-2 text-primary"></i>Document Items
                            <span class="badge bg-primary bg-opacity-10 text-primary ms-2 small">{{ $document->items->count() }} items</span>
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <div class="me-2">
                                <span class="badge bg-light border text-dark px-2 py-1 small" id="selectedCount">
                                    0 selected
                                </span>
                            </div>

                            @if(in_array($document->status, ['booked', 'partial']))
                            <form id="checkStockForm" action="{{ route('stock.fetch', $document->document_no) }}"
                                method="POST" class="d-inline">
                                @csrf
                                <div class="input-group input-group-sm" style="width: 180px;">
                                    <input type="text" class="form-control form-control-sm border-end-0"
                                        name="plant" placeholder="Plant"
                                        value="{{ request('plant', $document->plant) }}" required>
                                    <button type="submit" class="btn btn-primary btn-sm px-2">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>

                            @php
                                $hasStockInfo = false;
                                foreach ($document->items as $item) {
                                    if (!empty($item->stock_info) && !empty($item->stock_info['details'])) {
                                        $hasStockInfo = true;
                                        break;
                                    }
                                }
                            @endphp

                            @if($hasStockInfo)
                            <form id="resetStockForm" action="{{ route('stock.clear-cache', $document->document_no) }}"
                                method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-secondary btn-sm px-2" title="Reset Stock Data">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </form>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-hover table-borderless mb-0">
                            <thead class="table-light border-bottom">
                                <tr>
                                    <th class="border-0 py-2 px-2" style="width: 40px;">
                                        <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                    </th>
                                    <th class="border-0 text-muted py-2 px-2" style="width: 40px;">#</th>
                                    <th class="border-0 text-muted py-2 px-2">Material</th>
                                    <th class="border-0 text-muted py-2 px-2">Description</th>
                                    <th class="border-0 text-muted py-2 px-2 text-center">Req Qty</th>
                                    <th class="border-0 text-muted py-2 px-2 text-center">Remaining</th>
                                    <th class="border-0 text-muted py-2 px-2 text-center">Unit</th>
                                    <th class="border-0 text-muted py-2 px-2 text-center">Stock</th>
                                    <th class="border-0 text-muted py-2 px-2 text-center">Status</th>
                                    <th class="border-0 text-muted py-2 px-2 text-center">Batch Source</th>
                                    <th class="border-0 text-muted py-2 px-2 text-center">SO</th>
                                    <th class="border-0 text-muted py-2 px-2 text-center">Source PRO</th>
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

                                    // Gunakan data yang sudah dihitung di controller
                                    $requestedQty = $item->requested_qty;
                                    $transferredQty = $item->transferred_qty ?? 0;
                                    $remainingQty = $item->remaining_qty ?? 0;

                                    // Stock information
                                    $stockInfo = $item->stock_info ?? null;
                                    $totalStock = $stockInfo['total_stock'] ?? 0;
                                    $stockDetails = $stockInfo['details'] ?? [];

                                    // Batch info
                                    $batchInfo = [];
                                    if (!empty($stockDetails) && is_array($stockDetails)) {
                                        foreach ($stockDetails as $detail) {
                                            if (is_array($detail)) {
                                                $batchInfo[] = [
                                                    'batch' => $detail['charg'] ?? ($detail['batch'] ?? ''),
                                                    'sloc' => $detail['lgort'] ?? ($detail['sloc'] ?? ''),
                                                    'qty' => isset($detail['clabs']) ? (is_numeric($detail['clabs']) ? floatval($detail['clabs']) : 0) : 0,
                                                    'clabs' => isset($detail['clabs']) ? (is_numeric($detail['clabs']) ? floatval($detail['clabs']) : 0) : 0
                                                ];
                                            }
                                        }
                                    }

                                    // Check if stock is available
                                    $hasStock = $totalStock > 0;
                                    $transferableQty = min($remainingQty, $totalStock);

                                    // PERBAIKAN: Cek force completed
                                    $isForceCompleted = $item->force_completed ?? false;

                                    // PERBAIKAN: Check if item is transferable
                                    $isTransferable = !$isForceCompleted && $remainingQty > 0 && $hasStock && $transferableQty > 0;

                                    // MRP Comp (dispc)
                                    $mrpComp = $item->dispc ?? ($item->mrp_comp ?? ($item->dispo ?? '-'));
                                @endphp

                                <tr class="item-row draggable-row"
                                    draggable="{{ $isTransferable ? 'true' : 'false' }}"
                                    data-item-id="{{ $item->id }}"
                                    data-material-code="{{ $item->material_code }}"
                                    data-material-description="{{ $item->material_description }}"
                                    data-requested-qty="{{ $requestedQty }}"
                                    data-transferred-qty="{{ $transferredQty }}"
                                    data-remaining-qty="{{ $remainingQty }}"
                                    data-available-stock="{{ $totalStock }}"
                                    data-transferable-qty="{{ $isTransferable ? min($remainingQty, $totalStock) : 0 }}"
                                    data-unit="{{ $unit }}"
                                    data-sloc="{{ !empty($batchInfo) ? ($batchInfo[0]['sloc'] ?? '') : '' }}"
                                    data-can-transfer="{{ $isTransferable ? 'true' : 'false' }}"
                                    data-force-completed="{{ $isForceCompleted ? 'true' : 'false' }}"
                                    data-batch-info="{{ htmlspecialchars(json_encode($batchInfo), ENT_QUOTES, 'UTF-8') }}"
                                    style="cursor: {{ $isTransferable ? 'move' : 'default' }}; background-color: {{ $isForceCompleted ? 'rgba(40, 167, 69, 0.05)' : 'transparent' }};">

                                    <td class="align-middle py-2 px-2">
                                        <input type="checkbox" class="form-check-input row-select"
                                            data-item-id="{{ $item->id }}"
                                            {{ $isTransferable ? '' : 'disabled' }}>
                                    </td>

                                    <td class="align-middle text-muted py-2 px-2">
                                        <span class="fw-medium small">{{ $index + 1 }}</span>
                                    </td>

                                    <td class="align-middle py-2 px-2">
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold text-dark small">{{ $materialCode }}</span>
                                            <small class="text-muted">{{ $mrpComp }}</small>
                                        </div>
                                    </td>

                                    <td class="align-middle py-2 px-2">
                                        <div class="text-truncate" style="max-width: 180px;"
                                            title="{{ $item->material_description }}">
                                            <span class="small">{{ $item->material_description }}</span>
                                        </div>
                                    </td>

                                    <td class="align-middle text-center py-2 px-2">
                                        <span class="fw-medium small">{{ \App\Helpers\NumberHelper::formatQuantity($requestedQty) }}</span>
                                    </td>

                                    <td class="align-middle text-center py-2 px-2">
                                        <span class="fw-bold small {{ $remainingQty > 0 ? 'text-primary' : 'text-success' }}">
                                            {{ \App\Helpers\NumberHelper::formatQuantity($remainingQty) }}
                                        </span>
                                    </td>

                                    <td class="align-middle text-center py-2 px-2">
                                        <span class="text-muted small">{{ $unit }}</span>
                                    </td>

                                    <td class="align-middle text-center py-2 px-2">
                                        @if($totalStock > 0)
                                            <span class="badge bg-{{ $remainingQty > 0 ? 'success' : 'warning' }} bg-opacity-10
                                                text-{{ $remainingQty > 0 ? 'success' : 'warning' }} px-2 py-1 small">
                                                {{ \App\Helpers\NumberHelper::formatStockNumber($totalStock) }}
                                            </span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 small">
                                                No Stock
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Kolom Status -->
                                    <td class="align-middle text-center py-2 px-2">
                                        @php
                                            // Gunakan status yang sudah dihitung di controller
                                            $hasTransferHistory = $item->has_transfer_history ?? false;
                                        @endphp
                                        <button class="badge {{ $item->transfer_badge_class ?? 'bg-secondary' }} px-2 py-1 small view-transfer-details"
                                                data-item-id="{{ $item->id }}"
                                                data-material-code="{{ $item->material_code }}"
                                                data-material-description="{{ $item->material_description }}"
                                                data-has-history="{{ $hasTransferHistory ? 'true' : 'false' }}"
                                                style="border: none; cursor: {{ $hasTransferHistory ? 'pointer' : 'default' }};"
                                                title="{{ $hasTransferHistory ? 'Click to view transfer details' : 'No transfer history' }}"
                                                {{ $hasTransferHistory ? '' : 'disabled' }}>
                                            <i class="fas {{ $item->transfer_icon ?? 'fa-clock' }} me-1"></i>{{ $item->transfer_label ?? 'Pending' }}
                                        </button>
                                    </td>

                                    <!-- Kolom Batch Source -->
                                    <td class="align-middle text-center py-2 px-2">
                                        @if(!empty($batchInfo))
                                            @if(count($batchInfo) > 1)
                                                <select class="form-control form-control-sm batch-dropdown small"
                                                        style="min-width: 150px; font-size: 0.85rem; padding: 2px 5px;"
                                                        title="Batch Source">
                                                    @foreach($batchInfo as $batch)
                                                        <option value="{{ $batch['batch'] }}"
                                                                data-sloc="{{ $batch['sloc'] }}"
                                                                data-qty="{{ $batch['qty'] }}">
                                                            {{ $batch['sloc'] }} | {{ $batch['batch'] }} | {{ \App\Helpers\NumberHelper::formatStockNumber($batch['qty']) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @elseif(count($batchInfo) == 1)
                                                <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 small">
                                                    {{ $batchInfo[0]['batch'] ?? 'N/A' }}
                                                </span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    <!-- Kolom SO -->
                                    <td class="align-middle text-center py-2 px-2">
                                        @if(!empty($salesOrders))
                                            @if(is_array($salesOrders))
                                                @foreach(array_slice($salesOrders, 0, 2) as $so)
                                                    <span class="badge bg-info bg-opacity-10 text-info px-1 py-0 mb-1 small d-block">
                                                        {{ $so }}
                                                    </span>
                                                @endforeach
                                                @if(count($salesOrders) > 2)
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-1 py-0 small">
                                                        +{{ count($salesOrders) - 2 }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-info bg-opacity-10 text-info px-1 py-0 small">
                                                    {{ $salesOrders }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    <!-- Kolom Source PRO -->
                                    <td class="align-middle text-center py-2 px-2">
                                        @if(!empty($sources))
                                            @if(is_array($sources))
                                                @foreach(array_slice($sources, 0, 2) as $source)
                                                    <span class="badge bg-warning bg-opacity-10 text-warning px-1 py-0 mb-1 small d-block">
                                                        {{ $source }}
                                                    </span>
                                                @endforeach
                                                @if(count($sources) > 2)
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-1 py-0 small">
                                                        +{{ count($sources) - 2 }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-warning bg-opacity-10 text-warning px-1 py-0 small">
                                                    {{ $sources }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-top py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="selectAllHeader">
                            <label class="form-check-label text-muted small" for="selectAllHeader">
                                Select All Transferable Items
                            </label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2" id="clearSelection">
                                <i class="fas fa-times me-1"></i> Clear
                            </button>
                            @if($hasTransferableItems && $canGenerateTransfer)
                            <button type="button" class="btn btn-primary btn-sm px-2" id="addSelectedToTransfer">
                                <i class="fas fa-arrow-right me-1"></i> Add Selected
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer List -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom bg-light py-2 px-3">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-truck-loading me-2 text-primary"></i>Transfer List
                        <span class="badge bg-primary ms-2 small" id="transferCount">0</span>
                    </h6>
                    <p class="text-muted small mb-0 mt-1">
                        <i class="fas fa-info-circle me-1"></i>Drag items or select from list
                    </p>
                </div>
                <div class="card-body p-0">
                    <div class="transfer-container" id="transferContainer"
                         style="min-height: 400px; max-height: 500px; overflow-y: auto;">
                        <div id="transferSlots" class="p-2">
                            <div class="empty-state text-center text-muted py-4">
                                <div class="mb-2">
                                    <i class="fas fa-arrow-left fa-2x opacity-25"></i>
                                </div>
                                <h6 class="mb-1 small">No items added</h6>
                                <p class="small mb-0">Drag items here or select from list</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light border-top py-2 px-3">
                    <div class="d-grid gap-2">
                        @if($hasTransferableItems && $canGenerateTransfer)
                        <button type="button" class="btn btn-primary btn-sm" id="generateTransferList">
                            <i class="fas fa-file-export me-1"></i> Generate Transfer
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearTransferList">
                            <i class="fas fa-trash me-1"></i> Clear All
                        </button>
                        @else
                        <button type="button" class="btn btn-secondary btn-sm" disabled>
                            <i class="fas fa-ban me-1"></i>
                            @if(!$hasTransferableItems)
                                No Transferable Items
                            @elseif(!$canGenerateTransfer)
                                No Permission
                            @else
                                Transfer Not Available
                            @endif
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
@include('documents.partials.transfer-details-modal')
@include('documents.partials.transfer-preview-modal')
@include('documents.partials.sap-credentials-modal')

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="d-flex flex-column align-items-center">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
        <h5 class="text-dark">Processing...</h5>
    </div>
</div>

<style>
:root {
    --primary-color: #4a6cf7;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #17a2b8;
    --dark-color: #343a40;
    --light-color: #f8f9fa;
}

/* Base font size - Increased */
body {
    font-size: 0.95rem;
}

/* Modern Card Design */
.card {
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.08);
    background: white;
}

.card-animate {
    transition: all 0.3s ease;
    cursor: pointer;
}

.card-animate:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.08) !important;
}

.card-header {
    background: rgba(0,0,0,0.02);
    border-bottom: 1px solid rgba(0,0,0,0.08);
    border-radius: 8px 8px 0 0 !important;
}

.card-title {
    color: var(--dark-color);
    font-weight: 600;
    font-size: 0.98rem;
}

/* Table Design - Increased font size */
.table {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(0,0,0,0.02);
    --bs-table-hover-bg: rgba(var(--primary-color-rgb), 0.04);
    margin-bottom: 0;
    font-size: 0.9rem;
}

.table thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.78rem;
    letter-spacing: 0.5px;
    color: var(--secondary-color);
    border-bottom: 2px solid rgba(0,0,0,0.08);
    background: rgba(0,0,0,0.02);
    white-space: nowrap;
}

.table tbody td {
    padding: 0.6rem 0.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    vertical-align: middle;
    color: #333;
}

.table tbody tr:hover {
    background: rgba(var(--primary-color-rgb), 0.02);
}

/* Transfer Container - Smaller width */
.transfer-container {
    background: rgba(0,0,0,0.02);
    border-radius: 6px;
    font-size: 0.9rem;
}

.transfer-item {
    background: white;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 6px;
    transition: all 0.2s ease;
    border-left: 3px solid var(--primary-color);
    font-size: 0.88rem;
}

.transfer-item:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
    border-color: var(--primary-color);
}

.transfer-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.transfer-item-code {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 0.9rem;
}

.transfer-item-remove {
    color: var(--danger-color);
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
    font-size: 0.9rem;
}

.transfer-item-remove:hover {
    opacity: 1;
}

/* Badge Design */
.badge {
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
}

.bg-soft-primary {
    background-color: rgba(74, 108, 247, 0.1) !important;
    color: var(--primary-color) !important;
}

.bg-soft-success {
    background-color: rgba(40, 167, 69, 0.1) !important;
    color: var(--success-color) !important;
}

.bg-soft-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
    color: var(--warning-color) !important;
}

.bg-soft-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
    color: var(--danger-color) !important;
}

.bg-soft-info {
    background-color: rgba(23, 162, 184, 0.1) !important;
    color: var(--info-color) !important;
}

/* Button Design */
.btn {
    border-radius: 6px;
    font-weight: 500;
    padding: 6px 12px;
    font-size: 0.88rem;
    transition: all 0.3s ease;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.85rem;
}

.btn-primary {
    background: linear-gradient(135deg, #4a6cf7 0%, #3a56d7 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #3a56d7 0%, #2a46b7 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(74, 108, 247, 0.3);
}

.btn-outline-secondary {
    border-color: #dee2e6;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

/* Progress Bar */
.progress {
    border-radius: 8px;
    height: 6px;
    background: rgba(0,0,0,0.05);
}

.progress-bar {
    border-radius: 8px;
}

/* Avatar */
.avatar-sm {
    width: 40px;
    height: 40px;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: 8px;
}

/* Drag and Drop */
.draggable-row.dragging {
    opacity: 0.5;
    background: rgba(var(--primary-color-rgb), 0.05);
    border: 2px dashed var(--primary-color);
}

.drag-over {
    background: rgba(var(--primary-color-rgb), 0.05) !important;
    border: 2px dashed var(--primary-color) !important;
    border-radius: 8px;
}

/* Loading Overlay */
.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(3px);
}

.loading-overlay.show {
    display: flex;
}

/* Toast Design */
.toast {
    border-radius: 8px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    font-size: 0.85rem;
}

.toast-body {
    padding: 0.75rem 1rem;
}

/* Form Controls */
.form-control, .form-select {
    border-radius: 6px;
    border: 1px solid rgba(0,0,0,0.1);
    padding: 7px 12px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(74, 108, 247, 0.25);
}

/* Custom Stock Colors */
.stock-custom-unavailable {
    color: #999999 !important;
    font-weight: 500;
}

.stock-custom-low {
    color: #ff9800 !important;
    font-weight: 500;
}

.stock-custom-available {
    color: #4caf50 !important;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        font-size: 0.9rem;
    }

    .card-body {
        padding: 0.75rem;
    }

    .table-responsive {
        font-size: 0.85rem;
    }

    .btn {
        padding: 4px 8px;
        font-size: 0.85rem;
    }

    .card-header {
        padding: 0.5rem 0.75rem;
    }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.05);
    border-radius: 8px;
}

::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 8px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(0,0,0,0.2);
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease;
}

/* Status Colors */
.text-primary { color: var(--primary-color) !important; }
.text-success { color: var(--success-color) !important; }
.text-warning { color: var(--warning-color) !important; }
.text-danger { color: var(--danger-color) !important; }
.text-info { color: var(--info-color) !important; }

/* Fix for table visibility */
.table tbody tr td {
    background-color: transparent !important;
    color: #333 !important;
}

.table tbody tr:hover td {
    background-color: rgba(74, 108, 247, 0.04) !important;
}

/* Transfer item selected state */
.transfer-item-selected {
    background-color: rgba(74, 108, 247, 0.05) !important;
}

.row-selected {
    background-color: rgba(74, 108, 247, 0.08) !important;
    border-left: 3px solid var(--primary-color);
}

/* Force completed row style */
.force-completed-row {
    background-color: rgba(40, 167, 69, 0.05) !important;
}

/* Modal fixes */
.modal-content {
    border-radius: 8px;
    border: none;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

/* Small text utility */
.small {
    font-size: 0.88rem !important;
}

/* Checkbox alignment */
.form-check-input {
    width: 16px;
    height: 16px;
    margin-top: 0.1rem;
}

/* Page title adjustments */
.page-title {
    font-size: 1.3rem;
}

/* Badge in button */
.btn .badge {
    font-size: 0.65rem;
}

/* Empty state adjustments */
.empty-state .fa-2x {
    font-size: 1.75rem;
}

.empty-state h6 {
    font-size: 0.95rem;
}

/* Specific for transfer list column */
.col-lg-3 .card {
    width: 100%;
}

.col-lg-3 .transfer-container {
    min-height: 400px;
    max-height: 500px;
}

/* Ensure proper text size in all elements */
.card-body, .modal-body, .form-label, .input-group-text {
    font-size: 0.9rem;
}

/* Batch dropdown styling */
.batch-dropdown {
    font-size: 0.8rem !important;
    padding: 2px 5px !important;
    height: 24px !important;
    min-width: 150px !important;
    max-width: 200px !important;
}

.batch-dropdown option {
    font-size: 0.8rem !important;
    padding: 4px 8px !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    let transferItems = [];
    let selectedItems = new Set();
    let isProcessingTransfer = false;

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

    // Show/hide loading overlay
    function showLoading() {
        document.getElementById('loadingOverlay').classList.add('show');
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('show');
    }

    // Toast notification
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast-' + Date.now();

        const bgClass = {
            'success': 'bg-success',
            'error': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-primary'
        }[type] || 'bg-primary';

        const iconClass = {
            'success': 'fa-check-circle',
            'error': 'fa-times-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        }[type] || 'fa-info-circle';

        const toastHTML = `
            <div id="${toastId}" class="toast fade-in ${bgClass} text-white border-0" role="alert">
                <div class="toast-body d-flex align-items-center">
                    <i class="fas ${iconClass} me-3 fs-5"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);

        const toast = new bootstrap.Toast(toastElement, {
            delay: 4000,
            animation: true,
            autohide: true
        });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    // Setup event listener untuk view transfer details
    function setupTransferDetailsListeners() {
        console.log('Setting up transfer details listeners...');

        // Debug: Tampilkan semua tombol transfer details
        const transferButtons = document.querySelectorAll('.view-transfer-details');
        console.log(`Found ${transferButtons.length} transfer detail buttons`);

        transferButtons.forEach((btn, index) => {
            console.log(`Button ${index + 1}:`, {
                itemId: btn.getAttribute('data-item-id'),
                materialCode: btn.getAttribute('data-material-code'),
                hasHistory: btn.getAttribute('data-has-history'),
                disabled: btn.disabled
            });
        });

        // Event delegation
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.view-transfer-details');

            if (!target) return;

            // Cek apakah tombol tidak disabled
            if (target.disabled) {
                console.log('Transfer details button is disabled');
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const itemId = target.getAttribute('data-item-id');
            const materialCode = target.getAttribute('data-material-code');
            const materialDescription = target.getAttribute('data-material-description');
            const hasHistory = target.getAttribute('data-has-history') === 'true';

            console.log('Fetching transfer history for:', {
                itemId,
                materialCode,
                materialDescription,
                hasHistory
            });

            // Jika tidak ada history, tidak perlu fetch
            if (!hasHistory) {
                console.log('No transfer history flag set');
                showToast('No transfer history available for this item', 'info');
                return;
            }

            showLoading();

            // Gunakan route yang benar
            const url = `/documents/{{ $document->id }}/items/${encodeURIComponent(materialCode)}/transfer-history`;

            console.log('Fetching URL:', url);

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    console.error('Response not OK:', response.status);
                    return response.text().then(text => {
                        console.error('Response body:', text);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Transfer history data received:', data);
                hideLoading();

                if (data.error) {
                    console.error('Error in response:', data.error);
                    showToast(data.error, 'error');
                    return;
                }

                if (!Array.isArray(data) || data.length === 0) {
                    console.warn('No transfer history array or empty');
                    showToast('No transfer history found for this item', 'info');
                    return;
                }

                showTransferDetailsModal(materialCode, materialDescription, data);
            })
            .catch(error => {
                console.error('Error loading transfer details:', error);
                hideLoading();
                showToast('Error loading transfer details. Please check console for details.', 'error');
            });
        });
    }

    function showTransferDetailsModal(materialCode, materialDescription, transferData) {
        console.log('Showing transfer details modal');

        const modalElement = document.getElementById('transferDetailsModal');
        if (!modalElement) {
            console.error('Transfer details modal not found');
            showToast('Transfer details modal not found', 'error');
            return;
        }

        // Initialize Bootstrap modal jika belum
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) {
            modal = new bootstrap.Modal(modalElement);
        }

        // Update modal content
        const modalTitle = document.getElementById('transferDetailsModalLabel');
        if (modalTitle) {
            modalTitle.innerHTML = `<i class="fas fa-list-alt me-2 text-primary"></i>Transfer Details - ${materialCode}`;
        }

        const materialDesc = document.getElementById('detailMaterialDescription');
        if (materialDesc) {
            materialDesc.textContent = materialDescription;
        }

        const tbody = document.querySelector('#transferDetailsTable tbody');
        if (tbody) {
            tbody.innerHTML = '';

            if (transferData && Array.isArray(transferData) && transferData.length > 0) {
                transferData.forEach((transfer, index) => {
                    const row = tbody.insertRow();

                    // Format tanggal dengan fallback
                    let formattedDate = '-';
                    if (transfer.created_at) {
                        // Coba parse jika bukan string kosong
                        if (transfer.created_at.trim() !== '' &&
                            transfer.created_at !== 'Tanggal tidak tersedia' &&
                            transfer.created_at !== 'Format tidak valid') {

                            // Jika sudah dalam format yang diinginkan (d/m/Y H:i:s)
                            if (transfer.created_at.match(/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}/)) {
                                formattedDate = transfer.created_at;
                            } else {
                                // Coba parse dengan berbagai format
                                try {
                                    const dateObj = new Date(transfer.created_at);
                                    if (!isNaN(dateObj.getTime())) {
                                        formattedDate = dateObj.toLocaleDateString('id-ID', {
                                            day: '2-digit',
                                            month: '2-digit',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            second: '2-digit'
                                        });
                                    }
                                } catch (e) {
                                    console.warn('Date parsing failed:', e);
                                }
                            }
                        }
                    }

                    row.innerHTML = `
                        <td class="text-center align-middle">${index + 1}</td>
                        <td class="align-middle">
                            <span class="badge bg-soft-primary text-primary small">${transfer.transfer_no || '-'}</span>
                        </td>
                        <td class="align-middle small">${transfer.material_code || materialCode}</td>
                        <td class="align-middle small">${transfer.batch || '-'}</td>
                        <td class="text-center align-middle fw-medium small">${formatAngka(transfer.quantity || 0)}</td>
                        <td class="text-center align-middle small">${transfer.unit || 'PC'}</td>
                        <td class="text-center align-middle small">${formattedDate}</td>
                    `;
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3 small">
                            <i class="fas fa-info-circle me-2"></i>No transfer history found
                        </td>
                    </tr>
                `;
            }
        }

        // Show modal
        modal.show();
    }

    // Setup drag and drop
    function setupDragAndDrop() {
        const rows = document.querySelectorAll('.draggable-row');
        const dropZone = document.getElementById('transferContainer');

        rows.forEach(function(row) {
            const canTransfer = row.dataset.canTransfer === 'true';
            const isForceCompleted = row.dataset.forceCompleted === 'true';

            // Force completed items tidak bisa didrag
            if (canTransfer && !isForceCompleted) {
                row.draggable = true;

                row.addEventListener('dragstart', function(e) {
                    this.classList.add('dragging');
                    e.dataTransfer.setData('text/plain', this.dataset.itemId);
                    e.dataTransfer.effectAllowed = 'copy';

                    // Add visual feedback
                    setTimeout(() => {
                        this.style.opacity = '0.4';
                    }, 0);
                });

                row.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                    this.style.opacity = '1';
                });

                row.addEventListener('dragenter', function(e) {
                    e.preventDefault();
                    if (canTransfer) {
                        this.classList.add('drag-over');
                    }
                });

                row.addEventListener('dragleave', function() {
                    this.classList.remove('drag-over');
                });

                row.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (canTransfer) {
                        e.dataTransfer.dropEffect = 'copy';
                    }
                });
            } else {
                row.draggable = false;
            }
        });

        const canGenerateTransfer = @json($canGenerateTransfer ?? false);
        const hasTransferableItems = @json($hasTransferableItems ?? false);

        if (canGenerateTransfer && hasTransferableItems && dropZone) {
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                this.classList.add('drag-over');
            });

            dropZone.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('drag-over');

                const itemId = e.dataTransfer.getData('text/plain');
                if (itemId) {
                    addItemById(itemId);
                }

                // Remove dragging class from all rows
                document.querySelectorAll('.draggable-row').forEach(row => {
                    row.classList.remove('dragging');
                    row.classList.remove('drag-over');
                    row.style.opacity = '1';
                });
            });
        }
    }

    // Setup checkbox selection
    function setupCheckboxSelection() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const selectAllHeader = document.getElementById('selectAllHeader');

        function handleSelectAll(isChecked) {
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
            updateRowSelectionStyles();
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function(e) {
                handleSelectAll(e.target.checked);
            });
        }

        if (selectAllHeader) {
            selectAllHeader.addEventListener('change', function(e) {
                handleSelectAll(e.target.checked);
            });
        }

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
                updateRowSelectionStyles();
            });

            // Initialize selection state
            if (checkbox.checked) {
                selectedItems.add(checkbox.dataset.itemId);
            }
        });

        const clearSelectionBtn = document.getElementById('clearSelection');
        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function() {
                if (selectedItems.size > 0) {
                    selectedItems.clear();
                    document.querySelectorAll('.row-select').forEach(cb => cb.checked = false);
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    if (selectAllHeader) selectAllHeader.checked = false;
                    updateSelectionCount();
                    updateRowSelectionStyles();
                    showToast('Selection cleared', 'info');
                }
            });
        }

        const addSelectedToTransferBtn = document.getElementById('addSelectedToTransfer');
        if (addSelectedToTransferBtn) {
            addSelectedToTransferBtn.addEventListener('click', addSelectedItemsToTransfer);
        }
    }

    // Update row selection styles
    function updateRowSelectionStyles() {
        document.querySelectorAll('.draggable-row').forEach(function(row) {
            const itemId = row.dataset.itemId;
            if (selectedItems.has(itemId)) {
                row.classList.add('row-selected');
            } else {
                row.classList.remove('row-selected');
            }
        });
    }

    // Update selection count
    function updateSelectionCount() {
        const count = selectedItems.size;
        document.getElementById('selectedCount').textContent = count + ' selected';

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

    // Get item data from row
    function getItemDataFromRow(rowElement) {
        const row = rowElement;
        const isForceCompleted = row.dataset.forceCompleted === 'true';

        // Jika force completed, return null
        if (isForceCompleted) {
            return null;
        }

        const batchInfoData = row.dataset.batchInfo;

        let batchInfo = [];
        if (batchInfoData) {
            try {
                const cleanedData = batchInfoData.replace(/&quot;/g, '"');
                batchInfo = JSON.parse(cleanedData);

                if (Array.isArray(batchInfo)) {
                    batchInfo = batchInfo.map(batch => {
                        return {
                            batch: batch.batch || batch.charg || '',
                            sloc: batch.sloc || batch.lgort || '',
                            qty: batch.qty || batch.clabs || 0,
                            clabs: batch.clabs || batch.qty || 0
                        };
                    }).filter(batch => batch.batch || batch.sloc);
                }
            } catch (e) {
                console.warn('Error parsing batch info:', e.message);
                batchInfo = [];
            }
        }

        return {
            id: row.dataset.itemId,
            materialCode: row.dataset.materialCode,
            materialDesc: row.dataset.materialDescription,
            requestedQty: parseFloat(row.dataset.requestedQty || 0),
            transferredQty: parseFloat(row.dataset.transferredQty || 0),
            remainingQty: parseFloat(row.dataset.remainingQty || 0),
            availableStock: parseFloat(row.dataset.availableStock || 0),
            unit: row.dataset.unit || 'PC',
            sloc: row.dataset.sloc || '',
            batchInfo: batchInfo,
            canTransfer: row.dataset.canTransfer === 'true'
        };
    }

    // Add selected items to transfer
    function addSelectedItemsToTransfer() {
        const canGenerateTransfer = @json($canGenerateTransfer ?? false);
        if (!canGenerateTransfer) {
            showToast('You do not have permission to create transfers', 'error');
            return;
        }

        if (selectedItems.size === 0) {
            showToast('No items selected', 'warning');
            return;
        }

        let addedCount = 0;
        let alreadyAddedCount = 0;
        let forceCompletedCount = 0;

        selectedItems.forEach(function(itemId) {
            const itemRow = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
            if (itemRow) {
                const isForceCompleted = itemRow.dataset.forceCompleted === 'true';

                // Skip jika force completed
                if (isForceCompleted) {
                    forceCompletedCount++;
                    return;
                }

                const itemData = getItemDataFromRow(itemRow);

                if (!itemData) return;

                if (!transferItems.some(item => item.id === itemData.id)) {
                    if (itemData.remainingQty > 0 && itemData.availableStock > 0) {
                        addItemToTransferByData(itemData);
                        addedCount++;
                    }
                } else {
                    alreadyAddedCount++;
                }
            }
        });

        if (addedCount > 0) {
            showToast(addedCount + ' items added to transfer list', 'success');
        }

        if (alreadyAddedCount > 0) {
            showToast(alreadyAddedCount + ' items already in transfer list', 'info');
        }

        if (forceCompletedCount > 0) {
            showToast(forceCompletedCount + ' force completed items skipped', 'warning');
        }

        if (addedCount === 0 && alreadyAddedCount === 0 && forceCompletedCount === 0) {
            showToast('No transferable items selected', 'warning');
        }
    }

    // Add item by ID
    function addItemById(itemId) {
        const canGenerateTransfer = @json($canGenerateTransfer ?? false);
        if (!canGenerateTransfer) {
            showToast('You do not have permission to create transfers', 'error');
            return;
        }

        const itemRow = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
        if (!itemRow) {
            showToast('Item not found', 'error');
            return;
        }

        const isForceCompleted = itemRow.dataset.forceCompleted === 'true';

        // Skip jika force completed
        if (isForceCompleted) {
            showToast('Cannot add force completed item to transfer list', 'error');
            return;
        }

        const itemData = getItemDataFromRow(itemRow);

        if (!itemData) {
            showToast('Item data not found', 'error');
            return;
        }

        if (transferItems.some(item => item.id === itemData.id)) {
            showToast('Item already in transfer list', 'warning');
            return;
        }

        if (itemData.remainingQty <= 0) {
            showToast('Item already fully transferred', 'error');
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
            const validBatch = item.batchInfo.find(batch => (batch.qty || 0) > 0);
            if (validBatch) {
                selectedBatch = validBatch.batch || validBatch.sloc || '';
                batchQty = validBatch.qty || validBatch.clabs || 0;
                batchSloc = validBatch.sloc || selectedBatch;
            } else if (item.batchInfo[0]) {
                selectedBatch = item.batchInfo[0].batch || item.batchInfo[0].sloc || '';
                batchQty = item.batchInfo[0].qty || item.batchInfo[0].clabs || 0;
                batchSloc = item.batchInfo[0].sloc || selectedBatch;
            }
        } else if (item.sloc) {
            const slocs = item.sloc.split(',');
            if (slocs.length > 0) {
                selectedBatch = slocs[0].trim();
                batchQty = item.availableStock;
                batchSloc = selectedBatch;
            }
        }

        // Calculate transferable quantity
        const maxTransferable = Math.min(item.remainingQty, batchQty > 0 ? batchQty : item.availableStock);

        // Add to transfer items array
        const transferItem = {
            id: item.id,
            materialCode: item.materialCode,
            materialDesc: item.materialDesc,
            maxQty: maxTransferable,
            remainingQty: item.remainingQty,
            availableStock: item.availableStock,
            batchQty: batchQty,
            qty: maxTransferable,
            quantity: maxTransferable,
            unit: item.unit,
            sloc: item.sloc,
            batchInfo: item.batchInfo,
            selectedBatch: selectedBatch,
            batchQty: batchQty,
            batchSloc: batchSloc,
            plantTujuan: defaultPlant,
            plant_dest: defaultPlant,
            slocTujuan: '',
            sloc_dest: ''
        };

        transferItems.push(transferItem);

        // Render transfer item
        renderTransferItem(transferItem);

        // Update transfer count
        updateTransferCount();

        // Mark row as selected in transfer
        const row = document.querySelector('.draggable-row[data-item-id="' + item.id + '"]');
        if (row) {
            row.classList.add('transfer-item-selected');
        }

        showToast('Item added to transfer list', 'success');
    }

    // Render transfer item
    function renderTransferItem(item) {
        const transferSlots = document.getElementById('transferSlots');

        const emptyState = transferSlots.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }

        const itemDiv = document.createElement('div');
        itemDiv.className = 'transfer-item fade-in';
        itemDiv.dataset.itemId = item.id;

        itemDiv.innerHTML = `
            <div class="transfer-item-header">
                <div>
                    <span class="transfer-item-code">${item.materialCode}</span>
                </div>
                <span class="transfer-item-remove" title="Remove from transfer list">
                    <i class="fas fa-times"></i>
                </span>
            </div>
            <div class="transfer-item-desc small text-muted mb-2">
                ${item.materialDesc.length > 40 ? item.materialDesc.substring(0, 40) + '...' : item.materialDesc}
            </div>
            <div class="transfer-item-qty d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-soft-primary text-primary me-1 small">
                        ${formatAngka(item.remainingQty)} remaining
                    </span>
                    <span class="badge bg-soft-success text-success small">
                        Max: ${formatAngka(item.maxQty)} ${item.unit}
                    </span>
                </div>
            </div>
        `;

        itemDiv.querySelector('.transfer-item-remove').addEventListener('click', function() {
            removeTransferItem(item.id);
        });

        transferSlots.appendChild(itemDiv);
    }

    // Remove transfer item
    function removeTransferItem(itemId) {
        transferItems = transferItems.filter(item => item.id !== itemId);
        const itemElement = document.querySelector('.transfer-item[data-item-id="' + itemId + '"]');
        if (itemElement) itemElement.remove();

        const row = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
        if (row) row.classList.remove('transfer-item-selected');

        updateTransferCount();
        if (transferItems.length === 0) showEmptyState();
        showToast('Item removed from transfer list', 'info');
    }

    // Update transfer count
    function updateTransferCount() {
        document.getElementById('transferCount').textContent = transferItems.length;
    }

    // Show empty state
    function showEmptyState() {
        const transferSlots = document.getElementById('transferSlots');
        if (transferSlots.children.length === 0) {
            transferSlots.innerHTML = `
                <div class="empty-state text-center text-muted py-4">
                    <div class="mb-2">
                        <i class="fas fa-arrow-left fa-2x opacity-25"></i>
                    </div>
                    <h6 class="mb-1 small">No items added</h6>
                    <p class="small mb-0">Drag items here or select from list</p>
                </div>
            `;
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

            if (confirm('Are you sure you want to clear all items from the transfer list?')) {
                transferItems = [];
                const transferSlots = document.getElementById('transferSlots');
                if (transferSlots) transferSlots.innerHTML = '';

                document.querySelectorAll('.draggable-row').forEach(row =>
                    row.classList.remove('transfer-item-selected'));

                updateTransferCount();
                showEmptyState();
                showToast('Transfer list cleared', 'info');
            }
        });
    }

    // Generate transfer list
    const generateTransferListBtn = document.getElementById('generateTransferList');
    if (generateTransferListBtn) {
        generateTransferListBtn.addEventListener('click', function() {
            const canGenerateTransfer = @json($canGenerateTransfer ?? false);
            if (!canGenerateTransfer) {
                showToast('You do not have permission to create transfers', 'error');
                return;
            }

            if (transferItems.length === 0) {
                showToast('Please add items to transfer list first', 'warning');
                return;
            }

            try {
                populateTransferPreviewModal();
                const modalElement = document.getElementById('transferPreviewModal');
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
                console.error(error);
            }
        });
    }

    // Populate transfer preview modal
    function populateTransferPreviewModal() {
        const tbody = document.querySelector('#transferPreviewTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        transferItems.forEach(function(item, index) {
            const batchOptions = createBatchOptions(item.batchInfo, item.selectedBatch);

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center align-middle small">${index + 1}</td>
                <td class="align-middle"><div class="fw-medium small">${item.materialCode}</div></td>
                <td class="align-middle"><div class="text-truncate-2 small" title="${item.materialDesc}">${item.materialDesc.length > 30 ? item.materialDesc.substring(0, 30) + '...' : item.materialDesc}</div></td>
                <td class="text-center align-middle"><div class="fw-medium small">${formatAngka(item.remainingQty)}</div></td>
                <td class="text-center align-middle"><div class="${item.batchQty > 0 ? 'stock-custom-available' : 'stock-custom-unavailable'} fw-medium small" id="batchQtyDisplay-${index}">${formatAngka(item.batchQty || 0)}</div></td>
                <td class="text-center align-middle">
                    <input type="text" class="form-control form-control-sm qty-transfer-input uppercase-input" value="${formatAngka(item.qty || 0)}" placeholder="0" data-index="${index}" required style="width: 70px;">
                </td>
                <td class="text-center align-middle small">${item.unit}</td>
                <td class="text-center align-middle">
                    <input type="text" class="form-control form-control-sm plant-tujuan-input uppercase-input" value="${item.plantTujuan || '{{ $document->plant }}'}" data-index="${index}" required style="width: 60px;">
                </td>
                <td class="text-center align-middle">
                    <input type="text" class="form-control form-control-sm sloc-tujuan-input uppercase-input" value="${item.slocTujuan || ''}" placeholder="SLOC" data-index="${index}" required style="width: 60px;">
                </td>
                <td class="text-center align-middle">
                    <select class="form-control form-control-sm batch-source-select" data-index="${index}" required style="min-width: 150px;">
                        <option value="">Select Batch *</option>
                        ${batchOptions}
                    </select>
                </td>
            `;

            tbody.appendChild(row);
        });

        updateModalTotals();
        setupModalEventListeners();
        setupConfirmButton();
    }

    // Create batch options
    function createBatchOptions(batchInfo, selectedBatch) {
        if (!selectedBatch) selectedBatch = '';

        let options = '';

        if (!batchInfo || batchInfo.length === 0) {
            options += `<option value="NOBATCH" data-qty="0" data-sloc="">No Batch Available</option>`;
            return options;
        }

        batchInfo.forEach(function(batch, index) {
            const batchValue = batch.batch || batch.charg || `BATCH${index + 1}`;
            const batchQty = batch.qty || batch.clabs || 0;
            const batchSloc = batch.sloc || batch.lgort || batchValue;
            const displayQty = formatAngka(batchQty);
            const batchLabel = `${batchSloc} | ${batchValue} | ${displayQty}`;
            const selected = batchValue === selectedBatch ? 'selected' : '';

            options += `<option value="${batchValue}" data-qty="${batchQty}" data-sloc="${batchSloc}" ${selected}>${batchLabel}</option>`;
        });

        return options;
    }

    // Setup modal event listeners
    function setupModalEventListeners() {
        // Quantity input change
        document.querySelectorAll('.qty-transfer-input').forEach(function(input) {
            input.addEventListener('blur', function() {
                const index = parseInt(this.dataset.index);
                let value = this.value.trim();
                let parsedValue = parseAngka(value);
                const batchQty = transferItems[index].batchQty || 0;
                const remainingQty = transferItems[index].remainingQty || 0;

                if (isNaN(parsedValue) || parsedValue < 0) {
                    this.value = '';
                    transferItems[index].qty = 0;
                    transferItems[index].quantity = 0;
                    showToast('Quantity is required', 'error');
                } else if (parsedValue > batchQty) {
                    this.value = formatAngka(batchQty);
                    transferItems[index].qty = batchQty;
                    transferItems[index].quantity = batchQty;
                    showToast('Quantity cannot exceed batch quantity', 'warning');
                } else if (parsedValue > remainingQty) {
                    this.value = formatAngka(remainingQty);
                    transferItems[index].qty = remainingQty;
                    transferItems[index].quantity = remainingQty;
                    showToast('Quantity cannot exceed remaining quantity', 'warning');
                } else {
                    this.value = formatAngka(parsedValue);
                    transferItems[index].qty = parsedValue;
                    transferItems[index].quantity = parsedValue;
                }

                updateModalTotals();
            });

            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^\d.,]/g, '');
            });

            input.addEventListener('focus', function() {
                const index = parseInt(this.dataset.index);
                if (transferItems[index].qty > 0) {
                    this.value = transferItems[index].qty.toString().replace('.', ',');
                }
            });
        });

        // Plant tujuan change
        document.querySelectorAll('.plant-tujuan-input').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });

            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                if (!this.value.trim()) {
                    showToast('Plant Destination is required', 'error');
                    this.focus();
                    return;
                }
                transferItems[index].plantTujuan = this.value.toUpperCase();
                transferItems[index].plant_dest = this.value.toUpperCase();
            });
        });

        // SLOC tujuan change
        document.querySelectorAll('.sloc-tujuan-input').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });

            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                if (!this.value.trim()) {
                    showToast('SLOC Destination is required', 'error');
                    this.focus();
                    return;
                }
                transferItems[index].slocTujuan = this.value.toUpperCase();
                transferItems[index].sloc_dest = this.value.toUpperCase();
            });
        });

        // Batch select change
        document.querySelectorAll('.batch-source-select').forEach(function(select) {
            select.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                const selectedValue = this.value;
                const selectedOption = this.options[this.selectedIndex];

                if (!selectedValue) {
                    showToast('Batch Source is required', 'error');
                    this.focus();
                    return;
                }

                if (selectedValue === "NOBATCH") {
                    showToast('Please select a valid batch', 'error');
                    this.focus();
                    return;
                }

                const batchQty = parseFloat(selectedOption.dataset.qty) || 0;
                const batchSloc = selectedOption.dataset.sloc || selectedValue;

                transferItems[index].selectedBatch = selectedValue;
                transferItems[index].batchQty = batchQty;
                transferItems[index].batchSloc = batchSloc;

                const batchQtyDisplay = document.getElementById('batchQtyDisplay-' + index);
                if (batchQtyDisplay) {
                    batchQtyDisplay.textContent = formatAngka(batchQty);
                    batchQtyDisplay.className = batchQty > 0 ? 'stock-custom-available fw-medium small' : 'stock-custom-unavailable fw-medium small';
                }

                const qtyInput = document.querySelector('.qty-transfer-input[data-index="' + index + '"]');
                if (qtyInput) {
                    const currentQty = parseAngka(qtyInput.value);
                    if (currentQty > batchQty && batchQty > 0) {
                        qtyInput.value = formatAngka(batchQty);
                        transferItems[index].qty = batchQty;
                        transferItems[index].quantity = batchQty;
                        showToast('Quantity adjusted to available batch', 'info');
                        updateModalTotals();
                    }
                }
            });
        });
    }

    // Setup confirm button event listener
    function setupConfirmButton() {
        const confirmTransferBtn = document.getElementById('confirmTransfer');
        if (confirmTransferBtn) {
            // Remove existing event listeners
            const newConfirmBtn = confirmTransferBtn.cloneNode(true);
            confirmTransferBtn.parentNode.replaceChild(newConfirmBtn, confirmTransferBtn);

            newConfirmBtn.addEventListener('click', function() {
                validateAndConfirmTransfer();
            });
        }
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

    // Validate and confirm transfer
    function validateAndConfirmTransfer() {
        let isValid = true;
        let errorMessages = [];

        // Validasi semua input required
        document.querySelectorAll('.qty-transfer-input, .plant-tujuan-input, .sloc-tujuan-input, .batch-source-select').forEach(function(input) {
            const value = input.value.trim();
            const index = input.dataset.index;

            if (!value) {
                isValid = false;
                input.classList.add('is-invalid');

                const fieldName = input.classList.contains('qty-transfer-input') ? 'Quantity' :
                                input.classList.contains('plant-tujuan-input') ? 'Plant Destination' :
                                input.classList.contains('sloc-tujuan-input') ? 'SLOC Destination' :
                                'Batch Source';

                errorMessages.push(`${fieldName} is required for item ${parseInt(index) + 1}`);
            } else {
                input.classList.remove('is-invalid');

                // Validasi khusus untuk quantity
                if (input.classList.contains('qty-transfer-input')) {
                    const qty = parseAngka(value);
                    if (qty <= 0) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        errorMessages.push(`Quantity must be greater than 0 for item ${parseInt(index) + 1}`);
                    }
                }
            }
        });

        // Validasi batch selection
        document.querySelectorAll('.batch-source-select').forEach(function(select) {
            const value = select.value;
            const index = select.dataset.index;

            if (value === "NOBATCH" || !value) {
                isValid = false;
                select.classList.add('is-invalid');
                errorMessages.push(`Valid batch is required for item ${parseInt(index) + 1}`);
            }
        });

        if (!isValid) {
            showToast(errorMessages.join('; '), 'error');
            return;
        }

        // Validasi transfer remarks
        const transferRemarks = document.getElementById('transferRemarks');
        const remarks = transferRemarks ? transferRemarks.value.trim() : '';

        if (!remarks) {
            showToast('Remarks is required', 'error');
            if (transferRemarks) {
                transferRemarks.focus();
                transferRemarks.classList.add('is-invalid');
            }
            return;
        } else if (transferRemarks) {
            transferRemarks.classList.remove('is-invalid');
        }

        // Update data dari modal
        document.querySelectorAll('.qty-transfer-input').forEach(function(input) {
            const index = parseInt(input.dataset.index);
            let value = input.value.trim();

            if (value) {
                let parsedValue = parseAngka(value);
                transferItems[index].qty = parsedValue;
                transferItems[index].quantity = parsedValue;
            }
        });

        document.querySelectorAll('.plant-tujuan-input').forEach(function(input) {
            const index = parseInt(input.dataset.index);
            transferItems[index].plantTujuan = input.value.toUpperCase().trim();
            transferItems[index].plant_dest = input.value.toUpperCase().trim();
        });

        document.querySelectorAll('.sloc-tujuan-input').forEach(function(input) {
            const index = parseInt(input.dataset.index);
            const value = input.value.toUpperCase().trim();
            if (!value) {
                showToast(`SLOC Destination is required for item ${index + 1}`, 'error');
                isValid = false;
            }
            transferItems[index].slocTujuan = value;
            transferItems[index].sloc_dest = value;
        });

        document.querySelectorAll('.batch-source-select').forEach(function(select) {
            const index = parseInt(select.dataset.index);
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption && select.value !== "NOBATCH") {
                transferItems[index].selectedBatch = select.value;
                transferItems[index].batch = select.value;
                transferItems[index].batchSloc = selectedOption.dataset.sloc || '';
                transferItems[index].batchQty = parseFloat(selectedOption.dataset.qty) || 0;
            }
        });

        // Set remarks untuk semua items
        transferItems.forEach(function(item) {
            item.remarks = remarks;
        });

        if (!isValid) return;

        // Show SAP credentials modal
        const transferModalElement = document.getElementById('transferPreviewModal');
        if (transferModalElement) {
            const transferModal = bootstrap.Modal.getInstance(transferModalElement);
            if (transferModal) {
                transferModal.hide();
            }
        }

        const sapCredentialsModal = document.getElementById('sapCredentialsModal');
        if (sapCredentialsModal) {
            const modal = new bootstrap.Modal(sapCredentialsModal);
            modal.show();

            // Setup SAP form submission
            setupSapFormSubmission();
        } else {
            showToast('SAP Credentials modal not found', 'error');
        }
    }

    // Setup SAP form submission
    function setupSapFormSubmission() {
        const sapForm = document.getElementById('sapCredentialsForm');
        if (!sapForm) return;

        // Hapus event listener lama jika ada
        const newForm = sapForm.cloneNode(true);
        sapForm.parentNode.replaceChild(newForm, sapForm);

        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Cek apakah sedang proses transfer
            if (isProcessingTransfer) {
                showToast('Transfer is already being processed. Please wait...', 'warning');
                return;
            }

            // Set flag processing
            isProcessingTransfer = true;

            // Validate form
            const username = document.getElementById('sapUsername').value.trim();
            const password = document.getElementById('sapPassword').value.trim();
            const additionalRemarks = document.getElementById('additionalRemarks')?.value.trim() || '';

            if (!username || !password) {
                showToast('Please enter both SAP username and password', 'error');
                isProcessingTransfer = false;
                return;
            }

            // Filter hanya item dengan quantity > 0
            const validItems = transferItems.filter(item => {
                const qty = item.quantity || item.qty || 0;
                return qty > 0;
            });

            if (validItems.length === 0) {
                showToast('No items with valid quantity (> 0)', 'error');
                isProcessingTransfer = false;
                return;
            }

            // Disable tombol submit untuk mencegah klik berulang
            const submitBtn = document.getElementById('submitSapCredentials');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            }

            // Log untuk debugging
            console.log('Processing transfer with', validItems.length, 'items');

            // Prepare transfer data sesuai format controller
            const transferData = {
                plant: "{{ $document->plant }}",
                sloc_supply: "{{ $document->plant_supply ?? $document->sloc_supply ?? '' }}",
                items: validItems.map(function(item) {
                    return {
                        material_code: item.materialCode,
                        material_desc: item.materialDesc,
                        quantity: parseFloat(item.quantity || item.qty || 0),
                        unit: item.unit,
                        plant_tujuan: item.plantTujuan || "{{ $document->plant }}",
                        sloc_tujuan: item.slocTujuan || '',
                        batch: item.selectedBatch || '',
                        batch_sloc: item.batchSloc || '',
                        requested_qty: parseFloat(item.remainingQty || 0),
                        available_stock: parseFloat(item.batchQty || 0)
                    };
                }),
                sap_credentials: {
                    user: username,
                    passwd: password,
                    client: "{{ env('SAP_CLIENT', '100') }}",
                    lang: "{{ env('SAP_LANG', 'EN') }}",
                    ashost: "{{ env('SAP_ASHOST') }}",
                    sysnr: "{{ env('SAP_SYSNR', '00') }}"
                },
                remarks: additionalRemarks || transferItems[0]?.remarks || "Transfer from Document {{ $document->document_no }}",
                header_text: "Transfer from Document {{ $document->document_no }}"
            };

            console.log('Transfer data to send:', JSON.stringify(transferData, null, 2));

            showLoading();

            // Gunakan route yang benar
            const processUrl = "{{ route('documents.transfers.process', $document->id) }}";

            fetch(processUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(transferData)
            })
            .then(async response => {
                const contentType = response.headers.get("content-type");

                // Jika response tidak OK, coba parse error
                if (!response.ok) {
                    if (contentType && contentType.includes("application/json")) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    } else {
                        const text = await response.text();
                        throw new Error(`HTTP error! status: ${response.status}, message: ${text}`);
                    }
                }

                // Jika OK, parse JSON
                if (contentType && contentType.includes("application/json")) {
                    return response.json();
                } else {
                    const text = await response.text();
                    return { success: false, message: text };
                }
            })
            .then(data => {
                hideLoading();

                // Reset flag processing
                isProcessingTransfer = false;

                // Enable tombol submit kembali
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }

                if (data.success) {
                    showToast(data.message || 'Transfer processed successfully', 'success');

                    // Close SAP modal
                    const sapModal = bootstrap.Modal.getInstance(document.getElementById('sapCredentialsModal'));
                    if (sapModal) sapModal.hide();

                    // Clear transfer items
                    transferItems = [];
                    const transferSlots = document.getElementById('transferSlots');
                    if (transferSlots) transferSlots.innerHTML = '';
                    updateTransferCount();
                    showEmptyState();

                    // Clear selected items
                    selectedItems.clear();
                    document.querySelectorAll('.row-select').forEach(cb => cb.checked = false);
                    updateSelectionCount();
                    updateRowSelectionStyles();

                    // Refresh page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Tampilkan error detail jika ada
                    let errorMsg = data.message || 'Failed to process transfer';
                    if (data.errors) {
                        errorMsg += ': ' + JSON.stringify(data.errors);
                    }
                    showToast(errorMsg, 'error');
                    console.error('Transfer failed:', data);
                }
            })
            .catch(error => {
                hideLoading();

                // Reset flag processing
                isProcessingTransfer = false;

                // Enable tombol submit kembali
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }

                let errorMsg = 'Error processing transfer: ' + error.message;
                showToast(errorMsg, 'error');
                console.error('Transfer error:', error);
            });
        });
    }

    // Update remaining quantities function
    function updateRemainingQuantities() {
        document.querySelectorAll('.draggable-row').forEach(row => {
            const requestedQty = parseFloat(row.dataset.requestedQty || 0);
            const transferredQty = parseFloat(row.dataset.transferredQty || 0);
            const remainingQty = Math.max(0, requestedQty - transferredQty);

            // Update data attribute
            row.dataset.remainingQty = remainingQty;

            // Update display in table
            const remainingCell = row.querySelector('td:nth-child(6) span');
            if (remainingCell) {
                remainingCell.textContent = formatAngka(remainingQty);
                remainingCell.className = `fw-bold small ${remainingQty > 0 ? 'text-primary' : 'text-success'}`;
            }
        });
    }

    // Check stock form
    const checkStockForm = document.getElementById('checkStockForm');
    if (checkStockForm) {
        checkStockForm.addEventListener('submit', function(e) {
            showLoading();
        });
    }

    // Reset stock form
    const resetStockForm = document.getElementById('resetStockForm');
    if (resetStockForm) {
        resetStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            showLoading();

            const formData = new FormData(this);
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showToast('Error resetting stock data: ' + error.message, 'error');
            });
        });
    }

    // Initialize all functions
    function initialize() {
        console.log('Initializing document show page...');

        try {
            // 1. Setup transfer details listeners
            setupTransferDetailsListeners();
            console.log('✅ Transfer details listeners setup complete');

            // 2. Setup drag and drop
            setupDragAndDrop();
            console.log('✅ Drag and drop setup complete');

            // 3. Setup checkbox selection
            setupCheckboxSelection();
            console.log('✅ Checkbox selection setup complete');

            // 4. Update remaining quantities
            updateRemainingQuantities();
            console.log('✅ Remaining quantities updated');

            // 5. Debug: Log semua transfer detail buttons
            const transferButtons = document.querySelectorAll('.view-transfer-details');
            console.log(`✅ Found ${transferButtons.length} transfer detail buttons`);

            transferButtons.forEach((btn, index) => {
                console.log(`Button ${index + 1}:`, {
                    itemId: btn.getAttribute('data-item-id'),
                    materialCode: btn.getAttribute('data-material-code'),
                    materialDesc: btn.getAttribute('data-material-description'),
                    hasHistory: btn.getAttribute('data-has-history'),
                    disabled: btn.disabled
                });
            });

            // 6. Auto-hide alerts setelah 5 detik
            setTimeout(function() {
                document.querySelectorAll('.alert').forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);

            console.log('✅ All initialization complete');

        } catch (error) {
            console.error('❌ Error during initialization:', error);
            showToast('Error initializing page: ' + error.message, 'error');
        }
    }

    // TAMBAHKAN error handling untuk seluruh script
    window.addEventListener('error', function(e) {
        console.error('Global error:', e.error);
        console.error('Error at:', e.filename, 'line:', e.lineno);
    });

    // Run initialization
    initialize();
});
</script>
@endsection
