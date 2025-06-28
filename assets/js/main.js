document.addEventListener("DOMContentLoaded", function() {
    
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