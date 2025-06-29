<?php
session_start();
// Nếu admin đã đăng nhập, chuyển hướng thẳng vào dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: /admin/');
    exit();
}
$page_title = "Đăng nhập Quản trị";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo $page_title; ?></title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body>
   <div class="login-wrapper">
      <div class="login-box">
         <h2 class="text-center mb-4">Khóm Bedding - Admin</h2>
         <form id="admin-login-form">
            <div id="login-alert" class="alert d-none"></div>
            <div class="mb-3">
               <label for="email" class="form-label">Email</label>
               <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
               <label for="password" class="form-label">Mật khẩu</label>
               <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid">
               <button type="submit" class="btn btn-primary">Đăng nhập</button>
            </div>
         </form>
      </div>
   </div>
   <script src="/admin/assets/js/admin-login.js"></script>
</body>

</html>