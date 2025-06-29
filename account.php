<?php
// Bắt đầu session để có thể truy cập các biến session
session_start();

// === NGƯỜI GÁC CỔNG: KIỂM TRA ĐĂNG NHẬP ===
// Nếu không tồn tại session user_id, tức là người dùng chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng họ về trang đăng nhập và kết thúc script
    header('Location: /auth.html');
    exit();
}

// Nếu đã đăng nhập, lấy ID người dùng từ session
$user_id = $_SESSION['user_id'];

// Include các file cần thiết
require_once __DIR__ . '/core/db_connect.php';

// Truy vấn để lấy thông tin mới nhất của người dùng
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Nếu không tìm thấy user (trường hợp hiếm), hủy session và chuyển hướng
if (!$user) {
    session_unset();
    session_destroy();
    header('Location: /auth.html');
    exit();
}


// Thiết lập các biến cho trang
$page_title = "Tài Khoản Của Tôi";
$page_description = "Quản lý thông tin cá nhân, địa chỉ và đơn hàng của bạn.";

// --- PHẦN GIAO DIỆN ---
include 'templates/header.php';
?>

<div class="container my-5">
   <div class="row">
      <div class="col-lg-3">
         <aside class="account-sidebar">
            <div class="account-user-info mb-4">
               <div class="account-avatar">
                  <?php echo strtoupper(mb_substr($user['full_name'], 0, 1)); ?>
               </div>
               <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h5>
               <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
            </div>
            <nav class="nav flex-column nav-pills">
               <a class="nav-link active" href="/account.html"><i class="bi bi-person-circle me-2"></i>Thông tin tài
                  khoản</a>
               <a class="nav-link" href="/account-addresses.html"><i class="bi bi-geo-alt-fill me-2"></i>Sổ địa chỉ</a>
               <a class="nav-link" href="/account-orders.html"><i class="bi bi-box-seam-fill me-2"></i>Đơn hàng của
                  tôi</a>
               <a class="nav-link" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a>
            </nav>
         </aside>
      </div>

      <div class="col-lg-9">
         <main class="account-content">
            <h2 class="mb-4">Thông Tin Tài Khoản</h2>

            <form id="update-info-form">
               <div id="update-info-alert" class="alert d-none"></div>
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label for="full_name" class="form-label">Họ và Tên</label>
                     <input type="text" class="form-control" id="full_name" name="full_name"
                        value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                  </div>
                  <div class="col-md-6 mb-3">
                     <label for="email" class="form-label">Email</label>
                     <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                     <small class="form-text text-muted">Email không thể thay đổi.</small>
                  </div>
               </div>
               <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
            </form>

            <hr class="my-5">

            <h2 class="mb-4">Đổi Mật Khẩu</h2>
            <form id="update-password-form">
               <div id="update-password-alert" class="alert d-none"></div>
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                     <input type="password" class="form-control" id="current_password" name="current_password">
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label for="new_password" class="form-label">Mật khẩu mới</label>
                     <input type="password" class="form-control" id="new_password" name="new_password">
                  </div>
                  <div class="col-md-6 mb-3">
                     <label for="confirm_new_password" class="form-label">Xác nhận mật khẩu mới</label>
                     <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password">
                  </div>
               </div>
               <button type="submit" class="btn btn-secondary">Đổi mật khẩu</button>
            </form>
         </main>
      </div>
   </div>
</div>

<script src="/assets/js/account.js"></script>
<?php 
include 'templates/footer.php'; 
?>