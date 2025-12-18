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
    <div class="modal fade" id="sapCredentialsModal" tabindex="-1" aria-labelledby="sapCredentialsModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 10px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);">
            <div class="modal-header bg-primary text-white py-2" style="border-radius: 10px 10px 0 0;">
                <h5 class="modal-title fs-6 mb-0" id="sapCredentialsModalLabel">
                    <i class="fas fa-key me-2"></i> SAP Login Required
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-info py-2 mb-3 fs-7" style="background: rgba(23, 162, 184, 0.15); border-color: rgba(23, 162, 184, 0.3);">
                    <i class="fas fa-info-circle me-2"></i> Enter your SAP credentials to process transfer
                </div>

                <form id="sapCredsForm">
                    <div class="mb-3">
                        <label for="sap_user" class="form-label fs-7 mb-1 fw-semibold">SAP Username *</label>
                        <input type="text"
                               class="form-control form-control-sm border-primary"
                               id="sap_user"
                               placeholder="Enter SAP username"
                               required
                               autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label for="sap_password" class="form-label fs-7 mb-1 fw-semibold">SAP Password *</label>
                        <div class="input-group input-group-sm">
                            <input type="password"
                                   class="form-control form-control-sm border-primary"
                                   id="sap_password"
                                   placeholder="Enter SAP password"
                                   required
                                   autocomplete="off">
                            <button class="btn btn-outline-primary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm fs-7" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary btn-sm fs-7 fw-semibold" id="saveSapCredentials">
                    <i class="fas fa-paper-plane me-1"></i> Process Transfer
                </button>
            </div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
