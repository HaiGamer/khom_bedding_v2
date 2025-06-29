document.addEventListener('DOMContentLoaded', function() {
    const addressModal = new bootstrap.Modal(document.getElementById('address-modal'));
    const addressForm = document.getElementById('address-form');
    const addressListContainer = document.getElementById('address-list-container');
    const modalTitle = document.getElementById('addressModalLabel');
    const modalAlert = document.getElementById('modal-alert');
    const addressIdInput = document.getElementById('address_id');
    
    // Hàm để tải và render lại danh sách địa chỉ
    function refreshAddressList() {
        fetch('/api/get-addresses.php')
            .then(response => response.text())
            .then(html => {
                addressListContainer.innerHTML = html;
                // ===================================================================
                // === LOGIC MỚI: KIỂM TRA SỐ LƯỢNG VÀ ẨN/HIỆN NÚT THÊM MỚI ===
                // ===================================================================
                const addressCount = addressListContainer.querySelectorAll('.address-card').length;
                const addAddressButton = document.getElementById('btn-add-address');

                if (addAddressButton) {
                    if (addressCount >= 4) {
                        addAddressButton.style.display = 'none'; // Ẩn nút nếu đã đủ 4 địa chỉ
                    } else {
                        addAddressButton.style.display = 'block'; // Hiện nút nếu chưa đủ
                    }
                }
                // ===================================================================
            });
    }

    // Tải danh sách địa chỉ lần đầu khi trang được mở
    refreshAddressList();

    // Xử lý khi nhấn nút "Thêm địa chỉ mới"
    document.getElementById('btn-add-address').addEventListener('click', function() {
        modalTitle.textContent = 'Thêm địa chỉ mới';
        addressForm.reset();
        addressIdInput.value = '';
        hideAlert(modalAlert);
        addressModal.show();
    });

    // Xử lý khi nhấn nút Sửa, Xóa, Đặt làm mặc định
    addressListContainer.addEventListener('click', function(e) {
        const target = e.target;
        
        // --- Sửa địa chỉ ---
        if (target.matches('.btn-edit-address')) {
            e.preventDefault();
            const card = target.closest('.address-card');
            hideAlert(modalAlert);
            modalTitle.textContent = 'Cập nhật địa chỉ';
            
            addressIdInput.value = card.dataset.id;
            document.getElementById('address-full-name').value = card.dataset.name;
            document.getElementById('address-phone-number').value = card.dataset.phone;
            document.getElementById('address-line').value = card.dataset.address;
            document.getElementById('address-is-default').checked = (card.dataset.default === '1');

            addressModal.show();
        }

        // --- Xóa địa chỉ ---
        if (target.matches('.btn-delete-address')) {
            e.preventDefault();
            if (confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')) {
                const addressId = target.closest('.address-card').dataset.id;
                fetchApi('/api/delete-address.php', { address_id: addressId }, 'Địa chỉ đã được xóa thành công.');
            }
        }

        // --- Đặt làm mặc định ---
        if (target.matches('.btn-set-default')) {
            e.preventDefault();
            const addressId = target.closest('.address-card').dataset.id;
            fetchApi('/api/set-default-address.php', { address_id: addressId }, 'Đã đặt làm địa chỉ mặc định.');
        }
    });

    // Xử lý khi submit form trong modal
    addressForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(addressForm);
        const submitBtn = addressForm.querySelector('button[type="submit"]');

        toggleSubmitButton(submitBtn, true);

        fetch('/api/manage-address.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addressModal.hide();
                showAlert(document.getElementById('address-list-alert'), data.message, 'alert-success');
                refreshAddressList();
            } else {
                showAlert(modalAlert, data.message, 'alert-danger');
            }
        })
        .catch(error => showAlert(modalAlert, 'Đã có lỗi xảy ra.', 'alert-danger'))
        .finally(() => toggleSubmitButton(submitBtn, false));
    });

    // Hàm chung để gọi API cho các hành động đơn giản
    function fetchApi(endpoint, bodyData, successMessage) {
        fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(bodyData)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showAlert(document.getElementById('address-list-alert'), successMessage, 'alert-success');
                refreshAddressList();
            } else {
                 showAlert(document.getElementById('address-list-alert'), data.message, 'alert-danger');
            }
        })
        .catch(error => showAlert(document.getElementById('address-list-alert'), 'Đã có lỗi xảy ra.', 'alert-danger'));
    }

    // Các hàm tiện ích
    const showAlert = (element, message, type) => {
        element.textContent = message;
        element.className = `alert ${type}`;
    };
    const hideAlert = (element) => element.className = 'alert d-none';
    const toggleSubmitButton = (btn, isLoading) => {
        btn.disabled = isLoading;
        btn.innerHTML = isLoading ? '<span class="spinner-border spinner-border-sm"></span> Đang lưu...' : 'Lưu địa chỉ';
    };
});