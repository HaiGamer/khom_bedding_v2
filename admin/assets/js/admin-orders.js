document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('.table tbody');
    if (!tableBody) return;

    tableBody.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.btn-delete-order');
        if (!deleteBtn) return;

        e.preventDefault();
        const orderId = deleteBtn.dataset.id;

        if (confirm(`Bạn có chắc chắn muốn XÓA VĨNH VIỄN đơn hàng #${orderId}? Hành động này sẽ không thể hoàn tác.`)) {
            fetch('/admin/api/orders/handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', order_id: orderId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Xóa hàng khỏi bảng trên giao diện
                    deleteBtn.closest('tr').remove();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(err => {
                alert('Đã có lỗi nghiêm trọng xảy ra.');
                console.error(err);
            });
        }
    });
});