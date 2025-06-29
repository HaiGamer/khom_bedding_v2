<?php
// Tạm thời include header trước để lấy $pdo
include 'templates/header.php';

// --- PHẦN LOGIC CỦA TRANG ---

// 1. Lấy slug sản phẩm từ URL
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    header("Location: /");
    exit();
}

// 2. Truy vấn CSDL để lấy thông tin sản phẩm
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

// === PHẦN SỬA LỖI: LẤY THÔNG TIN PHIÊN BẢN BAN ĐẦU MỘT CÁCH AN TOÀN ===
// Truy vấn này sẽ ưu tiên lấy phiên bản mặc định (is_default=1), nếu không có, nó sẽ lấy phiên bản đầu tiên được tạo.
$stmt_initial_variant = $pdo->prepare("SELECT sku, price, original_price, stock_quantity FROM product_variants WHERE product_id = ? ORDER BY is_default DESC, id ASC LIMIT 1");
$stmt_initial_variant->execute([$product['id']]);
$initial_variant = $stmt_initial_variant->fetch(PDO::FETCH_ASSOC);

// Khởi tạo các biến với giá trị rỗng để tránh lỗi nếu sản phẩm không có phiên bản nào
$sku_for_schema = $product['id'];
$price_for_schema = "0";
$availability_for_schema = "https://schema.org/OutOfStock";

// Chỉ gán lại nếu tìm thấy một phiên bản
if ($initial_variant) { 
    $sku_for_schema = $initial_variant['sku'] ?? $product['id'];
    $price_for_schema = $initial_variant['price'] ?? "0";
    $availability_for_schema = ($initial_variant['stock_quantity'] > 0) ? "https://schema.org/InStock" : "https://schema.org/OutOfStock";
}
// === KẾT THÚC PHẦN SỬA LỖI ===

