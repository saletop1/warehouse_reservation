@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Dashboard</h2>
            <p class="text-muted">Welcome back, {{ Auth::user()->name }}!</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Reservations</h6>
                            <h3 class="mb-0">{{ \Illuminate\Support\Facades\DB::table('sap_reservations')->count() }}</h3>
                        </div>
                        <div>
                            <i class="fas fa-database fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Documents Created</h6>
                            <h3 class="mb-0">{{ \App\Models\ReservationDocument::count() }}</h3>
                        </div>
                        <div>
                            <i class="fas fa-file-alt fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Materials</h6>
                            <h3 class="mb-0">{{ \Illuminate\Support\Facades\DB::table('sap_reservations')->distinct('matnr')->count('matnr') }}</h3>
                        </div>
                        <div>
                            <i class="fas fa-boxes fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Quantity</h6>
                            <h3 class="mb-0">{{ number_format(\Illuminate\Support\Facades\DB::table('sap_reservations')->sum('psmng'), 0) }}</h3>
                        </div>
                        <div>
                            <i class="fas fa-weight fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('reservations.create') }}" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                Create New Reservation
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('reservations.index') }}" class="btn btn-success w-100 py-3">
                                <i class="fas fa-list fa-2x mb-2"></i><br>
                                View Reservations
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('documents.index') }}" class="btn btn-info w-100 py-3">
                                <i class="fas fa-file-alt fa-2x mb-2"></i><br>
                                View Documents
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('reservations.index') }}?sync=true" class="btn btn-warning w-100 py-3">
                                <i class="fas fa-sync fa-2x mb-2"></i><br>
                                Sync from SAP
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Documents -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Documents</h5>
                </div>
                <div class="card-body">
                    @php
                        $recentDocuments = \App\Models\ReservationDocument::orderBy('created_at', 'desc')
                            ->limit(10)
                            ->get();
                    @endphp

                    @if($recentDocuments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Document No</th>
                                        <th>Plant</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentDocuments as $doc)
                                        <tr>
                                            <td>
                                                <a href="{{ route('documents.show', $doc->id) }}" class="{{ $doc->plant == '3000' ? 'text-primary' : 'text-success' }} fw-bold">
                                                    {{ $doc->document_no }}
                                                </a>
                                            </td>
                                            <td><span class="badge bg-info">{{ $doc->plant }}</span></td>
                                            <td>
                                                @if($doc->status == 'created')
                                                    <span class="badge bg-warning">Created</span>
                                                @elseif($doc->status == 'posted')
                                                    <span class="badge bg-success">Posted</span>
                                                @else
                                                    <span class="badge bg-danger">Cancelled</span>
                                                @endif
                                            </td>
                                            <td class="text-start">
                                                @if($doc->remarks && trim($doc->remarks) != '')
                                                    <div class="remarks-text" data-bs-toggle="tooltip" title="{{ $doc->remarks }}">
                                                        {{ Str::limit($doc->remarks, 50) }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $doc->created_by_name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($doc->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No documents created yet</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-primary">
                        View All Documents
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .remarks-text {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: help;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
