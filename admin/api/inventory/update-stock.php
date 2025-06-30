<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { exit(json_encode(['success' => false, 'message' => 'Unauthorized'])); }
require_once __DIR__ . '/../../../core/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$variant_id = filter_var($data['variant_id'] ?? null, FILTER_VALIDATE_INT);
$quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);

if (!$variant_id || $quantity === null || $quantity < 0) {
    exit(json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']));
}

try {
    $stmt = $pdo->prepare("UPDATE product_variants SET stock_quantity = ? WHERE id = ?");
    $stmt->execute([$quantity, $variant_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu.']);
}