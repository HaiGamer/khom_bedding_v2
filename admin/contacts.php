<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Tin nhắn Liên hệ";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// Logic lọc và phân trang
$status_filter = $_GET['status'] ?? 'all';
$limit = 20;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;

// Xây dựng câu truy vấn
$sql = "SELECT * FROM contacts";
$params = [];
if (in_array($status_filter, ['new', 'read', 'replied'])) {
    $sql .= " WHERE status = ?";
    $params[] = $status_filter;
}
$sql .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="p-4">
   <h1 class="mb-4">Hộp thư Liên hệ</h1>
   <div class="mb-4">
      <a href="?status=all" class="btn btn-sm <?= $status_filter == 'all' ? 'btn-dark' : 'btn-outline-dark' ?>">Tất
         cả</a>
      <a href="?status=new" class="btn btn-sm <?= $status_filter == 'new' ? 'btn-dark' : 'btn-outline-dark' ?>">Mới</a>
      <a href="?status=read" class="btn btn-sm <?= $status_filter == 'read' ? 'btn-dark' : 'btn-outline-dark' ?>">Đã
         đọc</a>
      <a href="?status=replied"
         class="btn btn-sm <?= $status_filter == 'replied' ? 'btn-dark' : 'btn-outline-dark' ?>">Đã trả lời</a>
   </div>

   <div class="card">
      <div class="card-body" id="contacts-container">
         <?php if (empty($contacts)): ?>
         <p class="text-center text-muted p-4">Không có tin nhắn nào trong mục này.</p>
         <?php else: ?>
         <?php foreach ($contacts as $contact): ?>
         <div class="contact-message-item p-3 border-bottom" id="contact-row-<?= $contact['id'] ?>">
            <div class="d-flex justify-content-between">
               <div>
                  <strong class="me-2"><?= htmlspecialchars($contact['name']) ?></strong>
                  <a
                     href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a>
                  <?php if($contact['phone']): ?>
                  <span class="text-muted">- <?= htmlspecialchars($contact['phone']) ?></span>
                  <?php endif; ?>
                  <p class="mb-1 fw-bold mt-1"><?= htmlspecialchars($contact['subject']) ?></p>
               </div>
               <small class="text-muted text-nowrap"><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></small>
            </div>
            <p class="mb-2 mt-2 bg-light p-3 rounded"><?= nl2br(htmlspecialchars($contact['message'])) ?></p>
            <div class="mt-2 action-buttons">
               <?php if ($contact['status'] === 'new'): ?>
               <button class="btn btn-sm btn-outline-success btn-action" data-action="mark_read"
                  data-id="<?= $contact['id'] ?>"><i class="bi bi-check-lg"></i> Đã đọc</button>
               <?php else: ?>
               <button class="btn btn-sm btn-outline-secondary btn-action" data-action="mark_new"
                  data-id="<?= $contact['id'] ?>"><i class="bi bi-envelope"></i> Đánh dấu là mới</button>
               <?php endif; ?>
               <button class="btn btn-sm btn-outline-danger btn-action" data-action="delete"
                  data-id="<?= $contact['id'] ?>"><i class="bi bi-trash"></i> Xóa</button>
            </div>
         </div>
         <?php endforeach; ?>
         <?php endif; ?>
      </div>
   </div>
</div>

<script src="/admin/assets/js/admin-contacts.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>