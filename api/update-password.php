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

// Yêu cầu đăng nhập và phương thức POST
if (!isset($_SESSION['user_id'])) { json_response(false, 'Bạn cần đăng nhập.', 401); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(false, 'Yêu cầu không hợp lệ.', 405); }

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($current_password) || empty($new_password)) {
    json_response(false, 'Vui lòng điền đầy đủ các trường.', 400);
}

if (strlen($new_password) < 6) {
    json_response(false, 'Mật khẩu mới phải có ít nhất 6 ký tự.', 400);
}

try {
    // 1. Lấy mật khẩu đã mã hóa hiện tại từ CSDL
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(false, 'Không tìm thấy người dùng.', 404);
    }

    // 2. Xác minh mật khẩu hiện tại
    if (!password_verify($current_password, $user['password'])) {
        json_response(false, 'Mật khẩu hiện tại không chính xác.', 400);
    }
    
    // 3. Mã hóa và cập nhật mật khẩu mới
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_hashed_password, $user_id]);

    json_response(true, 'Đổi mật khẩu thành công!');

} catch (PDOException $e) {
    error_log("Lỗi đổi mật khẩu: " . $e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra. Vui lòng thử lại.', 500);
}