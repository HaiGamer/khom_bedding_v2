document.addEventListener('DOMContentLoaded', function() {
    if (typeof orderData === 'undefined') return;

    const shippingContainer = document.getElementById('shipping-info-container');
    const statusForm = document.getElementById('status-update-form');
    const copyBtn = document.getElementById('btn-copy-shipping');
    const mainAlert = document.getElementById('main-alert');

    // Hàm để render thông tin giao hàng
    function renderShippingInfo(data, isEditing = false) {
        if (isEditing) {
            shippingContainer.innerHTML = `
                <form id="shipping-update-form">
                    <input type="hidden" name="order_id" value="${data.id}">
                    <div class="mb-2">
                        <label class="form-label">Tên người nhận</label>
                        <input type="text" class="form-control" name="customer_name" value="${data.customer_name}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" class="form-control" name="customer_phone" value="${data.customer_phone}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <textarea class="form-control" name="customer_address" rows="3">${data.customer_address}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-cancel-edit">Hủy</button>
                </form>
            `;
        } else {
            shippingContainer.innerHTML = `
                <p class="mb-1"><strong>${data.customer_name}</strong></p>
                <p class="mb-1">${data.customer_phone}</p>
                <p class="mb-0">${data.customer_address}</p>
                <hr>
                <button class="btn btn-sm btn-outline-primary" id="btn-edit-shipping">Sửa thông tin</button>
            `;
        }
    }

    // --- XỬ LÝ SỰ KIỆN ---

    // Cập nhật trạng thái
    statusForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(statusForm);
        formData.append('action', 'update_status');
        
        fetch('/admin/api/orders/order-handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => handleApiResponse(data, 'Cập nhật trạng thái thành công!'));
    });

    // Các nút bấm trên khu vực giao hàng (Sửa, Hủy, Lưu)
    shippingContainer.addEventListener('click', function(e) {
        if (e.target.id === 'btn-edit-shipping') {
            renderShippingInfo(orderData, true);
        }
        if (e.target.id === 'btn-cancel-edit') {
            renderShippingInfo(orderData, false);
        }
    });

    // Submit form sửa thông tin giao hàng
    shippingContainer.addEventListener('submit', function(e) {
        if (e.target.id === 'shipping-update-form') {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_shipping');

            fetch('/admin/api/orders/order-handler.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Cập nhật lại dữ liệu gốc và render lại
                        orderData.customer_name = formData.get('customer_name');
                        orderData.customer_phone = formData.get('customer_phone');
                        orderData.customer_address = formData.get('customer_address');
                        renderShippingInfo(orderData, false);
                        handleApiResponse(data, 'Cập nhật thông tin thành công!');
                    } else {
                        handleApiResponse(data);
                    }
                });
        }
    });

    // Nút sao chép
    copyBtn.addEventListener('click', function() {
        const textToCopy = `Tên: ${orderData.customer_name}\nSĐT: ${orderData.customer_phone}\nĐịa chỉ: ${orderData.customer_address}`;
        navigator.clipboard.writeText(textToCopy).then(() => {
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="bi bi-check-lg"></i> Đã chép!';
            setTimeout(() => { copyBtn.innerHTML = originalText; }, 2000);
        });
    });

    // Hàm xử lý phản hồi API chung
    function handleApiResponse(data, successMessage) {
        mainAlert.textContent = data.message || successMessage;
        mainAlert.className = `alert ${data.success ? 'alert-success' : 'alert-danger'}`;
        if (data.success) {
            setTimeout(() => { mainAlert.className = 'alert d-none'; }, 3000);
        }
    }

    // --- KHỞI TẠO ---
    renderShippingInfo(orderData);
});