<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Quản lý Thuộc tính";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// Lấy danh sách tất cả các thuộc tính để hiển thị ở cột trái
$attributes = $pdo->query("SELECT * FROM attributes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$selected_attribute_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
?>

<div class="p-4">
   <h1 class="mb-4">Quản lý Thuộc tính & Giá trị</h1>
   <div class="row">
      <div class="col-lg-4">
         <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
               <strong>Các thuộc tính</strong>
               <button class="btn btn-primary btn-sm" id="btn-new-attribute"><i class="bi bi-plus-circle"></i> Thêm
                  mới</button>
            </div>
            <ul class="list-group list-group-flush" id="attribute-list">
               <?php foreach ($attributes as $attribute): ?>
               <a href="?id=<?php echo $attribute['id']; ?>"
                  class="list-group-item list-group-item-action <?php echo ($selected_attribute_id == $attribute['id']) ? 'active' : ''; ?>"
                  data-id="<?php echo $attribute['id']; ?>">
                  <?php echo htmlspecialchars($attribute['name']); ?>
               </a>
               <?php endforeach; ?>
            </ul>
         </div>
      </div>

      <div class="col-lg-8">
         <div id="attribute-details-container">
            <?php if ($selected_attribute_id): ?>
            <div class="text-center p-5">
               <div class="spinner-border"></div>
            </div>
            <?php else: ?>
            <div class="card">
               <div class="card-body text-center text-muted">
                  <p><i class="bi bi-arrow-left-circle-fill fs-2"></i></p>
                  <p>Hãy chọn một thuộc tính từ danh sách bên trái để xem chi tiết và quản lý các giá trị của nó.</p>
               </div>
            </div>
            <?php endif; ?>
         </div>
      </div>
   </div>
</div>

<script>
// Truyền ID thuộc tính đang được chọn sang JS
const selectedAttributeId = <?php echo json_encode($selected_attribute_id); ?>;
</script>
<script src="/admin/assets/js/admin-attributes.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>