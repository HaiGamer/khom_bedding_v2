<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Quản lý Danh mục";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// Lấy tất cả danh mục để hiển thị ban đầu
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Quản lý Danh mục</h1>
   </div>

   <div class="row">
      <div class="col-lg-4">
         <div class="card">
            <div class="card-header">
               <h5 class="card-title mb-0" id="form-title">Thêm danh mục mới</h5>
            </div>
            <div class="card-body">
               <form id="category-form">
                  <input type="hidden" name="category_id" id="category_id">
                  <div class="mb-3">
                     <label for="category-name" class="form-label">Tên danh mục</label>
                     <input type="text" class="form-control" id="category-name" name="name" required>
                  </div>
                  <div class="mb-3">
                     <label for="category-slug" class="form-label">Đường dẫn (slug)</label>
                     <input type="text" class="form-control" id="category-slug" name="slug">
                  </div>
                  <div class="mb-3">
                     <label for="category-description" class="form-label">Mô tả</label>
                     <textarea class="form-control" id="category-description" name="description" rows="4"></textarea>
                  </div>
                  <div class="d-flex justify-content-end">
                     <button type="button" id="btn-cancel-edit" class="btn btn-secondary me-2 d-none">Hủy</button>
                     <button type="submit" class="btn btn-primary">Lưu</button>
                  </div>
               </form>
            </div>
         </div>
      </div>

      <div class="col-lg-8">
         <div class="card">
            <div class="card-body">
               <div class="table-responsive">
                  <table class="table table-hover">
                     <thead>
                        <tr>
                           <th>ID</th>
                           <th>Tên danh mục</th>
                           <th>Đường dẫn</th>
                           <th>Hành động</th>
                        </tr>
                     </thead>
                     <tbody id="categories-table-body">
                        <?php foreach ($categories as $category): ?>
                        <tr data-id="<?php echo $category['id']; ?>">
                           <td><?php echo $category['id']; ?></td>
                           <td class="cat-name"><?php echo htmlspecialchars($category['name']); ?></td>
                           <td class="cat-slug"><?php echo htmlspecialchars($category['slug']); ?></td>
                           <td class="cat-description d-none"><?php echo htmlspecialchars($category['description']); ?>
                           </td>
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
</div>

<script src="/admin/assets/js/admin-categories.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>