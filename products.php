<?php
// --- LOGIC CỦA TRANG ---
// --- LOGIC MỚI: XỬ LÝ CẢ DANH MỤC VÀ BỘ SƯU TẬP ---
$category_slug = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_SPECIAL_CHARS);
$collection_slug = filter_input(INPUT_GET, 'collection_slug', FILTER_SANITIZE_SPECIAL_CHARS);

// Tạm thời, tiêu đề là chung. Sau này sẽ làm động theo danh mục.
$page_title = "Sản phẩm - Khóm Bedding";
$page_description = "Khám phá tất cả sản phẩm chăn ga gối đệm chất lượng cao của Khóm Bedding.";
$filter_type = '';
$filter_slug = '';
$current_name = '';

include 'templates/header.php';

if ($category_slug) {
    // Lấy tên danh mục để hiển thị
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE slug = ?");
    $stmt->execute([$category_slug]);
    $current_name = $stmt->fetchColumn();
    $page_title = $current_name ?: "Sản phẩm";
    $filter_type = 'category';
    $filter_slug = $category_slug;
} elseif ($collection_slug) {
    // Lấy tên bộ sưu tập để hiển thị
    $stmt = $pdo->prepare("SELECT name FROM collections WHERE slug = ?");
    $stmt->execute([$collection_slug]);
    $current_name = $stmt->fetchColumn();
    $page_title = "Bộ sưu tập: " . ($current_name ?: "");
    $filter_type = 'collection';
    $filter_slug = $collection_slug;
}
// --- TRUY VẤN DỮ LIỆU BAN ĐẦU (Sẽ làm ở các bước sau) ---
// Lấy tất cả thuộc tính và giá trị để hiển thị bộ lọc
try {
    // Sửa câu truy vấn: Đưa a.name lên đầu để PDO::FETCH_GROUP hoạt động đúng
    $stmt = $pdo->query("
        SELECT a.name as attribute_name, 
               a.id as attribute_id, 
               av.id as value_id, 
               av.value 
        FROM attributes a
        JOIN attribute_values av ON a.id = av.attribute_id
        ORDER BY a.name, av.value
    ");
    // Bây giờ, $all_attribute_values sẽ được nhóm theo tên thuộc tính ('Kích thước', 'Màu sắc')
    $all_attribute_values = $stmt->fetchAll(PDO::FETCH_GROUP);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $all_attribute_values = [];
}

?>

<div class="container py-5">
   <div class="row">
      <aside class="col-lg-3 d-none d-lg-block">
         <form id="filter-form">
            <div class="accordion">
               <div class="accordion-item">
                  <h2 class="accordion-header">
                     <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse">
                        Bộ Lọc
                     </button>
                  </h2>
                  <div class="accordion-body">
                     <div class="form-check">
                        <input class="form-check-input" type="radio" name="price_range" id="price_all" value="" checked>
                        <label class="form-check-label" for="price_all">Tất cả giá</label>
                     </div>
                     <div class="form-check">
                        <input class="form-check-input" type="radio" name="price_range" id="price_1" value="0-1000000">
                        <label class="form-check-label" for="price_1">Dưới 1tr</label>
                     </div>
                     <div class="form-check">
                        <input class="form-check-input" type="radio" name="price_range" id="price_2"
                           value="1000000-3000000">
                        <label class="form-check-label" for="price_2">1tr - 3tr</label>
                     </div>
                     <div class="form-check">
                        <input class="form-check-input" type="radio" name="price_range" id="price_3"
                           value="3000000-Infinity">
                        <label class="form-check-label" for="price_3">Trên 3tr</label>
                     </div>
                  </div>
               </div>
            </div>

            <div class="accordion" id="filter-accordion">
               <?php foreach ($all_attribute_values as $attribute_name => $values): ?>
               <div class="accordion-item">
                  <h2 class="accordion-header">
                     <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse-<?php echo htmlspecialchars(str_replace(' ', '-', $attribute_name)); ?>">
                        <?php echo htmlspecialchars($attribute_name); ?>
                     </button>
                  </h2>
                  <div id="collapse-<?php echo htmlspecialchars(str_replace(' ', '-', $attribute_name)); ?>"
                     class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <?php foreach($values as $value): ?>
                        <div class="form-check">
                           <input class="form-check-input" name="attributes[]" type="checkbox"
                              value="<?php echo $value['value_id']; ?>" id="attr_<?php echo $value['value_id']; ?>">
                           <label class="form-check-label" for="attr_<?php echo $value['value_id']; ?>">
                              <?php echo htmlspecialchars($value['value']); ?>
                           </label>
                        </div>
                        <?php endforeach; ?>
                     </div>
                  </div>
               </div>
               <?php endforeach; ?>
            </div>

         </form>
      </aside>




      <main class="col-lg-9" data-filter-type="<?php echo $filter_type; ?>"
         data-filter-slug="<?php echo $filter_slug; ?>">
         <div id="products-main-header" class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3" id="page-dynamic-title"><?php echo htmlspecialchars($page_title); ?></h1>
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas"
               data-bs-target="#offcanvasFilters" aria-controls="offcanvasFilters">
               <i class="bi bi-funnel-fill"></i> Lọc
            </button>
            <div class="d-none d-lg-block">
               <select class="form-select" id="sort-by">
                  <option value="default" selected>Mặc định</option>
                  <option value="price_asc">Giá: Tăng dần</option>
                  <option value="price_desc">Giá: Giảm dần</option>
                  <option value="name_asc">Tên: A-Z</option>
                  <option value="name_desc">Tên: Z-A</option>
               </select>
            </div>
         </div>

         <div id="product-list-container" class="row">
            <div class="col-12 text-center">
               <div class="spinner-border" role="status">
                  <span class="visually-hidden">Loading...</span>
               </div>
            </div>
         </div>

         <nav id="pagination-container" class="d-flex justify-content-center mt-5">
         </nav>
      </main>
   </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasFilters" aria-labelledby="offcanvasFiltersLabel">
   <div class="offcanvas-header">
      <h5 id="offcanvasFiltersLabel"><i class="bi bi-funnel-fill"></i> Bộ Lọc</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
   </div>
   <div class="offcanvas-body">
   </div>
</div>

<?php
// Tạm thời chưa cần dùng hàm render card vì JS sẽ nhận HTML từ API
include 'templates/footer.php';
?>