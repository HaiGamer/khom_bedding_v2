document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contact-form');
    if (!contactForm) return;

    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const alertBox = document.getElementById('contact-alert');
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang gửi...';
        alertBox.className = 'alert d-none';

        const formData = new FormData(this);

        fetch('/api/contact-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alertBox.className = `alert alert-${data.success ? 'success' : 'danger'}`;
            alertBox.textContent = data.message;
            if (data.success) {
                contactForm.reset();
            }
        })
        .catch(err => {
            console.error(err);
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = 'Đã có lỗi xảy ra, vui lòng thử lại.';
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            // Reset hCaptcha sau khi submit
            hcaptcha.reset();
        });
    });
});