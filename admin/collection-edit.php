<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

// --- LOGIC LẤY DỮ LIỆU ---
$collection_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$collection = ['id' => null, 'name' => '', 'slug' => '', 'description' => '', 'image_url' => null];
$page_title_action = "Thêm bộ sưu tập mới";
$assigned_product_ids = [];

if ($collection_id) {
    $page_title_action = "Chỉnh sửa bộ sưu tập";
    $stmt_coll = $pdo->prepare("SELECT * FROM collections WHERE id = ?");
    $stmt_coll->execute([$collection_id]);
    $collection = $stmt_coll->fetch(PDO::FETCH_ASSOC);
    if (!$collection) { exit('Bộ sưu tập không tồn tại.'); }
    
    // Lấy ID các sản phẩm đã được gán vào bộ sưu tập này
    $stmt_assigned = $pdo->prepare("SELECT product_id FROM product_collections WHERE collection_id = ?");
    $stmt_assigned->execute([$collection_id]);
    $assigned_product_ids = $stmt_assigned->fetchAll(PDO::FETCH_COLUMN);
}

// Lấy tất cả sản phẩm trong cửa hàng để hiển thị checkbox
$products = $pdo->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = $page_title_action;
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>
<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0"><?php echo $page_title; ?></h1>
      <a href="/admin/collections.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
   </div>

   <form action="/admin/api/collections/save.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="collection_id" value="<?php echo $collection['id']; ?>">
      <div class="row">
         <div class="col-lg-8">
            <div class="card mb-4">
               <div class="card-header"><strong>Thông tin Bộ sưu tập</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label for="collection-name" class="form-label">Tên bộ sưu tập</label>
                     <input type="text" class="form-control" id="collection-name" name="name"
                        value="<?= htmlspecialchars($collection['name']) ?>" required>
                  </div>
                  <div class="mb-3">
                     <label for="collection-slug" class="form-label">Đường dẫn (slug)</label>
                     <input type="text" class="form-control" id="collection-slug" name="slug"
                        value="<?= htmlspecialchars($collection['slug']) ?>">
                  </div>
                  <div class="mb-3">
                     <label for="collection-description" class="form-label">Mô tả</label>
                     <textarea class="form-control" id="collection-description" name="description"
                        rows="3"><?= htmlspecialchars($collection['description']) ?></textarea>
                  </div>
                  <div class="mb-3">
                     <label for="collection-image" class="form-label">Ảnh đại diện</label>
                     <input class="form-control" type="file" id="collection-image" name="image" accept="image/*">
                     <?php if ($collection['image_url']): ?>
                     <img src="<?= htmlspecialchars($collection['image_url']) ?>" class="mt-2 img-fluid rounded"
                        style="max-height: 100px;">
                     <?php endif; ?>
                  </div>
               </div>
            </div>
            <div class="mt-4">
               <button type="submit" class="btn btn-success btn-lg">Lưu lại thay đổi</button>
            </div>
         </div>
         <div class="col-lg-4">
            <div class="card">
               <div class="card-header"><strong>Gán sản phẩm</strong></div>
               <div class="card-body product-assignment-list">
                  <?php foreach ($products as $product): ?>
                  <div class="form-check">
                     <input class="form-check-input" type="checkbox" name="product_ids[]"
                        value="<?php echo $product['id']; ?>" id="product_<?php echo $product['id']; ?>"
                        <?php echo in_array($product['id'], $assigned_product_ids) ? 'checked' : ''; ?>>
                     <label class="form-check-label" for="product_<?php echo $product['id']; ?>">
                        <?php echo htmlspecialchars($product['name']); ?>
                     </label>
                  </div>
                  <?php endforeach; ?>
               </div>
            </div>
         </div>
      </div>
   </form>
</div>
<style>
.product-assignment-list {
   max-height: 500px;
   overflow-y: auto;
}
</style>
<script>
// JS đơn giản để tạo slug và xem trước ảnh
document.addEventListener('DOMContentLoaded', function() {
   const nameInput = document.getElementById('collection-name');
   const slugInput = document.getElementById('collection-slug');
   if (nameInput && slugInput) {
      nameInput.addEventListener('keyup', () => slugInput.value = generateSlug(nameInput.value));
   }

   function generateSlug(text) {
      text = text.toString().toLowerCase().trim();
      const a = 'àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ';
      const b = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd';
      for (let i = 0; i < a.length; i++) {
         text = text.replace(new RegExp(a.charAt(i), 'g'), b.charAt(i));
      }
      return text.replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').replace(/\-\-+/g, '-');
   }
});
</script>
<?php include __DIR__ . '/templates/footer.php'; ?>