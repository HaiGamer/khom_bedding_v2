<?php
session_start();
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'message'=>'Unauthorized']); http_response_code(401); exit(); }
require_once __DIR__ . '/../core/db_connect.php';

$user_id = $_SESSION['user_id'];
$address_id = filter_var($data['address_id'] ?? null, FILTER_VALIDATE_INT);

if (!$address_id) { echo json_encode(['success'=>false, 'message'=>'ID địa chỉ không hợp lệ.']); exit(); }

try {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$address_id, $user_id]);
    $pdo->commit();
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success'=>false, 'message'=>'Đã có lỗi xảy ra.']);
}