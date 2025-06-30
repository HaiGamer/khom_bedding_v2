<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Lịch sử Xuất kho";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// Logic phân trang và tìm kiếm
$limit = 15;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;

// Truy vấn lấy các phiếu xuất kho
$sql = "
    SELECT se.id, se.export_code, se.note, se.created_at, u.full_name as created_by
    FROM stock_exports se
    JOIN users u ON se.user_id = u.id
    ORDER BY se.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$exports = $stmt->fetchAll();
?>

<div class="p-4">
   <h1 class="mb-4">Lịch sử Xuất kho</h1>
   <div class="card">
      <div class="card-body table-responsive">
         <table class="table table-hover align-middle">
            <thead class="table-light">
               <tr>
                  <th>Mã Phiếu</th>
                  <th>Người tạo</th>
                  <th>Ngày tạo</th>
                  <th>Ghi chú</th>
                  <th class="text-end">Hành động</th>
               </tr>
            </thead>
            <tbody>
               <?php foreach ($exports as $export): ?>
               <tr>
                  <th scope="row"><?= htmlspecialchars($export['export_code']) ?></th>
                  <td><?= htmlspecialchars($export['created_by']) ?></td>
                  <td><?= date('d/m/Y H:i', strtotime($export['created_at'])) ?></td>
                  <td><?= htmlspecialchars($export['note']) ?></td>
                  <td class="text-end">
                     <button class="btn btn-sm btn-outline-primary btn-view-detail" data-bs-toggle="modal"
                        data-bs-target="#export-detail-modal" data-export-id="<?= $export['id'] ?>">
                        Xem chi tiết
                     </button>
                  </td>
               </tr>
               <?php endforeach; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<div class="modal fade" id="export-detail-modal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Chi tiết Phiếu Xuất Kho</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body" id="export-detail-modal-body">
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
         </div>
      </div>
   </div>
</div>

<script src="/admin/assets/js/admin-stock-history.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>