<?php
require_once __DIR__ . '/../core/admin-guard.php';
require_once __DIR__ . '/../../core/db_connect.php';

$page_title = "Quản lý Khách hàng";
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Lấy tất cả khách hàng để hiển thị ban đầu
$customers = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Quản lý Khách hàng</h1>
   </div>

   <div id="page-alert" class="alert d-none" role="alert"></div>
   <div class="row">
      <div class="col-lg-4">
         <div class="card">
            <div class="card-header">
               <h5 class="card-title mb-0" id="form-title">Thêm khách hàng mới</h5>
            </div>
            <div class="card-body">
               <form id="customer-form">
                  <input type="hidden" name="customer_id" id="customer_id">
                  <div class="mb-3">
                     <label for="customer-name" class="form-label">Tên khách hàng</label>
                     <input type="text" class="form-control" id="customer-name" name="customer_name" required>
                  </div>
                  <div class="mb-3">
                     <label for="customer-phone" class="form-label">Số điện thoại</label>
                     <input type="tel" class="form-control" id="customer-phone" name="phone_number">
                  </div>
                  <div class="mb-3">
                     <label for="customer-address" class="form-label">Địa chỉ</label>
                     <textarea class="form-control" id="customer-address" name="address" rows="3"></textarea>
                  </div>
                  <div class="mb-3">
                     <label for="customer-email" class="form-label">Email</label>
                     <input type="email" class="form-control" id="customer-email" name="email">
                  </div>
                  <div class="mb-3">
                     <label class="form-label">Loại khách hàng</label>
                     <select name="customer_type" id="customer-type" class="form-select">
                        <option value="retail">Khách Lẻ</option>
                        <option value="wholesale">Khách Sỉ</option>
                     </select>
                  </div>
                  <div class="d-flex justify-content-end">
                     <button type="button" id="btn-cancel-edit" class="btn btn-secondary me-2 d-none">Hủy</button>
                     <button type="submit" class="btn btn-primary">Lưu khách hàng</button>
                  </div>
               </form>
            </div>
         </div>
      </div>

      <div class="col-lg-8">
         <div class="card">
            <div class="card-body table-responsive">
               <table class="table table-hover">
                  <thead>
                     <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>SĐT</th>
                        <th>Loại</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                     </tr>
                  </thead>
                  <tbody id="customers-table-body">
                     <?php foreach ($customers as $customer): ?>
                     <tr data-customer='<?php echo json_encode($customer, JSON_UNESCAPED_UNICODE); ?>'>
                        <td><?= $customer['id'] ?></td>
                        <td><?= htmlspecialchars($customer['customer_name']) ?></td>
                        <td><?= htmlspecialchars($customer['phone_number']) ?></td>
                        <td>
                           <span
                              class="badge <?= $customer['customer_type'] == 'wholesale' ? 'bg-success' : 'bg-secondary' ?>">
                              <?= $customer['customer_type'] == 'wholesale' ? 'Khách Sỉ' : 'Khách Lẻ' ?>
                           </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($customer['created_at'])) ?></td>
                        <td>
                           <button class="btn btn-sm btn-outline-primary btn-edit">Sửa</button>
                           <button class="btn btn-sm btn-outline-danger btn-delete">Xóa</button>
                        </td>
                     </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   </div>
</div>

<script src="/admin/assets/js/admin-customers.js"></script>
<?php include __DIR__ . '/../templates/footer.php'; ?>