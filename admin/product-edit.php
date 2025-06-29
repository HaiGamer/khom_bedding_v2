<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

// --- LOGIC LẤY DỮ LIỆU ---
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = ['id' => null, 'name' => '', 'slug' => '', 'category_id' => null, 'short_description' => '', 'description' => ''];
$product_images = [];
$product_variants = [];
$page_title_action = "Thêm sản phẩm mới";

$stmt_all_attrs = $pdo->query("SELECT id, name FROM attributes ORDER BY name");
$all_attributes = $stmt_all_attrs->fetchAll(PDO::FETCH_ASSOC);
foreach ($all_attributes as $key => $attr) {
    $stmt_values = $pdo->prepare("SELECT id, value FROM attribute_values WHERE attribute_id = ? ORDER BY value");
    $stmt_values->execute([$attr['id']]);
    $all_attributes[$key]['values'] = $stmt_values->fetchAll(PDO::FETCH_ASSOC);
}
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

if ($product_id) {
    $page_title_action = "Chỉnh sửa sản phẩm";
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) { exit('Sản phẩm không tồn tại.'); }

    $stmt_images = $pdo->prepare("SELECT id, image_url, is_featured FROM product_images WHERE product_id = ? ORDER BY is_featured DESC, id ASC");
    $stmt_images->execute([$product_id]);
    $product_images = $stmt_images->fetchAll();

    $stmt_variants_raw = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
    $stmt_variants_raw->execute([$product_id]);
    $product_variants_raw = $stmt_variants_raw->fetchAll(PDO::FETCH_ASSOC);

    foreach ($product_variants_raw as $variant) {
        $stmt_variant_attrs = $pdo->prepare("SELECT v.attribute_id, v.id FROM variant_values vv JOIN attribute_values v ON vv.attribute_value_id = v.id WHERE vv.variant_id = ?");
        $stmt_variant_attrs->execute([$variant['id']]);
        $variant['attributes'] = $stmt_variant_attrs->fetchAll(PDO::FETCH_KEY_PAIR);
        $product_variants[] = $variant;
    }
}

