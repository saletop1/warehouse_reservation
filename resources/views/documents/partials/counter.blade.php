<div class="text-muted small">
    <i class="fas fa-file-alt me-1"></i>
    Showing {{ $documents->firstItem() ?? 0 }}-{{ $documents->lastItem() ?? 0 }} of {{ $documents->total() }} documents
    @if(request('search'))
        <span class="text-primary ms-2">
            <i class="fas fa-search me-1"></i>Search: "{{ request('search') }}"
        </span>
    @endif
    @if(request('status') && in_array(request('status'), ['booked', 'partial', 'closed', 'cancelled']))
        <span class="text-info ms-2">
            <i class="fas fa-filter me-1"></i>Status: {{ ucfirst(request('status')) }}
        </span>
    @endif
</div>
