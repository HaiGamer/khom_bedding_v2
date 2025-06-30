<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Quản lý Banner Trang chủ";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

$banners = $pdo->query("SELECT * FROM banners ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-4">
   <h1 class="mb-4">Quản lý Banner Trang chủ</h1>
   <div class="row">
      <div class="col-lg-5">
         <div class="card">
            <div class="card-header">
               <h5 class="card-title mb-0" id="form-title">Thêm banner mới</h5>
            </div>
            <div class="card-body">
               <form id="banner-form" enctype="multipart/form-data">
                  <input type="hidden" name="banner_id" id="banner_id">
                  <div class="mb-3">
                     <label for="banner-title" class="form-label">Tiêu đề (để quản lý nội bộ)</label>
                     <input type="text" class="form-control" id="banner-title" name="title">
                  </div>
                  <div class="mb-3">
                     <label for="banner-link" class="form-label">Link đích (URL)</label>
                     <input type="text" class="form-control" id="banner-link" name="link_url"
                        placeholder="VD: /san-pham/ten-san-pham.html">
                  </div>
                  <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="banner-image-desktop" class="form-label">Ảnh cho máy tính</label>
                        <input class="form-control" type="file" id="banner-image-desktop" name="image_url_desktop"
                           accept="image/*">
                     </div>
                     <div class="col-md-6 mb-3">
                        <label for="banner-image-mobile" class="form-label">Ảnh cho di động (nếu có)</label>
                        <input class="form-control" type="file" id="banner-image-mobile" name="image_url_mobile"
                           accept="image/*">
                     </div>
                  </div>
                  <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="display-order" class="form-label">Thứ tự hiển thị</label>
                        <input type="number" class="form-control" id="display-order" name="display_order" value="0">
                     </div>
                     <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                           <input class="form-check-input" type="checkbox" role="switch" id="is-active" name="is_active"
                              value="1" checked>
                           <label class="form-check-label" for="is-active">Hiển thị banner</label>
                        </div>
                     </div>
                  </div>
                  <div class="d-flex justify-content-end">
                     <button type="button" id="btn-cancel-edit" class="btn btn-secondary me-2 d-none">Hủy</button>
                     <button type="submit" class="btn btn-primary">Lưu Banner</button>
                  </div>
               </form>
            </div>
         </div>
      </div>

      <div class="col-lg-7">
         <div class="card">
            <div class="card-body table-responsive">
               <table class="table table-hover align-middle">
                  <thead>
                     <tr>
                        <th>Thứ tự</th>
                        <th>Ảnh</th>
                        <th>Tiêu đề</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                     </tr>
                  </thead>
                  <tbody id="banners-table-body">
                     <?php foreach ($banners as $banner): ?>
                     <tr data-banner='<?php echo json_encode($banner); ?>'>
                        <td><?= $banner['display_order'] ?></td>
                        <td><img src="<?= htmlspecialchars($banner['image_url_desktop']) ?>" style="width: 150px;"
                              class="img-thumbnail"></td>
                        <td><?= htmlspecialchars($banner['title']) ?></td>
                        <td>
                           <span class="badge <?= $banner['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                              <?= $banner['is_active'] ? 'Đang hiện' : 'Đang ẩn' ?>
                           </span>
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

<script src="/admin/assets/js/admin-banners.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>