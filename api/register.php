<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../core/db_connect.php';

// Hàm tiện ích để trả về phản hồi JSON và kết thúc script
function json_response($success, $message, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.', 405);
}

// --- 1. Lấy và kiểm tra dữ liệu đầu vào ---
$full_name = trim($_POST['full_name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$hcaptcha_response = $_POST['h-captcha-response'] ?? '';

if (empty($full_name) || empty($email) || empty($password)) {
    json_response(false, 'Vui lòng điền đầy đủ các trường bắt buộc.', 400);
}

if (strlen($password) < 6) {
    json_response(false, 'Mật khẩu phải có ít nhất 6 ký tự.', 400);
}

// --- 2. Xác thực hCaptcha ---
if (empty($hcaptcha_response)) {
    json_response(false, 'Vui lòng hoàn thành xác thực bảo mật.', 400);
}

$hcaptcha_secret = 'ES_54b52b18b0dd4c86becd86357ad8b706'; // <-- THAY THẾ SECRET KEY CỦA BẠN VÀO ĐÂY
$verify_url = 'https://hcaptcha.com/siteverify';
$data = [
    'secret'   => $hcaptcha_secret,
    'response' => $hcaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($verify_url, false, $context);
$hcaptcha_result = json_decode($result);

if ($hcaptcha_result->success == false) {
    json_response(false, 'Xác thực bảo mật thất bại. Vui lòng thử lại.', 403);
}

// --- 3. Xử lý logic nghiệp vụ và CSDL ---
try {
    // Kiểm tra xem email đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(false, 'Email này đã được sử dụng. Vui lòng chọn một email khác.', 409);
    }

    // Mã hóa mật khẩu - Rất quan trọng cho bảo mật!
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Thêm người dùng mới vào CSDL
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'user')");
    $stmt->execute([$full_name, $email, $hashed_password]);

    json_response(true, 'Đăng ký tài khoản thành công! Bạn có thể chuyển qua tab Đăng nhập để tiếp tục.');

} catch (PDOException $e) {
    // Ghi log lỗi thực tế cho admin xem, không hiển thị cho người dùng
    error_log("Lỗi đăng ký: " . $e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra phía máy chủ. Vui lòng thử lại sau.', 500);
}