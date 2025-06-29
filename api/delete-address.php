<?php
session_start();
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'message'=>'Unauthorized']); http_response_code(401); exit(); }
require_once __DIR__ . '/../core/db_connect.php';

$user_id = $_SESSION['user_id'];
$address_id = filter_var($data['address_id'] ?? null, FILTER_VALIDATE_INT);

if (!$address_id) { echo json_encode(['success'=>false, 'message'=>'ID địa chỉ không hợp lệ.']); exit(); }

$stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
if ($stmt->execute([$address_id, $user_id])) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'message'=>'Không thể xóa địa chỉ.']);
}