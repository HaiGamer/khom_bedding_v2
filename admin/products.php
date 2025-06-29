<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Quản lý Sản phẩm";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// --- LOGIC LẤY DỮ LIỆU, TÌM KIẾM VÀ PHÂN TRANG ---

// 1. Phân trang
$limit = 10; // Số sản phẩm trên mỗi trang
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;

// 2. Tìm kiếm
$search_term = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

// 3. Xây dựng câu truy vấn
$sql = "
    SELECT 
        p.id, p.name, p.created_at, c.name as category_name,
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_featured = 1 LIMIT 1) as featured_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
";
$count_sql = "SELECT COUNT(p.id) FROM products p ";
$params = [];

if ($search_term) {
    $sql .= " WHERE p.name LIKE ? ";
    $count_sql .= " WHERE p.name LIKE ? ";
    $params[] = "%{$search_term}%";
}

$sql .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Thực thi câu truy vấn lấy sản phẩm
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Thực thi câu truy vấn đếm tổng số sản phẩm (cho phân trang)
$count_params = $search_term ? ["%{$search_term}%"] : [];
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Quản lý Sản phẩm</h1>
      <a href="/admin/product-edit.php" class="btn btn-primary">
         <i class="bi bi-plus-circle-fill me-2"></i>Thêm sản phẩm mới
      </a>
   </div>

   <div class="card mb-4">
      <div class="card-body">
         <form method="GET" class="d-flex">
            <input type="text" class="form-control me-2" name="search" placeholder="Nhập tên sản phẩm để tìm..."
               value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn btn-success">Tìm kiếm</button>
         </form>
      </div>
   </div>

   <div class="table-responsive">
      <table class="table table-hover align-middle">
         <thead class="table-light">
            <tr>
               <th>ID</th>
               <th style="width: 80px;">Ảnh</th>
               <th>Tên sản phẩm</th>
               <th>Danh mục</th>
               <th>Ngày tạo</th>
               <th class="text-end">Hành động</th>
            </tr>
         </thead>
         <tbody>
            <?php if (empty($products)): ?>
            <tr>
               <td colspan="6" class="text-center">Không tìm thấy sản phẩm nào.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($products as $product): ?>
            <tr>
               <th scope="row"><?php echo $product['id']; ?></th>
               <td>
                  <img
                     src="<?php echo htmlspecialchars($product['featured_image'] ?? '/assets/images/placeholder.png'); ?>"
                     class="admin-product-thumbnail" alt="">
               </td>
               <td><?php echo htmlspecialchars($product['name']); ?></td>
               <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
               <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
               <td class="text-end">
                  <a href="/admin/product-edit.php?id=<?php echo $product['id']; ?>"
                     class="btn btn-sm btn-outline-secondary">Sửa</a>
                  <a href="#" class="btn btn-sm btn-outline-danger">Xóa</a>
               </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
         </tbody>
      </table>
   </div>

   <?php if ($total_pages > 1): ?>
   <nav class="mt-4">
      <ul class="pagination justify-content-center">
         <?php for ($i = 1; $i <= $total_pages; $i++): ?>
         <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link"
               href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search_term); ?>"><?php echo $i; ?></a>
         </li>
         <?php endfor; ?>
      </ul>
   </nav>
   <?php endif; ?>
</div>

<?php
include __DIR__ . '/templates/footer.php';
?>