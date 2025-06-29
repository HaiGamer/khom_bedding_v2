document.addEventListener('DOMContentLoaded', function() {
    const addressSelector = document.getElementById('address-selector');
    const nameInput = document.getElementById('checkout-name');
    const phoneInput = document.getElementById('checkout-phone');
    const addressInput = document.getElementById('checkout-address');
    const shippingForm = document.getElementById('shipping-details-form');
    const checkoutForm = document.getElementById('checkout-form');

    // Hàm để cập nhật form với dữ liệu từ một option
    function updateShippingForm(selectedOption) {
        if (selectedOption.value === 'new') {
            // Xóa trống form để người dùng nhập địa chỉ mới
            nameInput.value = '';
            phoneInput.value = '';
            addressInput.value = '';
            nameInput.focus();
        } else {
            // Lấy dữ liệu từ data attributes và điền vào form
            nameInput.value = selectedOption.dataset.name || '';
            phoneInput.value = selectedOption.dataset.phone || '';
            addressInput.value = selectedOption.dataset.address || '';
        }
    }

    // Gắn sự kiện 'change' cho dropdown chọn địa chỉ
    if (addressSelector) {
        addressSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            updateShippingForm(selectedOption);
        });

        // Tự động điền form lần đầu khi tải trang
        updateShippingForm(addressSelector.options[addressSelector.selectedIndex]);
    }

    // Xử lý nút "Đặt Hàng"
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';

            const formData = new FormData(checkoutForm);

            fetch('/api/checkout/place-order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Chuyển hướng đến trang cảm ơn với mã đơn hàng
                    window.location.href = `/thank-you.html?order_id=${data.order_id}`;
                } else {
                    alert(data.message); // Hiển thị lỗi cho người dùng
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã có lỗi nghiêm trọng xảy ra. Vui lòng thử lại sau.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            });
        });
    }
});