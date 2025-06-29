document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('admin-login-form');
    if (!loginForm) return;

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const alertBox = document.getElementById('login-alert');
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
        alertBox.className = 'alert d-none';

        fetch('/admin/api/login-handler.php', {
            method: 'POST',
            body: new FormData(loginForm)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect_url || '/admin/';
            } else {
                alertBox.textContent = data.message;
                alertBox.className = 'alert alert-danger';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertBox.textContent = 'Đã có lỗi xảy ra. Vui lòng thử lại.';
            alertBox.className = 'alert alert-danger';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        });
    });
});