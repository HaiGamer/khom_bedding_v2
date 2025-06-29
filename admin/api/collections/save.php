<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit(); }
require_once __DIR__ . '/../../../core/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Invalid request'); }

$collection_id = filter_input(INPUT_POST, 'collection_id', FILTER_VALIDATE_INT);
$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$description = trim($_POST['description'] ?? '');
$product_ids = $_POST['product_ids'] ?? [];

if (empty($name) || empty($slug)) {
    // Xử lý lỗi
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Tên và đường dẫn không được để trống.'];
    header('Location: /admin/collection-edit.php' . ($collection_id ? '?id='.$collection_id : ''));
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Cập nhật hoặc Thêm mới thông tin bộ sưu tập
    $image_url = null;
    if ($collection_id) { // Lấy ảnh cũ nếu có
        $stmt_img = $pdo->prepare("SELECT image_url FROM collections WHERE id = ?");
        $stmt_img->execute([$collection_id]);
        $image_url = $stmt_img->fetchColumn();
    }
    // Xử lý upload ảnh mới (tương tự logic ở handler cũ)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) { /* ... */ }

    if ($collection_id) { // Cập nhật
        $stmt = $pdo->prepare("UPDATE collections SET name=?, slug=?, description=?, image_url=? WHERE id=?");
        $stmt->execute([$name, $slug, $description, $image_url, $collection_id]);
    } else { // Thêm mới
        $stmt = $pdo->prepare("INSERT INTO collections (name, slug, description, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $image_url]);
        $collection_id = $pdo->lastInsertId();
    }

    // 2. Cập nhật danh sách sản phẩm (xóa cũ, thêm mới)
    $stmt_delete = $pdo->prepare("DELETE FROM product_collections WHERE collection_id = ?");
    $stmt_delete->execute([$collection_id]);
    
    if (!empty($product_ids)) {
        $stmt_insert = $pdo->prepare("INSERT INTO product_collections (collection_id, product_id) VALUES (?, ?)");
        foreach ($product_ids as $product_id) {
            if(filter_var($product_id, FILTER_VALIDATE_INT)) {
                $stmt_insert->execute([$collection_id, $product_id]);
            }
        }
    }

    $pdo->commit();
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Lưu bộ sưu tập thành công!'];

} catch (Exception $e) {
    if($pdo->inTransaction()) { $pdo->rollBack(); }
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi: ' . $e->getMessage()];
}

// Chuyển hướng về trang sửa để người dùng thấy kết quả
header('Location: /admin/collection-edit.php?id=' . $collection_id);
exit();