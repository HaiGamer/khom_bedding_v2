<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit('Unauthorized'); }

require_once __DIR__ . '/../core/db_connect.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

if (empty($addresses)) {
    echo '<div class="col-12"><p>Bạn chưa có địa chỉ nào được lưu.</p></div>';
} else {
    foreach ($addresses as $address) {
        $default_badge = $address['is_default'] ? '<span class="badge bg-primary float-end">Mặc định</span>' : '';
        $default_border = $address['is_default'] ? 'border-primary' : '';
        $set_default_link = !$address['is_default'] ? '<a href="#" class="card-link btn-set-default">Đặt làm mặc định</a>' : '';

        echo <<<HTML
        <div class="col-md-6 mb-4">
            <div class="card h-100 address-card {$default_border}"
                 data-id="{$address['id']}"
                 data-name="{$address['full_name']}"
                 data-phone="{$address['phone_number']}"
                 data-address="{$address['address_line']}"
                 data-default="{$address['is_default']}">
                <div class="card-body">
                    {$default_badge}
                    <h5 class="card-title">{$address['full_name']}</h5>
                    <p class="card-text mb-1">{$address['address_line']}</p>
                    <p class="card-text text-muted">SĐT: {$address['phone_number']}</p>
                    <div class="address-actions pt-2 mt-2 border-top">
                        <a href="#" class="card-link btn-edit-address">Sửa</a>
                        <a href="#" class="card-link text-danger btn-delete-address">Xóa</a>
                        {$set_default_link}
                    </div>
                </div>
            </div>
        </div>
HTML;
    }
}