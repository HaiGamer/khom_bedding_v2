document.addEventListener('DOMContentLoaded', function() {
    // Lấy các element của form
    const form = document.getElementById('collection-form');
    const formTitle = document.getElementById('form-title');
    const collectionIdInput = document.getElementById('collection_id');
    const nameInput = document.getElementById('collection-name');
    const slugInput = document.getElementById('collection-slug');
    const descriptionInput = document.getElementById('collection-description');
    const imageInput = document.getElementById('collection-image');
    const imagePreview = document.getElementById('image-preview');
    const cancelBtn = document.getElementById('btn-cancel-edit');
    const tableBody = document.getElementById('collections-table-body');

    // Hàm reset form về trạng thái ban đầu
    function resetForm() {
        formTitle.textContent = 'Thêm bộ sưu tập mới';
        form.reset();
        collectionIdInput.value = '';
        imagePreview.src = '#';
        imagePreview.style.display = 'none';
        cancelBtn.classList.add('d-none');
    }

    // Hàm tạo slug tự động
    function generateSlug(text) {
        text = text.toString().toLowerCase().trim();
        const a = 'àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ';
        const b = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd';
        for (let i = 0; i < a.length; i++) {
            text = text.replace(new RegExp(a.charAt(i), 'g'), b.charAt(i));
        }
        return text.replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').replace(/\-\-+/g, '-');
    }
    nameInput.addEventListener('keyup', () => slugInput.value = generateSlug(nameInput.value));

    // Xử lý xem trước ảnh khi người dùng chọn file
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            imagePreview.src = URL.createObjectURL(file);
            imagePreview.style.display = 'block';
        }
    });

    // Xử lý nút "Hủy" trên form
    cancelBtn.addEventListener('click', () => resetForm());

    // Xử lý các nút trên bảng (Sửa, Xóa)
    tableBody.addEventListener('click', function(e) {
        const target = e.target;
        const row = target.closest('tr');
        if (!row) return;

        const id = row.dataset.id;

        // Xử lý nút "Sửa"
        if (target.classList.contains('btn-edit')) {
            formTitle.textContent = 'Chỉnh sửa bộ sưu tập';
            collectionIdInput.value = id;
            nameInput.value = row.dataset.name;
            slugInput.value = row.dataset.slug;
            descriptionInput.value = row.dataset.description;
            
            const imageUrl = row.dataset.imageUrl;
            if (imageUrl) {
                imagePreview.src = imageUrl;
                imagePreview.style.display = 'block';
            } else {
                imagePreview.style.display = 'none';
            }
            
            cancelBtn.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Xử lý nút "Xóa"
        if (target.classList.contains('btn-delete')) {
            if (confirm(`Bạn có chắc chắn muốn xóa bộ sưu tập ID ${id}? Hành động này không thể hoàn tác.`)) {
                // Do có file upload, chúng ta cần gửi FormData
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('collection_id', id);
                handleFormSubmit(formData, 'Xóa bộ sưu tập thành công!');
            }
        }
    });

    // Xử lý submit form
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const action = collectionIdInput.value ? 'edit' : 'add';
        formData.append('action', action);
        
        const message = action === 'add' ? 'Thêm bộ sưu tập thành công!' : 'Cập nhật bộ sưu tập thành công!';
        handleFormSubmit(formData, message);
    });

    // Hàm gửi yêu cầu AJAX chung
    function handleFormSubmit(formData, successMessage) {
        fetch('/admin/api/collections/handler.php', {
            method: 'POST',
            body: formData // Gửi FormData để upload file
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                alert(successMessage);
                window.location.reload();
            } else {
                alert('Lỗi: ' + result.message);
            }
        })
        .catch(err => {
            alert('Đã có lỗi nghiêm trọng xảy ra.');
            console.error(err);
        });
    }
});