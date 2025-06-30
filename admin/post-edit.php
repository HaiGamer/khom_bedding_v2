<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$post = ['id' => null, 'title' => '', 'slug' => '', 'content' => '', 'excerpt' => '', 'featured_image' => null, 'status' => 'published'];
$page_title_action = "Thêm bài viết mới";

if ($post_id) {
    $page_title_action = "Chỉnh sửa bài viết";
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) { exit('Bài viết không tồn tại.'); }
}

$page_title = $page_title_action;
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>
<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0"><?php echo $page_title_action; ?></h1>
      <a href="/admin/posts.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
   </div>

   <?php if (isset($_SESSION['flash_message'])): ?>
   <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
      <?php echo $_SESSION['flash_message']['message']; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
   </div>
   <?php unset($_SESSION['flash_message']); ?>
   <?php endif; ?>

   <form action="/admin/api/posts/save.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
      <div class="row">
         <div class="col-lg-9">
            <div class="card">
               <div class="card-body">
                  <div class="mb-3">
                     <label for="post-title" class="form-label">Tiêu đề bài viết</label>
                     <input type="text" class="form-control" id="post-title" name="title"
                        value="<?= htmlspecialchars($post['title']) ?>" required>
                  </div>
                  <input type="hidden" id="post-slug" name="slug" value="<?= htmlspecialchars($post['slug']) ?>">
                  <div class="mb-3">
                     <label for="post-content" class="form-label">Nội dung</label>
                     <textarea class="form-control" id="post-content" name="content"
                        rows="15"><?= htmlspecialchars($post['content']) ?></textarea>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-lg-3">
            <div class="card mb-4">
               <div class="card-header"><strong>Xuất bản</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label for="post-status" class="form-label">Trạng thái</label>
                     <select name="status" id="post-status" class="form-select">
                        <option value="published" <?= $post['status'] == 'published' ? 'selected' : '' ?>>Đã xuất bản
                        </option>
                        <option value="draft" <?= $post['status'] == 'draft' ? 'selected' : '' ?>>Bản nháp</option>
                     </select>
                  </div>
                  <div class="d-grid">
                     <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                  </div>
               </div>
            </div>
            <div class="card mb-4">
               <div class="card-header"><strong>Ảnh đại diện</strong></div>
               <div class="card-body">
                  <input class="form-control" type="file" id="featured-image" name="featured_image" accept="image/*">
                  <?php if ($post['featured_image']): ?>
                  <img src="<?= htmlspecialchars($post['featured_image']) ?>" class="img-fluid rounded mt-3"
                     alt="Ảnh đại diện hiện tại">
                  <?php endif; ?>
               </div>
            </div>
            <div class="card">
               <div class="card-header"><strong>Tóm tắt</strong></div>
               <div class="card-body">
                  <textarea class="form-control" name="excerpt"
                     rows="4"><?= htmlspecialchars($post['excerpt']) ?></textarea>
               </div>
            </div>
         </div>
      </div>
   </form>
</div>

<script src="/admin/assets/js/admin-post-form.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>