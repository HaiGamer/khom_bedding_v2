<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/db_connect.php';

function json_response($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.');
}

// 1. Lấy dữ liệu và xác thực
$name = trim($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = trim($_POST['phone'] ?? ''); // Thêm dòng lấy số điện thoại
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$hcaptcha_response = $_POST['h-captcha-response'] ?? '';

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    json_response(false, 'Vui lòng điền đầy đủ các trường bắt buộc.');
}

// 2. Xác thực hCaptcha
$hcaptcha_secret = 'ES_54b52b18b0dd4c86becd86357ad8b706'; // <-- NHỚ THAY THẾ SECRET KEY CỦA BẠN
$data = ['secret' => $hcaptcha_secret, 'response' => $hcaptcha_response];
$options = ['http' => ['header'  => "Content-type: application/x-www-form-urlencoded\r\n", 'method'  => 'POST', 'content' => http_build_query($data)]];
$context  = stream_context_create($options);
$result = file_get_contents('https://hcaptcha.com/siteverify', false, $context);
$hcaptcha_result = json_decode($result);

if ($hcaptcha_result->success == false) {
    json_response(false, 'Xác thực bảo mật thất bại. Vui lòng thử lại.');
}

// 3. Lưu vào CSDL
try {
    // Sửa lại câu lệnh INSERT để thêm cột "phone"
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    // Thêm biến $phone vào mảng execute
    $stmt->execute([$name, $email, $phone, $subject, $message]);
    json_response(true, 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.');
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra phía máy chủ.');
}