$page_title = $page_title_action;
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>
<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0"><?php echo $page_title_action; ?></h1>
      <a href="/admin/products.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
   </div>
   <form id="product-form">
      <div id="form-alert" class="alert d-none"></div>
      <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id'] ?? ''); ?>">
      <div class="row">
         <div class="col-lg-8">
            <div class="card mb-4">
               <div class="card-header"><strong>Thông tin cơ bản</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label for="product-name" class="form-label">Tên sản phẩm /</label>
                     <input type="text" class="form-control" id="product-name" name="name"
                        value="<?php echo htmlspecialchars($product['name']); ?>" required>
                  </div>
                  <input type="hidden" id="product-slug" name="slug"
                     value="<?php echo htmlspecialchars($product['slug']); ?>">
                  <div class="mb-3">
                     <label for="product-description" class="form-label">Mô tả chi tiết</label>
                     <textarea class="form-control" id="product-description" name="description"
                        rows="10"><?php echo htmlspecialchars($product['description']); ?></textarea>
                  </div>
                  <div class="mb-3">
                     <label for="product-short-description" class="form-label">Mô tả ngắn</label>
                     <textarea class="form-control" id="product-short-description" name="short_description"
                        rows="3"><?php echo htmlspecialchars($product['short_description']); ?></textarea>
                  </div>
               </div>
            </div>
            <div class="card mb-4">
               <div class="card-header"><strong>Phân loại</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label for="product-category" class="form-label">Danh mục sản phẩm</label>
                     <select class="form-select" id="product-category" name="category_id">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                           <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                           <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                     </select>
                  </div>
               </div>
            </div>
            <?php if ($product_id): ?>
            <div class="card mb-4">
               <div class="card-header"><strong>Thư viện ảnh</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label for="image-upload-input" class="form-label">Tải lên ảnh mới</label>
                     <input class="form-control" type="file" id="image-upload-input" multiple accept="image/*">
                  </div>
                  <div id="image-gallery-container" class="d-flex flex-wrap gap-3">
                     <?php foreach ($product_images as $image): ?>
                     <div class="admin-image-thumbnail <?php echo $image['is_featured'] ? 'featured' : ''; ?>"
                        data-image-id="<?php echo $image['id']; ?>">
                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Product Image">
                        <div class="thumbnail-actions">
                           <button type="button" class="btn btn-sm btn-light btn-set-featured"
                              title="Đặt làm ảnh đại diện"><i
                                 class="bi <?php echo $image['is_featured'] ? 'bi-star-fill text-warning' : 'bi-star'; ?>"></i></button>
                           <button type="button" class="btn btn-sm btn-danger btn-delete-image" title="Xóa ảnh"><i
                                 class="bi bi-trash-fill"></i></button>
                        </div>
                     </div>
                     <?php endforeach; ?>
                  </div>
               </div>
            </div>
            <?php endif; ?>
         </div>
         <div class="col-lg-4">
            <div class="card">
               <div class="card-header"><strong>Các phiên bản sản phẩm</strong></div>
               <div class="card-body">
                  <div id="variants-container">
                     <?php foreach ($product_variants as $index => $variant): ?>
                     <div class="variant-item p-3 border rounded mb-3">
                        <input type="hidden" name="variants[<?php echo $index; ?>][id]"
                           value="<?php echo $variant['id']; ?>">
                        <button type="button" class="btn-close float-end btn-remove-variant"
                           aria-label="Close"></button>
                        <div class="form-check mb-3">
                           <input class="form-check-input" type="radio" name="default_variant_index"
                              id="default_variant_<?php echo $index; ?>" value="<?php echo $index; ?>"
                              <?php echo $variant['is_default'] ? 'checked' : ''; ?>>
                           <label class="form-check-label fw-bold" for="default_variant_<?php echo $index; ?>">
                              Đặt làm phiên bản mặc định
                           </label>
                        </div>
                        <div class="row">
                           <div class="col-md-6 mb-3"><label class="form-label">SKU</label><input type="text"
                                 class="form-control" name="variants[<?php echo $index; ?>][sku]"
                                 value="<?php echo htmlspecialchars($variant['sku']); ?>"></div>
                           <div class="col-md-6 mb-3"><label class="form-label">Số lượng tồn kho</label><input
                                 type="number" class="form-control"
                                 name="variants[<?php echo $index; ?>][stock_quantity]"
                                 value="<?php echo htmlspecialchars($variant['stock_quantity']); ?>"></div>
                        </div>
                        <div class="row">
                           <div class="col-md-6 mb-3"><label class="form-label">Giá bán</label><input type="number"
                                 step="1000" class="form-control" name="variants[<?php echo $index; ?>][price]"
                                 value="<?php echo htmlspecialchars($variant['price']); ?>"></div>
                           <div class="col-md-6 mb-3"><label class="form-label">Giá gốc</label><input type="number"
                                 step="1000" class="form-control" name="variants[<?php echo $index; ?>][original_price]"
                                 value="<?php echo htmlspecialchars($variant['original_price']); ?>"></div>
                        </div>
                        <div class="row">
                           <?php foreach ($all_attributes as $attribute): ?>
                           <div class="col-md-6 mb-3">
                              <label class="form-label"><?php echo htmlspecialchars($attribute['name']); ?></label>
                              <select class="form-select"
                                 name="variants[<?php echo $index; ?>][attributes][<?php echo $attribute['id']; ?>]">
                                 <option value="">-- Chọn
                                    <?php echo htmlspecialchars(strtolower($attribute['name'])); ?> --</option>
                                 <?php foreach ($attribute['values'] as $value): ?>
                                 <option value="<?php echo $value['id']; ?>"
                                    <?php echo (($variant['attributes'][$attribute['id']] ?? null) == $value['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($value['value']); ?>
                                 </option>
                                 <?php endforeach; ?>
                              </select>
                           </div>
                           <?php endforeach; ?>
                        </div>
                     </div>
                     <?php endforeach; ?>
                  </div>
                  <button type="button" id="btn-add-variant" class="btn btn-outline-primary mt-2"><i
                        class="bi bi-plus-circle"></i> Thêm phiên bản</button>
               </div>
            </div>
         </div>
      </div>
      <div class="mt-4">
         <button type="submit" class="btn btn-success btn-lg">Lưu sản phẩm</button>
      </div>
   </form>
</div>

<template id="variant-template">
   <div class="variant-item p-3 border rounded mb-3">
      <input type="hidden" name="variants[__INDEX__][id]" value="">
      <button type="button" class="btn-close float-end btn-remove-variant" aria-label="Close"></button>
      <div class="form-check mb-3">
         <input class="form-check-input" type="radio" name="default_variant_index" id="default_variant___INDEX__"
            value="__INDEX__">
         <label class="form-check-label fw-bold" for="default_variant___INDEX__">
            Đặt làm phiên bản mặc định
         </label>
      </div>
      <div class="row">
         <div class="col-md-6 mb-3"><label class="form-label">SKU</label><input type="text" class="form-control"
               name="variants[__INDEX__][sku]"></div>
         <div class="col-md-6 mb-3"><label class="form-label">Số lượng tồn kho</label><input type="number"
               class="form-control" name="variants[__INDEX__][stock_quantity]" value="0"></div>
      </div>
      <div class="row">
         <div class="col-md-6 mb-3"><label class="form-label">Giá bán</label><input type="number" step="1000"
               class="form-control" name="variants[__INDEX__][price]"></div>
         <div class="col-md-6 mb-3"><label class="form-label">Giá gốc</label><input type="number" step="1000"
               class="form-control" name="variants[__INDEX__][original_price]"></div>
      </div>
      <div class="row">
         <?php foreach ($all_attributes as $attribute): ?>
         <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo htmlspecialchars($attribute['name']); ?></label>
            <select class="form-select" name="variants[__INDEX__][attributes][<?php echo $attribute['id']; ?>]">
               <option value="">-- Chọn <?php echo htmlspecialchars(strtolower($attribute['name'])); ?> --</option>
               <?php foreach ($attribute['values'] as $value): ?>
               <option value="<?php echo $value['id']; ?>"><?php echo htmlspecialchars($value['value']); ?></option>
               <?php endforeach; ?>
            </select>
         </div>
         <?php endforeach; ?>
      </div>
   </div>
</template>

<script src="/admin/assets/js/product-form.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>