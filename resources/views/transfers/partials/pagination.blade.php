@if($transfers->hasPages() && $transfers->lastPage() > 1)
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted">
            Page {{ $transfers->currentPage() }} of {{ $transfers->lastPage() }}
        </div>
        <div class="d-flex justify-content-end">
            {{ $transfers->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
