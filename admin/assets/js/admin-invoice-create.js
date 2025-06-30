document.addEventListener('DOMContentLoaded', function() {
    const mainForm = document.getElementById('invoice-form');
    if (!mainForm) return;

    // --- KHAI BÁO BIẾN ---
    const customerSearchInput = document.getElementById('customer-search');
    const customerSearchResults = document.getElementById('customer-search-results');
    const productSearchInput = document.getElementById('product-search');
    const productSearchResults = document.getElementById('product-search-results');
    const itemsTableBody = document.getElementById('invoice-items-body');
    const addItemBtn = document.getElementById('btn-add-item');
    const invoiceTotalEl = document.getElementById('invoice-total');
    
    let searchTimeout;
    let selectedProductForInvoice = null; 

    // --- CÁC HÀM XỬ LÝ ---

    // Hàm chung để tìm kiếm
    function handleSearch(inputElement, resultsContainer, apiUrl) {
        clearTimeout(searchTimeout);
        const term = inputElement.value;
        if (term.length < 2) {
            resultsContainer.innerHTML = '';
            resultsContainer.classList.remove('d-block');
            return;
        }
        searchTimeout = setTimeout(() => {
            fetch(`${apiUrl}?term=${term}`)
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            if (apiUrl.includes('customers')) { 
                                html += `<a href="#" class="list-group-item list-group-item-action" data-customer='${JSON.stringify(item)}'>
                                    <strong>${item.customer_name}</strong><br><small class="text-muted">${item.phone_number || ''}</small>
                                 </a>`;
                            } else {
                                html += `<a href="#" class="list-group-item list-group-item-action" data-variant='${JSON.stringify(item)}'>
                                    <strong>${item.product_name}</strong> (${item.sku})<br><small class="text-muted">${item.variant_attributes || ''}</small>
                                 </a>`;
                            }
                        });
                    } else {
                        html = '<span class="list-group-item">Không tìm thấy kết quả.</span>';
                    }
                    resultsContainer.innerHTML = html;
                    resultsContainer.classList.add('d-block');
                });
        }, 300);
    }

    // Hàm cập nhật tổng tiền hóa đơn
    function updateInvoiceTotal() {
        let total = 0;
        itemsTableBody.querySelectorAll('tr').forEach(row => {
            if (row.classList.contains('placeholder-row')) return;
            const lineTotal = parseFloat(row.querySelector('.line-total').dataset.value || 0);
            total += lineTotal;
        });
        invoiceTotalEl.textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
    }

    // Hàm thêm một dòng sản phẩm vào bảng hóa đơn
    function addItemToTable() {
        if (!selectedProductForInvoice) {
            alert('Vui lòng tìm và chọn một sản phẩm trước.');
            return;
        }
        const variantId = selectedProductForInvoice.id;
        if (document.querySelector(`tr[data-variant-id="${variantId}"]`)) {
            alert('Sản phẩm này đã có trong hóa đơn.');
            return;
        }
        const quantity = parseInt(document.getElementById('item-quantity').value, 10);
        const price = parseFloat(document.getElementById('item-price').value);
        if (isNaN(quantity) || quantity <= 0 || isNaN(price) || price < 0) {
            alert('Vui lòng nhập số lượng và đơn giá hợp lệ.');
            return;
        }
        document.querySelector('.placeholder-row')?.remove();
        const lineTotal = quantity * price;
        const row = document.createElement('tr');
        row.dataset.variantId = variantId;
        row.innerHTML = `
            <td>
                ${selectedProductForInvoice.product_name}<br>
                <small class="text-muted">SKU: ${selectedProductForInvoice.sku}</small>
                <input type="hidden" name="items[${variantId}][name]" value="${selectedProductForInvoice.product_name}">
                <input type="hidden" name="items[${variantId}][price]" value="${price}">
            </td>
            <td class="text-center">${quantity}<input type="hidden" name="items[${variantId}][quantity]" value="${quantity}"></td>
            <td class="text-end">${new Intl.NumberFormat('vi-VN').format(price)}đ</td>
            <td class="text-end fw-bold line-total" data-value="${lineTotal}">${new Intl.NumberFormat('vi-VN').format(lineTotal)}đ</td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Xóa">&times;</button></td>
        `;
        itemsTableBody.appendChild(row);
        updateInvoiceTotal();
        productSearchInput.value = '';
        document.getElementById('item-quantity').value = 1;
        document.getElementById('item-price').value = '';
        selectedProductForInvoice = null;
    }

    // --- GẮN CÁC SỰ KIỆN ---
    customerSearchInput.addEventListener('keyup', () => handleSearch(customerSearchInput, customerSearchResults, '/admin/api/customers/search.php'));
    productSearchInput.addEventListener('keyup', () => handleSearch(productSearchInput, productSearchResults, '/admin/api/inventory/search-variants.php'));
    
    customerSearchResults.addEventListener('click', function(e) {
        e.preventDefault();
        const link = e.target.closest('a');
        if (!link) return;
        const customer = JSON.parse(link.dataset.customer);
        document.getElementById('customer_id').value = customer.id;
        document.getElementById('customer_name').value = customer.customer_name;
        document.getElementById('customer_phone').value = customer.phone_number;
        document.getElementById('customer_address').value = customer.address;
        customerSearchResults.innerHTML = '';
        customerSearchResults.classList.remove('d-block');
    });

    productSearchResults.addEventListener('click', function(e) {
        e.preventDefault();
        const link = e.target.closest('a');
        if (!link) return;
        const variant = JSON.parse(link.dataset.variant);
        selectedProductForInvoice = variant;
        productSearchInput.value = `${variant.product_name} (${variant.sku})`;
        document.getElementById('item-price').value = variant.price || 0;
        productSearchResults.innerHTML = '';
        productSearchResults.classList.remove('d-block');
    });

    addItemBtn.addEventListener('click', addItemToTable);
    
    itemsTableBody.addEventListener('click', e => {
        if (e.target.classList.contains('btn-remove-item')) {
            e.target.closest('tr').remove();
            updateInvoiceTotal();
            if (itemsTableBody.children.length === 0) {
                itemsTableBody.innerHTML = '<tr class="placeholder-row"><td colspan="5" class="text-center text-muted">Thêm sản phẩm từ ô tìm kiếm bên dưới</td></tr>';
            }
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!customerSearchInput.contains(e.target)) customerSearchResults.classList.remove('d-block');
        if (!productSearchInput.contains(e.target)) productSearchResults.classList.remove('d-block');
    });

    // Xử lý submit form chính
     // =======================================================
    // === LOGIC MỚI: XỬ LÝ SUBMIT FORM CHÍNH ===
    // =======================================================
    mainForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = mainForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';

        // Sử dụng FormData để thu thập toàn bộ dữ liệu form một cách dễ dàng
        const formData = new FormData(mainForm);
        
        // Kiểm tra xem có sản phẩm trong hóa đơn không
        if (itemsTableBody.querySelectorAll('tr').length === 0 || itemsTableBody.querySelector('.placeholder-row')) {
            alert('Vui lòng thêm ít nhất một sản phẩm vào hóa đơn.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Tạo & Lưu Hóa Đơn';
            return;
        }

        fetch('/admin/api/customers/invoice-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Chuyển hướng đến trang xem hóa đơn vừa tạo
                window.location.href = `/admin/customers/invoice-view.php?id=${data.invoice_id}`;
            } else {
                alert('Lỗi: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Tạo & Lưu Hóa Đơn';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi nghiêm trọng xảy ra.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Tạo & Lưu Hóa Đơn';
        });
    });
});