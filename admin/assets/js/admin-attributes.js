document.addEventListener('DOMContentLoaded', function() {
    const detailsContainer = document.getElementById('attribute-details-container');
    const attributeList = document.getElementById('attribute-list');
    const newAttributeBtn = document.getElementById('btn-new-attribute');

    // Hàm để tải và hiển thị chi tiết một thuộc tính
    const loadAttributeDetails = (attributeId) => {
        detailsContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        fetch(`/admin/api/attributes/get-details.php?id=${attributeId}`)
            .then(res => res.text())
            .then(html => detailsContainer.innerHTML = html)
            .catch(err => detailsContainer.innerHTML = '<div class="alert alert-danger">Lỗi tải dữ liệu.</div>');
    };

    // Tải chi tiết của thuộc tính đã được chọn khi trang tải xong
    if (selectedAttributeId) {
        loadAttributeDetails(selectedAttributeId);
    }

    // Xử lý khi click vào một thuộc tính trong danh sách
    attributeList.addEventListener('click', function(e) {
        if (e.target.matches('.list-group-item-action')) {
            e.preventDefault();
            const attributeId = e.target.dataset.id;
            
            // Cập nhật trạng thái active
            attributeList.querySelector('.active')?.classList.remove('active');
            e.target.classList.add('active');

            // Cập nhật URL và tải chi tiết
            history.pushState(null, '', `?id=${attributeId}`);
            loadAttributeDetails(attributeId);
        }
    });

    // Xử lý khi nhấn nút "Thêm mới" thuộc tính
    newAttributeBtn.addEventListener('click', function() {
        const name = prompt("Nhập tên thuộc tính mới (ví dụ: Chất liệu):");
        if (name && name.trim() !== '') {
            fetchApi({ action: 'add_attribute', name: name.trim() }, 'Thêm thuộc tính thành công!');
        }
    });

    // Xử lý các sự kiện trong container chi tiết (Thêm/Sửa/Xóa giá trị, Sửa/Xóa thuộc tính)
    detailsContainer.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        if (form.matches('.attribute-value-form, .attribute-edit-form')) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            const message = data.action === 'add_value' ? 'Thêm giá trị thành công!' : 'Cập nhật thành công!';
            fetchApi(data, message);
        }
    });
    
    detailsContainer.addEventListener('click', function(e) {
        const target = e.target;
        if (target.matches('.btn-delete-attribute')) {
            if (confirm('Bạn có chắc muốn xóa thuộc tính này và TẤT CẢ các giá trị của nó?')) {
                fetchApi({ action: 'delete_attribute', id: target.dataset.id }, 'Xóa thuộc tính thành công!');
            }
        }
        if (target.matches('.btn-delete-value')) {
            if (confirm('Bạn có chắc muốn xóa giá trị này?')) {
                fetchApi({ action: 'delete_value', id: target.dataset.id }, 'Xóa giá trị thành công!');
            }
        }
    });

    // Hàm gọi API chung
    function fetchApi(data, successMessage) {
        fetch('/admin/api/attributes/handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                alert(successMessage);
                window.location.reload(); // Tải lại để thấy thay đổi
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