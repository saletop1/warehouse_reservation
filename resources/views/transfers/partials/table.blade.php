@php
    $displayedTransfers = [];
@endphp

@forelse($transfers as $transfer)
    @php
        $transferKey = $transfer->transfer_no . '_' . $transfer->plant_destination;

        if (in_array($transferKey, $displayedTransfers)) {
            continue;
        }

        $displayedTransfers[] = $transferKey;
    @endphp

    <tr class="align-middle transfer-row">
        <td class="ps-3">
            <a href="javascript:void(0);" class="text-decoration-none text-dark view-transfer-clickable"
            data-id="{{ $transfer->id }}"
            style="display: block;">
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        @if($transfer->status == 'COMPLETED')
                        <i class="fas fa-check-circle text-success"></i>
                        @elseif($transfer->status == 'SUBMITTED')
                        <i class="fas fa-clock text-warning"></i>
                        @elseif($transfer->status == 'FAILED')
                        <i class="fas fa-times-circle text-danger"></i>
                        @else
                        <i class="fas fa-question-circle text-secondary"></i>
                        @endif
                    </div>
                    <div>
                        <div class="fw-semibold transfer-no text-primary">{{ $transfer->transfer_no ?? 'N/A' }}</div>
                        <div class="text-muted small">
                            {{ \Carbon\Carbon::parse($transfer->created_at)->format('H:i') }}
                        </div>
                    </div>
                </div>
            </a>
        </td>
        <td>
            @if($transfer->document)
            <a href="{{ route('documents.show', $transfer->document->id) }}"
            class="text-decoration-none text-dark fw-medium d-block">
                {{ $transfer->document_no }}
            </a>
            <div class="text-muted small">
                Plant: {{ $transfer->document->plant ?? 'N/A' }}
            </div>
            @else
            <div class="text-muted document-no">{{ $transfer->document_no }}</div>
            @endif
        </td>
        <td>
            @php
                $statusConfig = [
                    'COMPLETED' => ['class' => 'success', 'icon' => 'check-circle', 'label' => 'Done'],
                    'SUBMITTED' => ['class' => 'warning', 'icon' => 'clock', 'label' => 'Sent'],
                    'FAILED' => ['class' => 'danger', 'icon' => 'times-circle', 'label' => 'Failed'],
                    'PENDING' => ['class' => 'secondary', 'icon' => 'hourglass-half', 'label' => 'Pending'],
                    'PROCESSING' => ['class' => 'info', 'icon' => 'sync-alt', 'label' => 'Processing'],
                ];
                $config = $statusConfig[$transfer->status] ?? ['class' => 'secondary', 'icon' => 'question-circle', 'label' => $transfer->status];
            @endphp
            <span class="badge bg-{{ $config['class'] }}-subtle text-{{ $config['class'] }} border border-{{ $config['class'] }}-subtle px-3 py-1 transfer-status">
                <i class="fas fa-{{ $config['icon'] }} me-1"></i>
                {{ $config['label'] }}
            </span>
        </td>
        <td>
            <div class="d-flex align-items-center">
                <div class="text-center me-2">
                    <div class="badge plant-supply border px-3 py-1" style="color: #28a745 !important; border-color: #28a745 !important; background-color: rgba(40, 167, 69, 0.1) !important;">
                        {{ $transfer->plant_supply ?? 'N/A' }}
                    </div>
                    <div class="text-muted small mt-1">Supply</div>
                </div>
                @if(!empty($transfer->plant_destination) && $transfer->plant_destination != $transfer->plant_supply)
                <div class="me-2">
                    <i class="fas fa-arrow-right text-muted"></i>
                </div>
                <div class="text-center">
                    <div class="badge plant-destination border px-3 py-1" style="color: #007bff !important; border-color: #007bff !important; background-color: rgba(0, 123, 255, 0.1) !important;">
                        {{ $transfer->plant_destination ?? 'N/A' }}
                    </div>
                    <div class="text-muted small mt-1">Dest</div>
                </div>
                @endif
            </div>
        </td>
        <td class="text-center">
            <span class="badge bg-light text-dark border px-3 py-1">
                {{ $transfer->total_items ?? 0 }}
            </span>
        </td>
        <td class="text-center">
            <div class="fw-bold">
                @php
                    $totalQty = 0;
                    if($transfer->items && $transfer->items->count() > 0) {
                        $totalQty = $transfer->items->sum('quantity');
                    } elseif($transfer->total_qty) {
                        $totalQty = $transfer->total_qty;
                    }
                @endphp
                {{ number_format($totalQty, 0, ',', '.') }}
            </div>
            <div class="text-muted small">{{ $transfer->items->first()->unit ?? 'PC' }}</div>
        </td>
        <td>
            <div>
                <div>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/y') }}</div>
                <div class="text-muted small">{{ $transfer->created_by_name ?? 'System' }}</div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center py-5">
            <div class="py-4">
                <i class="fas fa-exchange-alt fa-3x text-muted opacity-25 mb-3"></i>
                <h5 class="text-muted mb-2">No transfers found</h5>
                <p class="text-muted mb-3">
                    @if(request()->has('search'))
                        No results found for "{{ request('search') }}"
                    @else
                        No transfer records available
                    @endif
                </p>
            </div>
        </td>
    </tr>
@endforelse
