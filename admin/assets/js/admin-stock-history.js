document.addEventListener('DOMContentLoaded', function() {
    const detailModal = document.getElementById('export-detail-modal');
    if (!detailModal) return;

    detailModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Nút đã kích hoạt modal
        const exportId = button.dataset.exportId;
        
        const modalTitle = detailModal.querySelector('.modal-title');
        const modalBody = detailModal.querySelector('.modal-body');

        modalTitle.textContent = `Chi tiết Phiếu Xuất Kho #${exportId}`;
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';

        // Gọi API để lấy chi tiết
        fetch(`/admin/api/inventory/get-export-detail.php?id=${exportId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Lỗi mạng hoặc server.');
                }
                return response.text(); // Nhận về HTML
            })
            .then(html => {
                modalBody.innerHTML = html;
            })
            .catch(error => {
                modalBody.innerHTML = '<div class="alert alert-danger">Không thể tải chi tiết. Vui lòng thử lại.</div>';
                console.error('Fetch Error:', error);
            });
    });
});