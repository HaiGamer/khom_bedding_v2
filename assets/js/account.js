document.addEventListener('DOMContentLoaded', function() {
    
    const updateInfoForm = document.getElementById('update-info-form');
    if (updateInfoForm) {
        updateInfoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(
                updateInfoForm,
                '/api/update-info.php',
                '#update-info-alert',
                () => { // Hàm callback khi thành công
                    // Cập nhật tên hiển thị ở sidebar mà không cần tải lại trang
                    const newName = document.getElementById('full_name').value;
                    document.querySelector('.account-user-info h5').textContent = newName;
                }
            );
        });
    }

    const updatePasswordForm = document.getElementById('update-password-form');
    if (updatePasswordForm) {
        updatePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('new_password').value;
            const confirmNewPassword = document.getElementById('confirm_new_password').value;
            const alertBox = document.querySelector('#update-password-alert');

            if (newPassword !== confirmNewPassword) {
                showAlert(alertBox, 'Mật khẩu mới không khớp.', 'alert-danger');
                return;
            }

            handleFormSubmit(
                updatePasswordForm,
                '/api/update-password.php',
                '#update-password-alert',
                () => { // Hàm callback khi thành công
                    updatePasswordForm.reset(); // Xóa các trường mật khẩu
                }
            );
        });
    }


    // Hàm chung để xử lý gửi form AJAX
    function handleFormSubmit(formElement, apiEndpoint, alertSelector, successCallback) {
        const alertBox = document.querySelector(alertSelector);
        const submitBtn = formElement.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
        hideAlert(alertBox);

        const formData = new FormData(formElement);

        fetch(apiEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showAlert(alertBox, data.message, data.success ? 'alert-success' : 'alert-danger');
            if (data.success && successCallback) {
                successCallback();
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showAlert(alertBox, 'Đã có lỗi xảy ra. Vui lòng thử lại.', 'alert-danger');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        });
    }
    
    function showAlert(alertBox, message, alertClass) {
        alertBox.textContent = message;
        alertBox.className = `alert mt-3 ${alertClass}`;
    }

    function hideAlert(alertBox) {
        alertBox.textContent = '';
        alertBox.className = 'alert d-none';
    }
});