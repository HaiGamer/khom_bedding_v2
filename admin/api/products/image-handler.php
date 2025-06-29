<?php
session_start();
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// Bảo mật và kiểm tra
if (!isset($_SESSION['admin_id'])) { exit(json_encode(['success' => false, 'message' => 'Unauthorized'])); }
require_once __DIR__ . '/../../../core/db_connect.php';

$action = $data['action'] ?? '';
$image_id = filter_var($data['image_id'] ?? 0, FILTER_VALIDATE_INT);
$product_id = filter_var($data['product_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$action || !$image_id) { exit(json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ'])); }

try {
    if ($action === 'delete') {
        // Lấy url để xóa file vật lý
        $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image_url = $stmt->fetchColumn();
        if ($image_url) {
            $file_path = __DIR__ . '/../../../' . $image_url;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        // Xóa trong CSDL
        $stmt_delete = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt_delete->execute([$image_id]);
        echo json_encode(['success' => true, 'message' => 'Đã xóa ảnh.']);
    } 
    elseif ($action === 'set_featured') {
        if (!$product_id) { exit(json_encode(['success' => false, 'message' => 'Product ID không hợp lệ'])); }
        $pdo->beginTransaction();
        // Bỏ tất cả ảnh đại diện cũ của sản phẩm này
        $stmt_reset = $pdo->prepare("UPDATE product_images SET is_featured = 0 WHERE product_id = ?");
        $stmt_reset->execute([$product_id]);
        // Đặt ảnh mới làm đại diện
        $stmt_set = $pdo->prepare("UPDATE product_images SET is_featured = 1 WHERE id = ?");
        $stmt_set->execute([$image_id]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Đã đặt làm ảnh đại diện.']);
    }
} catch (PDOException $e) {
    if(isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ.']);
}