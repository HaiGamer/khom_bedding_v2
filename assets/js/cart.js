document.addEventListener('DOMContentLoaded', function() {
    const cartContainer = document.getElementById('cart-items-container');
    if (!cartContainer) return;

    // Hàm cập nhật tổng tiền trên giao diện
    const updateSummaryTotals = (subtotal) => {
        const subtotalEl = document.getElementById('cart-subtotal');
        const grandtotalEl = document.getElementById('cart-grandtotal');
        const formattedTotal = formatCurrency(subtotal);
        if (subtotalEl) subtotalEl.textContent = formattedTotal;
        if (grandtotalEl) grandtotalEl.textContent = formattedTotal;
    };

    // Hàm gọi API
    const updateCartApi = (variantId, quantity, action) => {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('variant_id', variantId);
        formData.append('quantity', quantity);
        return fetch('/api/cart/cart-handler.php', { method: 'POST', body: formData })
            .then(response => response.json());
    };

    // Bắt sự kiện click trên toàn bộ container
    cartContainer.addEventListener('click', function(e) {
        // Xử lý nút Xóa
        if (e.target.matches('.btn-remove-item')) {
            e.preventDefault();
            const itemElement = e.target.closest('.cart-item');
            const variantId = itemElement.dataset.variantId;

            if (confirm('Bạn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
                updateCartApi(variantId, 0, 'remove').then(data => {
                    if (data.success) {
                        itemElement.remove(); // Xóa sản phẩm khỏi giao diện
                        updateSummaryTotals(data.subtotal); // Cập nhật tổng tiền từ API
                        document.querySelectorAll('.cart-count').forEach(el => el.textContent = data.cart_item_count);
                        window.showToast('Thành công', 'Đã xóa sản phẩm khỏi giỏ hàng.');
                        
                        if (document.querySelectorAll('.cart-item').length === 0) {
                            location.reload(); 
                        }
                    } else {
                        alert(data.message || 'Có lỗi xảy ra.');
                    }
                });
            }
        }
    });
    
    // Xử lý khi thay đổi số lượng
    cartContainer.addEventListener('change', function(e) {
        if(e.target.matches('.quantity-input')) {
            const input = e.target;
            const itemElement = input.closest('.cart-item');
            const variantId = itemElement.dataset.variantId;
            let quantity = parseInt(input.value, 10);
            const maxQuantity = parseInt(input.max, 10);
            const originalValue = input.dataset.originalValue || 1; // Lưu giá trị cũ để khôi phục khi lỗi

            // Kiểm tra số lượng hợp lệ
            if(isNaN(quantity) || quantity < 1) {
                quantity = 1;
                input.value = 1;
            }
            if(quantity > maxQuantity) {
                alert(`Xin lỗi, chỉ còn ${maxQuantity} sản phẩm trong kho.`);
                quantity = maxQuantity;
                input.value = maxQuantity;
            }
            
            const pricePerItem = parseFloat(itemElement.querySelector('.price-per-item').textContent.replace(/[^0-9]/g, ''));

            updateCartApi(variantId, quantity, 'update').then(data => {
                if (data.success) {
                    itemElement.querySelector('.line-total').textContent = formatCurrency(pricePerItem * quantity);
                    updateSummaryTotals(data.subtotal); // Cập nhật tổng tiền từ API
                    document.querySelectorAll('.cart-count').forEach(el => el.textContent = data.cart_item_count);
                    input.dataset.originalValue = quantity; // Cập nhật giá trị gốc mới
                } else {
                    alert(data.message);
                    input.value = originalValue; // Trả lại giá trị cũ nếu có lỗi
                }
            });
        }
    });

    // Lưu giá trị ban đầu của ô số lượng
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.dataset.originalValue = input.value;
    });

    const formatCurrency = number => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
});