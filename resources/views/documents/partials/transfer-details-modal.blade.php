<!-- Transfer Details Modal -->
<div class="modal fade" id="transferDetailsModal" tabindex="-1" aria-labelledby="transferDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-semibold" id="transferDetailsModalLabel">
                    <i class="fas fa-list-alt me-2 text-primary"></i>Transfer Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeTransferDetailsModal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <h6 class="fw-semibold" id="detailMaterialDescription"></h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="transferDetailsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Transfer No</th>
                                <th>Material Code</th>
                                <th>Batch</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Unit</th>
                                <th class="text-center">Created At</th>
                            </tr>
                        </thead>
                        <tbody id="transferDetailsTableBody">
                            <!-- Data will be inserted by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="closeTransferDetailsBtn">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi untuk reset modal dengan aman
function safeResetTransferDetailsModal() {
    // Hanya reset jika modal tidak sedang ditampilkan
    const modalElement = document.getElementById('transferDetailsModal');
    if (!modalElement) return;

    const isModalVisible = modalElement.classList.contains('show');

    if (!isModalVisible) {
        // Reset judul modal
        const modalTitle = document.getElementById('transferDetailsModalLabel');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-list-alt me-2 text-primary"></i>Transfer Details';
        }

        // Reset deskripsi material
        const materialDesc = document.getElementById('detailMaterialDescription');
        if (materialDesc) {
            materialDesc.textContent = '';
        }

        // Kosongkan tabel
        const tbody = document.querySelector('#transferDetailsTable tbody');
        if (tbody) {
            tbody.innerHTML = '';
        }
    }
}

// Event listener untuk modal hidden
document.getElementById('transferDetailsModal').addEventListener('hidden.bs.modal', function () {
    setTimeout(() => {
        safeResetTransferDetailsModal();
    }, 300);
});

// Event listener untuk tombol close
document.getElementById('closeTransferDetailsBtn')?.addEventListener('click', function() {
    setTimeout(() => {
        safeResetTransferDetailsModal();
    }, 100);
});

document.getElementById('closeTransferDetailsModal')?.addEventListener('click', function() {
    setTimeout(() => {
        safeResetTransferDetailsModal();
    }, 100);
});
</script>
