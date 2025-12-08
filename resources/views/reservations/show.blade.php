@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('reservations.index') }}">Reservations</a></li>
                    <li class="breadcrumb-item active">Detail Reservation</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Detail Reservation</h2>
                <a href="{{ route('reservations.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            @if($reservation)
                <div class="row">
                    {{-- Basic Information --}}
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">ID</th>
                                        <td>{{ $reservation->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Reservation Number</th>
                                        <td><strong>{{ $reservation->rsnum }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Reservation Position</th>
                                        <td>{{ $reservation->rspos }}</td>
                                    </tr>
                                    <tr>
                                        <th>Plant</th>
                                        <td>
                                            <span class="badge bg-info">{{ $reservation->sap_plant }}</span>
                                            ({{ $reservation->dwerk }} - {{ $reservation->nampl }})
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Storage Location</th>
                                        <td>{{ $reservation->lgort }} - {{ $reservation->namsl }}</td>
                                    </tr>
                                    <tr>
                                        <th>SAP Order</th>
                                        <td>
                                            <span class="badge bg-secondary">{{ $reservation->sap_order }}</span>
                                            ({{ $reservation->aufnr }})
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Dates Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Start Date (GSTRP)</th>
                                        <td>
                                            @if($reservation->gstrp)
                                                {{ \Carbon\Carbon::parse($reservation->gstrp)->format('d F Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Finish Date (GLTRP)</th>
                                        <td>
                                            @if($reservation->gltrp)
                                                {{ \Carbon\Carbon::parse($reservation->gltrp)->format('d F Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Calendar Week</th>
                                        <td>{{ $reservation->cweek }}</td>
                                    </tr>
                                    <tr>
                                        <th>Sync Date</th>
                                        <td>{{ \Carbon\Carbon::parse($reservation->sync_date)->format('d F Y H:i:s') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created At</th>
                                        <td>{{ \Carbon\Carbon::parse($reservation->created_at)->format('d F Y H:i:s') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Material Information --}}
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Material Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Material Number</th>
                                        <td><code>{{ $reservation->matnr }}</code></td>
                                    </tr>
                                    <tr>
                                        <th>Material Description</th>
                                        <td>{{ $reservation->maktx }}</td>
                                    </tr>
                                    <tr>
                                        <th>Material Type</th>
                                        <td>{{ $reservation->mtart }}</td>
                                    </tr>
                                    <tr>
                                        <th>Material Group</th>
                                        <td>{{ $reservation->matkl }}</td>
                                    </tr>
                                    <tr>
                                        <th>Size</th>
                                        <td>{{ $reservation->groes }}</td>
                                    </tr>
                                    <tr>
                                        <th>Quantity</th>
                                        <td>
                                            <strong>{{ number_format($reservation->psmng, 3) }}</strong>
                                            {{ $reservation->meins }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>MRP Controller</th>
                                        <td>{{ $reservation->dispo }} - {{ $reservation->dispc }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">Additional Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Production Version</th>
                                        <td>{{ $reservation->ferth }}</td>
                                    </tr>
                                    <tr>
                                        <th>Entry Sheet</th>
                                        <td>{{ $reservation->zeinr }}</td>
                                    </tr>
                                    <tr>
                                        <th>Receiving Plant</th>
                                        <td>{{ $reservation->rewrk }}</td>
                                    </tr>
                                    <tr>
                                        <th>Material Document</th>
                                        <td>{{ $reservation->mblnr }}</td>
                                    </tr>
                                    <tr>
                                        <th>Reference</th>
                                        <td>{{ $reservation->numbr }}</td>
                                    </tr>
                                    <tr>
                                        <th>Receiving Storage</th>
                                        <td>{{ $reservation->umlgo }} - {{ $reservation->naslr }}</td>
                                    </tr>
                                    <tr>
                                        <th>Sync User</th>
                                        <td>{{ $reservation->sync_user }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Raw Data --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Raw Data</h5>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-3 rounded">{{ json_encode($reservation, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @else
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Data tidak ditemukan.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
