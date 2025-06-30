<?php
require_once __DIR__ . '/../core/admin-guard.php';
require_once __DIR__ . '/../../core/db_connect.php';
require_once __DIR__ . '/../core/helpers.php'; // Gọi file helpers

$page_title = "Khách Lẻ & Khách Sỉ";
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Lấy tất cả hóa đơn đã tạo
$invoices = $pdo->query("SELECT * FROM invoices ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Tổng quan Hóa đơn</h1>
      <a href="/admin/customers/invoice-create.php" class="btn btn-primary"><i
            class="bi bi-plus-circle-fill me-2"></i>Tạo hóa đơn mới</a>
   </div>

   <div class="card">
      <div class="card-body table-responsive">
         <table class="table table-hover align-middle">
            <thead class="table-light">
               <tr>
                  <th>Mã HĐ</th>
                  <th>Khách hàng</th>
                  <th>Ngày tạo</th>
                  <th>Trạng thái</th>
                  <th class="text-end">Tổng tiền</th>
                  <th class="text-end">Hành động</th>
               </tr>
            </thead>
            <tbody>
               <?php if (empty($invoices)): ?>
               <tr>
                  <td colspan="6" class="text-center">Chưa có hóa đơn nào được tạo.</td>
               </tr>
               <?php else: ?>
               <?php foreach ($invoices as $invoice): ?>
               <tr>
                  <th scope="row"><?= htmlspecialchars($invoice['invoice_code']) ?></th>
                  <td>
                     <?php // === THAY ĐỔI Ở ĐÂY: Thêm liên kết nếu có customer_id === ?>
                     <?php if (!empty($invoice['customer_id'])): ?>
                     <a href="/admin/customers/customer-detail.php?id=<?= $invoice['customer_id'] ?>"
                        title="Xem chi tiết khách hàng" target="_blank" class="btn btn-outline-primary fw-bold">
                        <?= htmlspecialchars($invoice['customer_name']) ?>
                     </a>
                     <?php else: ?>
                     <?= htmlspecialchars($invoice['customer_name']) ?>
                     <?php endif; ?>
                  </td>
                  <td><?= date('d/m/Y', strtotime($invoice['created_at'])) ?></td>
                  <td>
                     <?php 
                                        $status_text = 'Đã thanh toán';
                                        $status_class = 'bg-success';
                                        if ($invoice['status'] == 'unpaid') {
                                            $status_text = 'Chưa thanh toán';
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
                        class="btn btn-sm btn-outline-primary" target="_blank">Xem & In</a>
                     <a href="/admin/customers/invoice-edit.php?id=<?= $invoice['id'] ?>"
                        class="btn btn-sm btn-outline-secondary">Sửa</a>
                  </td>
               </tr>
               <?php endforeach; ?>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>