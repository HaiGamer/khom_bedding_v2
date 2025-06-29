<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized'])); 
}
require_once __DIR__ . '/../../core/db_connect.php';

try {
    $pdo->beginTransaction();

    // 1. LƯU THÔNG TIN SẢN PHẨM CƠ BẢN
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
    $description = $_POST['description'] ?? '';
    $short_description = $_POST['short_description'] ?? '';

    if (empty($name) || empty($slug)) {
        throw new Exception("Tên sản phẩm và đường dẫn không được để trống.");
    }
    
    if ($product_id) {
        $stmt_product = $pdo->prepare("UPDATE products SET name=?, slug=?, category_id=?, description=?, short_description=? WHERE id=?");
        $stmt_product->execute([$name, $slug, $category_id, $description, $short_description, $product_id]);
    } else {
        $stmt_product = $pdo->prepare("INSERT INTO products (name, slug, category_id, description, short_description) VALUES (?, ?, ?, ?, ?)");
        $stmt_product->execute([$name, $slug, $category_id, $description, $short_description]);
        $product_id = $pdo->lastInsertId();
    }
    
    // 2. XỬ LÝ CÁC PHIÊN BẢN SẢN PHẨM
    $variants_data = $_POST['variants'] ?? [];
    $submitted_variant_ids = [];
    $variant_id_map = []; // Mảng mới để map index từ form với ID trong CSDL

    foreach ($variants_data as $index => $variant_data) {
        $variant_id = filter_var($variant_data['id'] ?? null, FILTER_VALIDATE_INT);
        $attributes = array_filter($variant_data['attributes'] ?? []);

        if ($variant_id) { // Cập nhật
            $stmt_variant = $pdo->prepare("UPDATE product_variants SET sku=?, price=?, original_price=?, stock_quantity=? WHERE id=? AND product_id=?");
            $stmt_variant->execute([$variant_data['sku'], $variant_data['price'], $variant_data['original_price'] ?: null, $variant_data['stock_quantity'], $variant_id, $product_id]);
            $submitted_variant_ids[] = $variant_id;
            $variant_id_map[$index] = $variant_id; // Lưu id đã có
        } else { // Thêm mới
            $stmt_variant = $pdo->prepare("INSERT INTO product_variants (product_id, sku, price, original_price, stock_quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt_variant->execute([$product_id, $variant_data['sku'], $variant_data['price'], $variant_data['original_price'] ?: null, $variant_data['stock_quantity']]);
            $new_variant_id = $pdo->lastInsertId();
            $variant_id_map[$index] = $new_variant_id; // Lưu id vừa tạo
            $variant_id = $new_variant_id; // Cập nhật variant_id để lưu thuộc tính
        }

        // Cập nhật thuộc tính (logic này giữ nguyên)
        $stmt_delete_attrs = $pdo->prepare("DELETE FROM variant_values WHERE variant_id = ?");
        $stmt_delete_attrs->execute([$variant_id]);
        if (!empty($attributes)) {
            $stmt_insert_attr = $pdo->prepare("INSERT INTO variant_values (variant_id, attribute_value_id) VALUES (?, ?)");
            foreach ($attributes as $attr_value_id) {
                if(!empty($attr_value_id)) {
                    $stmt_insert_attr->execute([$variant_id, $attr_value_id]);
                }
            }
        }
    }

    // Tách riêng ID của các phiên bản được gửi lên
    foreach ($variants_data as $variant_data) {
        if (!empty($variant_data['id'])) {
            $submitted_variant_ids[] = (int)$variant_data['id'];
        }
    }
    
    // 3. XÓA CÁC PHIÊN BẢN CŨ NẾU CẦN
    if ($product_id) {
        $stmt_current_ids = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = ?");
        $stmt_current_ids->execute([$product_id]);
        $current_db_ids = $stmt_current_ids->fetchAll(PDO::FETCH_COLUMN);
        
        $ids_to_delete = array_diff($current_db_ids, $submitted_variant_ids);

        if (!empty($ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
            $stmt_delete_variants = $pdo->prepare("DELETE FROM product_variants WHERE id IN ($placeholders)");
            $stmt_delete_variants->execute(array_values($ids_to_delete));
        }
    }

    // 4. THÊM MỚI HOẶC CẬP NHẬT TỪNG PHIÊN BẢN
    foreach ($variants_data as $variant_data) {
        
        $variant_id = filter_var($variant_data['id'] ?? null, FILTER_VALIDATE_INT);
        $attributes = array_filter($variant_data['attributes'] ?? []);

        if ($variant_id) {
            // Cập nhật phiên bản đã có
            $stmt_variant = $pdo->prepare("UPDATE product_variants SET sku=?, price=?, original_price=?, stock_quantity=? WHERE id=? AND product_id=?");
            $stmt_variant->execute([$variant_data['sku'], $variant_data['price'], $variant_data['original_price'] ?: null, $variant_data['stock_quantity'], $variant_id, $product_id]);
        } else {
            // Thêm phiên bản mới
            $stmt_variant = $pdo->prepare("INSERT INTO product_variants (product_id, sku, price, original_price, stock_quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt_variant->execute([$product_id, $variant_data['sku'], $variant_data['price'], $variant_data['original_price'] ?: null, $variant_data['stock_quantity']]);
            $variant_id = $pdo->lastInsertId(); // Lấy ID mới để lưu thuộc tính
        }

        // Cập nhật thuộc tính cho phiên bản này: Xóa hết cũ, thêm lại mới
        $stmt_delete_attrs = $pdo->prepare("DELETE FROM variant_values WHERE variant_id = ?");
        $stmt_delete_attrs->execute([$variant_id]);

        if (!empty($attributes)) {
            $stmt_insert_attr = $pdo->prepare("INSERT INTO variant_values (variant_id, attribute_value_id) VALUES (?, ?)");
            foreach ($attributes as $attr_value_id) { // Giả sử attributes là một mảng các value_id
                if(!empty($attr_value_id)) {
                    $stmt_insert_attr->execute([$variant_id, $attr_value_id]);
                }
            }
        }
    }

   // ===================================================================
    // === LOGIC MỚI: XỬ LÝ PHIÊN BẢN MẶC ĐỊNH DO NGƯỜI DÙNG CHỌN ===
    // ===================================================================
    $default_variant_index = $_POST['default_variant_index'] ?? null;
    
    // Reset tất cả các phiên bản của sản phẩm này về is_default = 0
    $stmt_reset_default = $pdo->prepare("UPDATE product_variants SET is_default = 0 WHERE product_id = ?");
    $stmt_reset_default->execute([$product_id]);
    
    // Nếu admin có chọn một phiên bản làm mặc định
    if ($default_variant_index !== null && isset($variant_id_map[$default_variant_index])) {
        $default_variant_id = $variant_id_map[$default_variant_index];
        // Đặt phiên bản được chọn làm mặc định
        $stmt_set_default = $pdo->prepare("UPDATE product_variants SET is_default = 1 WHERE id = ? AND product_id = ?");
        $stmt_set_default->execute([$default_variant_id, $product_id]);
    }

    // --- Xóa bỏ logic tự động cũ ---
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Lưu sản phẩm thành công!', 'product_id' => $product_id]);

} catch (Exception $e) {
    if(isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}