<?php
// Tạm thời include header trước để lấy $pdo
include 'templates/header.php';

// --- PHẦN LOGIC CỦA TRANG ---

// 1. Lấy slug sản phẩm từ URL
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    // Nếu không có slug, chuyển hướng về trang chủ hoặc trang lỗi 404
    header("Location: /");
    exit();
}

// 2. Truy vấn CSDL để lấy thông tin sản phẩm
// Đây là một câu truy vấn phức tạp để lấy gần như mọi thứ chúng ta cần
$stmt = $pdo->prepare("
    SELECT 
        p.id, p.name, p.slug, p.description, p.short_description,
        c.name as category_name, c.slug as category_slug,
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
        (SELECT COUNT(id) FROM reviews WHERE product_id = p.id) as review_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ?
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

// 3. Xử lý trường hợp không tìm thấy sản phẩm
if (!$product) {
    // Hiển thị trang lỗi 404
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>Sản phẩm không tồn tại.</p>";
    include 'templates/footer.php';
    exit();
}

// Lấy tất cả ảnh của sản phẩm cho gallery
$stmt_images = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_featured DESC");
$stmt_images->execute([$product['id']]);
$product_images = $stmt_images->fetchAll(PDO::FETCH_COLUMN);


// 4. Thiết lập các biến SEO động
$page_title = htmlspecialchars($product['name']) . ' - Khóm Bedding';
$page_description = htmlspecialchars($product['short_description']);

// 5. TẠO DỮ LIỆU CÓ CẤU TRÚC (STRUCTURED DATA - SCHEMA.ORG) CHO SẢN PHẨM
// Rất quan trọng cho SEO, giúp Google hiểu rõ hơn về sản phẩm của bạn
// Tạm thời lấy thông tin từ phiên bản mặc định
$stmt_default_variant = $pdo->prepare("SELECT sku, price, original_price, stock_quantity FROM product_variants WHERE product_id = ? AND is_default = 1");
$stmt_default_variant->execute([$product['id']]);
$default_variant = $stmt_default_variant->fetch();

$schema = [
    "@context" => "https://schema.org/",
    "@type" => "Product",
    "name" => $product['name'],
    "image" => count($product_images) > 0 ? $product_images : [],
    "description" => $product['short_description'],
    "sku" => $default_variant['sku'] ?? $product['id'],
    "mpn" => $product['id'], // Manufacturer Part Number (có thể dùng product ID)
    "brand" => [
        "@type" => "Brand",
        "name" => "Khóm Bedding"
    ],
    "offers" => [
        "@type" => "Offer",
        "url" => "http://localhost/san-pham/". $product['slug'] .".html",
        "priceCurrency" => "VND",
        "price" => $default_variant['price'] ?? "0",
        "availability" => ($default_variant['stock_quantity'] > 0) ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
        "itemCondition" => "https://schema.org/NewCondition"
    ]
];
if ($product['review_count'] > 0) {
    $schema['aggregateRating'] = [
        "@type" => "AggregateRating",
        "ratingValue" => number_format($product['avg_rating'], 1),
        "reviewCount" => $product['review_count']
    ];
}


// --- PHẦN GIAO DIỆN ---
?>
<script type="application/ld+json">
<?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<div class="container py-5">
   <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="/">Trang Chủ</a></li>
         <li class="breadcrumb-item"><a
               href="/category/<?php echo htmlspecialchars($product['category_slug']); ?>.html"><?php echo htmlspecialchars($product['category_name']); ?></a>
         </li>
         <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
      </ol>
   </nav>

   <div class="row">
      <div class="col-lg-6">
         <div class="product-gallery">
            <div class="main-image-container mb-3">
               <img src="<?php echo htmlspecialchars($product_images[0] ?? '/assets/images/placeholder.png'); ?>"
                  alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid w-100"
                  id="main-product-image">
            </div>
            <div class="thumbnail-container">
               <?php foreach($product_images as $index => $image_url): ?>
               <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Thumbnail <?php echo $index + 1; ?>"
                  class="thumbnail-image <?php echo $index == 0 ? 'active' : ''; ?>">
               <?php endforeach; ?>
            </div>
         </div>
      </div>

      <div class="col-lg-6">
         <div class="product-details">
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

            <div class="product-meta d-flex align-items-center gap-3 text-muted mb-3">
               <div class="product-rating">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span><?php echo number_format($product['avg_rating'], 1); ?> (<?php echo $product['review_count']; ?>
                     đánh giá)</span>
               </div>
               <div>|</div>
               <div>Đã bán: 123+</div>
               <div>|</div>
               <div id="product-sku">SKU: <?php echo htmlspecialchars($default_variant['sku'] ?? 'N/A'); ?></div>
            </div>

            <div class="product-price h2 mb-3">
               <span class="price-sale"
                  id="product-price"><?php echo number_format($default_variant['price'] ?? 0, 0, ',', '.'); ?>đ</span>
               <span class="price-original"
                  id="product-original-price"><?php echo $default_variant['original_price'] ? number_format($default_variant['original_price'], 0, ',', '.') . 'đ' : ''; ?></span>
            </div>

            <p class="product-short-description"><?php echo htmlspecialchars($product['short_description']); ?></p>

            <div class="variant-selection my-4">
               <div class="variant-group">
                  <label class="form-label fw-bold">Kích thước:</label>
                  <div class="d-flex gap-2" id="variant-group-size">
                     <button class="btn btn-outline-secondary">1m6 x 2m</button>
                     <button class="btn btn-outline-secondary active">1m8 x 2m</button>
                     <button class="btn btn-outline-secondary">2m x 2m2</button>
                  </div>
               </div>
            </div>

            <div class="d-flex align-items-center gap-3 my-4">
               <div class="quantity-input">
                  <button class="btn btn-outline-secondary" type="button">-</button>
                  <input type="number" class="form-control text-center" value="1" min="1">
                  <button class="btn btn-outline-secondary" type="button">+</button>
               </div>
               <button class="btn btn-primary btn-lg flex-grow-1"
                  style="background-color: var(--color-accent); border-color: var(--color-accent);">Thêm vào giỏ
                  hàng</button>
            </div>
         </div>
      </div>
   </div>

   <div class="row mt-5">
      <div class="col-12">
         <ul class="nav nav-tabs" id="productInfoTabs" role="tablist">
            <li class="nav-item" role="presentation">
               <button class="nav-link active" id="description-tab" data-bs-toggle="tab"
                  data-bs-target="#description-pane" type="button" role="tab">Mô Tả Chi Tiết</button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-pane"
                  type="button" role="tab">Đánh Giá (<?php echo $product['review_count']; ?>)</button>
            </li>
         </ul>
         <div class="tab-content pt-4" id="productInfoTabsContent">
            <div class="tab-pane fade show active" id="description-pane" role="tabpanel">
               <?php echo $product['description']; // Dữ liệu từ CSDL, giả sử là an toàn ?>
            </div>
            <div class="tab-pane fade" id="reviews-pane" role="tabpanel">
               <p>Khu vực hiển thị các đánh giá và form gửi đánh giá sẽ được xây dựng ở bước tiếp theo.</p>
            </div>
         </div>
      </div>
   </div>
</div>

<?php 
include 'templates/footer.php'; 
?>