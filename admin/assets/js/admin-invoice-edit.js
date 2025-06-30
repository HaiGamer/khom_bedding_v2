document.addEventListener('DOMContentLoaded', function() {
    const mainForm = document.getElementById('invoice-edit-form');
    const tableBody = document.getElementById('invoice-items-body');
    const pageAlert = document.getElementById('page-alert');

    if (!mainForm || !tableBody) return;

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

    // Hàm tính toán lại các dòng và tổng tiền
    function updateLineTotal(row) {
        const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const lineTotalEl = row.querySelector('.line-total');
        const total = quantity * price;
        lineTotalEl.textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
        updateInvoiceTotal();
    }

    function updateInvoiceTotal() {
        let total = 0;
        tableBody.querySelectorAll('tr').forEach(row => {
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            total += quantity * price;
        });
        document.getElementById('invoice-total').textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
    }

    // Lắng nghe sự kiện input để tính lại tiền ngay lập tức
    tableBody.addEventListener('input', function(e) {
        if (e.target.matches('.item-quantity, .item-price')) {
            updateLineTotal(e.target.closest('tr'));
        }
    });

    // Lắng nghe sự kiện click để xóa dòng
    tableBody.addEventListener('click', function(e) {
        if (e.target.matches('.btn-remove-item')) {
            e.target.closest('tr').remove();
            updateInvoiceTotal();
        }
    });

    // Tính toán lại toàn bộ khi tải trang
    tableBody.querySelectorAll('tr').forEach(updateLineTotal);

    // === LOGIC MỚI: XỬ LÝ SUBMIT FORM BẰNG AJAX ===
    mainForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = mainForm.querySelector('button[type="submit"]');
        const formData = new FormData(mainForm);
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';

        fetch('/admin/api/customers/invoice-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Lưu thông báo vào sessionStorage và tải lại trang
                sessionStorage.setItem('flashMessage', JSON.stringify({ type: 'success', message: data.message }));
                window.location.reload();
            } else {
                showAlert(pageAlert, data.message, 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Lưu lại thay đổi';
            }
        })
        .catch(err => {
            showAlert(pageAlert, 'Có lỗi nghiêm trọng xảy ra, vui lòng kiểm tra Console.', 'danger');
            console.error(err);
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Lưu lại thay đổi';
        });
    });
});