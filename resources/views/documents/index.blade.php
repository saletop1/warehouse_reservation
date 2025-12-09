@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('reservations.index') }}">Reservations</a></li>
                    <li class="breadcrumb-item active">Reservation Documents</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Reservation Documents</h2>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-lg mb-3 floating-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-lg me-3"></i>
                        <div>
                            <strong class="d-block">Success!</strong>
                            <small class="d-block mt-1">{{ session('success') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                {{-- Set session flag untuk create page --}}
                @php
                    session(['accessed_reservations_index' => true]);
                @endphp
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Documents Table --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Reservation Documents</h5>
                        <small class="text-muted">Total: {{ $documents->total() }} documents</small>
                    </div>
                    <div>
                        <form id="exportPdfForm" action="{{ route('documents.export.selected.pdf') }}" method="POST" class="d-inline" target="_blank">
                            @csrf
                            <input type="hidden" name="document_ids" id="selectedDocumentsPdfInput">
                            <button type="submit" class="btn btn-sm btn-danger" id="exportPdfBtn" disabled>
                                <i class="fas fa-file-pdf"></i> Export Selected PDF
                            </button>
                        </form>
                        <form id="exportExcelForm" action="{{ route('documents.export.selected.excel') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="document_ids" id="selectedDocumentsExcelInput">
                            <button type="submit" class="btn btn-sm btn-success" id="exportExcelBtn" disabled>
                                <i class="fas fa-file-excel"></i> Export Selected Excel
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Live Search --}}
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="liveSearch" placeholder="Search by Document No, Plant, Status, or Created By...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="documentsTable">
                            <thead class="table-dark text-center align-middle">
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                        </div>
                                    </th>
                                    <th>Document No</th>
                                    <th>Plant</th>
                                    <th>Status</th>
                                    <th>Total Items</th>
                                    <th>Total Qty</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $document)
                                    <tr class="text-center align-middle">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input document-checkbox" type="checkbox" value="{{ $document->id }}">
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('documents.show', $document->id) }}"
                                               class="{{ $document->plant == '3000' ? 'text-primary' : 'text-success' }} fw-bold">
                                                {{ $document->document_no }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $document->plant }}</span>
                                        </td>
                                        <td>
                                            @if($document->status == 'created')
                                                <span class="badge bg-warning">Created</span>
                                            @elseif($document->status == 'posted')
                                                <span class="badge bg-success">Posted</span>
                                            @else
                                                <span class="badge bg-danger">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>{{ $document->total_items }}</td>
                                        <td>{{ \App\Helpers\NumberHelper::formatQuantity($document->total_qty) }}</td>
                                        <td>{{ $document->created_by_name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($document->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fas fa-file-alt fa-3x mb-3"></i>
                                            <h5>No documents found</h5>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($documents->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Menampilkan {{ $documents->firstItem() }} sampai {{ $documents->lastItem() }}
                                dari {{ $documents->total() }} dokumen
                            </div>
                            <div>
                                {{ $documents->appends(request()->except('page'))->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .selected-count {
        font-size: 0.875rem;
        color: #6c757d;
        margin-left: 10px;
    }
    .export-actions {
        display: none;
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }
    #documentsTable th,
    #documentsTable td {
        vertical-align: middle;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live Search Functionality
    const liveSearch = document.getElementById('liveSearch');
    const documentsTable = document.getElementById('documentsTable');
    const rows = documentsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    liveSearch.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            const cells = row.getElementsByTagName('td');
            let found = false;

            for (let i = 1; i < cells.length; i++) {
                const cell = cells[i];
                if (cell.textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                    break;
                }
            }

            row.style.display = found ? '' : 'none';
        }
    });

    // Select All Checkbox
    const selectAll = document.getElementById('selectAll');
    const documentCheckboxes = document.querySelectorAll('.document-checkbox');
    const exportPdfBtn = document.getElementById('exportPdfBtn');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const selectedDocumentsPdfInput = document.getElementById('selectedDocumentsPdfInput');
    const selectedDocumentsExcelInput = document.getElementById('selectedDocumentsExcelInput');
    const exportPdfForm = document.getElementById('exportPdfForm');
    const exportExcelForm = document.getElementById('exportExcelForm');

    selectAll.addEventListener('change', function() {
        const isChecked = this.checked;
        documentCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateExportButtons();
    });

    documentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateExportButtons);
    });

    function updateExportButtons() {
        const checkedBoxes = Array.from(documentCheckboxes).filter(cb => cb.checked);
        const documentIds = checkedBoxes.map(cb => cb.value);

        if (documentIds.length > 0) {
            exportPdfBtn.disabled = false;
            exportExcelBtn.disabled = false;
            selectedDocumentsPdfInput.value = documentIds.join(',');
            selectedDocumentsExcelInput.value = documentIds.join(',');
        } else {
            exportPdfBtn.disabled = true;
            exportExcelBtn.disabled = true;
            selectedDocumentsPdfInput.value = '';
            selectedDocumentsExcelInput.value = '';
        }
    }

    // Confirm before export
    exportPdfForm.addEventListener('submit', function(e) {
        const documentIds = selectedDocumentsPdfInput.value;
        if (!documentIds) {
            e.preventDefault();
            alert('Please select at least one document to export.');
            return false;
        }

        const count = documentIds.split(',').length;
        if (!confirm(`Are you sure you want to export ${count} selected document(s) to PDF?`)) {
            e.preventDefault();
            return false;
        }
    });

    exportExcelForm.addEventListener('submit', function(e) {
        const documentIds = selectedDocumentsExcelInput.value;
        if (!documentIds) {
            e.preventDefault();
            alert('Please select at least one document to export.');
            return false;
        }

        const count = documentIds.split(',').length;
        if (!confirm(`Are you sure you want to export ${count} selected document(s) to Excel?`)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endsection
