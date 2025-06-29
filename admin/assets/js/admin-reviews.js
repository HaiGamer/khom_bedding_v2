document.addEventListener('DOMContentLoaded', function() {
    // Vẽ các ngôi sao cho các đánh giá đã có
    document.querySelectorAll('.review-stars').forEach(starContainer => {
        const rating = parseFloat(starContainer.dataset.rating);
        if (isNaN(rating)) return;
        starContainer.innerHTML = '★'.repeat(rating) + '☆'.repeat(5 - rating);
        starContainer.style.color = '#ffc107';
    });

    const tableBody = document.getElementById('reviews-table-body');
    if (!tableBody) return;

    // Lắng nghe sự kiện click trên toàn bộ bảng
    tableBody.addEventListener('click', function(e) {
        const target = e.target;
        const action = target.matches('.btn-approve') ? 'approve' : (target.matches('.btn-reject') ? 'reject' : (target.matches('.btn-delete') ? 'delete' : null));
        
        if (!action) return;

        const reviewId = target.dataset.id;
        let confirmationMessage = 'Bạn có chắc chắn?';
        if (action === 'delete') {
            confirmationMessage = 'Bạn có chắc chắn muốn XÓA VĨNH VIỄN đánh giá này?';
        }

        if (confirm(confirmationMessage)) {
            fetch('/admin/api/reviews/handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action, review_id: reviewId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Cập nhật giao diện mà không cần tải lại trang
                    const row = document.getElementById(`review-row-${reviewId}`);
                    if (action === 'delete') {
                        row.remove();
                    } else {
                        // Ẩn các nút hành động và cập nhật trạng thái
                        row.querySelector('.action-buttons').innerHTML = '';
                        row.querySelector('.status-badge').textContent = (action === 'approve') ? 'Approved' : 'Rejected';
                        row.querySelector('.status-badge').className = `badge ${action === 'approve' ? 'bg-success' : 'bg-danger'} status-badge`;
                    }
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(err => console.error(err));
        }
    });
});