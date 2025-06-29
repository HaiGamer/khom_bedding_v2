document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('collection-form');
    if (!form) return;

    const formTitle = document.getElementById('form-title');
    const collectionIdInput = document.getElementById('collection_id');
    const actionInput = document.getElementById('form-action');
    const nameInput = document.getElementById('collection-name');
    const slugInput = document.getElementById('collection-slug');
    const descriptionInput = document.getElementById('collection-description');
    const imageInput = document.getElementById('collection-image');
    const imagePreview = document.getElementById('image-preview');
    const cancelBtn = document.getElementById('btn-cancel-edit');
    const tableBody = document.getElementById('collections-table-body');
    const submitBtn = form.querySelector('button[type="submit"]');

    function generateSlug(text) {
        text = text.toString().toLowerCase().trim();
        const a = 'àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ';
        const b = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd';
        for (let i = 0; i < a.length; i++) {
            text = text.replace(new RegExp(a.charAt(i), 'g'), b.charAt(i));
        }
        return text.replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').replace(/\-\-+/g, '-');
    }

    nameInput.addEventListener('keyup', () => {
        if (actionInput.value === 'add') { // Chỉ tự tạo slug khi thêm mới
            slugInput.value = generateSlug(nameInput.value);
        }
    });
    
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            imagePreview.src = URL.createObjectURL(file);
            imagePreview.style.display = 'block';
        } else {
            imagePreview.style.display = 'none';
        }
    });

    function resetForm() {
        formTitle.textContent = 'Thêm bộ sưu tập mới';
        form.reset();
        collectionIdInput.value = '';
        actionInput.value = 'add';
        slugInput.readOnly = false;
        imagePreview.src = '#';
        imagePreview.style.display = 'none';
        cancelBtn.classList.add('d-none');
    }
    cancelBtn.addEventListener('click', resetForm);

    tableBody.addEventListener('click', function(e) {
        const target = e.target;
        const row = target.closest('tr');
        if (!row) return;
        const id = row.dataset.id;

        if (target.classList.contains('btn-edit')) {
            formTitle.textContent = 'Chỉnh sửa bộ sưu tập';
            collectionIdInput.value = id;
            actionInput.value = 'edit';
            nameInput.value = row.dataset.name;
            slugInput.value = row.dataset.slug;
            slugInput.readOnly = true; // Không cho sửa slug để đảm bảo link không bị hỏng
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

        if (target.classList.contains('btn-delete')) {
            const collectionName = row.querySelector('.coll-name').textContent;
            if (confirm(`Bạn có chắc chắn muốn xóa bộ sưu tập "${collectionName}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('collection_id', id);
                handleFormSubmit(formData, 'Xóa bộ sưu tập thành công!');
            }
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const successMessage = (actionInput.value === 'add') ? 'Thêm bộ sưu tập thành công!' : 'Cập nhật thành công!';
        handleFormSubmit(formData, successMessage);
    });

    function handleFormSubmit(formData, successMessage) {
        toggleSubmitButton(true);
        fetch('/admin/api/collections/handler.php', { method: 'POST', body: formData })
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

    function toggleSubmitButton(isLoading) {
        const originalText = 'Lưu';
        submitBtn.disabled = isLoading;
        submitBtn.innerHTML = isLoading ? '<span class="spinner-border spinner-border-sm"></span> Đang lưu...' : originalText;
    }
});