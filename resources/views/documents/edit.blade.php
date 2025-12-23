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

            <!-- Delete Selected Items Form -->
            <form id="deleteItemsForm" action="{{ route('documents.items.delete-selected', $document->id) }}" method="POST" style="display: none;">
                @csrf
                @method('DELETE')
                <input type="hidden" name="selected_items" id="selectedItemsInput">
            </form>

            <form action="{{ route('documents.update', $document->id) }}" method="POST" id="editDocumentForm">
                @csrf
                @method('PUT')
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Edit Document</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Document No</label>
                                    <input type="text" class="form-control" value="{{ $document->document_no }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Plant Request</label>
                                    <input type="text" class="form-control" value="{{ $document->plant }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sloc_supply" class="form-label">Plant Supply *</label>
                                    <input type="text"
                                           class="form-control @error('sloc_supply') is-invalid @enderror"
                                           id="sloc_supply"
                                           name="sloc_supply"
                                           value="{{ old('sloc_supply', $document->sloc_supply ?? '') }}"
                                           placeholder="Enter plant for supply (e.g., 1200, 1300)"
                                           required>
                                    @error('sloc_supply')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Enter the plant for supply</small>
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
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Document Items</h5>
                            <div>
                                <button type="button" id="deleteSelectedItemsBtn" class="btn btn-danger btn-sm" disabled>
                                    <i class="fas fa-trash"></i> Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Selection Info -->
                        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3" id="selectionInfo" style="display: none;">
                            <div>
                                <i class="fas fa-check-circle"></i>
                                <span id="selectedCount">0</span> items selected
                            </div>
                            <button type="button" id="clearSelectionBtn" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear Selection
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                        </th>
                                        <th>No</th>
                                        <th>Material Code</th>
                                        <th>Description</th>
                                        <th>Add Info</th>
                                        <th>Sales Order</th>
                                        <th>MRP</th>
                                        <th class="text-end">Requested Qty</th>
                                        <th>Uom</th>
                                        <th>Source PRO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($document->items as $index => $item)
                                        @php
                                            // Format nilai untuk ditampilkan
                                            $qtyValue = $item->requested_qty;
                                            $displayValue = fmod($qtyValue, 1) != 0 ? $qtyValue : intval($qtyValue);

                                            // Check if quantity is editable based on MRP
                                            $isQtyEditable = $item->is_qty_editable ?? true;
                                            $allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1', 'D21', 'GF1', 'CH4', 'MF3', 'D28', 'D23', 'WE2'];

                                            // Pastikan dispo ada di item
                                            $dispo = $item->dispo ?? null;
                                            if ($dispo && !in_array($dispo, $allowedMRP)) {
                                                $isQtyEditable = false;
                                            }

                                            // Format material code: remove leading zeros if numeric
                                            $materialCode = $item->material_code;
                                            if (ctype_digit($materialCode)) {
                                                $materialCode = ltrim($materialCode, '0');
                                            }

                                            // Convert unit: if ST then PC
                                            $unit = $item->unit == 'ST' ? 'PC' : $item->unit;

                                            // Decode sources dengan cara yang aman
                                            $sources = [];
                                            if (is_string($item->sources)) {
                                                $sources = json_decode($item->sources, true) ?? [];
                                            } elseif (is_array($item->sources)) {
                                                $sources = $item->sources;
                                            }

                                            $processedSources = array_map(function($source) {
                                                return \App\Helpers\NumberHelper::removeLeadingZeros($source);
                                            }, $sources);

                                            // PERBAIKAN: Ambil sales orders dengan cara yang aman
                                            $salesOrders = [];
                                            if (is_string($item->sales_orders)) {
                                                $salesOrders = json_decode($item->sales_orders, true) ?? [];
                                            } elseif (is_array($item->sales_orders)) {
                                                $salesOrders = $item->sales_orders;
                                            }

                                            // Ambil sortf dari item
                                            $addInfo = $item->sortf ?? '-';
                                        @endphp
                                        <tr data-item-id="{{ $item->id }}">
                                            <td class="text-center">
                                                <input type="checkbox" class="item-checkbox form-check-input" value="{{ $item->id }}">
                                            </td>
                                            <td>{{ $index + 1 }}</td>
                                            <td><code>{{ $materialCode }}</code></td>
                                            <td style="white-space: normal; word-wrap: break-word; max-width: 300px;">{{ $item->material_description }}</td>
                                            <td>{{ $addInfo }}</td>
                                            <td>
                                                @if(!empty($salesOrders))
                                                    @foreach($salesOrders as $so)
                                                        <span class="badge bg-secondary me-1 mb-1">{{ $so }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->dispo)
                                                    <span class="badge bg-info">{{ $item->dispo }}</span>
                                                    @if(!$isQtyEditable)
                                                        <small class="text-muted d-block">(Fixed Qty)</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($isQtyEditable)
                                                    <input type="number"
                                                           step="0.001"
                                                           min="0"
                                                           class="form-control text-end qty-input"
                                                           name="items[{{ $index }}][requested_qty]"
                                                           value="{{ old('items.'.$index.'.requested_qty', $displayValue) }}"
                                                           required>
                                                @else
                                                    <input type="text"
                                                           class="form-control text-end bg-light"
                                                           value="{{ \App\Helpers\NumberHelper::formatQuantity($displayValue) }}"
                                                           readonly
                                                           title="Quantity cannot be changed for MRP: {{ $item->dispo }}">
                                                    <input type="hidden"
                                                           name="items[{{ $index }}][requested_qty]"
                                                           value="{{ $displayValue }}">
                                                @endif
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            </td>
                                            <td>{{ $unit }}</td>
                                            <td>
                                                @if(!empty($processedSources))
                                                    @foreach($processedSources as $source)
                                                        <span class="badge bg-info me-1 mb-1">{{ $source }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="deleteItemCount">0</span> selected item(s)?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Hilangkan spinner tombol naik turun di semua browser */
.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.qty-input[type=number] {
    -moz-appearance: textfield;
}

/* Hilangkan spinner untuk input readonly */
input[readonly]::-webkit-outer-spin-button,
input[readonly]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[readonly][type=number] {
    -moz-appearance: textfield;
}

/* Styling untuk input yang non-editable */
.bg-light {
    background-color: #f8f9fa !important;
    cursor: not-allowed;
}

/* Checkbox styling */
.item-checkbox {
    cursor: pointer;
}

/* Selected row styling */
tr.selected-row {
    background-color: rgba(13, 110, 253, 0.1) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables for item selection
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const deleteSelectedItemsBtn = document.getElementById('deleteSelectedItemsBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const selectionInfo = document.getElementById('selectionInfo');
    const selectedCount = document.getElementById('selectedCount');
    const selectedItemsInput = document.getElementById('selectedItemsInput');
    const deleteItemsForm = document.getElementById('deleteItemsForm');
    const deleteConfirmationModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteItemCount = document.getElementById('deleteItemCount');

    // Update selection count and button states
    function updateSelection() {
        const selectedItems = Array.from(itemCheckboxes).filter(cb => cb.checked);
        const count = selectedItems.length;

        selectedCount.textContent = count;

        if (count > 0) {
            selectionInfo.style.display = 'flex';
            deleteSelectedItemsBtn.disabled = false;

            // Update select all checkbox state
            selectAllCheckbox.checked = count === itemCheckboxes.length;
            selectAllCheckbox.indeterminate = count > 0 && count < itemCheckboxes.length;

            // Highlight selected rows
            document.querySelectorAll('tbody tr').forEach(row => {
                const checkbox = row.querySelector('.item-checkbox');
                if (checkbox && checkbox.checked) {
                    row.classList.add('selected-row');
                } else {
                    row.classList.remove('selected-row');
                }
            });

            // Update selected items for form submission
            const selectedIds = selectedItems.map(cb => cb.value);
            selectedItemsInput.value = JSON.stringify(selectedIds);
        } else {
            selectionInfo.style.display = 'none';
            deleteSelectedItemsBtn.disabled = true;
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;

            // Remove highlight from all rows
            document.querySelectorAll('tbody tr').forEach(row => {
                row.classList.remove('selected-row');
            });

            // Clear selected items for form submission
            selectedItemsInput.value = '[]';
        }
    }

    // Select all checkbox handler
    selectAllCheckbox.addEventListener('change', function() {
        itemCheckboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        updateSelection();
    });

    // Individual checkbox handlers
    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });

    // Clear selection button
    clearSelectionBtn.addEventListener('click', function() {
        itemCheckboxes.forEach(cb => {
            cb.checked = false;
        });
        updateSelection();
    });

    // Delete selected items button
    deleteSelectedItemsBtn.addEventListener('click', function() {
        const selectedItems = Array.from(itemCheckboxes).filter(cb => cb.checked);

        if (selectedItems.length === 0) {
            alert('Please select items to delete.');
            return;
        }

        // Update modal message
        deleteItemCount.textContent = selectedItems.length;

        // Show confirmation modal
        deleteConfirmationModal.show();
    });

    // Confirm delete button in modal
    confirmDeleteBtn.addEventListener('click', function() {
        // Submit the delete form
        deleteItemsForm.submit();
    });

    // Validasi form sebelum submit
    const form = document.getElementById('editDocumentForm');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessages = [];

        // Validasi Plant Supply
        const slocSupply = document.getElementById('sloc_supply');
        if (!slocSupply.value.trim()) {
            isValid = false;
            slocSupply.classList.add('is-invalid');
            errorMessages.push('Plant Supply is required');
        } else {
            slocSupply.classList.remove('is-invalid');
        }

        // Validasi quantity hanya untuk yang editable
        const qtyInputs = document.querySelectorAll('.qty-input');
        qtyInputs.forEach(input => {
            const value = parseFloat(input.value);
            if (isNaN(value) || value < 0) {
                isValid = false;
                input.classList.add('is-invalid');
                errorMessages.push('Quantity must be a positive number for material');
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n' + errorMessages.join('\n'));
        }
    });

    // Auto-format quantity input untuk yang editable
    const qtyInputs = document.querySelectorAll('.qty-input');
    qtyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            let value = parseFloat(this.value);
            if (!isNaN(value)) {
                // Pastikan tidak negatif
                value = Math.max(value, 0);

                // Format 3 digit desimal
                value = Math.round(value * 1000) / 1000;

                this.value = value;
            }
        });

        // Prevent keyboard up/down arrows
        input.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                e.preventDefault();
            }
        });
    });
});
</script>
@endsection
