<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Quản lý Bộ sưu tập";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

$collections = $pdo->query("SELECT * FROM collections ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Quản lý Bộ sưu tập</h1>
      <a href="/admin/collection-edit.php" class="btn btn-primary">
         <i class="bi bi-plus-circle-fill me-2"></i>Thêm bộ sưu tập mới
      </a>
   </div>

   <div class="card">
      <div class="card-body table-responsive">
         <table class="table table-hover align-middle">
            <thead>
               <tr>
                  <th>ID</th>
                  <th style="width: 80px;">Ảnh</th>
                  <th>Tên</th>
                  <th>Đường dẫn</th>
                  <th>Hành động</th>
               </tr>
            </thead>
            <tbody>
               <?php foreach ($collections as $collection): ?>
               <tr>
                  <td><?= $collection['id'] ?></td>
                  <td><img src="<?= htmlspecialchars($collection['image_url'] ?? '/assets/images/placeholder.png') ?>"
                        class="admin-product-thumbnail"></td>
                  <td><?= htmlspecialchars($collection['name']) ?></td>
                  <td><?= htmlspecialchars($collection['slug']) ?></td>
                  <td>
                     <a href="/admin/collection-edit.php?id=<?= $collection['id'] ?>"
                        class="btn btn-sm btn-outline-primary">Sửa / Gán sản phẩm</a>
                  </td>
               </tr>
               <?php endforeach; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>