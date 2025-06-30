document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('customer-form');
    if (!form) return;

    // Lấy các element
    const formAlert = document.getElementById('form-alert');
    const pageAlert = document.getElementById('page-alert');
    const formTitle = document.getElementById('form-title');
    const customerIdInput = document.getElementById('customer_id');
    const nameInput = document.getElementById('customer-name');
    const phoneInput = document.getElementById('customer-phone');
    const addressInput = document.getElementById('customer-address');
    const emailInput = document.getElementById('customer-email');
    const typeInput = document.getElementById('customer-type');
    const cancelBtn = document.getElementById('btn-cancel-edit');
    const tableBody = document.getElementById('customers-table-body');

    // Hàm hiển thị thông báo
    function showAlert(element, message, type = 'danger') {
        element.textContent = message;
        element.className = `alert alert-${type}`;
    }

    // Kiểm tra và hiển thị thông báo thành công được lưu từ lần trước
    const flashMessage = sessionStorage.getItem('flashMessage');
    if (flashMessage) {
        const { type, message } = JSON.parse(flashMessage);
        showAlert(pageAlert, message, type);
        sessionStorage.removeItem('flashMessage');
    }

    // Hàm reset form về trạng thái ban đầu
    function resetForm() {
        formTitle.textContent = 'Thêm khách hàng mới';
        form.reset();
        customerIdInput.value = '';
        cancelBtn.classList.add('d-none');
        formAlert.className = 'alert d-none'; // Ẩn thông báo lỗi của form
    }
    cancelBtn.addEventListener('click', resetForm);
    
    // Xử lý nút Sửa và Xóa trên bảng
    tableBody.addEventListener('click', function(e) {
        const target = e.target;
        const row = target.closest('tr');
        if (!row) return;
        
        // Dùng try...catch để phòng trường hợp dữ liệu JSON không hợp lệ
        try {
            const customerData = JSON.parse(row.dataset.customer);

            // === PHẦN LOGIC BỊ THIẾU ĐÃ ĐƯỢC BỔ SUNG ===
            if (target.classList.contains('btn-edit')) {
                formTitle.textContent = 'Chỉnh sửa khách hàng';
                customerIdInput.value = customerData.id;
                nameInput.value = customerData.customer_name;
                phoneInput.value = customerData.phone_number;
                addressInput.value = customerData.address;
                emailInput.value = customerData.email;
                typeInput.value = customerData.customer_type;
                cancelBtn.classList.remove('d-none');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            // === KẾT THÚC PHẦN BỔ SUNG ===

            if (target.classList.contains('btn-delete')) {
                if (confirm(`Bạn có chắc chắn muốn xóa khách hàng "${customerData.customer_name}"?`)) {
                    handleApiRequest(
                        { action: 'delete', customer_id: customerData.id },
                        'Xóa khách hàng thành công!'
                    );
                }
            }
        } catch (error) {
            console.error("Lỗi khi đọc dữ liệu khách hàng:", error);
            alert("Không thể đọc dữ liệu của khách hàng này.");
        }
    });

    // Xử lý submit form
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const action = customerIdInput.value ? 'edit' : 'add';
        formData.append('action', action);
        const message = action === 'add' ? 'Thêm khách hàng thành công!' : 'Cập nhật thành công!';
        handleApiRequest(Object.fromEntries(formData.entries()), message);
    });

    // Hàm gửi yêu cầu AJAX được nâng cấp
    function handleApiRequest(data, successMessage) {
        fetch('/admin/api/customers/handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                sessionStorage.setItem('flashMessage', JSON.stringify({ type: 'success', message: successMessage }));
                window.location.reload();
            } else {
                showAlert(formAlert, result.message, 'danger');
            }
        })
        .catch(err => {
            showAlert(formAlert, 'Đã có lỗi nghiêm trọng xảy ra.', 'danger');
            console.error(err);
        });
    }
});