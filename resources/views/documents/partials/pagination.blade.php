@if($documents->hasPages())
    <div class="card-footer bg-white border-top-0">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Page {{ $documents->currentPage() }} of {{ $documents->lastPage() }}
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    {{-- Previous Page Link --}}
                    @if($documents->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link">&laquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $documents->previousPageUrl() }}" rel="prev">&laquo;</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @php
                        $current = $documents->currentPage();
                        $last = $documents->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);
                    @endphp

                    @if($start > 1)
                        <li class="page-item">
                            <a class="page-link" href="{{ $documents->url(1) }}">1</a>
                        </li>
                        @if($start > 2)
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        @endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $current)
                            <li class="page-item active">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $documents->url($page) }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endfor

                    @if($end < $last)
                        @if($end < $last - 1)
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        @endif
                        <li class="page-item">
                            <a class="page-link" href="{{ $documents->url($last) }}">{{ $last }}</a>
                        </li>
                    @endif

                    {{-- Next Page Link --}}
                    @if($documents->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $documents->nextPageUrl() }}" rel="next">&raquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link">&raquo;</span>
                        </li>
                    @endif
                </ul>
            </nav>
            <div class="text-muted small">
                {{ $documents->perPage() }} items per page
            </div>
        </div>
    </div>
@endif
