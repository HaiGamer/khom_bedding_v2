document.addEventListener('DOMContentLoaded', function() {

    // =============================================
    // === THÊM VÀO: LOGIC XỬ LÝ HASH TRÊN URL ===
    // =============================================
    // Kích hoạt tab dựa trên hash của URL (ví dụ: #register)
    const urlHash = window.location.hash;
    if (urlHash) {
        const tabToActivate = document.querySelector(`#authTabs button[data-bs-target="${urlHash}"]`);
        if(tabToActivate) {
            const tab = new bootstrap.Tab(tabToActivate);
            tab.show();
        }
    }
    // Khi click vào các tab, cập nhật lại hash trên URL
    document.querySelectorAll('#authTabs button').forEach(button => {
        button.addEventListener('shown.bs.tab', event => {
            const hash = event.target.dataset.bsTarget;
            history.pushState(null, null, hash);
        });
    });
    // =============================================

    // Xử lý form đăng nhập
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(loginForm, '/api/login.php', '#login-alert');
        });
    }

    // Xử lý form đăng ký
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm-password').value;
            const alertBox = document.querySelector('#register-alert');

            if (password !== confirmPassword) {
                showAlert(alertBox, 'Mật khẩu xác nhận không khớp.', 'alert-danger');
                return;
            }

            handleFormSubmit(registerForm, '/api/register.php', '#register-alert');
        });
    }

    // Hàm chung để xử lý gửi form
    function handleFormSubmit(formElement, apiEndpoint, alertSelector) {
        const alertBox = document.querySelector(alertSelector);
        const submitBtn = formElement.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;

        // Vô hiệu hóa nút, hiển thị spinner
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
            if (data.success) {
                showAlert(alertBox, data.message, 'alert-success');
                // Nếu có đường dẫn chuyển hướng, thực hiện chuyển hướng
                if (data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1000); // Chờ 1 giây để người dùng đọc thông báo
                }
            } else {
                showAlert(alertBox, data.message, 'alert-danger');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showAlert(alertBox, 'Đã có lỗi xảy ra. Vui lòng thử lại.', 'alert-danger');
        })
        .finally(() => {
            // Khôi phục lại nút sau khi hoàn tất
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        });
    }
    
    // Hàm tiện ích để hiển thị và ẩn thông báo
    function showAlert(alertBox, message, alertClass) {
        alertBox.textContent = message;
        alertBox.className = `alert ${alertClass}`; // Reset class và thêm class mới
    }

    function hideAlert(alertBox) {
        alertBox.textContent = '';
        alertBox.classList.add('d-none');
    }

    

});