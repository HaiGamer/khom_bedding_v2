<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../core/db_connect.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';
$variant_id = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

// Hàm tính tổng số lượng sản phẩm trong giỏ hàng
function calculate_cart_total_items($cart) {
    return array_sum(array_column($cart, 'quantity'));
}
// Hàm mới để tính tổng tiền
function calculate_cart_subtotal($cart, $pdo) {
    $subtotal = 0;
    if (empty($cart)) return $subtotal;

    $variant_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));
    
    $stmt = $pdo->prepare("SELECT id, price FROM product_variants WHERE id IN ($placeholders)");
    $stmt->execute($variant_ids);
    $variants = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($cart as $v_id => $item) {
        if (isset($variants[$v_id])) {
            $subtotal += $variants[$v_id] * $item['quantity'];
        }
    }
    return $subtotal;
}


switch ($action) {
    case 'add':
        if (!$variant_id || !$quantity || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
            exit;
        }

        // Kiểm tra số lượng tồn kho
        $stmt = $pdo->prepare("SELECT stock_quantity FROM product_variants WHERE id = ?");
        $stmt->execute([$variant_id]);
        $stock = $stmt->fetchColumn();

        $current_cart_qty = $_SESSION['cart'][$variant_id]['quantity'] ?? 0;
        
        if (($current_cart_qty + $quantity) > $stock) {
            echo json_encode(['success' => false, 'message' => 'Số lượng tồn kho không đủ.']);
            exit;
        }

        // Nếu sản phẩm đã có trong giỏ, cộng dồn số lượng
        if (isset($_SESSION['cart'][$variant_id])) {
            $_SESSION['cart'][$variant_id]['quantity'] += $quantity;
        } else {
            // Nếu chưa có, thêm mới
            $_SESSION['cart'][$variant_id] = [
                'variant_id' => $variant_id,
                'quantity' => $quantity
            ];
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Đã thêm sản phẩm vào giỏ hàng!',
            'cart_item_count' => calculate_cart_total_items($_SESSION['cart'])
        ]);
        break;
        
        // --- LOGIC MỚI ---
    case 'update':
        if (!$variant_id || $quantity === null || $quantity < 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
            exit;
        }

        // Nếu số lượng là 0, xóa sản phẩm
        if ($quantity == 0) {
            unset($_SESSION['cart'][$variant_id]);
        } else {
             // Kiểm tra tồn kho
            $stmt = $pdo->prepare("SELECT stock_quantity FROM product_variants WHERE id = ?");
            $stmt->execute([$variant_id]);
            $stock = $stmt->fetchColumn();

            if ($quantity > $stock) {
                echo json_encode(['success' => false, 'message' => 'Số lượng tồn kho không đủ.']);
                exit;
            }
            // Cập nhật số lượng
            $_SESSION['cart'][$variant_id]['quantity'] = $quantity;
        }

        echo json_encode([
            'success' => true,
            'cart_item_count' => calculate_cart_total_items($_SESSION['cart']),
            'subtotal' => calculate_cart_subtotal($_SESSION['cart'], $pdo)
        ]);
        break;

    case 'remove':
        if (!$variant_id) {
            echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ.']);
            exit;
        }

        unset($_SESSION['cart'][$variant_id]);

        echo json_encode([
            'success' => true,
            'cart_item_count' => calculate_cart_total_items($_SESSION['cart']),
            'subtotal' => calculate_cart_subtotal($_SESSION['cart'], $pdo)
        ]);
        break;
    // --- KẾT THÚC LOGIC MỚI ---

    // Các case 'update', 'remove' sẽ được thêm ở các bước sau
    // ...

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}