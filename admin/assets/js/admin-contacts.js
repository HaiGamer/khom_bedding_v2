document.addEventListener('DOMContentLoaded', function() {
    const contactContainer = document.getElementById('contacts-container');
    if (!contactContainer) return;

    // Hàm hiển thị toast
    function showAdminToast(message, isSuccess = true) {
        const toastEl = document.getElementById('adminToast');
        const toastTitle = document.getElementById('adminToastTitle');
        const toastBody = document.getElementById('adminToastBody');
        const toastIcon = toastEl.querySelector('.toast-header i');

        if (!toastEl) return;
        
        toastTitle.textContent = isSuccess ? 'Thành công' : 'Lỗi';
        toastBody.textContent = message;
        toastIcon.className = isSuccess ? 'bi bi-check-circle-fill text-success me-2' : 'bi bi-exclamation-triangle-fill text-danger me-2';

        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }

    contactContainer.addEventListener('click', function(e) {
        const button = e.target.closest('.btn-action');
        if (!button) return;

        const action = button.dataset.action;
        const contactId = button.dataset.id;
        
        if (action === 'delete') {
            if (!confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN tin nhắn này?')) {
                return;
            }
        }

        const row = document.getElementById(`contact-row-${contactId}`);
        row.style.opacity = '0.5'; // Làm mờ đi để báo hiệu đang xử lý

        fetch('/admin/api/contacts/handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, contact_id: contactId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAdminToast(data.message);
                // Xóa dòng khỏi giao diện
                row.style.transition = 'opacity 0.5s ease';
                row.style.opacity = '0';
                setTimeout(() => { row.remove(); }, 500);
            } else {
                showAdminToast(data.message, false);
                row.style.opacity = '1'; // Trả lại bình thường nếu lỗi
            }
        })
        .catch(err => {
            showAdminToast('Có lỗi nghiêm trọng xảy ra.', false);
            console.error(err);
            row.style.opacity = '1';
        });
    });
});