<!-- Transfer Preview Modal -->
<div class="modal fade" id="transferPreviewModal" tabindex="-1" aria-labelledby="transferPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h5 class="modal-title fw-semibold" id="transferPreviewModalLabel">
                        <i class="fas fa-file-export me-2 text-primary"></i>Transfer Preview
                    </h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary ms-2">
                            <i class="fas fa-file-alt me-1"></i>Doc: {{ $document->document_no }}
                        </span>
                        <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 bg-light mb-4">
                    <i class="fas fa-info-circle me-2"></i>Review and edit transfer details before confirming. All fields are required.
                </div>

                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-borderless mb-0" id="transferPreviewTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 40px;">No</th>
                                <th style="width: 90px;">Material</th>
                                <th style="min-width: 150px;">Description</th>
                                <th class="text-center" style="width: 70px;">Remaining</th>
                                <th class="text-center" style="width: 70px;">Selected Batch Qty</th>
                                <th class="text-center" style="width: 90px;">Transfer Qty *</th>
                                <th class="text-center" style="width: 50px;">Unit</th>
                                <th class="text-center" style="width: 80px;">Plant Dest *</th>
                                <th class="text-center" style="width: 80px;">Sloc Dest *</th>
                                <th class="text-center" style="width: 200px;">Batch Source *</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Preview rows will be inserted here -->
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <!-- PERBAIKAN: Tambahkan Document Information -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-file-alt me-1 text-muted"></i>Document Information
                            </label>
                            <div class="form-control-plaintext border rounded p-2 bg-light">
                                @if($document->remarks)
                                    <div class="mb-2">{{ $document->remarks }}</div>
                                @endif
                                @if($document->created_by_name ?? $document->user->name ?? false)
                                    <div class="mt-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-user me-1"></i>
                                            {{ $document->created_by_name ?? $document->user->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="transferRemarks" class="form-label fw-semibold">
                                <i class="fas fa-sticky-note me-1 text-muted"></i>Transfer Remarks *
                            </label>
                            <textarea class="form-control" id="transferRemarks" rows="2"
                                    placeholder="Add remarks for this transfer..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3">
                                    <i class="fas fa-clipboard-list me-2 text-primary"></i>Transfer Summary
                                </h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Items:</span>
                                    <span class="fw-bold" id="modalTotalItems">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Transfer Qty:</span>
                                    <span class="fw-bold" id="modalTotalQty">0</span>
                                </div>
                                <!-- PERBAIKAN: Tampilkan Plant Supply dengan field yang benar -->
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Plant Supply:</span>
                                    <span class="fw-medium">
                                        @if(isset($document->plant_supply) && !empty($document->plant_supply))
                                            {{ $document->plant_supply }}
                                        @elseif(isset($document->sloc_supply) && !empty($document->sloc_supply))
                                            {{ $document->sloc_supply }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmTransfer">
                    <i class="fas fa-paper-plane me-1"></i> Confirm
                </button>
            </div>
        </div>
    </div>
</div>
