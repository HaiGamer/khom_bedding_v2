<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../core/db_connect.php';

// Bảo mật và kiểm tra đầu vào
if (!isset($_SESSION['admin_id'])) { exit(json_encode(['success' => false, 'message' => 'Unauthorized'])); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit(json_encode(['success' => false, 'message' => 'Invalid request'])); }
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
if (!$product_id) { exit(json_encode(['success' => false, 'message' => 'Product ID không hợp lệ.'])); }

$upload_dir = __DIR__ . '/../../../uploads/products/';
if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }

$uploaded_files = [];
foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
    if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;
    
    $file_name = $_FILES['images']['name'][$key];
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_filename = uniqid('product_' . $product_id . '_', true) . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;

    if (move_uploaded_file($tmp_name, $destination)) {
        $image_url = '/uploads/products/' . $new_filename;
        
        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
        $stmt->execute([$product_id, $image_url]);
        $image_id = $pdo->lastInsertId();

        $uploaded_files[] = ['id' => $image_id, 'url' => $image_url];
    }
}

if (!empty($uploaded_files)) {
    echo json_encode(['success' => true, 'files' => $uploaded_files]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không có file nào được tải lên thành công.']);
} 
?>
// End of file: admin/api/products/image-upload.php