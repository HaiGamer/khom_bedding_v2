<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { exit(json_encode(['success' => false, 'message' => 'Unauthorized'])); }
require_once __DIR__ . '/../../../core/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$review_id = filter_var($data['review_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$action || !$review_id) { exit(json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'])); }

try {
    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
            $stmt->execute([$review_id]);
            $message = 'Đã duyệt đánh giá.';
            break;
        case 'reject':
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$review_id]);
            $message = 'Đã từ chối đánh giá.';
            break;
        case 'delete':
            // Cần xóa cả ảnh liên quan trước khi xóa đánh giá
            $pdo->beginTransaction();
            // Lấy URL ảnh để xóa file
            $stmt_images = $pdo->prepare("SELECT image_url FROM review_images WHERE review_id = ?");
            $stmt_images->execute([$review_id]);
            $images = $stmt_images->fetchAll(PDO::FETCH_COLUMN);
            foreach ($images as $img_url) {
                $file_path = __DIR__ . '/../../../' . $img_url;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            // Xóa record trong CSDL (bảng review_images sẽ tự xóa do ràng buộc khóa ngoại ON DELETE CASCADE)
            $stmt_delete = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt_delete->execute([$review_id]);
            $pdo->commit();
            $message = 'Đã xóa vĩnh viễn đánh giá.';
            break;
        default:
            throw new Exception("Hành động không hợp lệ.");
    }
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    if(isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}