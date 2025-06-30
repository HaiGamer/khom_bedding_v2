<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/db_connect.php';

$term = $_GET['q'] ?? '';
if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Chỉ lấy các thông tin cần thiết để hiển thị gợi ý
    $stmt = $pdo->prepare("
        SELECT 
            p.name, p.slug, pv.price,
            (SELECT image_url FROM product_images WHERE product_id = p.id AND is_featured = 1 LIMIT 1) as image_url
        FROM products p
        JOIN product_variants pv ON p.id = pv.product_id AND pv.is_default = 1
        WHERE p.name LIKE ?
        GROUP BY p.id
        ORDER BY p.name ASC
        LIMIT 5
    ");
    $stmt->execute(["%{$term}%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode([]);
}