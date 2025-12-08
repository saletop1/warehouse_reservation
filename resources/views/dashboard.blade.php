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
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Documents</h5>
                </div>
                <div class="card-body">
                    @php
                        $recentDocuments = \App\Models\ReservationDocument::orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp

                    @if($recentDocuments->count() > 0)
                        <div class="list-group">
                            @foreach($recentDocuments as $doc)
                                <a href="{{ route('documents.show', $doc->id) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 {{ $doc->plant == '3000' ? 'text-primary' : 'text-success' }}">
                                            {{ $doc->document_no }}
                                        </h6>
                                        <small>{{ \Carbon\Carbon::parse($doc->created_at)->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1">
                                        <span class="badge bg-info">{{ $doc->plant }}</span>
                                        <span class="badge bg-warning">{{ $doc->status }}</span>
                                        <span class="badge bg-secondary">{{ $doc->total_items }} items</span>
                                    </p>
                                    <small>By: {{ $doc->created_by_name }}</small>
                                </a>
                            @endforeach
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

        <!-- System Status -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Status</h5>
                </div>
                <div class="card-body">
                    @php
                        $flaskUrl = env('FLASK_API_URL', 'http://127.0.0.1:8010');
                        $flaskRunning = false;

                        try {
                            $client = new \GuzzleHttp\Client(['timeout' => 3]);
                            $response = $client->get($flaskUrl . '/api/sap/health');
                            if ($response->getStatusCode() == 200) {
                                $flaskRunning = true;
                            }
                        } catch (\Exception $e) {
                            $flaskRunning = false;
                        }
                    @endphp

                    <div class="list-group">
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Python Flask API</h6>
                                    <small class="text-muted">SAP Integration Service</small>
                                </div>
                                <div>
                                    @if($flaskRunning)
                                        <span class="badge bg-success">Running</span>
                                    @else
                                        <span class="badge bg-danger">Stopped</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Database Connection</h6>
                                    <small class="text-muted">MySQL Database</small>
                                </div>
                                <div>
                                    @try
                                        \Illuminate\Support\Facades\DB::connection()->getPdo();
                                        <span class="badge bg-success">Connected</span>
                                    @catch(\Exception $e)
                                        <span class="badge bg-danger">Disconnected</span>
                                    @endtry
                                </div>
                            </div>
                        </div>

                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">SAP Credentials</h6>
                                    <small class="text-muted">Username: {{ env('SAP_USERNAME', 'Not Set') }}</small>
                                </div>
                                <div>
                                    @if(env('SAP_USERNAME') && env('SAP_PASSWORD'))
                                        <span class="badge bg-success">Configured</span>
                                    @else
                                        <span class="badge bg-warning">Not Configured</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Last Data Sync</h6>
                                    @php
                                        $lastSync = \Illuminate\Support\Facades\DB::table('sap_reservations')
                                            ->orderBy('sync_date', 'desc')
                                            ->value('sync_date');
                                    @endphp
                                    <small class="text-muted">
                                        @if($lastSync)
                                            {{ \Carbon\Carbon::parse($lastSync)->format('d M Y H:i:s') }}
                                        @else
                                            Never
                                        @endif
                                    </small>
                                </div>
                                <div>
                                    @if($lastSync)
                                        <span class="badge bg-info">{{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}</span>
                                    @else
                                        <span class="badge bg-secondary">No Data</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!$flaskRunning)
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Python Flask API is not running!</strong><br>
                            Please start the service by running: <code>python sap_rfc.py</code>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