// 5. TẠO DỮ LIỆU CÓ CẤU TRÚC (STRUCTURED DATA)
$schema = [
    "@context" => "https://schema.org/",
    "@type" => "Product",
    "name" => $product['name'],
    "image" => count($product_images) > 0 ? $product_images : [],
    "description" => $product['short_description'],
    "sku" => $sku_for_schema,
    "mpn" => $product['id'],
    "brand" => ["@type" => "Brand", "name" => "Khóm Bedding"],
    "offers" => [
        "@type" => "Offer",
        "url" => "http://localhost/san-pham/". $product['slug'] .".html",
        "priceCurrency" => "VND",
        "price" => $price_for_schema,
        "availability" => $availability_for_schema,
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

// ===================================================================
// === PHẦN MỚI: LẤY DỮ LIỆU VÀ KIỂM TRA ĐIỀU KIỆN ĐÁNH GIÁ ===
// ===================================================================

// 1. Lấy tất cả đánh giá đã được DUYỆT cho sản phẩm này
$stmt_reviews = $pdo->prepare("
    SELECT r.id, r.rating, r.comment, r.created_at, u.full_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$stmt_reviews->execute([$product['id']]);
$reviews_raw = $stmt_reviews->fetchAll();

// Lấy hình ảnh cho từng đánh giá
$reviews = [];
if($reviews_raw) {
    $review_ids = array_column($reviews_raw, 'id');
    $placeholders = implode(',', array_fill(0, count($review_ids), '?'));

    $stmt_images = $pdo->prepare("SELECT review_id, image_url FROM review_images WHERE review_id IN ($placeholders)");
    $stmt_images->execute($review_ids);
    $images_by_review_id = $stmt_images->fetchAll(PDO::FETCH_GROUP);

    foreach($reviews_raw as $review) {
        $review['images'] = $images_by_review_id[$review['id']] ?? [];
        // Lấy ra URL
        if($review['images']) {
            $review['images'] = array_column($review['images'], 'image_url');
        }
        $reviews[] = $review;
    }
}

// 2. Kiểm tra xem người dùng hiện tại có đủ điều kiện để viết đánh giá không
$can_user_review = false;
// Để kiểm tra, chúng ta cần giả lập là người dùng đã đăng nhập.
// TẠM THỜI, hãy bỏ comment dòng dưới đây và đặt ID của một user có thật trong CSDL của bạn.
// $_SESSION['user_id'] = 1;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Kiểm tra xem user này đã từng mua sản phẩm này chưa (với đơn hàng đã hoàn thành)
    $stmt_check_purchase = $pdo->prepare("
        SELECT o.id FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN product_variants pv ON oi.variant_id = pv.id
        WHERE o.user_id = ? AND pv.product_id = ? AND o.status = 'completed'
        LIMIT 1
    ");
    $stmt_check_purchase->execute([$user_id, $product['id']]);
    $has_purchased = $stmt_check_purchase->fetch();

    if ($has_purchased) {
        // Nếu đã mua, kiểm tra xem họ đã đánh giá sản phẩm này chưa
        $stmt_check_existing_review = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
        $stmt_check_existing_review->execute([$user_id, $product['id']]);
        $has_reviewed = $stmt_check_existing_review->fetch();

        if (!$has_reviewed) {
            // Nếu đã mua VÀ chưa đánh giá -> Cho phép đánh giá
            $can_user_review = true;
        }
    }
}
// ===================================================================
// --- KẾT THÚC PHẦN MỚI: LẤY DỮ LIỆU VÀ KIỂM TRA ĐIỀU KIỆN ĐÁNH GIÁ ---
// ===================================================================  

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
            <div id="variant-options-container" class="my-4"></div>
            <div class="d-flex align-items-center gap-3 my-4">
               <div class="quantity-input">
                  <button class="btn btn-outline-secondary" type="button">-</button>
                  <input type="number" class="form-control text-center" value="1" min="1">
                  <button class="btn btn-outline-secondary" type="button">+</button>
               </div>
               <button id="btn-add-to-cart-detail" class="btn btn-primary btn-lg flex-grow-1"
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
               <?php echo $product['description']; ?>
            </div>
            <div class="tab-pane fade" id="reviews-pane" role="tabpanel">
               <div class="row">
                  <div class="col-lg-7">
                     <h4 class="mb-4">Đánh giá của khách hàng</h4>
                     <?php if (count($reviews) > 0): ?>
                     <ul class="list-unstyled">
                        <?php foreach ($reviews as $review): ?>
                        <li class="review-item mb-4">
                           <div class="d-flex">
                              <div class="flex-shrink-0">
                                 <div class="review-avatar">
                                    <?php echo strtoupper(mb_substr($review['full_name'], 0, 1)); ?></div>
                              </div>
                              <div class="flex-grow-1 ms-3">
                                 <div class="d-flex justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($review['full_name']); ?></h5>
                                    <small
                                       class="text-muted"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></small>
                                 </div>
                                 <div class="review-stars mb-2" data-rating="<?php echo $review['rating']; ?>"></div>
                                 <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                 </p>

                                 <?php if (!empty($review['images'])): ?>
                                 <div class="review-images-gallery d-flex flex-wrap gap-2 mt-2">
                                    <?php foreach ($review['images'] as $img_url): ?>
                                    <a href="<?php echo htmlspecialchars($img_url); ?>" data-bs-toggle="tooltip"
                                       title="Phóng to ảnh">
                                       <img src="<?php echo htmlspecialchars($img_url); ?>"
                                          class="review-image-thumbnail" alt="Ảnh đánh giá">
                                    </a>
                                    <?php endforeach; ?>
                                 </div>
                                 <?php endif; ?>
                              </div>
                           </div>
                        </li>
                        <?php endforeach; ?>
                     </ul>
                     <?php else: ?>
                     <p>Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên để lại đánh giá!</p>
                     <?php endif; ?>
                  </div>

                  <?php if ($can_user_review): ?>
                  <div class="col-lg-5">
                     <div class="review-form-wrapper p-4 border rounded">
                        <h4 class="mb-3">Viết đánh giá của bạn</h4>
                        <form id="review-form" enctype="multipart/form-data">
                           <div id="review-form-alert" class="alert d-none"></div>
                           <div class="mb-3">
                              <label class="form-label">Đánh giá của bạn:</label>
                              <div class="star-rating-input">
                                 <i class="bi bi-star" data-value="1"></i><i class="bi bi-star" data-value="2"></i><i
                                    class="bi bi-star" data-value="3"></i><i class="bi bi-star" data-value="4"></i><i
                                    class="bi bi-star" data-value="5"></i>
                              </div>
                              <input type="hidden" name="rating" id="rating-value" required>
                              <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                           </div>
                           <div class="mb-3">
                              <label for="review-comment" class="form-label">Nhận xét:</label>
                              <textarea class="form-control" id="review-comment" name="comment" rows="4"
                                 required></textarea>
                           </div>

                           <div class="mb-3">
                              <label for="review-images" class="form-label">Tải lên hình ảnh (tối đa 5 ảnh):</label>
                              <input class="form-control" type="file" id="review-images" name="review_images[]" multiple
                                 accept="image/png, image/jpeg, image/gif">
                              <div id="image-preview-container" class="d-flex flex-wrap gap-2 mt-2"></div>
                           </div>

                           <button type="submit" id="submit-review-btn" class="btn btn-primary"
                              style="background-color: var(--color-accent); border-color: var(--color-accent);">
                              Gửi đánh giá
                           </button>
                        </form>
                     </div>
                  </div>
                  <?php endif; ?>
               </div>
            </div>
            <div class="tab-pane fade" id="reviews-pane" role="tabpanel">
               <div class="row">
                  <div class="col-lg-7">
                     <h4 class="mb-4">Đánh giá của khách hàng</h4>
                     <?php if (count($reviews) > 0): ?>
                     <ul class="list-unstyled">
                        <?php foreach ($reviews as $review): ?>
                        <li class="review-item mb-4">
                           <div class="d-flex">
                              <div class="flex-shrink-0">
                                 <div class="review-avatar">
                                    <?php echo strtoupper(mb_substr($review['full_name'], 0, 1)); ?></div>
                              </div>
                              <div class="flex-grow-1 ms-3">
                                 <div class="d-flex justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($review['full_name']); ?></h5>
                                    <small
                                       class="text-muted"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></small>
                                 </div>
                                 <div class="review-stars mb-2" data-rating="<?php echo $review['rating']; ?>"></div>
                                 <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                 </p>

                                 <?php if (!empty($review['images'])): ?>
                                 <div class="review-images-gallery d-flex flex-wrap gap-2 mt-2">
                                    <?php foreach ($review['images'] as $img_url): ?>
                                    <a href="<?php echo htmlspecialchars($img_url); ?>" data-bs-toggle="tooltip"
                                       title="Phóng to ảnh">
                                       <img src="<?php echo htmlspecialchars($img_url); ?>"
                                          class="review-image-thumbnail" alt="Ảnh đánh giá">
                                    </a>
                                    <?php endforeach; ?>
                                 </div>
                                 <?php endif; ?>
                              </div>
                           </div>
                        </li>
                        <?php endforeach; ?>
                     </ul>
                     <?php else: ?>
                     <p>Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên để lại đánh giá!</p>
                     <?php endif; ?>
                  </div>

                  <?php if ($can_user_review): ?>
                  <div class="col-lg-5">
                     <div class="review-form-wrapper p-4 border rounded">
                        <h4 class="mb-3">Viết đánh giá của bạn</h4>
                        <form id="review-form" enctype="multipart/form-data">
                           <div id="review-form-alert" class="alert d-none"></div>
                           <div class="mb-3">
                              <label class="form-label">Đánh giá của bạn:</label>
                              <div class="star-rating-input">
                                 <i class="bi bi-star" data-value="1"></i><i class="bi bi-star" data-value="2"></i><i
                                    class="bi bi-star" data-value="3"></i><i class="bi bi-star" data-value="4"></i><i
                                    class="bi bi-star" data-value="5"></i>
                              </div>
                              <input type="hidden" name="rating" id="rating-value" required>
                              <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                           </div>
                           <div class="mb-3">
                              <label for="review-comment" class="form-label">Nhận xét:</label>
                              <textarea class="form-control" id="review-comment" name="comment" rows="4"
                                 required></textarea>
                           </div>

                           <div class="mb-3">
                              <label for="review-images" class="form-label">Tải lên hình ảnh (tối đa 5 ảnh):</label>
                              <input class="form-control" type="file" id="review-images" name="review_images[]" multiple
                                 accept="image/png, image/jpeg, image/gif">
                              <div id="image-preview-container" class="d-flex flex-wrap gap-2 mt-2"></div>
                           </div>

                           <button type="submit" id="submit-review-btn" class="btn btn-primary"
                              style="background-color: var(--color-accent); border-color: var(--color-accent);">
                              Gửi đánh giá
                           </button>
                        </form>
                     </div>
                  </div>
                  <?php endif; ?>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<?php
// Truy vấn và chuẩn bị dữ liệu phiên bản cho JavaScript
$stmt_variants = $pdo->prepare("
    SELECT
        pv.id, pv.sku, pv.price, pv.original_price, pv.stock_quantity, pv.image_url, pv.is_default,
        a.name as attribute_name,
        av.value as attribute_value
    FROM product_variants pv
    LEFT JOIN variant_values vv ON pv.id = vv.variant_id
    LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id
    LEFT JOIN attributes a ON av.attribute_id = a.id
    WHERE pv.product_id = ?
    ORDER BY pv.id
");
$stmt_variants->execute([$product['id']]);
$raw_variants_data = $stmt_variants->fetchAll();

$variants_by_id = [];
foreach ($raw_variants_data as $row) {
    $variant_id = $row['id'];
    if (!isset($variants_by_id[$variant_id])) {
        $variants_by_id[$variant_id] = [
            'id' => $variant_id,
            'sku' => $row['sku'],
            'price' => $row['price'],
            'original_price' => $row['original_price'],
            'stock_quantity' => $row['stock_quantity'],
            'image_url' => $row['image_url'],
            'is_default' => (bool)$row['is_default'],
            'attributes' => []
        ];
    }
    if ($row['attribute_name'] && $row['attribute_value']) {
        $variants_by_id[$variant_id]['attributes'][$row['attribute_name']] = $row['attribute_value'];
    }
}
$structured_variants = array_values($variants_by_id);
?>
<script>
const productVariantsData = <?php echo json_encode($structured_variants, JSON_UNESCAPED_UNICODE); ?>;
const productImagesData = <?php echo json_encode($product_images, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="/assets/js/product-detail.js"></script>
<?php 
include 'templates/footer.php'; 
?>