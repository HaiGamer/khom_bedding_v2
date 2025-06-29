<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../core/db_connect.php';

// Hàm tiện ích để trả về phản hồi JSON
function json_response($success, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    $response = ['success' => $success, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit();
}

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.', 405);
}

// --- 1. Lấy và kiểm tra dữ liệu đầu vào ---
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    json_response(false, 'Vui lòng điền đầy đủ email và mật khẩu.', 400);
}

// --- 2. Xử lý logic nghiệp vụ và CSDL ---
try {
    // Tìm người dùng bằng email
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Nếu không tìm thấy người dùng hoặc mật khẩu không khớp
    // Luôn trả về một thông báo chung để tránh lộ thông tin
    if (!$user || !password_verify($password, $user['password'])) {
        json_response(false, 'Email hoặc mật khẩu không chính xác.', 401);
    }

    // --- 3. Đăng nhập thành công: Quản lý Session ---
    
    // Xóa session cũ và tạo lại session ID mới -> Chống tấn công Session Fixation
    session_regenerate_id(true);

    // Lưu thông tin người dùng vào session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_full_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
    
    // Trả về thông báo thành công và đường dẫn để chuyển hướng
    json_response(true, 'Đăng nhập thành công! Đang chuyển hướng...', [
        'redirect_url' => '/account-orders.html' // Chuyển hướng đến trang tài khoản
    ]);

} catch (PDOException $e) {
    error_log("Lỗi đăng nhập: " . $e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra phía máy chủ. Vui lòng thử lại sau.', 500);
}