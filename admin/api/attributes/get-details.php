<?php
require_once __DIR__ . '/../../../core/db_connect.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(!$id) exit;

$stmt_attr = $pdo->prepare("SELECT * FROM attributes WHERE id = ?");
$stmt_attr->execute([$id]);
$attribute = $stmt_attr->fetch();

$stmt_values = $pdo->prepare("SELECT * FROM attribute_values WHERE attribute_id = ? ORDER BY value");
$stmt_values->execute([$id]);
$values = $stmt_values->fetchAll();
?>
<div class="card">
   <div class="card-header"><strong>Chi tiết thuộc tính</strong></div>
   <div class="card-body">
      <form class="attribute-edit-form mb-4">
         <input type="hidden" name="action" value="edit_attribute">
         <input type="hidden" name="id" value="<?= $attribute['id'] ?>">
         <div class="input-group">
            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($attribute['name']) ?>">
            <button class="btn btn-success" type="submit">Lưu tên</button>
            <button class="btn btn-danger btn-delete-attribute" type="button"
               data-id="<?= $attribute['id'] ?>">Xóa</button>
         </div>
      </form>
      <hr>
      <h6><strong>Các giá trị hiện có</strong></h6>
      <ul class="list-group mb-4">
         <?php foreach($values as $value): ?>
         <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($value['value']) ?>
            <button class="btn btn-sm btn-outline-danger btn-delete-value" data-id="<?= $value['id'] ?>">Xóa</button>
         </li>
         <?php endforeach; ?>
      </ul>
      <form class="attribute-value-form">
         <input type="hidden" name="action" value="add_value">
         <input type="hidden" name="attribute_id" value="<?= $attribute['id'] ?>">
         <label><strong>Thêm giá trị mới</strong></label>
         <div class="input-group">
            <input type="text" class="form-control" name="value" placeholder="Ví dụ: Đỏ, Xanh, L, XL...">
            <button class="btn btn-primary" type="submit">Thêm</button>
         </div>
      </form>
   </div>
</div>