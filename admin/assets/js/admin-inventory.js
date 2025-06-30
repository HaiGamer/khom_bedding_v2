document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('inventory-table-body');
    if (!tableBody) return;

    const formatCurrency = number => new Intl.NumberFormat('vi-VN').format(number) + 'đ';

    // Hiển thị các nút Lưu/Hủy khi người dùng thay đổi giá trị trong ô input
    tableBody.addEventListener('input', function(e) {
        if (e.target.matches('.stock-quantity-input')) {
            const group = e.target.closest('.stock-update-group');
            group.querySelector('.btn-save-stock').classList.remove('d-none');
            group.querySelector('.btn-cancel-stock').classList.remove('d-none');
        }
    });

    // Xử lý khi nhấn nút Lưu hoặc Hủy
    tableBody.addEventListener('click', function(e) {
        const button = e.target.closest('button');
        if (!button) return;

        const group = button.closest('.stock-update-group');
        const input = group.querySelector('.stock-quantity-input');
        
        if (button.matches('.btn-cancel-stock')) {
            input.value = input.defaultValue; // Trả lại giá trị ban đầu
            group.querySelector('.btn-save-stock').classList.add('d-none');
            group.querySelector('.btn-cancel-stock').classList.add('d-none');
        }

        if (button.matches('.btn-save-stock')) {
            const variantId = input.dataset.variantId;
            const newQuantity = input.value;
            const costPrice = parseFloat(input.dataset.costPrice);

            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            fetch('/admin/api/inventory/update-stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ variant_id: variantId, quantity: newQuantity })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.defaultValue = newQuantity; // Cập nhật giá trị gốc mới
                    
                    // NÂNG CẤP: Cập nhật lại cột Tổng vốn tồn kho
                    const totalValueCell = input.closest('tr').querySelector('.total-value-cell');
                    if (totalValueCell) {
                        const newTotalValue = costPrice * parseInt(newQuantity, 10);
                        totalValueCell.textContent = formatCurrency(newTotalValue);
                    }
                } else {
                    alert('Lỗi: ' + data.message);
                    input.value = input.defaultValue; // Trả lại giá trị cũ nếu lỗi
                }
            })
            .catch(err => {
                alert('Có lỗi nghiêm trọng xảy ra.');
                console.error(err);
                input.value = input.defaultValue; // Trả lại giá trị cũ nếu lỗi
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-check-lg"></i>';
                group.querySelector('.btn-save-stock').classList.add('d-none');
                group.querySelector('.btn-cancel-stock').classList.add('d-none');
            });
        }
    });

    // Lưu giá trị ban đầu cho tất cả các ô input khi trang tải
    document.querySelectorAll('.stock-quantity-input').forEach(input => {
        input.defaultValue = input.value;
    });
});