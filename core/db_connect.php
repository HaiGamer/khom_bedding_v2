<?php
// Thông tin kết nối cơ sở dữ liệu
define('DB_HOST', 'localhost');    // Thường là localhost
define('DB_USER', 'root');         // Tên người dùng CSDL của bạn
define('DB_PASS', '');             // Mật khẩu CSDL của bạn
define('DB_NAME', 'khom_bedding'); // Tên CSDL bạn đã tạo

// Thiết lập DSN (Data Source Name)
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

// Thiết lập các tùy chọn cho PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Bật báo lỗi (quan trọng khi phát triển)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Mặc định trả về mảng kết hợp
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt chế độ mô phỏng prepared statements
];

try {
    // Tạo một đối tượng PDO mới
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // Nếu kết nối thất bại, hiển thị lỗi và dừng chương trình
    // Trong thực tế, bạn nên ghi log lỗi thay vì hiển thị cho người dùng
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>