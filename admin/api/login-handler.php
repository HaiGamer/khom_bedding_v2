<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../core/db_connect.php';

/**
 * Hàm tiện ích để trả về phản hồi JSON và kết thúc script.
 * @param bool $success Trạng thái thành công hay thất bại.
 * @param string $message Thông điệp trả về.
 * @param array $data Dữ liệu bổ sung (nếu có).
 * @param int $http_code Mã trạng thái HTTP.
 */
function json_response($success, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    $response = ['success' => $success, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.', [], 405);
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    json_response(false, 'Vui lòng nhập email và mật khẩu.', [], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Luôn trả về một thông báo chung để tăng bảo mật
    if (!$user || !password_verify($password, $user['password'])) {
        json_response(false, 'Email hoặc mật khẩu không chính xác.', [], 401);
    }

    // Kiểm tra vai trò admin
    if ($user['role'] !== 'admin') {
        json_response(false, 'Tài khoản không có quyền truy cập vào khu vực quản trị.', [], 403);
    }

    // Đăng nhập thành công, tạo session cho admin
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_name'] = $user['full_name'];

    json_response(true, 'Đăng nhập thành công! Đang chuyển hướng...', ['redirect_url' => '/admin/']);

} catch (PDOException $e) {
    error_log("Lỗi đăng nhập admin: " . $e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra phía máy chủ.', [], 500);
}