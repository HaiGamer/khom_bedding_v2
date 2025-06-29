// File: /assets/js/main.js (phiên bản mới)

// Đưa hàm ra ngoài DOMContentLoaded để có thể truy cập toàn cục
function showToast(title, message, isSuccess = true) {
    const toastLiveExample = document.getElementById('liveToast');
    const toast = new bootstrap.Toast(toastLiveExample);
    document.getElementById('toast-title').textContent = title;
    document.getElementById('toast-body').textContent = message;
    toast.show();
}

function addToCart(variantId, quantity) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('variant_id', variantId);
    formData.append('quantity', quantity);

    fetch('/api/cart-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.cart_item_count;
            });
            showToast('Thành công', data.message);
        } else {
            showToast('Thất bại', data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Lỗi', 'Đã có lỗi xảy ra. Vui lòng thử lại.', false);
    });
}

// Gắn các hàm vào đối tượng window để các file script khác có thể gọi
window.showToast = showToast;
window.addToCart = addToCart;


document.addEventListener("DOMContentLoaded", function() {
    
    const quickAddModal = new bootstrap.Modal(document.getElementById('quick-add-modal'));
    const quickAddModalBody = document.getElementById('quick-add-modal-body');

    document.addEventListener('click', function(e) {
        const quickAddButton = e.target.closest('.btn-add-to-cart');
        if (quickAddButton) {
            e.preventDefault();
            const slug = quickAddButton.dataset.slug;

            // Hiển thị modal với spinner
            quickAddModalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
            quickAddModal.show();
            
            // Gọi API để lấy thông tin sản phẩm
            fetch(`/api/products/get-variant-info.php?slug=${slug}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // BƯỚC 1: Render cấu trúc HTML mới, phức tạp hơn cho modal
                        // Cấu trúc này phải có các class/id giống với trang chi tiết sản phẩm
                        quickAddModalBody.innerHTML = `
                            <div class="product-details">
                                <div class="row">
                                    <div class="col-md-5">
                                        <img src="${data.product.image_url || '/assets/images/placeholder.png'}" class="img-fluid rounded main-product-image">
                                    </div>
                                    <div class="col-md-7">
                                        <h4>${data.product.name}</h4>
                                        <div class="product-sku text-muted small mb-2">SKU: N/A</div>
                                        <div class="product-price h3 text-danger">
                                            <span class="price-sale"></span>
                                            <span class="price-original"></span>
                                        </div>
                                        <hr>
                                        <div class="variant-options-container my-3"></div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="quantity-input">
                                                <input type="number" class="form-control text-center" value="1" min="1" style="width: 60px;">
                                            </div>
                                            <button class="btn btn-primary btn-add-to-cart-submit">Thêm vào giỏ hàng</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // BƯỚC 2: Khởi tạo "bộ não" cho modal
                        new VariantSelector(quickAddModalBody, data.variants);

                    } else {
                        quickAddModalBody.innerHTML = `<p class="text-danger">${data.message}</p>`;
                    }
                });
        }
    });

    // Hàm format tiền tệ (có thể đã có)
    function formatCurrency(number) {
        if (isNaN(parseFloat(number))) return '';
        return parseFloat(number).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
    }

    //-----------------------------------------------------
    // LOGIC CHO TRANG DANH SÁCH SẢN PHẨM (products.php)
    //-----------------------------------------------------

    const productPage = document.getElementById('filter-form');
    if (productPage) {
        
        // Copy nội dung filter từ sidebar vào offcanvas trên mobile
        const offcanvasBody = document.querySelector('#offcanvasFilters .offcanvas-body');
        const filterFormContent = productPage.innerHTML;
        offcanvasBody.innerHTML = filterFormContent;
        
        // Lấy tất cả các input filter
        const filterInputs = document.querySelectorAll('#filter-form input, #sort-by, #offcanvasFilters input');
        
        // Hàm chính để fetch sản phẩm
        function fetchProducts() {
            const container = document.getElementById('product-list-container');
            const paginationContainer = document.getElementById('pagination-container');
            
            // Hiển thị spinner loading
            container.innerHTML = `<div class="col-12 text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
            paginationContainer.innerHTML = '';

            // Lấy dữ liệu từ form trên desktop (ưu tiên) hoặc mobile
            const form = document.getElementById('filter-form');
            const formData = new FormData(form);

            // Thêm giá trị của select sắp xếp
            formData.append('sort_by', document.getElementById('sort-by').value);

            // Lấy trang hiện tại (từ data attribute của pagination)
            const currentPage = document.querySelector('#pagination-container .page-item.active a')?.dataset.page || 1;
            formData.append('page', currentPage);

            // Chuyển FormData thành URL query string
            const params = new URLSearchParams(formData).toString();
            const url = `${window.location.origin}/api/filter-products.php?${params}`;

            // Gửi yêu cầu AJAX
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Cập nhật lưới sản phẩm và phân trang
                    container.innerHTML = data.product_html;
                    paginationContainer.innerHTML = data.pagination_html;
                    
                    // Cập nhật số lượng sản phẩm (nếu có)
                    // document.getElementById('product-count').textContent = data.total_products;
                    
                    // Cập nhật URL trình duyệt
                    history.pushState(null, '', `/products.html?${params}`);
                    
                    // Cuộn lên đầu khu vực tiêu đề để người dùng thấy cả bộ sắp xếp
                    const headerElement = document.getElementById('products-main-header');
                    if (headerElement) {
                        headerElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    container.innerHTML = '<div class="col-12"><p class="text-center text-danger">Đã có lỗi xảy ra. Vui lòng thử lại.</p></div>';
                });
        }
        
        // Gắn sự kiện 'change' cho tất cả các input filter
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                // Reset page về 1 khi filter thay đổi
                document.querySelector('#pagination-container').innerHTML = '';
                fetchProducts();
            });
        });
        
        // Gắn sự kiện 'click' cho pagination (sử dụng event delegation)
        document.getElementById('pagination-container').addEventListener('click', function(e) {
            e.preventDefault();
            if (e.target.matches('a.page-link')) {
                // Xóa active class cũ
                this.querySelector('.page-item.active')?.classList.remove('active');
                // Thêm active class mới
                const pageItem = e.target.closest('.page-item');
                pageItem.classList.add('active');
                fetchProducts();
            }
        });
        
        // Tải sản phẩm lần đầu khi trang được mở
        fetchProducts();
    }

});