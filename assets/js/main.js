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
            const mainContent = document.querySelector('main[data-filter-type]');
            
            container.innerHTML = `<div class="col-12 text-center p-5"><div class="spinner-border"></div></div>`;
            paginationContainer.innerHTML = '';

            const formData = new FormData(document.getElementById('filter-form'));
            
            // Xác định API và trang đích dựa trên filter-type
            let apiUrl = '/api/filter-products.php';
            let targetUrl = '/products.html'; // URL mặc định
            let searchTermForDisplay = ''; // Biến để lưu search term cho hiển thị URL

            if (mainContent) {
                const filterType = mainContent.dataset.filterType;
                formData.append('filter_type', filterType);
                
                if (filterType === 'search') {
                    apiUrl = '/api/search-filter-products.php';
                    targetUrl = '/search.html';
                    const searchTerm = mainContent.dataset.searchTerm;
                    formData.append('search_term', searchTerm);
                    searchTermForDisplay = searchTerm; // Lưu lại để hiển thị trên URL
                } else if (mainContent.dataset.filterSlug) {
                    // Xây dựng lại URL cho category và collection
                    targetUrl = `/${filterType}/${mainContent.dataset.filterSlug}.html`;
                    formData.append('filter_slug', mainContent.dataset.filterSlug);
                }
            }
            
            // Thêm các tham số khác
            formData.append('sort_by', document.getElementById('sort-by').value);
            const currentPage = document.querySelector('#pagination-container .page-item.active a')?.dataset.page || 1;
            formData.append('page', currentPage);
            
            // Tạo URL để fetch
            const fetchParams = new URLSearchParams(formData).toString();
            const fetchUrl = `${window.location.origin}${apiUrl}?${fetchParams}`;

            fetch(fetchUrl)
                .then(response => response.json())
                .then(data => {
                    container.innerHTML = data.product_html;
                    paginationContainer.innerHTML = data.pagination_html;
                    
                    // SỬA LỖI: Cập nhật URL trình duyệt cho đúng trang
                    // Tạo một bản sao của params để làm đẹp URL, loại bỏ các trường không cần thiết
                    const displayParams = new URLSearchParams(formData);
                    displayParams.delete('filter_type');
                    displayParams.delete('filter_slug');
                    displayParams.delete('search_term');

                    const searchInput = document.querySelector('input[name="q"]');
                    if (searchInput && searchInput.value) {
                         displayParams.set('q', searchInput.value);
                    } 
                    
                    
                    const finalUrl = `${targetUrl}?${displayParams.toString()}`;
                    history.pushState(null, '', finalUrl);
                    
                    // Cuộn lên đầu
                    document.getElementById('products-main-header')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

    // =============================================
    // === LOGIC MỚI: LIVE SEARCH CHO HEADER ===
    // =============================================
    const searchInput = document.getElementById('header-search-input');
    const searchResultsBox = document.getElementById('header-search-results');
    let searchTimeout;

    if (searchInput && searchResultsBox) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value;

            if (searchTerm.length < 2) {
                searchResultsBox.style.display = 'none';
                return;
            }

            // Chờ 300ms sau khi người dùng ngừng gõ mới gửi yêu cầu
            searchTimeout = setTimeout(() => {
                fetch(`/api/live-search.php?q=${searchTerm}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            let html = '<ul class="list-group list-group-flush">';
                            data.forEach(product => {
                                const price = new Intl.NumberFormat('vi-VN').format(product.price) + 'đ';
                                html += `
                                    <li class="list-group-item">
                                        <a href="/san-pham/${product.slug}.html" class="d-flex align-items-center text-decoration-none">
                                            <img src="${product.image_url || '/assets/images/placeholder.png'}" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-dark">${product.name}</div>
                                                <div class="text-danger">${price}</div>
                                            </div>
                                        </a>
                                    </li>
                                `;
                            });
                            html += '</ul>';
                            searchResultsBox.innerHTML = html;
                            searchResultsBox.style.display = 'block';
                        } else {
                            searchResultsBox.style.display = 'none';
                        }
                    });
            }, 300);
        });

        // Ẩn kết quả khi click ra ngoài
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target)) {
                searchResultsBox.style.display = 'none';
            }
        });
    }
    // =======================================================
    // === LOGIC MỚI: LIVE SEARCH CHO MOBILE (TRONG MODAL) ===
    // =======================================================
    const mobileSearchInput = document.getElementById('mobile-search-input');
    const mobileSearchResultsBox = document.getElementById('mobile-search-results');
    let mobileSearchTimeout;

    if (mobileSearchInput && mobileSearchResultsBox) {
        mobileSearchInput.addEventListener('keyup', function() {
            clearTimeout(mobileSearchTimeout);
            const searchTerm = this.value;

            if (searchTerm.length < 2) {
                mobileSearchResultsBox.innerHTML = ''; // Xóa kết quả cũ
                return;
            }

            mobileSearchTimeout = setTimeout(() => {
                fetch(`/api/live-search.php?q=${encodeURIComponent(searchTerm)}`)
                    .then(res => res.json())
                    .then(data => {
                        let html = '';
                        if (data.length > 0) {
                            html = '<ul class="list-group list-group-flush">';
                            data.forEach(product => {
                                const price = new Intl.NumberFormat('vi-VN').format(product.price) + 'đ';
                                html += `
                                    <li class="list-group-item">
                                        <a href="/san-pham/${product.slug}.html" class="d-flex align-items-center text-decoration-none">
                                            <img src="${product.image_url || '/assets/images/placeholder.png'}" class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-dark">${product.name}</div>
                                                <div class="text-danger">${price}</div>
                                            </div>
                                        </a>
                                    </li>
                                `;
                            });
                            html += '</ul>';
                        } else {
                            html = '<p class="text-center text-muted mt-3">Không tìm thấy sản phẩm nào.</p>';
                        }
                        mobileSearchResultsBox.innerHTML = html;
                    });
            }, 300);
        });
    }


    const mainContent = document.querySelector('main[data-filter-type]');
    let apiUrl = '/api/filter-products.php'; // API mặc định
    let targetUrl = '/products.html'; // Trang mặc định

    if (mainContent) {
        const formData = new FormData();
        const filterType = mainContent.dataset.filterType;
        formData.append('filter_type', filterType);
        
        // === SỬA LẠI LOGIC Ở ĐÂY ===
        if (filterType === 'search') {
            apiUrl = '/api/search-filter-products.php'; // Gọi API tìm kiếm
            targetUrl = '/search.html';
            formData.append('search_term', mainContent.dataset.searchTerm);
        } else if (filterType === 'collection') {
            targetUrl = `/collection/${mainContent.dataset.filterSlug}.html`;
            formData.append('filter_slug', mainContent.dataset.filterSlug);
        } else if (filterType === 'category') {
             targetUrl = `/category/${mainContent.dataset.filterSlug}.html`;
             formData.append('filter_slug', mainContent.dataset.filterSlug);
        }
    }
    const params = new URLSearchParams(formData).toString();
    // Sử dụng biến apiUrl đã được chọn ở trên
    const url = `${window.location.origin}${apiUrl}?${params}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // ...
            // Sửa lại cách cập nhật URL
            history.pushState(null, '', `${targetUrl}?${params.replace(/&?filter_type=[^&]*/g, '').replace(/&?filter_slug=[^&]*/g, '').replace(/&?search_term=[^&]*/g, '')}`);
            // ...
        });
   
});