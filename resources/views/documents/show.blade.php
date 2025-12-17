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
                                    <th class="w-50 py-1">Plant Supply:</th>
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
                                            <th class="text-center" style="background-color: #E6F7FF;">Plant</th>
                                            <th class="text-center" style="background-color: #FFF0E6;">Batch Info</th>
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
                                                <td class="text-center">
                                                    @if(!empty($stockDetails))
                                                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                                                            @foreach($stockDetails as $detail)
                                                                @php
                                                                    $batchNumber = $detail['charg'] ?? '';
                                                                    $batchQty = is_numeric($detail['clabs'] ?? 0) ? floatval($detail['clabs']) : 0;
                                                                    $batchSloc = $detail['lgort'] ?? '';
                                                                @endphp
                                                                @if($batchNumber)
                                                                    <span class="badge bg-info batch-badge"
                                                                          data-batch="{{ $batchNumber }}"
                                                                          data-sloc="{{ $batchSloc }}"
                                                                          data-qty="{{ $batchQty }}"
                                                                          title="SLOC: {{ $batchSloc }} | Batch: {{ $batchNumber }} | Qty: {{ \App\Helpers\NumberHelper::formatStockNumber($batchQty) }}">
                                                                        SLOC:{{ $batchSloc }} | {{ $batchNumber }}:{{ \App\Helpers\NumberHelper::formatStockNumber($batchQty) }}
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary batch-badge"
                                                                          data-batch="{{ $batchSloc }}"
                                                                          data-sloc="{{ $batchSloc }}"
                                                                          data-qty="{{ $batchQty }}"
                                                                          title="SLOC: {{ $batchSloc }} | Qty: {{ \App\Helpers\NumberHelper::formatStockNumber($batchQty) }}">
                                                                        SLOC:{{ $batchSloc }}:{{ \App\Helpers\NumberHelper::formatStockNumber($batchQty) }}
                                                                    </span>
                                                                @endif
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

                            <!-- PERUBAHAN: Hapus Transfer Summary -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Preview Modal -->
