@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Documents</a></li>
                    <li class="breadcrumb-item active">Document Detail</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Reservation Document</h2>
                <div>
                    <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('documents.print', $document->id) }}"
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-print"></i> Print
                    </a>
                    <a href="{{ route('documents.pdf', $document->id) }}"
                       class="btn btn-danger" target="_blank">
                        <i class="fas fa-file-pdf"></i> Export as PDF/Print
                    </a>
                </div>
            </div>

            <!-- Tambahkan alert info jika ada -->
            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Document Header -->
            <div class="card mb-4">
                <div class="card-header {{ $document->plant == '3000' ? 'bg-primary' : 'bg-success' }} text-white">
                    <h5 class="mb-0">Document Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Document No:</th>
                                    <td><h4 class="{{ $document->plant == '3000' ? 'text-primary' : 'text-success' }}">{{ $document->document_no }}</h4></td>
                                </tr>
                                <tr>
                                    <th>Plant:</th>
                                    <td><span class="badge bg-info">{{ $document->plant }}</span></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if($document->status == 'created')
                                            <span class="badge bg-warning">Created</span>
                                        @elseif($document->status == 'posted')
                                            <span class="badge bg-success">Posted</span>
                                        @else
                                            <span class="badge bg-danger">Cancelled</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-3">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Total Items:</th>
                                    <td>{{ $document->total_items }}</td>
                                </tr>
                                <tr>
                                    <th>Total Quantity:</th>
                                    <td><strong>{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Created Date:</th>
                                    <td>{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') }} WIB</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-3">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Created By:</th>
                                    <td>{{ $document->created_by_name }}</td>
                                </tr>
                                <tr>
                                    <th>Creator ID:</th>
                                    <td>{{ $document->created_by }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>{{ \Carbon\Carbon::parse($document->updated_at)->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') }} WIB</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-light">
                                <h6><i class="fas fa-info-circle"></i> Document Note</h6>
                                @if($document->remarks)
                                    <p class="mb-0">{{ $document->remarks }}</p>
                                @else
                                    <p class="text-muted mb-0">No remarks</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Document Items ({{ $document->items->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material Code</th>
                                    <th>Description</th>
                                    <th>SORTF</th>
                                    <th>Unit</th>
                                    <th class="text-end">Requested Qty</th>
                                    <th>Source PRO Numbers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><code>{{ $item->material_code }}</code></td>
                                        <td>{{ $item->material_description }}</td>
                                        <td>{{ $item->sortf ?? '-' }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td class="text-end"><strong>{{ \App\Helpers\NumberHelper::formatQuantity($item->requested_qty) }}</strong></td>
                                        <td>
                                            @if(!empty($item->processed_sources))
                                                @foreach($item->processed_sources as $source)
                                                    <span class="badge bg-info me-1 mb-1">{{ $source }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No sources</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- PRO Details (Hanya di tampilan browser, tidak di print) -->
                    @if($document->items->where('processed_pro_details', '!=', null)->count() > 0)
                    <div class="accordion mt-4" id="proDetailsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapseProDetails">
                                    <i class="fas fa-list-alt me-2"></i> View PRO Details
                                </button>
                            </h2>
                            <div id="collapseProDetails" class="accordion-collapse collapse"
                                 data-bs-parent="#proDetailsAccordion">
                                <div class="accordion-body">
                                    @foreach($document->items as $item)
                                        @if(!empty($item->processed_pro_details))
                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h6 class="mb-0">{{ $item->material_code }} - {{ $item->material_description }}</h6>
                                                    @if($item->sortf)
                                                        <small class="text-muted">SORTF: {{ $item->sortf }}</small>
                                                    @endif
                                                </div>
                                                <div class="card-body">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>PRO Number</th>
                                                                <th class="text-end">Quantity</th>
                                                                <th>Reservation No</th>
                                                                <th>Plant</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($item->processed_pro_details as $detail)
                                                                <tr>
                                                                    <td>{{ $detail['pro_number'] ?? 'N/A' }}</td>
                                                                    <td class="text-end">{{ \App\Helpers\NumberHelper::formatQuantity($detail['qty'] ?? 0) }}</td>
                                                                    <td>{{ $detail['reservation_no'] ?? 'N/A' }}</td>
                                                                    <td>{{ $detail['plant'] ?? 'N/A' }}</td>
                                                                </tr>
                                                            @endforeach
                                                            <tr class="table-light">
                                                                <td><strong>Total</strong></td>
                                                                <td class="text-end"><strong>{{ \App\Helpers\NumberHelper::formatQuantity($item->requested_qty) }}</strong></td>
                                                                <td colspan="2"></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
