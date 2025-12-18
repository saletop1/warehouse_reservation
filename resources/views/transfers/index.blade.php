@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Transfer History</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Transfer History</h2>
                <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Documents
                </a>
            </div>

            <!-- Transfer List -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Transfer Documents</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Transfer No</th>
                                    <th>Document No</th>
                                    <th>Plant Supply</th>
                                    <th>Plant Dest</th>
                                    <th>Items</th>
                                    <th>Total Qty</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                <tr>
                                    <td>
                                        @if($transfer->transfer_no)
                                            <code>{{ $transfer->transfer_no }}</code>
                                        @else
                                            <span class="text-muted">PENDING</span>
                                        @endif
                                    </td>
                                    <td>{{ $transfer->document_no }}</td>
                                    <td><span class="badge bg-info">{{ $transfer->plant_supply }}</span></td>
                                    <td><span class="badge bg-success">{{ $transfer->plant_destination }}</span></td>
                                    <td class="text-center">{{ $transfer->total_items }}</td>
                                    <td class="text-center">{{ \App\Helpers\NumberHelper::formatQuantity($transfer->total_quantity) }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'COMPLETED' => 'success',
                                                'SUBMITTED' => 'primary',
                                                'ERROR' => 'danger',
                                                'COMPLETED_WITH_WARNINGS' => 'warning',
                                                'PENDING' => 'secondary'
                                            ];
                                            $color = $statusColors[$transfer->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $transfer->status }}</span>
                                    </td>
                                    <td>{{ $transfer->created_by_name }}</td>
                                    <td>{{ $transfer->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('transfers.show', $transfer->id) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No transfer records found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($transfers->hasPages())
                    <div class="card-footer">
                        {{ $transfers->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
