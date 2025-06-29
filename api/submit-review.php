<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../core/db_connect.php';

// --- HÀM TRẢ VỀ JSON VÀ KẾT THÚC ---
function json_response($success, $message, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// --- KIỂM TRA ĐẦU VÀO ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.', 405);
}

if (!isset($_SESSION['user_id'])) {
    json_response(false, 'Bạn cần đăng nhập để thực hiện chức năng này.', 401);
}

$user_id = $_SESSION['user_id'];
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$product_id || !$rating || !$comment) {
    json_response(false, 'Vui lòng điền đầy đủ thông tin.', 400);
}
if ($rating < 1 || $rating > 5) {
    json_response(false, 'Điểm đánh giá không hợp lệ.', 400);
}

// --- KIỂM TRA QUYỀN ĐÁNH GIÁ (Bảo mật phía server) ---
// (Copy logic tương tự từ product-detail.php)
$stmt_check_purchase = $pdo->prepare("SELECT o.id FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN product_variants pv ON oi.variant_id = pv.id WHERE o.user_id = ? AND pv.product_id = ? AND o.status = 'completed' LIMIT 1");
$stmt_check_purchase->execute([$user_id, $product_id]);
if (!$stmt_check_purchase->fetch()) {
    json_response(false, 'Bạn chỉ có thể đánh giá sản phẩm đã mua.', 403);
}

$stmt_check_existing_review = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
$stmt_check_existing_review->execute([$user_id, $product_id]);
if ($stmt_check_existing_review->fetch()) {
    json_response(false, 'Bạn đã đánh giá sản phẩm này rồi.', 409);
}


// --- XỬ LÝ UPLOAD HÌNH ẢNH ---
$uploaded_image_paths = [];
$upload_dir = __DIR__ . '/../uploads/reviews/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!empty($_FILES['review_images']['name'][0])) {
    $file_count = count($_FILES['review_images']['name']);
    if ($file_count > 5) {
        json_response(false, 'Chỉ được phép tải lên tối đa 5 ảnh.', 400);
    }

    for ($i = 0; $i < $file_count; $i++) {
        // Kiểm tra lỗi
        if ($_FILES['review_images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        // Kiểm tra loại file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['review_images']['type'][$i], $allowed_types)) continue;

        // Tạo tên file duy nhất
        $file_extension = pathinfo($_FILES['review_images']['name'][$i], PATHINFO_EXTENSION);
        $new_filename = uniqid('review_'. $product_id .'_', true) . '.' . $file_extension;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['review_images']['tmp_name'][$i], $destination)) {
            // Lưu đường dẫn tương đối để sử dụng trong thẻ <img>
            $uploaded_image_paths[] = '/uploads/reviews/' . $new_filename;
        }
    }
}


// --- LƯU ĐÁNH GIÁ VÀO CSDL ---
try {
    $pdo->beginTransaction();

    // 1. Thêm đánh giá vào bảng `reviews`
    $stmt_insert_review = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt_insert_review->execute([$product_id, $user_id, $rating, $comment]);
    $review_id = $pdo->lastInsertId();

    // 2. Thêm các ảnh (nếu có) vào bảng `review_images`
    if (!empty($uploaded_image_paths)) {
        $stmt_insert_image = $pdo->prepare("INSERT INTO review_images (review_id, image_url) VALUES (?, ?)");
        foreach ($uploaded_image_paths as $path) {
            $stmt_insert_image->execute([$review_id, $path]);
        }
    }

    $pdo->commit();
    json_response(true, 'Đánh giá của bạn đã được gửi và đang chờ duyệt. Cảm ơn bạn!');

} catch (Exception $e) {
    $pdo->rollBack();
    // Ghi log lỗi thực tế cho admin
    error_log($e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra phía máy chủ, vui lòng thử lại.', 500);
}