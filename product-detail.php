<?php
// Bước 1: Kết nối CSDL trước tiên
require_once __DIR__ . '/core/db_connect.php';
// 1. Lấy slug sản phẩm từ URL
$slug = $_GET['slug'] ?? null;
if (!$slug) {
    header("Location: /");
    exit();
}
// Bước 2: Truy vấn sản phẩm dựa trên slug
$stmt2 = $pdo->prepare("
    SELECT 
        p.id, p.name, p.slug, p.short_description
    FROM products p 
    WHERE p.slug = ?
");
$stmt2->execute([$slug]);
$product2 = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$product2) {
    http_response_code(404);
    // Chúng ta vẫn include header và footer để trang lỗi có giao diện
    $page_title = "404 Not Found";
    include 'templates/header.php';
    echo "<div class='container text-center py-5'><h1>404 Not Found</h1><p>Sản phẩm không tồn tại.</p></div>";
    include 'templates/footer.php';
    exit();
}
// Bước 3: Định nghĩa các biến SEO động
$page_title = htmlspecialchars($product2['name']) . ' - Khóm Bedding';
$page_description = htmlspecialchars(strip_tags($product2['short_description'] ?? ''));

include 'templates/header.php';
// --- PHẦN LOGIC CỦA TRANG ---



// 2. === NÂNG CẤP TRUY VẤN: Thêm sold_count và sửa lại review_count ===
$stmt = $pdo->prepare("
    SELECT 
        p.id, p.name, p.slug, p.description, p.short_description,
        c.name as category_name, c.slug as category_slug,
        (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
        (SELECT COUNT(id) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count,
        (SELECT SUM(oi.quantity) FROM order_items oi JOIN product_variants pv ON oi.variant_id = pv.id JOIN orders o ON oi.order_id = o.id WHERE pv.product_id = p.id AND o.status = 'completed') as sold_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ?
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

// 3. Xử lý trường hợp không tìm thấy sản phẩm
if (!$product) {
    http_response_code(404);
    echo "<div class='container text-center py-5'><h1>404 Not Found</h1><p>Sản phẩm không tồn tại.</p></div>";
    include 'templates/footer.php';
    exit();
}

// Lấy tất cả ảnh của sản phẩm cho gallery
$stmt_images = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_featured DESC");
$stmt_images->execute([$product['id']]);
$product_images = $stmt_images->fetchAll(PDO::FETCH_COLUMN);




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

// === LOGIC MỚI: LẤY TẤT CẢ ẢNH TỪ ĐÁNH GIÁ ĐÃ DUYỆT ===
$stmt_review_images = $pdo->prepare("
    SELECT 
        ri.image_url, 
        r.comment, r.rating, 
        u.full_name
    FROM review_images ri
    JOIN reviews r ON ri.review_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$stmt_review_images->execute([$product['id']]);
$all_review_images = $stmt_review_images->fetchAll(PDO::FETCH_ASSOC);




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
                  <a href="#reviews-pane" class="text-muted text-decoration-none">
                     <i class="bi bi-star-fill text-warning"></i>
                     <span><?php echo number_format($product['avg_rating'] ?? 0, 1); ?>
                        (<?php echo (int)$product['review_count']; ?> đánh giá)</span>
                  </a>
               </div>
               <div>|</div>
               <div>Đã bán: <?php echo (int)($product['sold_count'] ?? 0); ?>+</div>
               <div>|</div>
               <div id="product-sku" style="text-transform: uppercase;">SKU:
                  <?php echo htmlspecialchars($default_variant['sku'] ?? 'N/A'); ?></div>
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
                  data-bs-target="#description-pane" type="button">Mô Tả Chi Tiết</button>
            </li>
            <li class="nav-item" role="presentation">
               <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-pane"
                  type="button">Đánh Giá (<?php echo $product['review_count']; ?>)</button>
            </li>
         </ul>
         <div class="tab-content pt-4" id="productInfoTabsContent">
            <div class="tab-pane fade show active" id="description-pane" role="tabpanel">
               <?php echo $product['description']; ?>
            </div>

            <div class="tab-pane fade" id="reviews-pane" role="tabpanel">

               <?php if (!empty($all_review_images)): ?>
               <div class="customer-photos-section mb-5">
                  <h4 class="mb-3">Hình ảnh từ khách hàng</h4>
                  <div class="customer-photos-grid">
                     <?php 
                        $images_to_show = array_slice($all_review_images, 0, 9);
                        foreach ($images_to_show as $img_data):
                        ?>
                     <div class="customer-photo-item" data-bs-toggle="modal" data-bs-target="#review-image-modal"
                        data-image-src="<?= htmlspecialchars($img_data['image_url']) ?>"
                        data-rating="<?= $img_data['rating'] ?>"
                        data-comment="<?= htmlspecialchars($img_data['comment']) ?>"
                        data-author="<?= htmlspecialchars($img_data['full_name']) ?>">
                        <img src="<?= htmlspecialchars($img_data['image_url']) ?>" alt="Ảnh đánh giá của khách hàng">
                     </div>
                     <?php endforeach; ?>

                     <?php if (count($all_review_images) > 9): ?>
                     <div class="customer-photo-item view-more" data-bs-toggle="modal"
                        data-bs-target="#review-image-modal" data-is-gallery="true">
                        <div class="view-more-overlay">+<?php echo count($all_review_images) - 9; ?><br>Xem thêm</div>
                     </div>
                     <?php endif; ?>
                  </div>
               </div>
               <?php endif; ?>

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

   <div class="modal fade" id="review-image-modal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-body p-0">
               <div class="row g-0">
                  <div class="col-lg-8" id="modal-image-col">
                  </div>
                  <div class="col-lg-4 d-flex flex-column">
                     <div class="modal-review-info p-4 flex-grow-1">
                        <button type="button" class="btn-close float-end" data-bs-dismiss="modal"></button>
                        <div class="author-info mb-2">
                           <strong id="modal-review-author"></strong>
                        </div>
                        <div class="review-stars mb-2" id="modal-review-stars"></div>
                        <p id="modal-review-comment"></p>
                     </div>
                     <div class="modal-thumbnails-wrapper p-3 border-top d-none">
                        <div class="modal-thumbnails d-flex gap-2">
                        </div>
                     </div>
                  </div>
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