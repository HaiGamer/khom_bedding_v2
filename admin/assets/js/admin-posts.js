document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('.table tbody');
    if (!tableBody) return;

    tableBody.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.btn-delete-post');
        if (!deleteBtn) return;

        e.preventDefault();
        const postId = deleteBtn.dataset.id;
        const postTitle = deleteBtn.closest('tr').querySelector('td:first-of-type').textContent;

        if (confirm(`Bạn có chắc chắn muốn XÓA VĨNH VIỄN bài viết "${postTitle}"? Hành động này không thể hoàn tác.`)) {
            fetch('/admin/api/posts/handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', post_id: postId })
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