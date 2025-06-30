<?php
require_once __DIR__ . '/../core/admin-guard.php';
require_once __DIR__ . '/../../core/db_connect.php';

$invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$invoice_id) {
    header('Location: /admin/customers/');
    exit();
}

// Lấy thông tin hóa đơn chính
$stmt_invoice = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt_invoice->execute([$invoice_id]);
$invoice = $stmt_invoice->fetch(PDO::FETCH_ASSOC);
if (!$invoice) {
    exit('Hóa đơn không tồn tại.');
}

// === SỬA LỖI: Thêm "WHERE ii.invoice_id = ?" vào câu truy vấn ===
// Lấy thông tin các sản phẩm trong đơn hàng
$stmt_items = $pdo->prepare("
    SELECT ii.*, pv.sku 
    FROM invoice_items ii 
    LEFT JOIN product_variants pv ON ii.product_name = pv.sku OR ii.product_name = pv.id 
    WHERE ii.invoice_id = ?
    ORDER BY ii.id ASC
");
$stmt_items->execute([$invoice_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);


$page_title = "Sửa Hóa Đơn: " . htmlspecialchars($invoice['invoice_code']);
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Chỉnh Sửa Hóa Đơn <span
            class="text-primary"><?= htmlspecialchars($invoice['invoice_code']) ?></span></h1>
      <a href="/admin/customers/" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại Danh
         sách</a>
   </div>
   <div id="page-alert" class="alert d-none" role="alert"></div>
   <form id="invoice-edit-form" method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">

      <div class="row">
         <div class="col-lg-8">
            <div class="card mb-4">
               <div class="card-header"><strong>Thông tin khách hàng</strong></div>
               <div class="card-body">
                  <input type="hidden" name="customer_id" id="customer_id"
                     value="<?= htmlspecialchars($invoice['customer_id']) ?>">
                  <div class="row">
                     <div class="col-md-6 mb-3">
                        <label class="form-label">Tên khách hàng</label>
                        <input type="text" class="form-control" name="customer_name" id="customer_name"
                           value="<?= htmlspecialchars($invoice['customer_name']) ?>" required>
                     </div>
                     <div class="col-md-6 mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" class="form-control" name="customer_phone" id="customer_phone"
                           value="<?= htmlspecialchars($invoice['customer_phone']) ?>">
                     </div>
                  </div>
                  <div class="mb-3">
                     <label class="form-label">Địa chỉ</label>
                     <textarea class="form-control" name="customer_address" id="customer_address"
                        rows="2"><?= htmlspecialchars($invoice['customer_address']) ?></textarea>
                  </div>
               </div>
            </div>

            <div class="card">
               <div class="card-header"><strong>Chi tiết hóa đơn</strong></div>
               <div class="card-body table-responsive">
                  <table class="table">
                     <thead class="table-light">
                        <tr>
                           <th>Sản phẩm</th>
                           <th style="width: 120px;">Số lượng</th>
                           <th style="width: 170px;">Đơn giá</th>
                           <th class="text-end">Thành tiền</th>
                           <th></th>
                        </tr>
                     </thead>
                     <tbody id="invoice-items-body">
                        <?php foreach ($items as $item): ?>
                        <tr data-variant-id="<?= $item['id'] ?>">
                           <td>
                              <?= htmlspecialchars($item['product_name']) ?><br>
                              <small class="text-muted">SKU: <?= htmlspecialchars($item['sku'] ?? 'N/A') ?></small>
                              <input type="hidden" name="items[<?= $item['id'] ?>][name]"
                                 value="<?= htmlspecialchars($item['product_name']) ?>">
                           </td>
                           <td><input type="number" class="form-control form-control-sm item-quantity"
                                 name="items[<?= $item['id'] ?>][quantity]" value="<?= $item['quantity'] ?>" min="1">
                           </td>
                           <td><input type="number" class="form-control form-control-sm item-price"
                                 name="items[<?= $item['id'] ?>][price]" value="<?= (int)$item['unit_price'] ?>"></td>
                           <td class="text-end fw-bold line-total"></td>
                           <td class="text-end"><button type="button"
                                 class="btn btn-sm btn-outline-danger btn-remove-item">&times;</button></td>
                        </tr>
                        <?php endforeach; ?>
                     </tbody>
                     <tfoot>
                        <tr>
                           <td colspan="3" class="text-end border-0"><strong>Tổng cộng</strong></td>
                           <td class="text-end h5 fw-bold border-0" id="invoice-total">0đ</td>
                           <td class="border-0"></td>
                        </tr>
                     </tfoot>
                  </table>
               </div>
            </div>
         </div>

         <div class="col-lg-4">
            <div class="card mb-4">
               <div class="card-header"><strong>Thông tin hóa đơn</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label class="form-label">Trạng thái</label>
                     <select name="status" class="form-select">
                        <option value="paid" <?= $invoice['status'] == 'paid' ? 'selected' : '' ?>>Đã thanh toán
                        </option>
                        <option value="unpaid" <?= $invoice['status'] == 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán
                        </option>
                        <option value="debt" <?= $invoice['status'] == 'debt' ? 'selected' : '' ?>>Công nợ</option>
                     </select>
                  </div>
                  <div class="mb-3">
                     <label class="form-label">Ghi chú</label>
                     <textarea class="form-control" name="note"
                        rows="3"><?= htmlspecialchars($invoice['note']) ?></textarea>
                  </div>
               </div>
            </div>
            <div class="d-grid">
               <button type="submit" class="btn btn-success btn-lg">Lưu lại thay đổi</button>
            </div>
         </div>
      </div>
   </form>
</div>

<script src="/admin/assets/js/admin-invoice-edit.js"></script>
<?php include __DIR__ . '/../templates/footer.php'; ?>