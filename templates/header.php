<?php
// Luôn bắt đầu session ở đầu file header để mọi trang đều có thể sử dụng
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Gọi các file cần thiết
require_once __DIR__ . '/../core/db_connect.php';
// require_once __DIR__ . '/../core/helpers.php'; // Gọi file helper nếu cần các hàm chung

// --- LẤY DỮ LIỆU CHO MENU ---
// Lấy tất cả danh mục để hiển thị trên menu "Sản phẩm"
try {
    $stmt_cats_menu = $pdo->query("SELECT name, slug FROM categories ORDER BY name ASC");
    $categories_menu = $stmt_cats_menu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories_menu = [];
    error_log("Lỗi khi truy vấn danh mục cho header: " . $e->getMessage());
}

// Thiết lập các biến SEO mặc định
$page_title = $page_title ?? 'Khóm Bedding - Nâng tầm phòng ngủ';
$page_description = $page_description ?? 'Chuyên cung cấp các sản phẩm chăn ga gối đệm chất lượng cao.';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($page_title); ?></title>
   <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link
      href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600&family=Nunito+Sans:wght@400;600;700&display=swap"
      rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

   <link rel="stylesheet" href="/assets/css/style.css">

   <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
</head>

<body>

   <header class="header-sticky">
      <div class="d-none d-lg-block">
         <div class="header-top">
            <div class="container d-flex justify-content-between align-items-center">
               <a href="/" class="header-logo">
                  <img src="/assets/images/logo.png" alt="Logo Khóm Bedding" style="max-height: 50px;">
               </a>
               <div class="header-top-right d-flex align-items-center">
                  <form action="/search.html" method="GET" class="search-form me-3">
                     <input type="text" id="header-search-input" name="q" placeholder="Tìm kiếm sản phẩm..." required
                        autocomplete="off">
                     <button type="submit"><i class="bi bi-search"></i></button>
                     <div id="header-search-results" class="header-search-results-box"></div>
                  </form>
                  <div class="header-hotline me-3">
                     <i class="bi bi-telephone-fill"></i>
                     <span>Hotline: <strong>1900.1234</strong></span>
                  </div>
                  <a href="/cart.html" class="header-cart-icon position-relative">
                     <i class="bi bi-bag"></i>
                     <span
                        class="cart-count badge bg-danger rounded-pill"><?= array_sum(array_column($_SESSION['cart'] ?? [], 'quantity')) ?: 0 ?></span>
                  </a>
               </div>
            </div>
         </div>
         <nav class="header-nav">
            <div class="container">
               <ul class="nav-list">
                  <li class="dropdown">
                     <a href="/products.html">Sản phẩm <i class="bi bi-chevron-down"></i></a>
                     <ul class="dropdown-menu">
                        <?php foreach ($categories_menu as $category): ?>
                        <li><a class="dropdown-item"
                              href="/category/<?php echo htmlspecialchars($category['slug']); ?>.html"><?php echo htmlspecialchars($category['name']); ?></a>
                        </li>
                        <?php endforeach; ?>
                     </ul>
                  </li>
                  <li><a class="smooth-scroll" href="/#homepage-collections">Bộ Sưu Tập</a></li>
                  <li><a class="smooth-scroll" href="/blog.html">Blog</a></li>
                  <li><a href="/contact.html">Liên Hệ</a></li>
                  <li class="dropdown">
                     <a href="/account.html">Tài Khoản <i class="bi bi-chevron-down"></i></a>
                     <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <li>
                           <h6 class="dropdown-header">Xin chào,
                              <?php echo htmlspecialchars($_SESSION['user_full_name']); ?></h6>
                        </li>
                        <li><a class="dropdown-item" href="/account-orders.html">Đơn hàng</a></li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><a class="dropdown-item" href="/admin/">Trang Admin</a></li>
                        <?php endif; ?>
                        <li>
                           <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="/logout.php">Đăng Xuất</a></li>
                        <?php else: ?>
                        <li><a class="dropdown-item" href="/auth.html">Đăng Nhập</a></li>
                        <li><a class="dropdown-item" href="/auth.html#register">Đăng Ký</a></li>
                        <?php endif; ?>
                     </ul>
                  </li>
               </ul>
            </div>
         </nav>
      </div>

      <div class="d-lg-none mobile-header">
         <div class="container d-flex justify-content-between align-items-center">
            <button class="btn p-0 fs-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
               aria-controls="offcanvasMenu">
               <i class="bi bi-list"></i>
            </button>
            <a href="/" class="mobile-logo">
               <img src="/assets/images/logo.png" alt="Logo Khóm Bedding">
            </a>
            <div class="mobile-header-right">
               <button class="btn p-0 fs-2" data-bs-toggle="modal" data-bs-target="#searchModal"><i
                     class="bi bi-search"></i></button>
               <a href="/cart.html" class="btn p-0 fs-2 position-relative ms-3">
                  <i class="bi bi-bag"></i>
                  <span
                     class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= array_sum(array_column($_SESSION['cart'] ?? [], 'quantity')) ?: 0 ?></span>
               </a>
            </div>
         </div>
      </div>
   </header>

   <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
      <div class="offcanvas-header">
         <a href="/" class="mobile-logo">
            <img src="/assets/images/logo.png" alt="Logo Khóm Bedding" style="max-height: 50px;">
         </a>
         <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
         <ul id="mobile-nav-list" class="nav flex-column mobile-nav"></ul>
      </div>
   </div>

   <div class="modal fade" id="searchModal" tabindex="-1">
      <div class="modal-dialog modal-fullscreen">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title">Tìm kiếm</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
               <form action="/search.html" method="GET" class="d-flex mb-3">
                  <input type="text" id="mobile-search-input" class="form-control form-control-lg" name="q"
                     placeholder="Nhập tên sản phẩm..." required autocomplete="off">
                  <button type="submit" class="btn btn-primary ms-2">Tìm</button>
               </form>
               <div id="mobile-search-results" class="mobile-search-results-box"></div>
            </div>
         </div>
      </div>
   </div>

   <main>