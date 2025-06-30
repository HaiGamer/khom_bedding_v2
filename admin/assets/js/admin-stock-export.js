document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('variant-search');
    const resultsContainer = document.getElementById('search-results');
    const itemsTableBody = document.getElementById('export-items-table-body');
    const mainForm = document.getElementById('stock-export-form');
    const allProductsList = document.getElementById('all-products-list');
    const sortSelect = document.getElementById('sort-select');

    if (!mainForm) return; // Nếu không ở đúng trang thì không chạy

    let searchTimeout;

    // Hàm thêm một sản phẩm vào bảng phiếu xuất
    function addItemToSlip(variant) {
        const variantId = variant.variant_id || variant.id;
        // Kiểm tra xem sản phẩm đã được thêm chưa
        if (document.querySelector(`tr[data-variant-id="${variantId}"]`)) {
            alert('Sản phẩm này đã có trong danh sách.');
            return;
        }
        
        document.querySelector('.placeholder-row')?.remove();

        const row = document.createElement('tr');
        row.dataset.variantId = variantId;
        row.innerHTML = `
            <td>
                ${variant.product_name}<br>
                <small class="text-muted">SKU: ${variant.sku}</small>
            </td>
            <td>${variant.stock_quantity}</td>
            <td><input type="number" class="form-control form-control-sm export-quantity" value="1" min="1" max="${variant.stock_quantity}"></td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Xóa">&times;</button>
            </td>
        `;
        itemsTableBody.appendChild(row);
    }
    
    // --- XỬ LÝ SỰ KIỆN ---

    // Tìm kiếm nhanh
    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        const term = searchInput.value;
        if (term.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }
        searchTimeout = setTimeout(() => {
            fetch(`/admin/api/inventory/search-variants.php?term=${term}`)
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    data.forEach(v => {
                        html += `<a href="#" class="list-group-item list-group-item-action" data-variant='${JSON.stringify(v)}'>
                            <strong>${v.product_name}</strong> (${v.sku})<br>
                            <small class="text-muted">${v.variant_attributes || 'Không có thuộc tính'}</small>
                         </a>`;
                    });
                    resultsContainer.innerHTML = html;
                });
        }, 300);
    });

    // Click vào kết quả tìm kiếm nhanh
    resultsContainer.addEventListener('click', function(e) {
        e.preventDefault();
        const link = e.target.closest('a');
        if (!link) return;
        const variantData = JSON.parse(link.dataset.variant);
        addItemToSlip(variantData);
        searchInput.value = '';
        resultsContainer.innerHTML = '';
    });

    // Click vào nút "Thêm" trong bảng danh sách sản phẩm
    if (allProductsList) {
        allProductsList.addEventListener('click', function(e) {
            if (e.target.matches('.btn-add-to-slip')) {
                const variantData = JSON.parse(e.target.dataset.variant);
                addItemToSlip(variantData);
            }
        });
    }

    // Xóa sản phẩm khỏi phiếu xuất
    itemsTableBody.addEventListener('click', e => {
        if (e.target.classList.contains('btn-remove-item')) {
            e.target.closest('tr').remove();
            if (itemsTableBody.children.length === 0) {
                itemsTableBody.innerHTML = '<tr class="placeholder-row"><td colspan="4" class="text-center text-muted">Chưa có sản phẩm nào</td></tr>';
            }
        }
    });

    // Gửi form chính để tạo phiếu xuất
    mainForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const items = [];
        itemsTableBody.querySelectorAll('tr').forEach(row => {
            if(row.classList.contains('placeholder-row')) return;
            items.push({
                variant_id: row.dataset.variantId,
                quantity: row.querySelector('.export-quantity').value
            });
        });

        if (items.length === 0) {
            alert('Vui lòng thêm ít nhất một sản phẩm vào phiếu xuất.');
            return;
        }

        const note = document.getElementById('export-note').value;
        const submitBtn = mainForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo...';

        fetch('/admin/api/inventory/create-stock-export.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ note: note, items: items })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                // Chuyển hướng đến trang lịch sử xuất kho (sẽ tạo ở bước sau)
                // Hoặc tải lại trang báo cáo tồn kho
                window.location.href = '/admin/inventory.php'; 
            }
        })
        .catch(err => {
            alert('Đã có lỗi nghiêm trọng xảy ra.');
            console.error(err);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Tạo Phiếu Xuất Kho';
        });
    });

    // Tự động submit form sắp xếp khi thay đổi lựa chọn
    if(sortSelect) {
        sortSelect.addEventListener('change', function() {
            document.getElementById('sort-form').submit();
        });
    }
});