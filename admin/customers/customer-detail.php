<?php
require_once __DIR__ . '/../core/admin-guard.php';
require_once __DIR__ . '/../../core/db_connect.php';
require_once __DIR__ . '/../core/helpers.php'; 

$customer_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$customer_id) { header('Location: /admin/customers/customer-manage.php'); exit(); }

// 1. Lấy thông tin cơ bản của khách hàng
$stmt_customer = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt_customer->execute([$customer_id]);
$customer = $stmt_customer->fetch(PDO::FETCH_ASSOC);
if (!$customer) { exit('Khách hàng không tồn tại.'); }

// 2. Lấy tất cả các hóa đơn của khách hàng này
$stmt_invoices = $pdo->prepare("SELECT * FROM invoices WHERE customer_id = ? ORDER BY id DESC");
$stmt_invoices->execute([$customer_id]);
$invoices = $stmt_invoices->fetchAll(PDO::FETCH_ASSOC);

// 3. Tính toán tổng công nợ
$total_debt = 0;
foreach($invoices as $invoice) {
    if ($invoice['status'] === 'unpaid' || $invoice['status'] === 'debt') {
        $total_debt += $invoice['total_amount'];
    }
}

$page_title = "Chi tiết Khách hàng: " . htmlspecialchars($customer['customer_name']);
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
?>
<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <a href="/admin/customers/customer-manage.php" class="btn btn-sm btn-outline-secondary mb-2"><i
               class="bi bi-arrow-left"></i> Quay lại Danh sách</a>
         <h1 class="mb-0">Chi Tiết Khách Hàng</h1>
      </div>
   </div>

   <div class="row">
      <div class="col-lg-4">
         <div class="card mb-4">
            <div class="card-body text-center">
               <i class="bi bi-person-circle fs-1 text-secondary"></i>
               <h4 class="card-title mt-2"><?= htmlspecialchars($customer['customer_name']) ?></h4>
               <p class="text-muted mb-1"><?= htmlspecialchars($customer['phone_number']) ?></p>
               <p class="text-muted"><?= htmlspecialchars($customer['email']) ?></p>
            </div>
         </div>
         <div class="card">
            <div class="card-header"><strong>Thống kê</strong></div>
            <ul class="list-group list-group-flush">
               <li class="list-group-item d-flex justify-content-between">
                  <span>Loại khách hàng:</span>
                  <span class="badge <?= $customer['customer_type'] == 'wholesale' ? 'bg-success' : 'bg-secondary' ?>">
                     <?= $customer['customer_type'] == 'wholesale' ? 'Khách Sỉ' : 'Khách Lẻ' ?>
                  </span>
               </li>
               <li class="list-group-item d-flex justify-content-between">
                  <span>Tổng số hóa đơn:</span>
                  <span class="fw-bold"><?= count($invoices) ?></span>
               </li>
               <li class="list-group-item d-flex justify-content-between">
                  <span>Tổng công nợ:</span>
                  <span class="fw-bold text-danger"><?= number_format($total_debt, 0, ',', '.') ?>đ</span>
               </li>
            </ul>
         </div>
      </div>

      <div class="col-lg-8">
         <div class="card">
            <div class="card-header"><strong>Lịch sử hóa đơn</strong></div>
            <div class="card-body table-responsive">
               <table class="table table-hover align-middle">
                  <thead class="table-light">
                     <tr>
                        <th>Mã HĐ</th>
                        <th>Ngày tạo</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Tổng tiền</th>
                        <th class="text-end"></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($invoices)): ?>
                     <tr>
                        <td colspan="5" class="text-center">Khách hàng này chưa có hóa đơn nào.</td>
                     </tr>
                     <?php else: ?>
                     <?php foreach ($invoices as $invoice): ?>
                     <tr>
                        <th scope="row"><?= htmlspecialchars($invoice['invoice_code']) ?></th>
                        <td><?= date('d/m/Y', strtotime($invoice['created_at'])) ?></td>
                        <td>
                           <?php 
                                                $status_text = 'Đã thanh toán';
                                                $status_class = 'bg-success';
                                                if ($invoice['status'] == 'unpaid') {
                                                    $status_text = 'Chưa TT';
                                                    $status_class = 'bg-warning text-dark';
                                                } elseif ($invoice['status'] == 'debt') {
                                                    $status_text = 'Công nợ';
                                                    $status_class = 'bg-danger';
                                                }
                                            ?>
                           <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                        <td class="text-end fw-bold"><?= number_format($invoice['total_amount'], 0, ',', '.') ?>đ</td>
                        <td class="text-end">
                           <a href="/admin/customers/invoice-view.php?id=<?= $invoice['id'] ?>"
                              class="btn btn-sm btn-outline-primary">Xem</a>
                        </td>
                     </tr>
                     <?php endforeach; ?>
                     <?php endif; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>