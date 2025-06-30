<?php
require_once __DIR__ . '/../core/admin-guard.php';
require_once __DIR__ . '/../../core/db_connect.php';
require_once __DIR__ . '/../core/helpers.php'; // Gọi file helper để dùng hàm docso()

$invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$invoice_id) { exit('ID hóa đơn không hợp lệ.'); }

$stmt_invoice = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt_invoice->execute([$invoice_id]);
$invoice = $stmt_invoice->fetch();
if (!$invoice) { exit('Hóa đơn không tồn tại.'); }

$stmt_items = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt_items->execute([$invoice_id]);
$items = $stmt_items->fetchAll();

// Thông tin cửa hàng (Bạn có thể thay đổi ở đây)
$shop_info = [
    'name' => 'SUYUE Vietnam',
    'address' => 'ĐX-100, Hiệp An, TP. Thủ Dầu Một, Bình Dương',
    'phone' => '0388.105.502'
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
   <meta charset="UTF-8">
   <title>Hóa đơn <?= htmlspecialchars($invoice['invoice_code']) ?></title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <style>
   body {
      background-color: #eee;
   }

   .invoice-wrapper {
      max-width: 800px;
      margin: 30px auto;
      background: #fff;
      padding: 20px;
   }

   .invoice-header {
      border-bottom: 2px solid #000;
      padding-bottom: 15px;
      margin-bottom: 20px;
   }

   .invoice-table th,
   .invoice-table td {
      vertical-align: middle;
   }

   @media print {
      body {
         background-color: #fff;
      }

      .invoice-wrapper {
         margin: 0;
         box-shadow: none;
      }

      .no-print {
         display: none !important;
      }
   }
   </style>
</head>

<body>
   <div class="container no-print my-3 text-center">
      <a href="/admin/customers/" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
      <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer-fill"></i> In hóa đơn / Lưu
         PDF</button>
   </div>

   <div class="invoice-wrapper">
      <header class="invoice-header row mb-2">
         <div class="col-8">
            <h5 class="fw-bold"><?= htmlspecialchars($shop_info['name']) ?></h5>
            <p class="mb-0 small">Địa chỉ: <?= htmlspecialchars($shop_info['address']) ?></p>
            <p class="mb-0 small">SĐT: <?= htmlspecialchars($shop_info['phone']) ?></p>
         </div>
         <div class="col-4 text-end">
            <h2 class="fw-bold mb-0">HÓA ĐƠN</h2>
            <p class="mb-0">Số: <strong><?= htmlspecialchars($invoice['invoice_code']) ?></strong></p>
            <p class="mb-0">Ngày: <?= date('d/m/Y', strtotime($invoice['created_at'])) ?></p>
         </div>
      </header>

      <section class="customer-info mb-2">
         <p class="mb-1"><strong>Khách hàng:</strong> <?= htmlspecialchars($invoice['customer_name']) ?>
            <strong style=" padding-left: 1.5rem; ">SĐT:</strong> <?= htmlspecialchars($invoice['customer_phone']) ?>
         </p>
         <p class=" mb-0"><strong>Địa chỉ:</strong> <?= htmlspecialchars($invoice['customer_address']) ?>
         </p>
      </section>

      <section class="invoice-details">
         <table class="table table-bordered invoice-table" style="margin-bottom: 0.2rem;">
            <thead class="table-light">
               <tr>
                  <th class="text-center">STT</th>
                  <th>Tên Hàng</th>
                  <th class="text-center">SL</th>
                  <th class="text-end">Đơn giá</th>
                  <th class="text-end">Thành tiền</th>
               </tr>
            </thead>
            <tbody>
               <?php foreach ($items as $index => $item): ?>
               <tr>
                  <td class="text-center"><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars($item['product_name']) ?></td>
                  <td class="text-center"><?= $item['quantity'] ?></td>
                  <td class="text-end"><?= number_format($item['unit_price'], 0, ',', '.') ?></td>
                  <td class="text-end"><?= number_format($item['total'], 0, ',', '.') ?></td>
               </tr>
               <?php endforeach; ?>
            </tbody>
            <tfoot>
               <tr>
                  <td colspan="4" class="text-end fw-bold">Tổng cộng:</td>
                  <td class="text-end fw-bold"><?= number_format($invoice['total_amount'], 0, ',', '.') ?>đ</td>
               </tr>
            </tfoot>
         </table>
         <p class="mt-1" style="margin-bottom: 0.2rem;"><strong>Cộng thành tiền (viết bằng chữ):</strong>
            <em><?php try { echo ucfirst(docso($invoice['total_amount'])) . ' đồng chẵn.'; } catch(Exception $e) { echo 'N/A'; } ?></em>
         </p>
      </section>

      <footer class="mt-0 pt-0">
         <div class="row text-center">
            <div class="col-6">
               <p class="fw-bold mb-0">Người nhận hàng</p>
               <p class="fst-italic" style="margin-bottom: 4rem;">(Ký, ghi rõ họ tên)</p>
            </div>
            <div class="col-6">
               <p class="fw-bold mb-0">Người tạo hóa đơn</p>
               <p class="fst-italic" style="margin-bottom: 4rem;">(Ký, ghi rõ họ tên)</p>
            </div>
         </div>
      </footer>
   </div>
</body>

</html>