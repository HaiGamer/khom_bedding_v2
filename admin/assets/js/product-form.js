/**
 * Hàm này sẽ được gọi sau khi TOÀN BỘ trang (bao gồm script của CKEditor) đã tải xong.
 * Đây là nơi an toàn để khởi tạo CKEditor.
 */
// Cần sửa lại hàm khởi tạo CKEditor một chút để có thể truy cập instance
    function initializeCKEditor() {
        const descriptionTextarea = document.getElementById('product-description');
        if (descriptionTextarea && typeof ClassicEditor !== 'undefined') {
            ClassicEditor
                .create(descriptionTextarea)
                .then(editor => {
                    // Lưu lại instance của editor để có thể lấy data khi submit
                    descriptionTextarea.ckeditorInstance = editor;
                })
                .catch(error => { console.error(error); });
        }
    }


document.addEventListener('DOMContentLoaded', function() {
    

    // --- HÀM TẠO SLUG ĐÃ SỬA LỖI TIẾNG VIỆT ---
    function generateSlug(text) {
        text = text.toString().toLowerCase().trim();
        const a = 'àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ';
        const b = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd';
        for (let i = 0; i < a.length; i++) {
            text = text.replace(new RegExp(a.charAt(i), 'g'), b.charAt(i));
        }
        return text
            .replace(/[^\w\s-]/g, '') // Xóa các ký tự không phải chữ, số, khoảng trắng, hoặc gạch ngang
            .replace(/\s+/g, '-')     // Thay thế khoảng trắng bằng gạch ngang
            .replace(/\-\-+/g, '-');    // Thay thế nhiều gạch ngang bằng một
    }

    const nameInput = document.getElementById('product-name');
    const slugInput = document.getElementById('product-slug');
    if (nameInput && slugInput) {
        nameInput.addEventListener('keyup', function() {
            slugInput.value = generateSlug(this.value);
        });
    }

    // --- LOGIC QUẢN LÝ FORM PHIÊN BẢN (giữ nguyên) ---
    const variantsContainer = document.getElementById('variants-container');
    const addVariantBtn = document.getElementById('btn-add-variant');
    const variantTemplate = document.getElementById('variant-template');
    if (addVariantBtn && variantTemplate && variantsContainer) {
        addVariantBtn.addEventListener('click', function() {
            const templateContent = variantTemplate.innerHTML;
            const newIndex = Date.now();
            const newVariantHtml = templateContent.replace(/__INDEX__/g, newIndex);
            variantsContainer.insertAdjacentHTML('beforeend', newVariantHtml);
        });
    }
    if (variantsContainer) {
        variantsContainer.addEventListener('click', function(e) {
            if (e.target.matches('.btn-remove-variant')) {
                if (confirm('Bạn có chắc chắn muốn xóa phiên bản này? Hành động này sẽ xóa vĩnh viễn phiên bản khỏi CSDL sau khi bạn Lưu sản phẩm.')) {
                    e.target.closest('.variant-item').remove();
                }
            }
        });
    }

    // --- LOGIC SUBMIT FORM CHÍNH ---
    const productForm = document.getElementById('product-form');
    if(productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('form-alert');

            const editor = document.getElementById('product-description').ckeditorInstance;
            if (editor) {
                document.getElementById('product-description').value = editor.getData();
            }

            const formData = new FormData(this);
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';
            alertBox.className = 'alert d-none';

            fetch('/admin/api/product-save.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                alertBox.textContent = data.message;
                alertBox.className = `alert ${data.success ? 'alert-success' : 'alert-danger'}`;
                
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = `/admin/product-edit.php?id=${data.product_id}`;
                    }, 1000);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Lưu sản phẩm';
                }
            })
            .catch(err => {
                alertBox.textContent = 'Có lỗi nghiêm trọng xảy ra.';
                alertBox.className = 'alert alert-danger';
                console.error(err);
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Lưu sản phẩm';
            });
        });
    }

      // --- XỬ LÝ NÚT TẢI ẢNH ---
      // =============================================
    // === LOGIC MỚI: QUẢN LÝ THƯ VIỆN ẢNH ===
    // =============================================
    const galleryContainer = document.getElementById('image-gallery-container');
    const imageUploadInput = document.getElementById('image-upload-input');
    const productId = document.querySelector('input[name="product_id"]').value;

    // --- Xử lý tải ảnh lên ---
    if (imageUploadInput) {
        imageUploadInput.addEventListener('change', function() {
            const files = this.files;
            if (files.length === 0) return;

            const formData = new FormData();
            formData.append('product_id', productId);
            for (let i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }
            
            // TODO: Hiển thị trạng thái đang tải...
            fetch('/admin/api/products/image-upload.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.files.forEach(file => {
                            // Tạo và thêm thumbnail mới vào giao diện
                            const newThumbnail = createThumbnailElement(file.id, file.url, false);
                            galleryContainer.appendChild(newThumbnail);
                        });
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => console.error(err));
        });
    }

    // --- Xử lý các nút bấm trên ảnh (Xóa, Đặt đại diện) ---
    if (galleryContainer) {
        galleryContainer.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete-image');
            const featureBtn = e.target.closest('.btn-set-featured');
            
            if (deleteBtn) {
                handleDeleteImage(deleteBtn);
            }
            if (featureBtn) {
                handleSetFeaturedImage(featureBtn);
            }
        });
    }

    function handleDeleteImage(button) {
        if (!confirm('Bạn có chắc chắn muốn xóa ảnh này?')) return;
        
        const thumbnail = button.closest('.admin-image-thumbnail');
        const imageId = thumbnail.dataset.imageId;

        fetch('/admin/api/products/image-handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'delete', image_id: imageId })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                thumbnail.remove();
            } else {
                alert(data.message);
            }
        });
    }
    
    function handleSetFeaturedImage(button) {
        const thumbnail = button.closest('.admin-image-thumbnail');
        const imageId = thumbnail.dataset.imageId;

        fetch('/admin/api/products/image-handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'set_featured', image_id: imageId, product_id: productId })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Bỏ trạng thái featured của ảnh cũ
                const oldFeatured = galleryContainer.querySelector('.admin-image-thumbnail.featured');
                if(oldFeatured) {
                    oldFeatured.classList.remove('featured');
                    oldFeatured.querySelector('.btn-set-featured i').className = 'bi bi-star';
                }
                // Thêm trạng thái featured cho ảnh mới
                thumbnail.classList.add('featured');
                button.querySelector('i').className = 'bi bi-star-fill text-warning';
            } else {
                alert(data.message);
            }
        });
    }

    // Hàm tiện ích để tạo một thumbnail HTML
    function createThumbnailElement(id, url, isFeatured) {
        const div = document.createElement('div');
        div.className = `admin-image-thumbnail ${isFeatured ? 'featured' : ''}`;
        div.dataset.imageId = id;
        div.innerHTML = `
            <img src="${url}" alt="Product Image">
            <div class="thumbnail-actions">
                <button type="button" class="btn btn-sm btn-light btn-set-featured" title="Đặt làm ảnh đại diện"><i class="bi ${isFeatured ? 'bi-star-fill text-warning' : 'bi-star'}"></i></button>
                <button type="button" class="btn btn-sm btn-danger btn-delete-image" title="Xóa ảnh"><i class="bi bi-trash-fill"></i></button>
            </div>
        `;
        return div;
    }




});

// Gắn hàm khởi tạo CKEditor vào sự kiện 'load' của trang
window.addEventListener('load', initializeCKEditor);