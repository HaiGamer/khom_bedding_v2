<aside class="admin-sidebar">
   <h3 class="text-center my-3 text-white">Khóm Admin</h3>
   <ul class="nav flex-column">
      <li class="nav-item">
         <a class="nav-link" href="/admin/"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      </li>
      <li class="nav-item">
         <a class="nav-link" href="/admin/orders.php"><i class="bi bi-receipt me-2"></i>Đơn hàng</a>
      </li>

      <li class="nav-item">
         <a class="nav-link" data-bs-toggle="collapse" href="#customers-menu" role="button">
            <i class="bi bi-people-fill me-2"></i>Khách Lẻ & Sỉ <i class="bi bi-chevron-down float-end"></i>
         </a>
         <div class="collapse show" id="customers-menu">
            <ul class="nav flex-column ms-3">
               <li class="nav-item"><a class="nav-link" href="/admin/customers/">Hóa Đơn</a></li>
               <li class="nav-item"><a class="nav-link" href="/admin/customers/customer-manage.php">Quản lý khách
                     hàng</a></li>
               <li class="nav-item"><a class="nav-link" href="/admin/customers/invoice-create.php">Tạo hóa đơn</a></li>
            </ul>
         </div>
      </li>

      <li class="nav-item">
         <a class="nav-link" data-bs-toggle="collapse" href="#product-data-menu" role="button">
            <i class="bi bi-box-seam me-2"></i>Dữ liệu Sản phẩm <i class="bi bi-chevron-down float-end"></i>
         </a>
         <div class="collapse show" id="product-data-menu">
            <ul class="nav flex-column ms-3">
               <li class="nav-item"><a class="nav-link" href="/admin/products.php">Sản phẩm</a></li>
               <li class="nav-item"><a class="nav-link" href="/admin/categories.php">Danh mục</a></li>
               <li class="nav-item"><a class="nav-link" href="/admin/attributes.php">Thuộc tính</a></li>
               <li class="nav-item"><a class="nav-link" href="/admin/collections.php">Bộ sưu tập</a></li>
               <li class="nav-item"><a class="nav-link" href="/admin/banners.php">Quản lý Banner</a></li>
            </ul>
         </div>
      </li>

      <li class="nav-item"><a class="nav-link" href="/admin/reviews.php"><i class="bi bi-star me-2"></i>Đánh giá</a>
      </li>
      <li class="nav-item"><a class="nav-link" href="/admin/posts.php"><i class="bi bi-pencil-square me-2"></i>Bài
            viết</a></li>

      <li class="nav-item">
         <a class="nav-link" data-bs-toggle="collapse" href="#reports-menu" role="button">
            <i class="bi bi-graph-up me-2"></i>Báo cáo & Kho <i class="bi bi-chevron-down float-end"></i>
         </a>
         <div class="collapse show" id="reports-menu">
            <ul class="nav flex-column ms-3">
               <li class="nav-item"><a class="nav-link" href="/admin/inventory.php">Báo cáo tồn kho</a></li>
               <li class="nav-item"><a class="nav-link" href="/admin/stock-export-create.php">Tạo phiếu xuất kho</a>
               </li>
               <li class="nav-item"><a class="nav-link" href="/admin/stock-export-history.php">Lịch sử xuất kho</a></li>
            </ul>
         </div>
      </li>

      <li class="nav-item">
         <hr>
      </li>
      <li class="nav-item"><a class="nav-link" href="/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng
            xuất</a></li>
   </ul>
</aside>
<main class="admin-main-content">