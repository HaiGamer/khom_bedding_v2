<?php
session_start();
header('Content-Type: application/json');

// Bảo mật
if (!isset($_SESSION['admin_id'])) { 
    http_response_code(401);
    exit(json_encode([])); 
}
require_once __DIR__ . '/../../../core/db_connect.php';

// Lấy từ khóa tìm kiếm
$term = $_GET['term'] ?? '';
if (strlen($term) < 1) { // Chỉ tìm kiếm khi người dùng gõ ít nhất 2 ký tự
    exit(json_encode([]));
}

// Tìm kiếm trong tên hoặc số điện thoại
$stmt = $pdo->prepare("
    SELECT id, customer_name, phone_number, address, email, customer_type 
    FROM customers 
    WHERE customer_name LIKE ? OR phone_number LIKE ?
    LIMIT 5
");
$stmt->execute(["%{$term}%", "%{$term}%"]);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($customers);