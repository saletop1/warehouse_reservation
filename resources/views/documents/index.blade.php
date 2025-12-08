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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Reservation Documents</h2>
                <a href="{{ route('reservations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Create New
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-lg mb-3 floating-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-lg me-3"></i>
                        <div>
                            <strong class="d-block">Success!</strong>
                            <small class="d-block mt-1">{{ session('success') }}</small>
                            <small class="d-block mt-1">
                                <a href="{{ route('reservations.create') }}" class="btn btn-sm btn-success mt-2">
                                    <i class="fas fa-plus-circle"></i> Go to Create Reservation
                                </a>
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                {{-- Set session flag untuk create page --}}
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

            {{-- Filter Form --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filter Documents</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('documents.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="document_no" class="form-label">Document No</label>
                                    <input type="text" class="form-control" id="document_no"
                                           name="document_no" value="{{ request('document_no') }}"
                                           placeholder="RSMG000001">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="plant" class="form-label">Plant</label>
                                    <select class="form-control" id="plant" name="plant">
                                        <option value="">All Plants</option>
                                        <option value="3000" {{ request('plant') == '3000' ? 'selected' : '' }}>3000</option>
                                        <option value="4000" {{ request('plant') == '4000' ? 'selected' : '' }}>4000</option>
                                        <option value="5000" {{ request('plant') == '5000' ? 'selected' : '' }}>5000</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="created" {{ request('status') == 'created' ? 'selected' : '' }}>Created</option>
                                        <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from"
                                           name="date_from" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to"
                                           name="date_to" value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Documents Table --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Reservation Documents</h5>
                        <small class="text-muted">Total: {{ $documents->total() }} documents</small>
                    </div>
                    <div>
                        <a href="{{ route('documents.export', ['type' => 'csv']) }}" class="btn btn-sm btn-success">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="{{ route('documents.export.pdf') }}"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('PDF export will generate CSV instead. Continue?')">
                            <i class="fas fa-file-pdf"></i> Export as CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Document No</th>
                                    <th>Plant</th>
                                    <th>Status</th>
                                    <th>Total Items</th>
                                    <th>Total Qty</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $document)
                                    <tr>
                                        <td>
                                            <strong class="{{ $document->plant == '3000' ? 'text-primary' : 'text-success' }}">
                                                {{ $document->document_no }}
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $document->plant }}</span>
                                        </td>
                                        <td>
                                            @if($document->status == 'created')
                                                <span class="badge bg-warning">Created</span>
                                            @elseif($document->status == 'posted')
                                                <span class="badge bg-success">Posted</span>
                                            @else
                                                <span class="badge bg-danger">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>{{ $document->total_items }}</td>
                                        <td class="text-end">{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</td>
                                        <td>{{ $document->created_by_name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
                                        <td>
                                            <a href="{{ route('documents.show', $document->id) }}"
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('documents.print', $document->id) }}"
                                               class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="{{ route('documents.pdf', $document->id) }}"
                                               class="btn btn-sm btn-danger" title="Export PDF" target="_blank">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fas fa-file-alt fa-3x mb-3"></i>
                                            <h5>No documents found</h5>
                                            <p>Click "Create New" to create your first reservation document.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($documents->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Menampilkan {{ $documents->firstItem() }} sampai {{ $documents->lastItem() }}
                                dari {{ $documents->total() }} dokumen
                            </div>
                            <div>
                                {{ $documents->appends(request()->except('page'))->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
