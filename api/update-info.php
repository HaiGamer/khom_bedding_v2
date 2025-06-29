<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../core/db_connect.php';

// Hàm tiện ích
function json_response($success, $message, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    json_response(false, 'Bạn cần đăng nhập để thực hiện chức năng này.', 401);
}

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.', 405);
}

$user_id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name'] ?? '');

if (empty($full_name)) {
    json_response(false, 'Họ và tên không được để trống.', 400);
}

try {
    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $stmt->execute([$full_name, $user_id]);

    // Cập nhật lại session để tên mới hiển thị ngay lập tức
    $_SESSION['user_full_name'] = $full_name;

    json_response(true, 'Cập nhật thông tin thành công!');

} catch (PDOException $e) {
    error_log("Lỗi cập nhật thông tin: " . $e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra. Vui lòng thử lại.', 500);
}