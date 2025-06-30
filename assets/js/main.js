/**
 * main.js - Chứa các script chung cho toàn bộ trang người dùng
 */

// =============================================
// === CÁC HÀM TOÀN CỤC (GLOBAL FUNCTIONS) ===
// =============================================

/**
 * Hiển thị thông báo toast của Bootstrap.
 * @param {string} title Tiêu đề của thông báo.
 * @param {string} message Nội dung thông báo.
 * @param {boolean} isSuccess Trạng thái thành công/thất bại để hiển thị icon.
 */
// === NÂNG CẤP HÀM showToast ===
function showToast(title, message, isSuccess = true, htmlContent = null) {
    const toastLiveExample = document.getElementById('liveToast');
    if (!toastLiveExample) return;
    
    const toastIcon = toastLiveExample.querySelector('.toast-header i');
    const toastBody = document.getElementById('toast-body');
    const toast = new bootstrap.Toast(toastLiveExample);
    
    document.getElementById('toast-title').textContent = title;

    // Ưu tiên hiển thị HTML nếu có, nếu không thì hiển thị message thường
    if (htmlContent) {
        toastBody.innerHTML = htmlContent;
    } else {
        toastBody.textContent = message;
    }

    toastIcon.className = isSuccess ? 'bi bi-check-circle-fill text-success me-2' : 'bi bi-exclamation-triangle-fill text-danger me-2';
    toast.show();
}

/**
 * Gửi yêu cầu thêm sản phẩm vào giỏ hàng qua API.
 * @param {number} variantId ID của phiên bản sản phẩm.
 * @param {number} quantity Số lượng.
 */
// === NÂNG CẤP HÀM addToCart ===
function addToCart(variantId, quantity) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('variant_id', variantId);
    formData.append('quantity', quantity);

    fetch('/api/cart/cart-handler.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.cart_item_count;
            });
            
            // Tạo nội dung HTML với nút bấm
            const toastHtml = `
                ${data.message} 
                <div class="mt-2 pt-2 border-top">
                    <a href="/cart.html" class="btn btn-primary btn-sm">Thanh toán</a>
                </div>
            `;
            // Gọi hàm showToast với nội dung HTML
            showToast('Thành công', '', true, toastHtml);

        } else {
            showToast('Thất bại', data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Lỗi', 'Đã có lỗi xảy ra. Vui lòng thử lại.', false);
    });
}


// Gắn các hàm vào đối tượng `window` để các file script khác có thể gọi
window.showToast = showToast;
window.addToCart = addToCart;


// =============================================
// === CÁC LOGIC CHẠY KHI TRANG TẢI XONG ===
// =============================================
document.addEventListener("DOMContentLoaded", function() {
    
    // --- XỬ LÝ MODAL THÊM NHANH SẢN PHẨM ---
    document.addEventListener('click', function(e) {
        const quickAddButton = e.target.closest('.btn-add-to-cart');
        if (quickAddButton) {
            e.preventDefault();
            
            const quickAddModalEl = document.getElementById('quick-add-modal');
            if (!quickAddModalEl) return;

            // SỬA LỖI: Chỉ khởi tạo modal ngay khi cần dùng
            const quickAddModal = new bootstrap.Modal(quickAddModalEl);
            const quickAddModalBody = document.getElementById('quick-add-modal-body');
            const slug = quickAddButton.dataset.slug;

            quickAddModalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
            quickAddModal.show();
            
            fetch(`/api/products/get-variant-info.php?slug=${slug}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Render nội dung vào modal
                        quickAddModalBody.innerHTML = `
                            <div class="product-details">
                                <div class="row">
                                    <div class="col-md-5">
                                        <img src="${data.product.image_url || '/assets/images/placeholder.png'}" class="img-fluid rounded main-product-image">
                                    </div>
                                    <div class="col-md-7">
                                        <h4>${data.product.name}</h4>
                                        <div class="product-sku text-muted small mb-2"></div>
                                        <div class="product-price h3 text-danger">
                                            <span class="price-sale"></span>
                                            <span class="price-original"></span>
                                        </div>
                                        <hr>
                                        <div class="variant-options-container my-3"></div>
                                        <div class="d-flex align-items-center gap-3 mt-4">
                                            <div class="quantity-input">
                                                <input type="number" class="form-control text-center" value="1" min="1" style="width: 70px;">
                                            </div>
                                            <button class="btn btn-primary flex-grow-1 btn-add-to-cart-submit">Thêm vào giỏ hàng</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        // Khởi tạo "bộ não" VariantSelector cho modal
                        new VariantSelector(quickAddModalBody.querySelector('.product-details'), data.variants);
                    } else {
                        quickAddModalBody.innerHTML = `<p class="text-danger">${data.message}</p>`;
                    }
                });
        }
    });

    // =======================================================
    // === TỰ ĐỘNG SAO CHÉP MENU SANG MOBILE (PHIÊN BẢN SỬA LỖI) ===
    // =======================================================
    const desktopNav = document.querySelector('.header-nav .nav-list');
    const mobileNavContainer = document.getElementById('mobile-nav-list');

    if (desktopNav && mobileNavContainer) {
        // Sao chép HTML của menu desktop sang mobile
        mobileNavContainer.innerHTML = desktopNav.innerHTML;
        
        // Lặp qua TẤT CẢ các thẻ <li> cấp 1 trong menu di động
        mobileNavContainer.querySelectorAll(':scope > li').forEach(li => {
            li.classList.add('nav-item'); // Thêm class nav-item cho tất cả
            
            const link = li.querySelector('a');
            const submenu = li.querySelector('ul');

            if (link) {
                link.classList.add('nav-link'); // Thêm class nav-link cho tất cả
            }

            // Nếu đây là một dropdown (có menu con)
            if (submenu) {
                li.classList.add('mobile-dropdown'); // Thêm class để dễ nhận biết
                
                const submenuId = 'mobile-submenu-' + link.textContent.trim().toLowerCase().replace(/[^a-z0-9]/g, '-');
                
                link.classList.add('mobile-dropdown-toggle');
                link.dataset.bsToggle = 'collapse';
                link.href = `#${submenuId}`;
                
                submenu.classList.remove('dropdown-menu', 'dropdown-menu-end');
                submenu.classList.add('collapse', 'mobile-submenu-list');
                submenu.id = submenuId;

                // Thêm class nav-link vào các thẻ <a> con trong submenu
                submenu.querySelectorAll('a').forEach(sublink => {
                    sublink.classList.add('nav-link');
                });
                // === THÊM VÀO: LOGIC MỞ SẴN MENU "SẢN PHẨM" ===
                if (link.textContent.trim() === 'Sản phẩm') {
                    link.setAttribute('aria-expanded', 'true');
                    submenu.classList.add('show'); // Thêm class 'show' của Bootstrap để xổ ra
                }
            }
        });
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
            // --- LOGIC MỚI: LẤY VÀ GỬI THAM SỐ LỌC CHÍNH ---
            const mainContent = document.querySelector('main[data-filter-type]');
            if (mainContent) {
                formData.append('filter_type', mainContent.dataset.filterType);
                formData.append('filter_slug', mainContent.dataset.filterSlug);
            }
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