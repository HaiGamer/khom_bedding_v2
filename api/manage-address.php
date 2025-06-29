<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'message'=>'Unauthorized']); http_response_code(401); exit(); }

require_once __DIR__ . '/../core/db_connect.php';
$user_id = $_SESSION['user_id'];

$address_id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
$full_name = trim($_POST['full_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$address_line = trim($_POST['address_line'] ?? '');
$is_default = isset($_POST['is_default']) ? 1 : 0;

if (empty($full_name) || empty($phone_number) || empty($address_line)) {
    echo json_encode(['success'=>false, 'message'=>'Vui lòng điền đầy đủ thông tin.']); exit();
}

try {
    // ===================================================================
    // === LOGIC MỚI: KIỂM TRA GIỚI HẠN SỐ LƯỢNG ĐỊA CHỈ ===
    // ===================================================================
    // Chỉ kiểm tra khi đây là hành động "thêm mới" (không có address_id)
    if (!$address_id) {
        $stmt_count = $pdo->prepare("SELECT COUNT(id) FROM user_addresses WHERE user_id = ?");
        $stmt_count->execute([$user_id]);
        $address_count = $stmt_count->fetchColumn();

        if ($address_count >= 4) {
            echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể lưu tối đa 4 địa chỉ.']);
            exit();
        }
    }
    // ===================================================================


    $pdo->beginTransaction();
    if ($is_default) {
        $stmt_reset = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $stmt_reset->execute([$user_id]);
    }
    if ($address_id) { // Cập nhật
        $stmt = $pdo->prepare("UPDATE user_addresses SET full_name=?, phone_number=?, address_line=?, is_default=? WHERE id=? AND user_id=?");
        $stmt->execute([$full_name, $phone_number, $address_line, $is_default, $address_id, $user_id]);
        $message = 'Cập nhật địa chỉ thành công!';
    } else { // Thêm mới
        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, full_name, phone_number, address_line, is_default) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $full_name, $phone_number, $address_line, $is_default]);
        $message = 'Thêm địa chỉ mới thành công!';
    }
    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>$message]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success'=>false, 'message'=>'Đã có lỗi xảy ra.']);
}