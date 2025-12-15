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
                        <div class="col-md-4">
                            <table class="table table-borderless mb-0">
                                <tr class="py-1">
                                    <th class="w-50 py-1">Document No:</th>
                                    <td class="py-1"><h5 class="mb-0 {{ $document->plant == '3000' ? 'text-primary' : 'text-success' }}">{{ $document->document_no }}</h5></td>
                                </tr>
                                <tr class="py-1">
                                    <th class="w-50 py-1">Created By:</th>
                                    <td class="py-1">{{ $document->created_by_name ?? $document->created_by }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Kolom Tengah: Plant dan SLOC Supply -->
                        <div class="col-md-4">
                            <table class="table table-borderless mb-0">
                                <tr class="py-1">
                                    <th class="w-50 py-1">Plant:</th>
                                    <td class="py-1"><span class="badge bg-info">{{ $document->plant }}</span></td>
                                </tr>
                                <tr class="py-1">
                                    <th class="w-50 py-1">SLOC Supply:</th>
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
                        <div class="col-md-4">
                            <table class="table table-borderless mb-0">
                                <tr class="py-1">
                                    <th class="w-50 py-1">Created Date:</th>
                                    <td class="py-1">{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') }} WIB</td>
                                </tr>
                                <tr class="py-1">
                                    <th class="w-50 py-1">Last Updated:</th>
                                    <td class="py-1">{{ \Carbon\Carbon::parse($document->updated_at)->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') }} WIB</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Items with Stock Information -->
            <div class="card">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="
                    background: linear-gradient(90deg, #4CAF50 0%, #2196F3 100%);
                    padding: 10px 15px;
                    min-height: 60px;
                ">
                    <h5 class="mb-0" style="
                        text-align: left;
                        margin: 0;
                        line-height: 1.2;
                    ">
                        Document Items with Stock Availability
                    </h5>
                    <div class="d-flex align-items-center">
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="background-color: #f8f9fa;">#</th>
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
                            <tbody>
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
                                                // Try to decode JSON string
                                                $decoded = json_decode($item->sources, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    $sources = $decoded;
                                                } elseif (!empty($item->sources)) {
                                                    // If not JSON, treat as comma-separated string
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
                                    @endphp
                                    <tr>
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
                                                {{ \App\Helpers\NumberHelper::formatStockNumber($totalStock) }}
                                            @else
                                                <span class="text-muted">-</span>
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
                </div>
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
</style>

<!-- Add JavaScript for auto-hide alerts and loader animation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Initialize Lottie animation
    let animation;

    // Function to load Lottie animation
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

    // Show loading animation on form submit
    document.getElementById('checkStockForm').addEventListener('submit', function(e) {
        // Show the loading modal
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();

        // Set loading text
        document.getElementById('loadingText').textContent = 'Checking Stock...';

        // Load and play animation
        setTimeout(function() {
            loadLottieAnimation();
            animation.play();
        }, 100);
    });

    // Hide loading animation when modal is hidden
    document.getElementById('loadingModal').addEventListener('hidden.bs.modal', function () {
        if (animation) {
            animation.stop();
            animation.destroy();
            animation = null;
        }
    });

    // Handle reset stock form submission
    const resetStockForm = document.getElementById('resetStockForm');
    if (resetStockForm) {
        resetStockForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to reset stock data?')) {
                return;
            }

            // Show the loading modal
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();

            // Change loading text
            document.getElementById('loadingText').textContent = 'Resetting Stock...';

            // Load and play animation
            setTimeout(function() {
                if (!animation) {
                    loadLottieAnimation();
                }
                animation.play();
            }, 100);

            // Get the CSRF token
            const token = resetStockForm.querySelector('input[name="_token"]').value;

            // Send AJAX request
            fetch(resetStockForm.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
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
                // Hide loading modal
                loadingModal.hide();

                if (data.success) {
                    // Show success message
                    alert(data.message);
                    // Refresh the page after a short delay
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
});
</script>
@endsection