<div class="modal fade" id="transferPreviewModal" tabindex="-1" aria-labelledby="transferPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content frosted-glass">
            <div class="modal-header glass-header">
                <h5 class="modal-title text-white" id="transferPreviewModalLabel">
                    <i class="fas fa-file-export me-2"></i> Transfer Document Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body glass-body p-2">
                <div class="alert alert-info glass-alert mb-2 p-2">
                    <i class="fas fa-info-circle me-2"></i> Review and edit transfer details before confirming.
                </div>

                <div class="table-responsive modal-table-container" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-borderless glass-table mb-1" id="transferPreviewTable">
                        <thead class="glass-thead sticky-top">
                            <tr>
                                <th class="text-center" style="width: 40px; font-size: 0.8rem; padding: 6px 4px;">No</th>
                                <th style="width: 90px; font-size: 0.8rem; padding: 6px 4px;">Material</th>
                                <th style="width: 120px; font-size: 0.8rem; padding: 6px 4px;">Description</th>
                                <th class="text-center" style="width: 80px; font-size: 0.8rem; padding: 6px 4px;">Req Qty</th>
                                <th class="text-center" style="width: 80px; font-size: 0.8rem; padding: 6px 4px;">Stock</th>
                                <th class="text-center" style="width: 90px; font-size: 0.8rem; padding: 6px 4px;">Transfer Qty</th>
                                <th class="text-center" style="width: 50px; font-size: 0.8rem; padding: 6px 4px;">Unit</th>
                                <th class="text-center" style="width: 80px; font-size: 0.8rem; padding: 6px 4px;">Plant Dest</th>
                                <th class="text-center" style="width: 80px; font-size: 0.8rem; padding: 6px 4px;">Sloc Dest</th>
                                <th class="text-center" style="width: 220px; font-size: 0.8rem; padding: 6px 4px;">Batch Source</th>
                            </tr>
                        </thead>
                        <tbody class="glass-tbody" style="font-size: 0.85rem;">
                            <!-- Preview rows will be inserted here -->
                        </tbody>
                    </table>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="glass-form-group">
                            <label for="transferRemarks" class="form-label text-white" style="font-size: 0.9rem;">
                                <i class="fas fa-sticky-note me-1"></i> Remarks:
                            </label>
                            <textarea class="form-control glass-input" id="transferRemarks" rows="2"
                                      placeholder="Add remarks for this transfer..." style="font-size: 0.85rem;"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-summary p-2" style="font-size: 0.85rem;">
                            <h6 class="text-white mb-2" style="font-size: 0.9rem;">
                                <i class="fas fa-clipboard-list me-2"></i>Transfer Summary
                            </h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-light">Total Items:</span>
                                <span class="text-white fw-bold" id="modalTotalItems">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-light">Total Transfer Qty:</span>
                                <span class="text-white fw-bold stock-number" id="modalTotalQty">0</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-light">SLOC Sumber:</span>
                                <span class="badge bg-info" style="font-size: 0.8rem;">{{ $document->sloc_supply ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer glass-footer py-2">
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal" style="font-size: 0.85rem;">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary glass-btn btn-sm" id="confirmTransfer" style="font-size: 0.85rem;">
                    <i class="fas fa-paper-plane me-1"></i> Confirm Transfer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Animation Modal (Simplified) -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="background: transparent; border: none;">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-white mt-2" id="loadingText">Loading...</div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;"></div>

<style>
/* Styles remain the same as before */
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
.table thead th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

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

/* HIDE SPINNER BUTTONS */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
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

.dragging {
    opacity: 0.5;
}

.drag-over {
    border: 2px dashed #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.draggable-row {
    cursor: move;
}

.draggable-row:hover {
    background-color: #e3f2fd !important;
}

.transfer-item-selected {
    border-left: 4px solid #0d6efd;
    background-color: #e3f2fd !important;
}

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

.table-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #3b82f6;
}

.table-checkbox:checked {
    background-color: #3b82f6;
}

.row-selected {
    background-color: #f0f7ff !important;
}

.selected-count {
    font-size: 0.9rem;
    font-weight: 500;
}

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

.transfer-card .card-header {
    padding: 8px 12px;
}

.transfer-card .card-footer {
    padding: 8px 12px;
}

.stock-number {
    font-family: monospace;
    font-weight: 600;
}

.remark-text {
    font-size: 0.9rem;
    color: #495057;
    max-height: 60px;
    overflow-y: auto;
    padding-right: 5px;
}

.time-badge {
    font-size: 0.75rem;
    padding: 2px 6px;
}

.frosted-glass {
    background: rgba(20, 25, 35, 0.9) !important;
    backdrop-filter: blur(15px) !important;
    -webkit-backdrop-filter: blur(15px) !important;
    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    border-radius: 10px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
}

.glass-header {
    background: rgba(0, 0, 0, 0.5) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 10px 10px 0 0 !important;
    padding: 12px 16px !important;
}

.glass-body {
    background: rgba(30, 35, 45, 0.7) !important;
    padding: 12px !important;
}

.glass-footer {
    background: rgba(0, 0, 0, 0.5) !important;
    border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 0 0 10px 10px !important;
    padding: 12px 16px !important;
}

.glass-alert {
    background: rgba(23, 162, 184, 0.25) !important;
    border: 1px solid rgba(23, 162, 184, 0.4) !important;
    color: #d1ecf1 !important;
    backdrop-filter: blur(10px);
    font-size: 0.85rem;
}

.glass-table {
    background: rgba(255, 255, 255, 0.08) !important;
    border-radius: 6px !important;
    overflow: hidden !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.glass-thead {
    background: rgba(0, 0, 0, 0.4) !important;
    color: #ffffff !important;
    border-bottom: 2px solid rgba(255, 255, 255, 0.15) !important;
    font-weight: 600 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.glass-tbody {
    background: rgba(255, 255, 255, 0.05) !important;
}

.glass-tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    transition: all 0.2s ease !important;
}

.glass-tbody tr:hover {
    background: rgba(255, 255, 255, 0.12) !important;
}

.glass-tbody td {
    color: #ffffff !important;
    vertical-align: middle !important;
    padding: 8px 4px !important;
    font-weight: 400 !important;
    font-size: 0.85rem !important;
}

.glass-tbody td code {
    background: rgba(0, 0, 0, 0.3);
    color: #4fc3f7 !important;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.8rem;
    font-weight: 500;
}

.glass-input {
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    border-radius: 6px !important;
    backdrop-filter: blur(10px);
    font-size: 0.85rem !important;
}

.glass-input:focus {
    background: rgba(255, 255, 255, 0.18) !important;
    border-color: rgba(255, 255, 255, 0.35) !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
}

.glass-input::placeholder {
    color: rgba(255, 255, 255, 0.5) !important;
}

.glass-btn {
    background: linear-gradient(135deg, #4dabf7 0%, #339af0 100%) !important;
    border: none !important;
    border-radius: 6px !important;
    padding: 8px 16px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 3px 10px rgba(51, 154, 240, 0.3) !important;
    color: white !important;
}

.glass-btn:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 5px 15px rgba(51, 154, 240, 0.4) !important;
    background: linear-gradient(135deg, #339af0 0%, #228be6 100%) !important;
}

.glass-form-group label {
    font-weight: 500;
    margin-bottom: 0.4rem;
    display: block;
    color: #e9ecef !important;
}

.glass-summary {
    background: rgba(0, 0, 0, 0.25);
    border-radius: 8px;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-input-sm {
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border-radius: 4px !important;
    padding: 4px 6px !important;
    font-size: 0.8rem !important;
    text-align: center !important;
    width: 80px !important;
    backdrop-filter: blur(10px);
    font-weight: 500;
}

.modal-input-sm:focus {
    background: rgba(255, 255, 255, 0.18) !important;
    border-color: rgba(255, 255, 255, 0.35) !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1) !important;
    color: white !important;
}

.modal-table-container {
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.batch-select {
    min-width: 200px;
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border-radius: 4px !important;
    padding: 4px 8px !important;
    font-size: 0.8rem !important;
    max-width: 100%;
}

.batch-select option {
    background: rgba(30, 35, 45, 0.95) !important;
    color: white !important;
    padding: 8px !important;
    font-size: 0.85rem !important;
}

.qty-input, .modal-input-sm {
    font-family: monospace;
    font-weight: 500;
}

.qty-input:focus, .modal-input-sm:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(77, 171, 247, 0.4);
}

.transfer-item-qty input {
    width: 100px;
    text-align: center;
    font-size: 0.8rem;
    padding: 0.15rem 0.3rem;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.batch-badge-main {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    margin: 1px;
    white-space: nowrap;
}

.text-center {
    text-align: center !important;
}

.glass-tbody .stock-number {
    font-family: 'Segoe UI', monospace;
    font-weight: 600;
    color: #a5d8ff !important;
}

.btn-outline-light {
    border-color: rgba(255, 255, 255, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 0.85rem !important;
}

.btn-outline-light:hover {
    background-color: rgba(255, 255, 255, 0.15) !important;
    color: white !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
}

.modal-table-container .sticky-top {
    position: sticky;
    top: 0;
    z-index: 1020;
}

.modal-table-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.modal-table-container::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.modal-table-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}

.modal-table-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

.form-control-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.glass-summary .badge {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

@media (max-width: 1200px) {
    .frosted-glass {
        margin: 10px;
    }
    .modal-table-container {
        max-height: 350px;
    }
}

.glass-tbody td:nth-child(3) {
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.glass-tbody td:nth-child(3):hover {
    white-space: normal;
    overflow: visible;
    background: rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 10;
}

.readonly-input {
    background-color: #f8f9fa !important;
    border: 1px solid #ced4da !important;
    color: #495057 !important;
    cursor: not-allowed !important;
    user-select: none !important;
}

.readonly-input:focus {
    box-shadow: none !important;
    border-color: #ced4da !important;
}

.requested-qty-badge {
    background-color: #28a745 !important;
    color: white !important;
    font-size: 0.7rem;
    padding: 2px 5px;
    margin-right: 3px;
}

/* Custom input untuk format angka Indonesia */
.angka-input {
    text-align: center;
    font-family: monospace;
    font-weight: 500;
}

/* Style untuk input modal yang menerima angka dengan koma */
.input-with-comma::-webkit-inner-spin-button,
.input-with-comma::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.input-with-comma {
    -moz-appearance: textfield;
    appearance: textfield;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    let transferItems = [];
    let isDragging = false;
    let selectedItems = new Set();

    // Helper function untuk format angka sesuai dengan permintaan
    function formatAngka(num, decimalDigits = 2) {
        if (typeof num === 'string') {
            num = parseFloat(num);
        }

        if (isNaN(num)) return '0';

        // Cek apakah bilangan bulat
        if (num % 1 === 0) {
            // Bilangan bulat: gunakan titik sebagai pemisah ribuan
            return num.toLocaleString('id-ID');
        } else {
            // Bilangan desimal: maksimal 2 digit di belakang koma
            return num.toLocaleString('id-ID', {
                minimumFractionDigits: decimalDigits,
                maximumFractionDigits: decimalDigits
            });
        }
    }

    // Helper function untuk parse angka dari input
    function parseAngka(str) {
        if (!str || str.trim() === '') return 0;

        // Ganti titik sebagai pemisah ribuan dengan kosong
        // Ganti koma sebagai pemisah desimal dengan titik
        let cleaned = str.replace(/\./g, '').replace(',', '.');
        return parseFloat(cleaned) || 0;
    }

    // Function untuk mendapatkan batch info dari badge di tabel
    function getBatchInfoFromBadges(row) {
        const badges = row.querySelectorAll('.batch-badge');
        const batchInfo = [];

        badges.forEach(function(badge) {
            const batch = badge.dataset.batch || '';
            const sloc = badge.dataset.sloc || '';
            const qty = parseFloat(badge.dataset.qty || 0);

            if (sloc && qty > 0) {
                batchInfo.push({
                    batch: batch,
                    sloc: sloc,
                    qty: qty
                });
            }
        });

        return batchInfo;
    }

    // Function untuk membuat opsi batch dropdown dari batch info
    function createBatchOptions(batchInfo, selectedBatch) {
        if (!selectedBatch) selectedBatch = '';
        if (!batchInfo || batchInfo.length === 0) {
            return '<option value="">No batch/sloc</option>';
        }

        let options = '';
        batchInfo.forEach(function(batch, index) {
            const batchValue = batch.batch || batch.sloc || 'BATCH' + (index + 1);
            const batchQty = batch.qty || 0;
            const batchSloc = batch.sloc || batchValue;
            const displayQty = formatAngka(batchQty);
            const batchLabel = 'SLOC:' + batchSloc + ' | Batch:' + batchValue + ' | Qty:' + displayQty;
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

    // Setup drag and drop events
    function setupDragAndDrop() {
        const rows = document.querySelectorAll('.draggable-row');

        rows.forEach(function(row) {
            const availableStock = parseFloat(row.dataset.availableStock || 0);

            if (availableStock > 0) {
                row.draggable = true;

                row.addEventListener('dragstart', function(e) {
                    isDragging = true;
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
                    isDragging = false;
                    this.classList.remove('dragging');
                });
            } else {
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
                addSelectedItemsToTransfer();
            } else if (data) {
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
            showToast(addedCount + ' item ditambahkan ke transfer list', 'success');
            clearSelection();
        } else {
            showToast('Tidak ada item baru yang ditambahkan', 'warning');
        }
    }

    // Function untuk menambahkan item ke transfer list
    function addItemById(itemId) {
        const itemRow = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
        if (!itemRow) {
            showToast('Item tidak ditemukan', 'error');
            return;
        }

        const itemData = getItemDataFromRow(itemRow);

        if (transferItems.some(function(transferItem) { return transferItem.id === itemData.id; })) {
            showToast('Item sudah ada di transfer list', 'warning');
            return;
        }

        if (itemData.availableStock <= 0) {
            showToast('Item tidak memiliki stock yang tersedia', 'error');
            return;
        }

        addItemToTransferByData(itemData);
    }

    // Function untuk mendapatkan data dari row
    function getItemDataFromRow(rowElement) {
        const row = rowElement;

        // Ambil batch info dari badge
        const batchInfo = getBatchInfoFromBadges(row);

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

    // Function untuk menambahkan item ke transfer list
    function addItemToTransferByData(item) {
        const defaultPlant = "{{ $document->plant }}";
        const defaultSloc = "{{ $document->sloc_supply }}";

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
            qty: 0, // Akan diisi di modal
            unit: item.unit,
            sloc: item.sloc,
            batchInfo: item.batchInfo,
            selectedBatch: selectedBatch,
            batchQty: batchQty,
            batchSloc: batchSloc,
            plantTujuan: defaultPlant,
            slocTujuan: defaultSloc,
            formattedMaxQty: formatAngka(item.availableStock),
            formattedRequestedQty: formatAngka(item.requestedQty)
        };

        transferItems.push(transferItem);

        // Render transfer item
        renderTransferItem(transferItem);

        // Update transfer count
        updateTransferCount();

        // Tandai row yang sudah dipilih
        const row = document.querySelector('.draggable-row[data-item-id="' + item.id + '"]');
        if (row) {
            row.classList.add('transfer-item-selected');
        }
    }

    // Function untuk render transfer item - DIPERBAIKI untuk menampilkan badge
    function renderTransferItem(item) {
        const emptyState = document.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }

        const slocArray = item.sloc ? item.sloc.split(',') : [];
        const firstSloc = slocArray.length > 0 ? slocArray[0] : '';

        const itemDiv = document.createElement('div');
        itemDiv.className = 'transfer-item';
        itemDiv.dataset.itemId = item.id;

        // Tampilkan badge requested qty (hijau) dan max stock (kuning)
        itemDiv.innerHTML = '<div class="transfer-item-header">' +
            '<div>' +
            '<span class="transfer-item-code">' + item.materialCode + '</span>' +
            (firstSloc ? '<span class="badge sloc-badge">' + firstSloc + '</span>' : '') +
            '</div>' +
            '<span class="transfer-item-remove">' +
            '<i class="fas fa-times"></i>' +
            '</span>' +
            '</div>' +
            '<div class="transfer-item-desc">' + item.materialDesc + '</div>' +
            '<div class="transfer-item-qty">' +
            '<span class="badge requested-qty-badge">Requested: ' + item.formattedRequestedQty + '</span>' +
            '<span class="badge max-stock-badge">Max: ' + item.formattedMaxQty + '</span>' +
            '<span class="text-muted ms-1">' + item.unit + '</span>' +
            '</div>';

        // Tambahkan event listener untuk tombol remove
        const removeBtn = itemDiv.querySelector('.transfer-item-remove');
        removeBtn.addEventListener('click', function() {
            removeTransferItem(item.id);
        });

        document.getElementById('transferSlots').appendChild(itemDiv);
    }

    // Function untuk menghapus transfer item
    function removeTransferItem(itemId) {
        transferItems = transferItems.filter(function(item) { return item.id !== itemId; });

        const itemElement = document.querySelector('.transfer-item[data-item-id="' + itemId + '"]');
        if (itemElement) {
            itemElement.remove();
        }

        const row = document.querySelector('.draggable-row[data-item-id="' + itemId + '"]');
        if (row) {
            row.classList.remove('transfer-item-selected');
        }

        updateTransferCount();

        if (transferItems.length === 0) {
            showEmptyState();
        }

        showToast('Item dihapus dari transfer list', 'info');
    }

    // Function untuk update transfer count
    function updateTransferCount() {
        const totalItems = transferItems.length;
        document.getElementById('transferCount').textContent = totalItems;
    }

    // Function untuk show empty state
    function showEmptyState() {
        const transferSlots = document.getElementById('transferSlots');
        transferSlots.innerHTML =
            '<div class="empty-state text-center text-muted py-5">' +
            '<i class="fas fa-arrow-left fa-2x mb-3"></i>' +
            '<p>Drag items here</p>' +
            '<small class="fs-7">Only items with available stock</small>' +
            '</div>';
    }

    // Function untuk update seleksi count
    function updateSelectionCount() {
        const count = selectedItems.size;
        document.getElementById('selectedCount').textContent = count + ' item terpilih';

        const clearBtn = document.getElementById('clearSelection');
        if (count > 0) {
            clearBtn.classList.add('active');
            clearBtn.title = 'Clear ' + count + ' selected items';
            document.querySelectorAll('.draggable-row').forEach(function(row) {
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
            document.querySelectorAll('.draggable-row').forEach(function(row) {
                row.classList.remove('row-selected');
            });
        }

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
        document.querySelectorAll('.row-select').forEach(function(cb) {
            cb.checked = false;
        });
        updateSelectionCount();
        showToast('Pilihan berhasil dihapus', 'success');
    }

    // Function untuk show toast
    function showToast(message, type) {
        if (!type) type = 'info';
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        const bgClass = type === 'error' ? 'danger' : type;
        toast.className = 'toast align-items-center text-bg-' + bgClass + ' border-0';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        const iconClass = type === 'success' ? 'fa-check-circle' :
                         type === 'warning' ? 'fa-exclamation-triangle' :
                         type === 'error' ? 'fa-times-circle' : 'fa-info-circle';

        toast.innerHTML =
            '<div class="d-flex">' +
                '<div class="toast-body">' +
                    '<i class="fas ' + iconClass + ' me-2"></i>' +
                    message +
                '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>';

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }

    // Setup checkbox selection
    function setupCheckboxSelection() {
        document.getElementById('selectAllCheckbox').addEventListener('click', function(e) {
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

        document.getElementById('selectAllHeader').addEventListener('click', function(e) {
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

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-select')) {
                const itemId = e.target.dataset.itemId;
                if (e.target.checked) {
                    selectedItems.add(itemId);
                } else {
                    selectedItems.delete(itemId);
                    document.getElementById('selectAllCheckbox').checked = false;
                    document.getElementById('selectAllHeader').checked = false;
                }
                updateSelectionCount();
            }
        });

        document.getElementById('clearSelection').addEventListener('click', function() {
            if (selectedItems.size > 0) {
                clearSelection();
            }
        });

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
            transferItems.forEach(function(item) {
                const row = document.querySelector('.draggable-row[data-item-id="' + item.id + '"]');
                if (row) {
                    row.classList.remove('transfer-item-selected');
                }
            });

            transferItems = [];
            document.getElementById('transferSlots').innerHTML = '';
            updateTransferCount();
            showEmptyState();
            showToast('Transfer list berhasil dibersihkan', 'info');
        }
    });

    // Function untuk populate modal preview
    function populateTransferPreviewModal() {
        const tbody = document.querySelector('#transferPreviewTable tbody');
        tbody.innerHTML = '';

        let totalItems = transferItems.length;

        transferItems.forEach(function(item, index) {
            // Buat opsi dropdown batch
            const batchOptions = createBatchOptions(item.batchInfo, item.selectedBatch);

            // Buat row untuk tabel modal
            const row = document.createElement('tr');
            row.innerHTML =
                '<td class="text-center" style="padding: 6px 4px;">' + (index + 1) + '</td>' +
                '<td style="padding: 6px 4px;"><code>' + item.materialCode + '</code></td>' +
                '<td style="padding: 6px 4px;" title="' + item.materialDesc + '">' + (item.materialDesc.length > 30 ? item.materialDesc.substring(0, 30) + '...' : item.materialDesc) + '</td>' +
                '<td class="text-center stock-number" style="padding: 6px 4px;">' + formatAngka(item.requestedQty) + '</td>' +
                '<td class="text-center stock-number" style="padding: 6px 4px;">' + formatAngka(item.availableStock) + '</td>' +
                '<td class="text-center" style="padding: 6px 4px;">' +
                    '<input type="text" class="form-control modal-input-sm qty-transfer-input angka-input input-with-comma" value="" placeholder="0" data-index="' + index + '" style="width: 80px; font-size: 0.8rem;">' +
                '</td>' +
                '<td class="text-center" style="padding: 6px 4px;">' + item.unit + '</td>' +
                '<td class="text-center" style="padding: 6px 4px;">' +
                    '<input type="text" class="form-control modal-input-sm plant-tujuan-input" value="' + (item.plantTujuan || '{{ $document->plant }}') + '" data-index="' + index + '" style="width: 70px; font-size: 0.8rem;">' +
                '</td>' +
                '<td class="text-center" style="padding: 6px 4px;">' +
                    '<input type="text" class="form-control modal-input-sm sloc-tujuan-input" value="' + (item.slocTujuan || '{{ $document->sloc_supply }}') + '" data-index="' + index + '" style="width: 70px; font-size: 0.8rem;">' +
                '</td>' +
                '<td class="text-center" style="padding: 6px 4px;">' +
                    '<select class="form-control batch-select batch-source-select" data-index="' + index + '" style="width: 200px; font-size: 0.8rem; padding: 4px 6px;">' +
                        batchOptions +
                    '</select>' +
                '</td>';

            tbody.appendChild(row);
        });

        // Update totals
        document.getElementById('modalTotalItems').textContent = totalItems;
        document.getElementById('modalTotalQty').textContent = formatAngka(0);

        // Setup event listeners untuk input di modal
        setupModalEventListeners();
    }

    // Helper function untuk mendapatkan max quantity berdasarkan batch yang dipilih
    function getMaxQtyForModalItem(index) {
        const item = transferItems[index];
        let maxQty = item.availableStock;

        if (item.selectedBatch && item.batchInfo && item.batchInfo.length > 0) {
            const selectedBatchInfo = item.batchInfo.find(function(batch) {
                return batch.batch === item.selectedBatch || batch.sloc === item.selectedBatch;
            });
            if (selectedBatchInfo) {
                maxQty = selectedBatchInfo.qty || 0;
            }
        }

        return maxQty;
    }

    // Setup event listeners untuk input di modal
    function setupModalEventListeners() {
        // Quantity input change - menggunakan event blur
        document.querySelectorAll('.qty-transfer-input').forEach(function(input) {
            // Format saat kehilangan fokus
            input.addEventListener('blur', function() {
                const index = parseInt(this.dataset.index);
                let value = this.value.trim();

                // Parse angka
                let parsedValue = parseAngka(value);

                // Validasi
                const maxQty = getMaxQtyForModalItem(index);

                if (isNaN(parsedValue) || parsedValue < 0) {
                    this.value = '';
                    transferItems[index].qty = 0;
                } else if (parsedValue > maxQty) {
                    this.value = formatAngka(maxQty);
                    transferItems[index].qty = maxQty;
                    showToast('Quantity tidak boleh melebihi ' + formatAngka(maxQty), 'warning');
                } else {
                    // Format ulang dengan format Indonesia
                    this.value = formatAngka(parsedValue);
                    transferItems[index].qty = parsedValue;
                }

                updateModalTotals();
            });

            // Validasi input real-time (hanya angka, titik, dan koma)
            input.addEventListener('input', function() {
                // Hapus karakter selain angka, titik, dan koma
                this.value = this.value.replace(/[^\d.,]/g, '');
            });

            // Format saat fokus (tampilkan angka tanpa format)
            input.addEventListener('focus', function() {
                const index = parseInt(this.dataset.index);
                if (transferItems[index].qty > 0) {
                    // Tampilkan angka tanpa format (dengan titik sebagai desimal untuk parsing)
                    this.value = transferItems[index].qty.toString().replace('.', ',');
                }
            });
        });

        // Plant tujuan change
        document.querySelectorAll('.plant-tujuan-input').forEach(function(input) {
            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                transferItems[index].plantTujuan = this.value;
            });
        });

        // SLOC tujuan change
        document.querySelectorAll('.sloc-tujuan-input').forEach(function(input) {
            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                transferItems[index].slocTujuan = this.value;
            });
        });

        // Batch select change
        document.querySelectorAll('.batch-source-select').forEach(function(select) {
            select.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                const selectedValue = this.value;
                const selectedOption = this.options[this.selectedIndex];
                const batchQty = parseFloat(selectedOption.dataset.qty) || 0;
                const batchSloc = selectedOption.dataset.sloc || selectedValue;

                transferItems[index].selectedBatch = selectedValue;
                transferItems[index].batchQty = batchQty;
                transferItems[index].batchSloc = batchSloc;

                // Dapatkan input quantity terkait
                const qtyInput = document.querySelector('.qty-transfer-input[data-index="' + index + '"]');
                const maxQty = getMaxQtyForModalItem(index);

                if (qtyInput) {
                    // Jika quantity melebihi batch qty, adjust it
                    const currentQty = parseAngka(qtyInput.value);
                    if (currentQty > maxQty && maxQty > 0) {
                        qtyInput.value = formatAngka(maxQty);
                        transferItems[index].qty = maxQty;
                        showToast('Quantity disesuaikan dengan batch yang tersedia: ' + formatAngka(maxQty), 'info');
                        updateModalTotals();
                    }
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

        document.getElementById('modalTotalQty').textContent = formatAngka(totalQty);
    }

    // Generate transfer list button
    document.getElementById('generateTransferList').addEventListener('click', function() {
        if (transferItems.length === 0) {
            showToast('Silakan tambahkan item ke transfer list terlebih dahulu', 'warning');
            return;
        }

        // Populate modal
        populateTransferPreviewModal();

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('transferPreviewModal'));
        modal.show();
    });

    // Confirm transfer button
    document.getElementById('confirmTransfer').addEventListener('click', function() {
        const remarks = document.getElementById('transferRemarks').value;

        // Validasi: Pastikan semua quantity valid
        let isValid = true;
        let errorMessage = '';

        transferItems.forEach(function(item, index) {
            if (!item.qty || item.qty <= 0) {
                isValid = false;
                errorMessage = 'Quantity untuk ' + item.materialCode + ' harus diisi';
                return;
            }

            const maxQty = getMaxQtyForModalItem(index);
            if (item.qty > maxQty) {
                isValid = false;
                errorMessage = 'Quantity untuk ' + item.materialCode + ' (' + formatAngka(item.qty) + ') melebihi available stock untuk batch yang dipilih (' + formatAngka(maxQty) + ')';
                return;
            }
        });

        if (!isValid) {
            showToast(errorMessage, 'error');
            return;
        }

        // Prepare data untuk API call
        const transferData = {
            document_id: {{ $document->id }},
            document_no: "{{ $document->document_no }}",
            plant: "{{ $document->plant }}",
            sloc_supply: "{{ $document->sloc_supply }}",
            items: transferItems.map(function(item) {
                return {
                    id: item.id,
                    material_code: item.materialCode,
                    material_desc: item.materialDesc,
                    requested_qty: item.requestedQty,
                    available_stock: item.availableStock,
                    quantity: item.qty,
                    unit: item.unit,
                    plant_tujuan: item.plantTujuan,
                    sloc_tujuan: item.slocTujuan,
                    batch: item.selectedBatch,
                    batch_qty: item.batchQty,
                    batch_sloc: item.batchSloc,
                    batch_info: item.batchInfo
                };
            }),
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
        .then(function(response) { return response.json(); })
        .then(function(data) {
            loadingModal.hide();

            if (data.success) {
                showToast('Transfer document berhasil dibuat!', 'success');

                // Close modal
                const transferModal = bootstrap.Modal.getInstance(document.getElementById('transferPreviewModal'));
                transferModal.hide();

                // Clear transfer list
                document.getElementById('clearTransferList').click();

                // Optionally redirect atau refresh
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        })
        .catch(function(error) {
            loadingModal.hide();
            console.error('Error:', error);
            showToast('Error creating transfer document', 'error');
        });
    });

    // Show loading animation pada form submit
    document.getElementById('checkStockForm').addEventListener('submit', function(e) {
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
        document.getElementById('loadingText').textContent = 'Checking Stock...';
    });

    // Handle reset stock form submission - SEDERHANA
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

            const token = resetStockForm.querySelector('input[name="_token"]').value;

            fetch(resetStockForm.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                loadingModal.hide();

                if (data.success) {
                    showToast(data.message, 'success');
                    // Auto refresh halaman
                    window.location.reload();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(function(error) {
                loadingModal.hide();
                console.error('Error:', error);
                showToast('Error resetting stock data.', 'error');
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
