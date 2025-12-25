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
                    <div class="d-flex align-items-center gap-2 mt-1">
                        @if($document->status == 'booked')
                            <span class="badge bg-warning">
                                <i class="fas fa-clock me-1"></i>Booked
                            </span>
                        @elseif($document->status == 'partial')
                            <span class="badge bg-info">
                                <i class="fas fa-tasks me-1"></i>Partial Transfer
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
                        <span class="text-muted small">• Completion: {{ round($document->completion_rate ?? 0, 2) }}%</span>
                        <!-- Nama pembuat dokumen di header -->
                        @if($document->created_by_name ?? $document->user->name ?? false)
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-user me-1"></i>
                                {{ $document->created_by_name ?? $document->user->name ?? 'N/A' }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    @if($document->status == 'booked')
                    <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    @endif
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
                            <!-- Tambahkan Document No di sini -->
                            <div class="info-item">
                                <span class="info-label">Document No</span>
                                <span class="info-value fw-bold text-primary">{{ $document->document_no }}</span>
                            </div>
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
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    @if($document->status == 'booked')
                                        <span class="badge bg-warning">Booked</span>
                                    @elseif($document->status == 'partial')
                                        <span class="badge bg-info">Partial Transfer</span>
                                    @elseif($document->status == 'closed')
                                        <span class="badge bg-success">Closed</span>
                                    @else
                                        <span class="badge bg-danger">Cancelled</span>
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Column 3 -->
                        <div class="info-column">
                            <!-- PERBAIKAN: Ganti dari Plant Supply menjadi Plant Supply dengan field yang benar -->
                            <div class="info-item">
                                <span class="info-label">Plant Supply</span>
                                <span class="info-value">
                                    @if(isset($document->plant_supply) && !empty($document->plant_supply))
                                        {{ $document->plant_supply }}
                                    @elseif(isset($document->sloc_supply) && !empty($document->sloc_supply))
                                        {{ $document->sloc_supply }}
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Completion Rate</span>
                                <span class="info-value">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: {{ round($document->completion_rate ?? 0, 2) }}%"></div>
                                    </div>
                                    <small>{{ round($document->completion_rate ?? 0, 2) }}%</small>
                                </span>
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
                            <!-- HAPUS TOTAL TRANSFERRED -->
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
                                    <small class="text-muted">{{ $document->items->count() }} items • {{ number_format($document->total_qty) }} total quantity</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="me-2">
                                        <span class="badge bg-light border text-dark" id="selectedCount">0 selected</span>
                                    </div>
                                    <!-- PERUBAHAN: Tampilkan tombol Stock dan Reset berdasarkan kondisi yang benar -->
                                    @if($document->status == 'booked')
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

                                    <!-- Tampilkan tombol Reset jika ada stock info -->
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
                                    <form id="resetStockForm" action="{{ route('stock.clear-cache', $document->document_no) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                    </form>
                                    @endif
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
                                            <!-- KOLOM BARU: DISPC = MRP Comp -->
                                            <th class="border-end-0 text-center" style="min-width: 100px;">MRP Comp</th>
                                            <th class="border-end-0 text-center">Req Qty</th>
                                            <th class="border-end-0 text-center">Remaining</th>
                                            <th class="border-end-0 text-center">Status</th>
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

                                                // PERBAIKAN: Pastikan perhitungan akurat
                                                $requestedQty = isset($item->requested_qty) && is_numeric($item->requested_qty)
                                                    ? floatval($item->requested_qty)
                                                    : 0;

                                                $transferredQty = isset($item->transferred_qty) && is_numeric($item->transferred_qty)
                                                    ? floatval($item->transferred_qty)
                                                    : 0;

                                                // Batasi transferred tidak melebihi requested
                                                $transferredQty = min($transferredQty, $requestedQty);

                                                // PERHITUNGAN REMAINING: Req Qty - Total Transferred
                                                $remainingQty = max(0, $requestedQty - $transferredQty);

                                                // PERBAIKAN: Hitung $hasTransferHistory dengan benar
                                                $hasTransferHistory = $transferredQty > 0;

                                                // PERBAIKAN: Gunakan transferred_qty dari relasi jika ada
                                                // Coba ambil dari transfer details jika ada
                                                $actualTransfers = 0;
                                                if (method_exists($item, 'transferDetails') && $item->transferDetails) {
                                                    $actualTransfers = $item->transferDetails->sum('quantity');
                                                    // Jika ada transfer details, gunakan jumlahnya
                                                    if ($actualTransfers > 0) {
                                                        $hasTransferHistory = true;
                                                        $transferredQty = $actualTransfers;
                                                        $remainingQty = max(0, $requestedQty - $transferredQty);
                                                    }
                                                }

                                                // Stock information - PERBAIKAN: Ambil dari stock_info yang sudah di-load
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
                                                            'qty' => is_numeric($detail['clabs'] ?? 0) ? floatval($detail['clabs']) : 0,
                                                            'clabs' => is_numeric($detail['clabs'] ?? 0) ? floatval($detail['clabs']) : 0
                                                        ];
                                                    }
                                                }

                                                // Check if stock is available
                                                $hasStock = $totalStock > 0;
                                                $transferableQty = min($remainingQty, $totalStock);
                                                $canTransfer = $remainingQty > 0 && $hasStock && $transferableQty > 0;

                                                // Determine stock color class
                                                if ($totalStock > 0) {
                                                    if ($transferableQty > 0) {
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

                                                // PERBAIKAN: Tentukan apakah harus menampilkan View Details
                                                // Tampilkan jika ada transfer history atau jika sudah ditransfer sebagian/seluruhnya
                                                $showViewDetails = $hasTransferHistory || $transferredQty > 0 || $remainingQty < $requestedQty;
                                            @endphp
                                            <tr class="item-row draggable-row"
                                                draggable="true"
                                                data-item-id="{{ $item->id }}"
                                                data-material-code="{{ $materialCode }}"
                                                data-material-description="{{ $item->material_description }}"
                                                data-requested-qty="{{ $requestedQty }}"
                                                data-transferred-qty="{{ $transferredQty }}"
                                                data-remaining-qty="{{ $remainingQty }}"
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
                                                           {{ $canTransfer ? '' : 'disabled' }}>
                                                </td>
                                                <td class="text-center border-end-0 align-middle">{{ $index + 1 }}</td>
                                                <td class="border-end-0 align-middle">
                                                    <div class="fw-medium text-dark">{{ $materialCode }}</div>
                                                </td>
                                                <td class="border-end-0">
                                                    <div class="text-wrap" style="max-width: 300px;" title="{{ $item->material_description }}">
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
                                                <!-- KOLOM BARU: DISPC = MRP Comp -->
                                                <td class="text-center border-end-0 align-middle" style="min-width: 100px;">
                                                    @if($item->dispc)
                                                        <div class="fw-medium">{{ $item->dispc }}</div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center border-end-0 align-middle">
                                                    <div class="fw-medium text-dark">{{ \App\Helpers\NumberHelper::formatQuantity($requestedQty) }}</div>
                                                </td>
                                                <td class="text-center border-end-0 align-middle">
                                                    <div class="fw-bold text-dark">
                                                        {{ \App\Helpers\NumberHelper::formatQuantity($remainingQty) }}
                                                    </div>
                                                </td>
                                                <td class="text-center border-end-0 align-middle">
                                                    <!-- STATUS BERDASARKAN REMAINING -->
                                                    @if($remainingQty == 0)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle me-1"></i>Completed
                                                        </span>
                                                    @elseif($remainingQty < $requestedQty && $remainingQty > 0)
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-tasks me-1"></i>Partial
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-clock me-1"></i>Pending
                                                        </span>
                                                    @endif

                                                    <!-- SELALU tampilkan View Details untuk semua item -->
                                                    <div class="mt-1">
                                                        <a href="#" class="small text-primary view-transfer-details"
                                                        data-material-code="{{ $item->material_code }}"
                                                        data-material-description="{{ $item->material_description }}">
                                                            <i class="fas fa-eye me-1"></i>View Details
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="text-center border-end-0 align-middle">
                                                    @if($totalStock > 0)
                                                        @if($transferableQty > 0)
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
                                                                    <div class="{{ $batchClass }} text-wrap"
                                                                         title="SLOC: {{ $batchSloc }} | Batch: {{ $batchNumber }} | Qty: {{ \App\Helpers\NumberHelper::formatStockNumber($batchQty) }}">
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
                                        Select All Transferable Items
                                    </label>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSelection">
                                        <i class="fas fa-times me-1"></i> Clear
                                    </button>
                                    <!-- PERUBAHAN DI SINI: Pastikan button Add Selected muncul jika ada item transferable -->
                                    @if($hasTransferableItems && $canGenerateTransfer)
                                    <button type="button" class="btn btn-primary btn-sm" id="addSelectedToTransfer">
                                        <i class="fas fa-arrow-right me-1"></i> Add Selected
                                    </button>
                                    @endif
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
                                <i class="fas fa-info-circle me-1"></i>Only items with available stock and remaining quantity
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
                                <!-- PERUBAHAN DI SINI: Tampilkan button berdasarkan kondisi yang benar -->
                                @if($hasTransferableItems && $canGenerateTransfer)
                                <button type="button"
                                        class="btn btn-primary"
                                        id="generateTransferList">
                                    <i class="fas fa-file-export me-1"></i> Generate Transfer
                                </button>
                                <button type="button"
                                        class="btn btn-outline-danger"
                                        id="clearTransferList">
                                    <i class="fas fa-trash me-1"></i> Clear All
                                </button>
                                @else
                                <button type="button" class="btn btn-secondary" disabled>
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
    </div>
</div>

<!-- Include Modals -->
@include('documents.partials.transfer-details-modal')
@include('documents.partials.transfer-preview-modal')
@include('documents.partials.sap-credentials-modal')

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

/* Text wrapping untuk batch dan description */
.text-wrap {
    white-space: normal !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
}

/* Pastikan tabel responsive */
.table-responsive {
    overflow-x: auto !important;
}

/* Aturan untuk kolom MRP Comp */
#itemsTable th:nth-child(8),
#itemsTable td:nth-child(8) {
    min-width: 100px;
}

/* Atur lebar kolom batch lebih fleksible */
#itemsTable td:nth-child(14),
#itemsTable th:nth-child(14) {
    min-width: 180px;
    max-width: 250px;
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

/* PERBAIKAN: Badge color fixes */
.badge.bg-success {
    background-color: #28a745 !important;
    color: white !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
    color: white !important;
}

.badge.bg-info {
    background-color: #17a2b8 !important;
    color: white !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
    color: white !important;
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

/* Button disabled styles */
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
}

/* View Details link */
.view-transfer-details {
    text-decoration: none;
    font-size: 11px;
    padding: 1px 4px;
    border-radius: 3px;
    background-color: rgba(13, 110, 253, 0.1);
    display: inline-block;
    margin-top: 3px;
}

.view-transfer-details:hover {
    text-decoration: underline;
    background-color: rgba(13, 110, 253, 0.2);
}

/* Status badge text colors */
.bg-success {
    color: white !important;
}

.bg-warning {
    color: #212529 !important;
}

.bg-secondary {
    color: white !important;
}

/* Loading overlay */
.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.loading-overlay.show {
    display: flex;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    let transferItems = [];
    let selectedItems = new Set();
    let pendingTransferData = null;
    let isDragging = false;

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

    // Function untuk show transfer details dengan data
    function showTransferDetailsModalWithData(materialCode, materialDescription, transferData) {
        const modalElement = document.getElementById('transferDetailsModal');
        if (!modalElement) {
            console.error('Modal element not found');
            return;
        }

        // Gunakan modal instance yang ada atau buat baru
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) {
            modal = new bootstrap.Modal(modalElement);
        }

        // Set judul modal
        const modalTitle = document.getElementById('transferDetailsModalLabel');
        if (modalTitle) {
            modalTitle.innerHTML = `<i class="fas fa-list-alt me-2 text-primary"></i>Transfer Details - ${materialCode}`;
        }

        // Set deskripsi material
        const materialDesc = document.getElementById('detailMaterialDescription');
        if (materialDesc) {
            materialDesc.textContent = materialDescription;
        }

        // Isi tabel dengan data transfer
        const tbody = document.querySelector('#transferDetailsTable tbody');
        if (tbody) {
            tbody.innerHTML = '';

            if (transferData && transferData.length > 0) {
                transferData.forEach((transfer, index) => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td class="text-center">${index + 1}</td>
                        <td>${transfer.transfer_no || '-'}</td>
                        <td>${transfer.material_code || materialCode}</td>
                        <td>${transfer.batch || '-'}</td>
                        <td class="text-center">${formatAngka(transfer.quantity || 0)}</td>
                        <td class="text-center">${transfer.unit || 'PC'}</td>
                        <td class="text-center">${transfer.created_at ? new Date(transfer.created_at).toLocaleString() : '-'}</td>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-info-circle me-2"></i>No transfer data found</td></tr>';
            }
        }

        // Tampilkan modal
        modal.show();
    }

    // Setup event listener untuk view transfer details
    document.querySelectorAll('.view-transfer-details').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const materialCode = this.getAttribute('data-material-code');
            const materialDescription = this.getAttribute('data-material-description');

            // Tampilkan loading
            showLoading();

            // Ambil data dari server
            fetch(`/documents/{{ $document->id }}/items/${encodeURIComponent(materialCode)}/transfer-history`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                // Sembunyikan loading
                hideLoading();

                // Tampilkan data di modal dengan fungsi aman
                showTransferDetailsModalWithData(materialCode, materialDescription, data);
            })
            .catch(error => {
                console.error('Error fetching transfer details:', error);
                // Sembunyikan loading
                hideLoading();
                showToast('Error loading transfer details: ' + error.message, 'error');

                // Tampilkan modal kosong dengan fungsi aman
                showTransferDetailsModalWithData(materialCode, materialDescription, []);
            });
        });
    });

    // Setup drag and drop
    function setupDragAndDrop() {
        const rows = document.querySelectorAll('.draggable-row');
        const dropZone = document.getElementById('transferContainer');

        // Make rows draggable
        rows.forEach(function(row) {
            const remainingQty = parseFloat(row.dataset.remainingQty || 0);
            const availableStock = parseFloat(row.dataset.availableStock || 0);

            if (remainingQty > 0 && availableStock > 0) {
                row.draggable = true;
                row.style.cursor = 'grab';

                row.addEventListener('dragstart', function(e) {
                    isDragging = true;
                    this.classList.add('dragging');

                    // Set the drag data
                    e.dataTransfer.setData('text/plain', this.dataset.itemId);
                    e.dataTransfer.effectAllowed = 'copy';

                    // Visual feedback
                    setTimeout(() => {
                        this.classList.add('opacity-50');
                    }, 0);
                });

                row.addEventListener('dragend', function() {
                    isDragging = false;
                    this.classList.remove('dragging', 'opacity-50');
                });

                row.addEventListener('dragenter', function(e) {
                    if (isDragging) {
                        e.preventDefault();
                        this.classList.add('drag-over');
                    }
                });

                row.addEventListener('dragover', function(e) {
                    if (isDragging) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'copy';
                    }
                });

                row.addEventListener('dragleave', function() {
                    this.classList.remove('drag-over');
                });
            } else {
                row.draggable = false;
                row.classList.add('zero-stock');
                row.title = remainingQty <= 0 ? 'Already transferred' : 'No stock available';
                row.style.cursor = 'not-allowed';
            }
        });

        // Drop zone events
        const canGenerateTransfer = @json($canGenerateTransfer ?? false);
        const hasTransferableItems = @json($hasTransferableItems ?? false);

        if (canGenerateTransfer && hasTransferableItems) {
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
                this.classList.remove('drag-over');
                const itemId = e.dataTransfer.getData('text/plain');

                if (itemId) {
                    addItemById(itemId);
                }
            });
        }
    }

    // Setup checkbox selection
    function setupCheckboxSelection() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const selectAllHeader = document.getElementById('selectAllHeader');

        // Handle select all checkboxes
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
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    if (selectAllHeader) selectAllHeader.checked = false;
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
        selectedItems.forEach(function(itemId) {
            const itemRow = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
            if (itemRow) {
                const itemData = getItemDataFromRow(itemRow);

                if (!transferItems.some(function(transferItem) { return transferItem.id === itemData.id; })) {
                    if (itemData.remainingQty > 0 && itemData.availableStock > 0) {
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

        const itemData = getItemDataFromRow(itemRow);

        if (transferItems.some(function(transferItem) { return transferItem.id === itemData.id; })) {
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
            '<span class="text-muted">Remaining: ' + formatAngka(item.remainingQty) + '</span>' +
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
                '<td class="text-center align-middle"><div class="fw-medium">' + formatAngka(item.remainingQty) + '</div></td>' +
                '<td class="text-center align-middle"><div class="' + (item.batchQty > 0 ? 'stock-custom-available' : 'stock-custom-unavailable') + ' fw-medium" id="batchQtyDisplay-' + index + '">' + formatAngka(item.batchQty || 0) + '</div></td>' +
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
                    '<select class="form-control form-control-sm batch-source-select" data-index="' + index + '" required style="min-width: 200px;">' +
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

        // Setup confirm button event listener
        setupConfirmButton();
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

                // Validasi berdasarkan batchQty
                const batchQty = transferItems[index].batchQty || 0;

                if (isNaN(parsedValue) || parsedValue < 0) {
                    this.value = '';
                    transferItems[index].qty = 0;
                    transferItems[index].quantity = 0;
                    showToast('Quantity for ' + transferItems[index].materialCode + ' is required', 'error');
                } else if (parsedValue > batchQty) {
                    this.value = formatAngka(batchQty);
                    transferItems[index].qty = batchQty;
                    transferItems[index].quantity = batchQty;
                    showToast('Quantity cannot exceed selected batch quantity (' + formatAngka(batchQty) + ')', 'warning');
                } else {
                    // Reformat with Indonesian format
                    this.value = formatAngka(parsedValue);
                    transferItems[index].qty = parsedValue;
                    transferItems[index].quantity = parsedValue;
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
                transferItems[index].plant_dest = this.value.toUpperCase();
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
                transferItems[index].sloc_dest = this.value.toUpperCase();
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

                // Update transfer item dengan batch yang dipilih
                transferItems[index].selectedBatch = selectedValue;
                transferItems[index].batchQty = batchQty;
                transferItems[index].batchSloc = batchSloc;

                // Update tampilan batch quantity
                const batchQtyDisplay = document.getElementById('batchQtyDisplay-' + index);
                if (batchQtyDisplay) {
                    batchQtyDisplay.textContent = formatAngka(batchQty);
                    batchQtyDisplay.className = batchQty > 0 ? 'stock-custom-available fw-medium' : 'stock-custom-unavailable fw-medium';
                }

                // Get related quantity input
                const qtyInput = document.querySelector('.qty-transfer-input[data-index="' + index + '"]');

                // Hitung max transfer berdasarkan batch quantity
                const maxTransferable = batchQty;

                if (qtyInput) {
                    // If quantity exceeds batch qty, adjust it
                    const currentQty = parseAngka(qtyInput.value);
                    if (currentQty > maxTransferable && maxTransferable > 0) {
                        qtyInput.value = formatAngka(maxTransferable);
                        transferItems[index].qty = maxTransferable;
                        transferItems[index].quantity = maxTransferable;
                        showToast('Quantity adjusted to available batch: ' + formatAngka(maxTransferable), 'info');
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

    // Setup confirm button event listener
    function setupConfirmButton() {
        const confirmTransferBtn = document.getElementById('confirmTransfer');
        if (confirmTransferBtn) {
            // Remove existing event listeners
            const newConfirmBtn = confirmTransferBtn.cloneNode(true);
            confirmTransferBtn.parentNode.replaceChild(newConfirmBtn, confirmTransferBtn);

            // Add new event listener
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
        const transferRemarks = document.getElementById('transferRemarks');
        const remarks = transferRemarks ? transferRemarks.value.trim() : '';

        // Validasi: Plant Supply
        const plantSupply = "{{ $document->plant_supply ?? '' }}";
        const slocSupply = "{{ $document->sloc_supply ?? '' }}";
        const finalPlantSupply = plantSupply || slocSupply;

        if (!finalPlantSupply) {
            showToast('Plant Supply is required. Please check document information.', 'error');
            return;
        }

        // Validasi: SLOC Destination untuk semua item
        let slocDestValid = true;
        let slocErrorMessage = '';
        document.querySelectorAll('.sloc-tujuan-input').forEach(function(input) {
            if (!input.value || input.value.trim() === '') {
                slocDestValid = false;
                const index = parseInt(input.dataset.index);
                const materialCode = transferItems[index].materialCode;
                slocErrorMessage = 'SLOC Destination for ' + materialCode + ' is required';
                input.classList.add('is-invalid');
                input.focus();
                return false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!slocDestValid) {
            showToast(slocErrorMessage, 'error');
            return;
        }

        // Validasi: Remarks
        if (!remarks) {
            showToast('Remarks is required. Please add remarks for this transfer.', 'error');
            if (transferRemarks) {
                transferRemarks.focus();
                transferRemarks.classList.add('is-invalid');
            }
            return;
        } else {
            if (transferRemarks) {
                transferRemarks.classList.remove('is-invalid');
            }
        }

        // Validasi: Ensure all mandatory fields are filled
        let isValid = true;
        let errorMessage = '';

        // Check all modal inputs
        document.querySelectorAll('.qty-transfer-input, .plant-tujuan-input, .batch-source-select').forEach(function(input) {
            if (!input.value || input.value.trim() === '') {
                isValid = false;
                const index = parseInt(input.dataset.index);
                const materialCode = transferItems[index].materialCode;
                const fieldName = input.classList.contains('qty-transfer-input') ? 'Transfer Quantity' :
                                input.classList.contains('plant-tujuan-input') ? 'Plant Destination' : 'Batch Source';
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

        // Validasi quantities berdasarkan batch quantity
        transferItems.forEach(function(item, index) {
            if (!item.qty || item.qty <= 0) {
                isValid = false;
                errorMessage = 'Quantity for ' + item.materialCode + ' must be greater than 0';
                return;
            }

            // Validasi: jumlah transfer tidak boleh melebihi batch quantity
            if (item.qty > item.batchQty) {
                isValid = false;
                errorMessage = 'Quantity for ' + item.materialCode + ' (' + formatAngka(item.qty) + ') exceeds selected batch quantity (' + formatAngka(item.batchQty) + ')';
                return;
            }
        });

        if (!isValid) {
            showToast(errorMessage, 'error');
            return;
        }

        // Update transferItems dengan data dari modal
        document.querySelectorAll('.qty-transfer-input').forEach(function(input) {
            const index = parseInt(input.dataset.index);
            let value = input.value.trim();

            if (value) {
                let parsedValue = parseAngka(value);
                transferItems[index].qty = parsedValue;
                transferItems[index].quantity = parsedValue;
            }
        });

        // Update plant and sloc tujuan
        document.querySelectorAll('.plant-tujuan-input').forEach(function(input) {
            const index = parseInt(input.dataset.index);
            transferItems[index].plantTujuan = input.value.toUpperCase();
            transferItems[index].plant_dest = input.value.toUpperCase();
        });

        document.querySelectorAll('.sloc-tujuan-input').forEach(function(input) {
            const index = parseInt(input.dataset.index);
            transferItems[index].slocTujuan = input.value.toUpperCase();
            transferItems[index].sloc_dest = input.value.toUpperCase();
        });

        // Update selected batch
        document.querySelectorAll('.batch-source-select').forEach(function(select) {
            const index = parseInt(select.dataset.index);
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption) {
                // Batch: nilai dari select (batch number)
                const batchValue = select.value;
                // Batch_sloc: dari data-sloc (storage location)
                const batchSloc = selectedOption.dataset.sloc || '';

                transferItems[index].selectedBatch = batchValue;
                transferItems[index].batch = batchValue;
                transferItems[index].batchSloc = batchSloc;
                transferItems[index].batchQty = parseFloat(selectedOption.dataset.qty) || 0;
            }
        });

        // Save remarks to transfer items
        transferItems.forEach(function(item) {
            item.remarks = remarks;
        });

        // Ambil plant_supply atau sloc_supply dari document
        const finalPlantSupplyForTransfer = finalPlantSupply;

        // Struktur data yang dikirim ke Laravel
        pendingTransferData = {
            document_no: "{{ $document->document_no }}",
            plant: "{{ $document->plant }}",
            plant_supply: finalPlantSupplyForTransfer,
            move_type: "311",
            posting_date: new Date().toISOString().split('T')[0].replace(/-/g, ''),
            header_text: "Transfer from Document {{ $document->document_no }}",
            items: transferItems.map(function(item) {
                return {
                    id: item.id,
                    material_code: item.materialCode,
                    material_desc: item.materialDesc,
                    requested_qty: item.requestedQty,
                    transferred_qty: item.transferredQty,
                    remaining_qty: item.remainingQty,
                    quantity: item.quantity,
                    transfer_qty: item.quantity,
                    unit: item.unit,
                    plant_tujuan: item.plantTujuan,
                    plant_dest: item.plant_dest,
                    sloc_tujuan: item.slocTujuan,
                    sloc_dest: item.sloc_dest,
                    batch: item.batch || item.selectedBatch,
                    batch_sloc: item.batchSloc,
                    available_stock: item.batchQty,
                    remarks: item.remarks
                };
            }),
            remarks: remarks
        };

        // Debug: Log data yang akan dikirim
        console.log('Data transfer yang akan dikirim ke backend:', JSON.stringify(pendingTransferData, null, 2));

        // Tutup modal transfer preview
        const transferModalElement = document.getElementById('transferPreviewModal');
        if (transferModalElement) {
            const transferModal = bootstrap.Modal.getInstance(transferModalElement);
            if (transferModal) {
                transferModal.hide();
            }
        }

        // Tampilkan modal SAP credentials
        const sapCredentialsModal = new bootstrap.Modal(document.getElementById('sapCredentialsModal'));
        sapCredentialsModal.show();
    }

    // Setup SAP credentials submit button
    const submitSapCredentialsBtn = document.getElementById('submitSapCredentials');
    if (submitSapCredentialsBtn) {
        submitSapCredentialsBtn.addEventListener('click', function() {
            const sapUsername = document.getElementById('sapUsername').value.trim();
            const sapPassword = document.getElementById('sapPassword').value.trim();

            if (!sapUsername || !sapPassword) {
                showToast('SAP username and password are required', 'error');
                return;
            }

            // Tutup modal SAP credentials
            const sapCredentialsModalElement = document.getElementById('sapCredentialsModal');
            if (sapCredentialsModalElement) {
                const sapCredentialsModal = bootstrap.Modal.getInstance(sapCredentialsModalElement);
                if (sapCredentialsModal) {
                    sapCredentialsModal.hide();
                }
            }

            // Show loading
            showLoading();

            // Struktur data sesuai dengan yang diharapkan Laravel controller
            const plantSupply = "{{ $document->plant_supply ?? '' }}";
            const slocSupply = "{{ $document->sloc_supply ?? '' }}";
            const finalPlantSupply = plantSupply || slocSupply || "{{ $document->plant }}";

            const transferData = {
                // Field yang diperlukan oleh validasi Laravel di root
                plant: pendingTransferData.plant || "{{ $document->plant }}",
                sloc_supply: finalPlantSupply,
                items: pendingTransferData.items,

                // Data untuk dikirim ke Python service
                transfer_data: {
                    transfer_info: {
                        document_no: pendingTransferData.document_no,
                        plant_supply: finalPlantSupply,
                        plant_destination: "{{ $document->plant }}",
                        move_type: pendingTransferData.move_type,
                        posting_date: pendingTransferData.posting_date,
                        header_text: pendingTransferData.header_text,
                        remarks: pendingTransferData.remarks,
                        created_by: '{{ auth()->user()->name ?? "SYSTEM" }}',
                        created_at: new Date().toISOString().split('T')[0].replace(/-/g, '')
                    },
                    items: pendingTransferData.items.map(function(item) {
                        return {
                            material_code: item.material_code || item.materialCode,
                            material_code_raw: item.material_code || item.materialCode,
                            quantity: item.quantity || item.qty,
                            unit: item.unit,
                            plant_supply: finalPlantSupply,
                            plant_tujuan: item.plant_tujuan || item.plant_dest,
                            sloc_tujuan: item.sloc_tujuan || item.sloc_dest,
                            batch: item.batch || item.selectedBatch,
                            batch_sloc: item.batch_sloc || item.batchSloc,
                            sales_ord: item.sales_ord || '',
                            s_ord_item: item.s_ord_item || '',
                            requested_qty: item.requested_qty,
                            transferred_qty: item.transferred_qty,
                            remaining_qty: item.remaining_qty,
                            available_stock: item.available_stock
                        };
                    })
                },
                sap_credentials: {
                    user: sapUsername,
                    passwd: sapPassword,
                    ashost: '{{ env("SAP_ASHOST", "192.168.254.154") }}',
                    sysnr: '{{ env("SAP_SYSNR", "01") }}',
                    client: '{{ env("SAP_CLIENT", "300") }}',
                    lang: 'EN'
                },
                user_id: {{ auth()->id() ?? 0 }},
                user_name: '{{ auth()->user()->name ?? "SYSTEM" }}'
            };

            // Debug: Log data yang akan dikirim
            console.log('Data lengkap yang dikirim ke Laravel:', JSON.stringify(transferData, null, 2));

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
                // Hide loading
                hideLoading();

                if (data.success) {
                    // Show transfer number
                    const transferNo = data.transfer_no || 'PENDING';
                    showToast('Transfer successful! Transfer No: ' + transferNo + ' created', 'success');

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
                // Hide loading
                hideLoading();

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

    // Reset form SAP credentials setiap kali modal ditampilkan
    const sapCredentialsModalElement = document.getElementById('sapCredentialsModal');
    if (sapCredentialsModalElement) {
        sapCredentialsModalElement.addEventListener('show.bs.modal', function() {
            document.getElementById('sapUsername').value = '';
            document.getElementById('sapPassword').value = '';
        });
    }

    // Custom loader for check stock form
    const checkStockForm = document.getElementById('checkStockForm');
    if (checkStockForm) {
        checkStockForm.addEventListener('submit', function(e) {
            // Show loading
            showLoading();
        });
    }

    // Handle reset stock form
    const resetStockForm = document.getElementById('resetStockForm');
    if (resetStockForm) {
        resetStockForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading
            showLoading();

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
                // Hide loading
                hideLoading();

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
                // Hide loading
                hideLoading();

                console.error('Error:', error);
                showToast('Error resetting stock data: ' + error.message, 'error');
            });
        });
    }

    // Handle focus untuk modal
    function handleModalFocus() {
        // Pastikan fokus dikembalikan ke body saat modal ditutup
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.addEventListener('hidden.bs.modal', function() {
                // Kembalikan fokus ke body atau elemen yang aman
                document.body.focus();
                // Pastikan tidak ada elemen yang tetap fokus
                if (document.activeElement && document.activeElement !== document.body) {
                    document.activeElement.blur();
                }
            });

            // Pastikan modal tidak mempertahankan fokus saat tersembunyi
            modal.addEventListener('show.bs.modal', function() {
                // Atur aria-hidden ke false saat modal ditampilkan
                this.setAttribute('aria-hidden', 'false');
            });

            modal.addEventListener('hide.bs.modal', function() {
                // Atur aria-hidden ke true saat modal disembunyikan
                this.setAttribute('aria-hidden', 'true');
            });
        });
    }

    // Initialize
    setupDragAndDrop();
    setupCheckboxSelection();
    handleModalFocus(); // Inisialisasi handler fokus modal

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
