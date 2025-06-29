<?php
require_once __DIR__ . '/core/admin-guard.php'; // Gọi "người gác cổng"
$page_title = "Dashboard";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>
<div class="p-4">
   <h1 class="mb-4">Dashboard</h1>
   <p>Chào mừng trở lại, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>

   <div class="row">
      <div class="col-md-4">
         <div class="card text-white bg-primary mb-3">
            <div class="card-body">
               <h5 class="card-title">Đơn hàng mới</h5>
               <p class="card-text fs-3">12</p>
            </div>
         </div>
      </div>
      <div class="col-md-4">
         <div class="card text-white bg-success mb-3">
            <div class="card-body">
               <h5 class="card-title">Doanh thu hôm nay</h5>
               <p class="card-text fs-3">5,200,000đ</p>
            </div>
         </div>
      </div>
   </div>
</div>

<?php
include __DIR__ . '/templates/footer.php';
?>