<?php
// --- PHẦN LOGIC CỦA TRANG ---
// Định nghĩa các biến SEO cho trang chủ
$page_title = "Khóm Bedding - Chăn Ga Gối Nệm";
$page_description = "Nâng tầm phòng ngủ, giá yêu thương cho mọi nhà. Mua sắm chăn ga gối đệm online chất lượng cao, mẫu mã đa dạng.";

// Include header.php ở đầu để có $pdo
include 'templates/header.php'; 

// --- TRUY VẤN DỮ LIỆU ---

// 1. Lấy 3 danh mục đầu tiên để làm tab "Sản phẩm nổi bật"
$stmt_cats_featured = $pdo->query("SELECT id, name, slug FROM categories LIMIT 3");
$featured_categories = $stmt_cats_featured->fetchAll();

// 2. Lấy 4 sản phẩm mới nhất
$stmt_new_products = $pdo->query("
    SELECT p.id, p.name, p.slug, pv.price, pv.original_price, pi.image_url
    FROM products p
    JOIN product_variants pv ON p.id = pv.product_id AND pv.is_default = 1
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_featured = 1
    ORDER BY p.created_at DESC
    LIMIT 4
");
$new_products = $stmt_new_products->fetchAll();

// 3. Lấy 4 sản phẩm bán chạy (TẠM THỜI lấy ngẫu nhiên)
// Sau này khi có dữ liệu đơn hàng, chúng ta sẽ thay bằng logic thật
$stmt_best_sellers = $pdo->query("
    SELECT p.id, p.name, p.slug, pv.price, pv.original_price, pi.image_url
    FROM products p
    JOIN product_variants pv ON p.id = pv.product_id AND pv.is_default = 1
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_featured = 1
    ORDER BY RAND() 
    LIMIT 4
");
$best_selling_products = $stmt_best_sellers->fetchAll();

// 4. LẤY 3 BỘ SƯU TẬP MỚI NHẤT CÓ HÌNH ẢNH
$stmt_collections = $pdo->query("
    SELECT name, slug, image_url 
    FROM collections 
    WHERE image_url IS NOT NULL AND image_url != '' 
    ORDER BY id DESC 
    LIMIT 3
");
$homepage_collections = $stmt_collections->fetchAll(PDO::FETCH_ASSOC);



// --- HÀM TÁI SỬ DỤNG ---
// Hàm để hiển thị một thẻ sản phẩm
function render_product_card($product) {
    // Định dạng giá tiền
    $price_formatted = number_format($product['price'], 0, ',', '.') . 'đ';
    $original_price_formatted = $product['original_price'] ? number_format($product['original_price'], 0, ',', '.') . 'đ' : '';
    
    // Tính phần trăm giảm giá
    $sale_percentage = 0;
    if ($product['original_price'] && $product['price'] < $product['original_price']) {
        $sale_percentage = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
    }

    echo '<div class="col-6 col-lg-3 mb-4">';
    echo '  <div class="product-card">';
    echo '      <div class="product-card-img">';
    echo '          <a href="/san-pham/'. htmlspecialchars($product['slug']) .'.html">';
    echo '              <img src="'. htmlspecialchars($product['image_url'] ? $product['image_url'] : '/assets/images/placeholder.png') .'" alt="'. htmlspecialchars($product['name']) .'">';
    echo '          </a>';
    if ($sale_percentage > 0) {
        echo '          <span class="product-card-sale">-' . $sale_percentage . '%</span>';
    }
    echo '          <div class="product-card-actions">';
    echo '              <a href="#" class="btn-action btn-add-to-cart" data-slug="'. htmlspecialchars($product['slug']) .'" title="Thêm vào giỏ hàng"><i class="bi bi-cart-plus"></i></a>';
    echo '              <a href="/san-pham/'. htmlspecialchars($product['slug']) .'.html" class="btn-action" title="Xem chi tiết"><i class="bi bi-eye"></i></a>';
    echo '          </div>';
    echo '      </div>';
    echo '      <div class="product-card-body">';
    echo '          <h3 class="product-card-title"><a href="/san-pham/'. htmlspecialchars($product['slug']) .'.html">'. htmlspecialchars($product['name']) .'</a></h3>';
    echo '          <div class="product-card-price">';
    echo '              <span class="price-sale">'. $price_formatted .'</span>';
    if ($original_price_formatted) {
        echo '          <span class="price-original">'. $original_price_formatted .'</span>';
    }
    echo '          </div>';
    echo '      </div>';
    echo '  </div>';
    echo '</div>';
}

// --- PHẦN GIAO DIỆN ---
?>

<section class="hero-banner" style="background-color: #F4F1EA; padding: 6rem 0;">
   <div class="container text-center">
      <h1>Nâng tầm phòng ngủ</h1>
      <p class="lead">Giá yêu thương cho mọi nhà.</p>
      <a href="/products.html" class="btn btn-primary btn-lg"
         style="background-color: #D4A373; border-color: #D4A373;">Khám Phá Ngay</a>
   </div>
</section>

<section class="featured-products py-5">
   <div class="container">
      <div class="section-title text-center mb-4">
         <h2>Sản Phẩm Nổi Bật</h2>
         <p>Khám phá những sản phẩm được yêu thích nhất</p>
      </div>

      <ul class="nav nav-tabs justify-content-center" id="featuredTabs" role="tablist">
         <?php foreach ($featured_categories as $index => $cat): ?>
         <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $index == 0 ? 'active' : ''; ?>"
               id="tab-<?php echo htmlspecialchars($cat['slug']); ?>" data-bs-toggle="tab"
               data-bs-target="#pane-<?php echo htmlspecialchars($cat['slug']); ?>" type="button"
               role="tab"><?php echo htmlspecialchars($cat['name']); ?></button>
         </li>
         <?php endforeach; ?>
      </ul>

      <div class="tab-content" id="featuredTabsContent">
         <?php foreach ($featured_categories as $index => $cat): 
                // Lấy 4 sản phẩm cho mỗi danh mục
                $stmt_products_by_cat = $pdo->prepare("
                    SELECT p.id, p.name, p.slug, pv.price, pv.original_price, pi.image_url
                    FROM products p
                    JOIN product_variants pv ON p.id = pv.product_id AND pv.is_default = 1
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_featured = 1
                    WHERE p.category_id = ?
                    LIMIT 4
                ");
                $stmt_products_by_cat->execute([$cat['id']]);
                $products_in_cat = $stmt_products_by_cat->fetchAll();
            ?>
         <div class="tab-pane fade <?php echo $index == 0 ? 'show active' : ''; ?>"
            id="pane-<?php echo htmlspecialchars($cat['slug']); ?>" role="tabpanel">
            <div class="row mt-4">
               <?php 
                    if (count($products_in_cat) > 0) {
                        foreach ($products_in_cat as $product) {
                            render_product_card($product);
                        }
                    } else {
                        echo '<p class="text-center">Chưa có sản phẩm nào trong danh mục này.</p>';
                    }
                    ?>
            </div>
            <div class="text-center mt-2">
               <a href="/category/<?php echo htmlspecialchars($cat['slug']); ?>.html"
                  class="btn btn-outline-primary btn-view-more">Xem Thêm</a>
            </div>
         </div>
         <?php endforeach; ?>
      </div>
   </div>
</section>
<?php if (!empty($homepage_collections)): ?>
<section class="homepage-collections">
   <div class="container">
      <div class="section-title text-center mb-4">
      </div>
      <div class="row">
         <?php foreach($homepage_collections as $collection): ?>
         <div class="col-md-4 mb-4">
            <a href="/collection/<?php echo htmlspecialchars($collection['slug']); ?>.html" class="collection-card">
               <img src="<?php echo htmlspecialchars($collection['image_url']); ?>"
                  alt="<?php echo htmlspecialchars($collection['name']); ?>" class="collection-card-img">
               <div class="collection-card-overlay">
                  <h3 class="collection-card-title"><?php echo htmlspecialchars($collection['name']); ?></h3>
               </div>
            </a>
         </div>
         <?php endforeach; ?>
      </div>
   </div>
</section>
<?php endif; ?>
<section class="new-bestsellers-section py-5 bg-light">
   <div class="container">
      <ul class="nav nav-tabs justify-content-center" id="newBestsellersTabs" role="tablist">
         <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-new" data-bs-toggle="tab" data-bs-target="#pane-new" type="button"
               role="tab">Hàng Mới Về</button>
         </li>
         <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-bestsellers" data-bs-toggle="tab" data-bs-target="#pane-bestsellers"
               type="button" role="tab">Bán Chạy Nhất</button>
         </li>
      </ul>

      <div class="tab-content" id="newBestsellersTabsContent">
         <div class="tab-pane fade show active" id="pane-new" role="tabpanel">
            <div class="row mt-4">
               <?php foreach ($new_products as $product) { render_product_card($product); } ?>
            </div>
         </div>
         <div class="tab-pane fade" id="pane-bestsellers" role="tabpanel">
            <div class="row mt-4">
               <?php foreach ($best_selling_products as $product) { render_product_card($product); } ?>
            </div>
         </div>
      </div>
   </div>
</section>

<?php 
include 'templates/footer.php'; 
?>