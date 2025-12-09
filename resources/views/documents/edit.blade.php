@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Documents</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('documents.show', $document->id) }}">{{ $document->document_no }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Edit Reservation Document</h2>
                <div>
                    <a href="{{ route('documents.show', $document->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('documents.update', $document->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Edit Document</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Document No</label>
                                    <input type="text" class="form-control" value="{{ $document->document_no }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Plant</label>
                                    <input type="text" class="form-control" value="{{ $document->plant }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Document Note (Remarks)</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                      placeholder="Enter document notes here...">{{ old('remarks', $document->remarks) }}</textarea>
                            <small class="text-muted">You can add or update document remarks here.</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Document Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Material Code</th>
                                        <th>Description</th>
                                        <th>SORTF</th>
                                        <th>Unit</th>
                                        <th class="text-end">Requested Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($document->items as $index => $item)
                                        @php
                                            // Tentukan step berdasarkan apakah quantity desimal atau integer
                                            $qtyValue = $item->requested_qty;
                                            $isDecimal = fmod($qtyValue, 1) != 0;
                                            $step = $isDecimal ? '0.001' : '1';
                                            $minValue = 0;

                                            // Format nilai untuk ditampilkan
                                            $displayValue = $isDecimal ? $qtyValue : intval($qtyValue);
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><code>{{ $item->material_code }}</code></td>
                                            <td>{{ $item->material_description }}</td>
                                            <td>{{ $item->sortf ?? '-' }}</td>
                                            <td>{{ $item->unit }}</td>
                                            <td>
                                                <input type="number"
                                                       step="{{ $step }}"
                                                       min="{{ $minValue }}"
                                                       class="form-control text-end"
                                                       name="items[{{ $index }}][requested_qty]"
                                                       value="{{ old('items.'.$index.'.requested_qty', $displayValue) }}"
                                                       required>
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Document
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validasi form sebelum submit
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessages = [];

        // Validasi quantity tidak boleh negatif
        const qtyInputs = document.querySelectorAll('input[name^="items["][name$="][requested_qty]"]');
        qtyInputs.forEach(input => {
            const value = parseFloat(input.value);
            if (isNaN(value) || value < 0) {
                isValid = false;
                input.classList.add('is-invalid');
                errorMessages.push('Quantity must be a positive number');
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n' + errorMessages.join('\n'));
        }
    });

    // Auto-format quantity input
    const qtyInputs = document.querySelectorAll('input[name^="items["][name$="][requested_qty]"]');
    qtyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            let value = parseFloat(this.value);
            if (!isNaN(value)) {
                // Jika step=1 (integer), bulatkan ke integer terdekat
                if (this.step === '1') {
                    value = Math.round(value);
                }
                // Pastikan tidak negatif
                value = Math.max(value, 0);
                this.value = value;
            }
        });
    });
});
</script>
@endsection
