<?php
// Include header để có $pdo và các session
include 'templates/header.php';

// Lấy từ khóa tìm kiếm từ URL
$search_term = trim(filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

// Nếu không có từ khóa, có thể chuyển hướng về trang chủ hoặc hiển thị thông báo
if (empty($search_term)) {
    // Để đơn giản, chúng ta sẽ hiển thị trang với thông báo, không chuyển hướng
    $page_title = "Tìm kiếm";
} else {
    $page_title = "Kết quả tìm kiếm cho: '" . htmlspecialchars($search_term) . "'";
}

$page_description = "Tìm kiếm sản phẩm chăn ga gối đệm tại Khóm Bedding.";

// Lấy tất cả thuộc tính để hiển thị bộ lọc (giống hệt products.php)
try {
    $stmt_attrs = $pdo->query("SELECT a.name as attribute_name, a.id as attribute_id, av.id as value_id, av.value FROM attributes a JOIN attribute_values av ON a.id = av.attribute_id ORDER BY a.name, av.value");
    $all_attribute_values = $stmt_attrs->fetchAll(PDO::FETCH_GROUP);
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

      <main class="col-lg-9" data-filter-type="search" data-search-term="<?php echo htmlspecialchars($search_term); ?>">
         <div id="products-main-header" class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3" id="page-dynamic-title"><?php echo $page_title; ?></h1>
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

         <nav id="pagination-container" class="d-flex justify-content-center mt-5"></nav>
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

<?php include 'templates/footer.php'; ?>