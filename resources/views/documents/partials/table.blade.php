@forelse($documents as $document)
    <tr class="align-middle">
        <td class="ps-4">
            <a href="{{ route('documents.show', $document->id) }}"
               class="document-link {{ $document->plant == '3000' ? 'text-primary' : 'text-success' }} fw-bold d-flex align-items-center">
                <div class="document-icon me-2">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="document-info">
                    <div class="fw-bold">{{ $document->document_no }}</div>
                    <small class="text-muted d-block">ID: {{ $document->id }}</small>
                </div>
            </a>
        </td>
        <td>
            <div class="plant-badge d-flex align-items-center">
                <div class="plant-indicator me-2
                    {{ $document->plant == '3000' ? 'bg-primary' : 'bg-success' }}"></div>
                <span class="fw-medium">{{ $document->plant }}</span>
            </div>
        </td>
        <td>
            <div class="status-container">
                @if($document->status == 'booked')
                    <span class="badge status-badge booked">
                        <i class="fas fa-clock me-1"></i> Booked
                    </span>
                @elseif($document->status == 'partial')
                    <span class="badge status-badge partial">
                        <i class="fas fa-sync-alt me-1"></i> Partial
                    </span>
                @elseif($document->status == 'closed')
                    <span class="badge status-badge closed">
                        <i class="fas fa-check-circle me-1"></i> Closed
                    </span>
                @elseif($document->status == 'cancelled')
                    <span class="badge status-badge cancelled">
                        <i class="fas fa-times-circle me-1"></i> Cancelled
                    </span>
                @else
                    <span class="badge status-badge secondary">
                        <i class="fas fa-question-circle me-1"></i> {{ $document->status }}
                    </span>
                @endif
            </div>
        </td>
        <td>
            <div class="completion-container">
                @php
                    $completionRate = $document->completion_rate ?? 0;
                    $color = $completionRate == 100 ? 'success' :
                             ($completionRate > 0 ? 'info' : 'secondary');
                @endphp
                <div class="d-flex align-items-center">
                    <div class="completion-chart me-3">
                        <div class="chart-circle" data-percent="{{ $completionRate }}">
                            <svg class="chart-svg" width="40" height="40" viewBox="0 0 40 40">
                                <circle class="chart-bg" cx="20" cy="20" r="18"></circle>
                                <circle class="chart-progress chart-{{ $color }}"
                                        cx="20" cy="20" r="18"
                                        style="stroke-dasharray: {{ $completionRate * 1.13 }} 113;"></circle>
                                <text class="chart-text" x="20" y="23" text-anchor="middle">
                                    {{ round($completionRate) }}%
                                </text>
                            </svg>
                        </div>
                    </div>
                    <div class="completion-text">
                        <div class="fw-medium">{{ round($completionRate, 1) }}% Complete</div>
                        @if($completionRate == 100)
                            <small class="text-success">Fully Completed</small>
                        @elseif($completionRate > 0)
                            <small class="text-info">In Progress</small>
                        @else
                            <small class="text-secondary">Not Started</small>
                        @endif
                    </div>
                </div>
            </div>
        </td>
        <td>
            <div class="remarks-container">
                @if($document->remarks && trim($document->remarks) != '')
                    <div class="remarks-text"
                         data-bs-toggle="tooltip"
                         title="{{ $document->remarks }}">
                        <i class="fas fa-comment me-2 text-muted"></i>
                        {{ Str::limit($document->remarks, 40) }}
                    </div>
                @else
                    <span class="text-muted">
                        <i class="fas fa-comment-slash me-2"></i>
                        No remarks
                    </span>
                @endif
            </div>
        </td>
        <td>
            <div class="creator-info">
                <div class="fw-medium">{{ $document->created_by_name }}</div>
                <small class="text-muted">Creator</small>
            </div>
        </td>
        <td class="pe-4">
            <div class="timestamp">
                <div class="date">{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</div>
                <div class="time text-muted">
                    {{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center text-muted py-5">
            <div class="empty-state">
                @if(request('search') || (request('status') && request('status') != 'all'))
                    <i class="fas fa-search fa-4x mb-4 text-light"></i>
                    <h4 class="mb-2">No documents found</h4>
                    <p class="text-muted">
                        No documents match your search criteria.
                        <a href="{{ route('documents.index') }}" class="text-primary">Clear filters</a>
                    </p>
                @else
                    <i class="fas fa-file-alt fa-4x mb-4 text-light"></i>
                    <h4 class="mb-2">No documents found</h4>
                    <p class="text-muted">No reservation documents available</p>
                @endif
            </div>
        </td>
    </tr>
@endforelse
