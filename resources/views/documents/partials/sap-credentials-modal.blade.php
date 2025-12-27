<!-- SAP Credentials Modal -->
<div class="modal fade" id="sapCredentialsModal" tabindex="-1" aria-labelledby="sapCredentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content border-0 shadow-lg">
            <!-- FORM dengan ID yang benar -->
            <form id="sapCredentialsForm">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-semibold" id="sapCredentialsModalLabel">
                        <i class="fas fa-key me-2 text-primary"></i>SAP Credentials Required
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 bg-light mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>Please enter your SAP credentials to proceed with the transfer.
                    </div>
                    <div class="mb-3">
                        <label for="sapUsername" class="form-label fw-semibold">
                            <i class="fas fa-user me-1 text-muted"></i>SAP Username *
                        </label>
                        <input type="text" class="form-control uppercase-input" id="sapUsername"
                               placeholder="Enter SAP username" required>
                    </div>
                    <div class="mb-3">
                        <label for="sapPassword" class="form-label fw-semibold">
                            <i class="fas fa-lock me-1 text-muted"></i>SAP Password *
                        </label>
                        <input type="password" class="form-control" id="sapPassword"
                               placeholder="Enter SAP password" required>
                    </div>
                    <div class="mb-3">
                        <label for="additionalRemarks" class="form-label fw-semibold">
                            <i class="fas fa-sticky-note me-1 text-muted"></i>Additional Remarks (Optional)
                        </label>
                        <textarea class="form-control" id="additionalRemarks" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Submit & Process Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
