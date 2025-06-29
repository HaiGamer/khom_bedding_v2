<?php
$page_title = "Đăng Nhập & Đăng Ký";
$page_description = "Tạo tài khoản hoặc đăng nhập để hưởng nhiều ưu đãi và quản lý đơn hàng tại Khóm Bedding.";

session_start(); // Di chuyển session_start lên đầu để có thể dùng trong logic chuyển hướng
if (isset($_SESSION['user_id'])) {
    header('Location: /account.html');
    exit();
}

include 'templates/header.php';
?>

<div class="container my-5">
   <div class="row justify-content-center">
      <div class="col-lg-6">
         <div class="auth-wrapper">
            <ul class="nav nav-pills nav-fill mb-4" id="authTabs" role="tablist">
               <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login"
                     type="button" role="tab">Đăng Nhập</button>
               </li>
               <li class="nav-item" role="presentation">
                  <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register"
                     type="button" role="tab">Đăng Ký</button>
               </li>
            </ul>

            <div class="tab-content" id="authTabsContent">
               <div class="tab-pane fade show active" id="login" role="tabpanel">
                  <h3 class="text-center mb-4">Chào mừng trở lại!</h3>
                  <form id="login-form">
                     <div id="login-alert" class="alert d-none"></div>
                     <div class="mb-3">
                        <label for="login-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="login-email" name="email" required>
                     </div>
                     <div class="mb-3">
                        <label for="login-password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="login-password" name="password" required>
                     </div>
                     <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Đăng Nhập</button>
                     </div>
                  </form>
               </div>

               <div class="tab-pane fade" id="register" role="tabpanel">
                  <h3 class="text-center mb-4">Tạo tài khoản mới</h3>
                  <form id="register-form">
                     <div id="register-alert" class="alert d-none"></div>
                     <div class="mb-3">
                        <label for="register-fullname" class="form-label">Họ và Tên</label>
                        <input type="text" class="form-control" id="register-fullname" name="full_name" required>
                     </div>
                     <div class="mb-3">
                        <label for="register-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="register-email" name="email" required>
                     </div>
                     <div class="mb-3">
                        <label for="register-password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="register-password" name="password" required>
                     </div>
                     <div class="mb-3">
                        <label for="register-confirm-password" class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" id="register-confirm-password"
                           name="confirm_password" required>
                     </div>
                     <div class="mb-3 d-flex justify-content-center">
                        <div class="h-captcha" data-sitekey="32f4ae2a-3f62-4624-8b84-d4ca3cc6072e"></div>
                     </div>
                     <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Đăng Ký</button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<script src="/assets/js/auth.js"></script>
<?php
include 'templates/footer.php'; 
?>