<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth.html');
    exit();
}
$user_id = $_SESSION['user_id'];
require_once __DIR__ . '/core/db_connect.php';

$stmt_user = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

$stmt_addresses = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt_addresses->execute([$user_id]);
$addresses = $stmt_addresses->fetchAll();

$page_title = "Sổ Địa Chỉ";
include 'templates/header.php';
?>

<div class="container my-5">
   <div class="row">
      <div class="col-lg-3">
         <aside class="account-sidebar">
            <div class="account-user-info mb-4">
               <div class="account-avatar"><?php echo strtoupper(mb_substr($user['full_name'], 0, 1)); ?></div>
               <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h5>
               <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
            </div>
            <nav class="nav flex-column nav-pills">
               <a class="nav-link" href="/account.html"><i class="bi bi-person-circle me-2"></i>Thông tin tài khoản</a>
               <a class="nav-link active" href="/account-addresses.html"><i class="bi bi-geo-alt-fill me-2"></i>Sổ địa
                  chỉ</a>
               <a class="nav-link" href="/account-orders.html"><i class="bi bi-box-seam-fill me-2"></i>Đơn hàng của
                  tôi</a>
               <a class="nav-link" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a>
            </nav>
         </aside>
      </div>

      <div class="col-lg-9">
         <main class="account-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
               <h2>Sổ Địa Chỉ</h2>
               <button id="btn-add-address" class="btn btn-primary">
                  <i class="bi bi-plus-circle-fill me-2"></i>Thêm địa chỉ mới
               </button>
            </div>
            <div id="address-list-alert" class="alert d-none"></div>
            <div class="row" id="address-list-container">
            </div>
         </main>
      </div>
   </div>
</div>

<div class="modal fade" id="address-modal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <form id="address-form">
            <div class="modal-header">
               <h5 class="modal-title" id="addressModalLabel">Thêm địa chỉ mới</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <div id="modal-alert" class="alert d-none"></div>
               <input type="hidden" name="address_id" id="address_id">
               <div class="mb-3">
                  <label for="address-full-name" class="form-label">Họ và tên</label>
                  <input type="text" class="form-control" id="address-full-name" name="full_name" required>
               </div>
               <div class="mb-3">
                  <label for="address-phone-number" class="form-label">Số điện thoại</label>
                  <input type="tel" class="form-control" id="address-phone-number" name="phone_number" required>
               </div>
               <div class="mb-3">
                  <label for="address-line" class="form-label">Địa chỉ chi tiết</label>
                  <textarea class="form-control" id="address-line" name="address_line" rows="3" required></textarea>
               </div>
               <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_default" value="1" id="address-is-default">
                  <label class="form-check-label" for="address-is-default">Đặt làm địa chỉ mặc định</label>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
               <button type="submit" class="btn btn-primary">Lưu địa chỉ</button>
            </div>
         </form>
      </div>
   </div>
</div>

<script src="/assets/js/address-book.js"></script>
<?php include 'templates/footer.php'; ?>