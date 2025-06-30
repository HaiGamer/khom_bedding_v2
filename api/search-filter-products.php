<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/db_connect.php';

/**
 * Hàm render thẻ sản phẩm (tái sử dụng).
 * @param array $product Mảng chứa thông tin sản phẩm.
 * @return string Chuỗi HTML của thẻ sản phẩm.
 */
function render_product_card_api($product) {
    $price_formatted = number_format($product['price'], 0, ',', '.') . 'đ';
    $original_price_formatted = $product['original_price'] ? number_format($product['original_price'], 0, ',', '.') . 'đ' : '';
    $sale_percentage = 0;
    if ($product['original_price'] && $product['price'] < $product['original_price']) {
        $sale_percentage = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
    }

    $html = '<div class="col-6 col-md-4 mb-4">';
    $html .= '<div class="product-card h-100">';
    $html .= '    <div class="product-card-img">';
    $html .= '        <a href="/san-pham/'. htmlspecialchars($product['slug']) .'.html">';
    $html .= '            <img src="'. htmlspecialchars($product['image_url'] ?? '/assets/images/placeholder.png') .'" alt="'. htmlspecialchars($product['name']) .'">';
    $html .= '        </a>';
    if ($sale_percentage > 0) {
        $html .= '        <span class="product-card-sale">-' . $sale_percentage . '%</span>';
    }
    $html .= '        <div class="product-card-actions">';
    $html .= '            <a href="#" class="btn-action btn-add-to-cart" data-slug="'. htmlspecialchars($product['slug']) .'" title="Thêm vào giỏ hàng"><i class="bi bi-cart-plus"></i></a>';
    $html .= '            <a href="/san-pham/'. htmlspecialchars($product['slug']) .'.html" class="btn-action" title="Xem chi tiết"><i class="bi bi-eye"></i></a>';
    $html .= '        </div>';
    $html .= '    </div>';
    $html .= '    <div class="product-card-body">';
    $html .= '        <h3 class="product-card-title"><a href="/san-pham/'. htmlspecialchars($product['slug']) .'.html">'. htmlspecialchars($product['name']) .'</a></h3>';
    $html .= '        <div class="product-card-price">';
    $html .= '            <span class="price-sale">'. $price_formatted .'</span>';
    if ($original_price_formatted) {
        $html .= '            <span class="price-original">'. $original_price_formatted .'</span>';
    }
    $html .= '        </div>';
    $html .= '    </div>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

try {
    // --- 1. LẤY TẤT CẢ CÁC THAM SỐ ---
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $search_term = trim($_GET['search_term'] ?? '');
    $price_range = $_GET['price_range'] ?? '';
    $attributes = isset($_GET['attributes']) && is_array($_GET['attributes']) ? $_GET['attributes'] : [];
    $sort_by = $_GET['sort_by'] ?? 'default';

    if (empty($search_term)) {
        echo json_encode(['product_html' => '<div class="col-12"><p class="text-center">Vui lòng nhập từ khóa để tìm kiếm.</p></div>', 'pagination_html' => '', 'total_products' => 0]);
        exit;
    }
    
    $products_per_page = 9;
    $offset = ($page - 1) * $products_per_page;

    // --- 2. XÂY DỰNG CÁC THÀNH PHẦN CỦA CÂU TRUY VẤN ---
    $sql_select = "SELECT DISTINCT p.id, p.name, p.slug, pv.price, pv.original_price, pi.image_url, p.created_at, pv.id as variant_id";
    $sql_from = " FROM products p 
                  JOIN product_variants pv ON p.id = pv.product_id 
                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_featured = 1";
    $sql_where = " WHERE pv.is_default = 1 AND (p.name LIKE ? OR p.description LIKE ? OR pv.sku LIKE ?) ";
    $params = ["%{$search_term}%", "%{$search_term}%", "%{$search_term}%"];

    // Áp dụng các bộ lọc phụ
    if ($price_range) {
        list($min_price, $max_price) = explode('-', $price_range);
        if ($max_price === 'Infinity') {
            $sql_where .= " AND pv.price >= ? ";
            $params[] = (float)$min_price;
        } else {
            $sql_where .= " AND pv.price BETWEEN ? AND ? ";
            $params[] = (float)$min_price;
            $params[] = (float)$max_price;
        }
    }
    if (!empty($attributes)) {
        $sql_from .= " JOIN variant_values vv ON pv.id = vv.variant_id ";
        $placeholders = implode(',', array_fill(0, count($attributes), '?'));
        $sql_where .= " AND vv.attribute_value_id IN ($placeholders) ";
        $params = array_merge($params, array_values($attributes));
    }

    // --- 3. THỰC THI TRUY VẤN ---

    // Đếm tổng số sản phẩm
    $count_sql = "SELECT COUNT(DISTINCT p.id) " . $sql_from . $sql_where;
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_products = $stmt_count->fetchColumn();
    $total_pages = ceil($total_products / $products_per_page);

    // Sắp xếp
    $sql_order_by = " ORDER BY ";
    switch ($sort_by) {
        case 'price_asc': $sql_order_by .= "pv.price ASC"; break;
        case 'price_desc': $sql_order_by .= "pv.price DESC"; break;
        case 'name_asc': $sql_order_by .= "p.name ASC"; break;
        case 'name_desc': $sql_order_by .= "p.name DESC"; break;
        default: $sql_order_by .= "p.created_at DESC"; break;
    }

    // Lấy sản phẩm cho trang hiện tại
    $product_sql = $sql_select . $sql_from . $sql_where . " GROUP BY p.id " . $sql_order_by . " LIMIT ? OFFSET ?";
    $product_params = $params;
    $product_params[] = $products_per_page;
    $product_params[] = $offset;

    $stmt_products = $pdo->prepare($product_sql);
    $stmt_products->execute($product_params);
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. TẠO HTML VÀ TRẢ VỀ JSON ---
    $product_html = '';
    if ($products) {
        foreach ($products as $product) {
            $product_html .= render_product_card_api($product);
        }
    } else {
        $product_html = '<div class="col-12"><p class="text-center mt-4">Không tìm thấy sản phẩm nào phù hợp.</p></div>';
    }

    $pagination_html = '';
    if ($total_pages > 1) {
        $pagination_html .= '<ul class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = $i == $page ? 'active' : '';
            $pagination_html .= "<li class='page-item {$active_class}'><a class='page-link' href='#' data-page='{$i}'>{$i}</a></li>";
        }
        $pagination_html .= '</ul>';
    }

    echo json_encode([
        'product_html' => $product_html,
        'pagination_html' => $pagination_html,
        'total_products' => $total_products
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("API Search Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database Error', 'message' => 'Đã có lỗi xảy ra với cơ sở dữ liệu.']);
}