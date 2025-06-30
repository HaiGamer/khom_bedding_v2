document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('banner-form');
    if (!form) return;

    // Lấy các element của form
    const formTitle = document.getElementById('form-title');
    const bannerIdInput = document.getElementById('banner_id');
    const titleInput = document.getElementById('banner-title');
    const linkInput = document.getElementById('banner-link');
    const orderInput = document.getElementById('display-order');
    const activeSwitch = document.getElementById('is-active');
    const cancelBtn = document.getElementById('btn-cancel-edit');
    const tableBody = document.getElementById('banners-table-body');
    const submitBtn = form.querySelector('button[type="submit"]');


    // Hàm reset form về trạng thái ban đầu
    function resetForm() {
        formTitle.textContent = 'Thêm banner mới';
        form.reset(); // Xóa tất cả các giá trị trong form
        bannerIdInput.value = '';
        cancelBtn.classList.add('d-none');
    }
    cancelBtn.addEventListener('click', resetForm);

    // Xử lý các nút trên bảng (Sửa, Xóa)
    tableBody.addEventListener('click', function(e) {
        const target = e.target;
        const row = target.closest('tr');
        if (!row) return;

        const bannerData = JSON.parse(row.dataset.banner);

        // Xử lý nút SỬA
        if (target.classList.contains('btn-edit')) {
            formTitle.textContent = 'Chỉnh sửa banner';
            bannerIdInput.value = bannerData.id;
            titleInput.value = bannerData.title;
            linkInput.value = bannerData.link_url;
            orderInput.value = bannerData.display_order;
            activeSwitch.checked = (bannerData.is_active == 1);
            
            cancelBtn.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' }); // Cuộn lên đầu trang
        }

        // Xử lý nút XÓA
        if (target.classList.contains('btn-delete')) {
            if (confirm(`Bạn có chắc chắn muốn xóa banner "${bannerData.title}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('banner_id', bannerData.id);
                handleFormSubmit(formData, 'Xóa banner thành công!');
            }
        }
    });

    // Xử lý SUBMIT form chính (Thêm hoặc Sửa)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const action = bannerIdInput.value ? 'edit' : 'add';
        formData.append('action', action);
        
        const successMessage = action === 'add' ? 'Thêm banner thành công!' : 'Cập nhật banner thành công!';
        handleFormSubmit(formData, successMessage);
    });


    // Hàm gửi yêu cầu AJAX chung
    function handleFormSubmit(formData, successMessage) {
        toggleSubmitButton(true);

        fetch('/admin/api/banners/handler.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                alert(successMessage);
                window.location.reload();
            } else {
                alert('Lỗi: ' + result.message);
                toggleSubmitButton(false);
            }
        })
        .catch(err => {
            alert('Đã có lỗi nghiêm trọng xảy ra.');
            console.error(err);
            toggleSubmitButton(false);
        });
    }

    // Hàm tiện ích để bật/tắt nút submit
    function toggleSubmitButton(isLoading) {
        const originalText = 'Lưu Banner';
        submitBtn.disabled = isLoading;
        submitBtn.innerHTML = isLoading ? '<span class="spinner-border spinner-border-sm"></span> Đang lưu...' : originalText;
    }
});