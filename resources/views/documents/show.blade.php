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
                    @if($document->status == 'created')
                        <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    @endif
                    <a href="{{ route('documents.pdf', $document->id) }}?autoPrint=true"
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-print"></i> Print Document
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
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Created Date:</th>
                                    <td>{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') }} WIB</td>
                                </tr>
                                <tr>
                                    <th>Created By:</th>
                                    <td>{{ $document->created_by_name }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-light mb-0">
                                <h6><i class="fas fa-info-circle"></i> Document Note</h6>
                                @if($document->remarks && trim($document->remarks) != '')
                                    <p class="mb-0" style="white-space: pre-wrap;">{{ $document->remarks }}</p>
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
                    <h5 class="mb-0">Document Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material Code</th>
                                    <th>Description</th>
                                    <th>Add Info</th>
                                    <th class="text-center">Requested Qty</th>
                                    <th>Uom</th>
                                    <th>Source PRO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->items as $index => $item)
                                    @php
                                        // Format material code: remove leading zeros if numeric
                                        $materialCode = $item->material_code;
                                        if (ctype_digit($materialCode)) {
                                            $materialCode = ltrim($materialCode, '0');
                                        }

                                        // Convert unit: if ST then PC
                                        $unit = $item->unit == 'ST' ? 'PC' : $item->unit;

                                        // Get processed sources (already handled in controller)
                                        $sources = $item->processed_sources ?? [];

                                        // PERBAIKAN: Gunakan null coalescing untuk sortf
                                        $addInfo = $item->sortf ?? '-';
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><code>{{ $materialCode }}</code></td>
                                        <td>{{ $item->material_description }}</td>
                                        <td>{{ $addInfo }}</td>
                                        <td class="text-center"><strong>{{ \App\Helpers\NumberHelper::formatQuantity($item->requested_qty) }}</strong></td>
                                        <td>{{ $unit }}</td>
                                        <td>
                                            @if(!empty($sources))
                                                @foreach($sources as $source)
                                                    <span class="badge bg-info me-1 mb-1">{{ $source }}</span>
                                                @endforeach
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
@endsection
