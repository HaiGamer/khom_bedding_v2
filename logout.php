<?php
// Luôn bắt đầu session trước khi làm việc với nó
session_start();

// Hủy tất cả các biến session
$_SESSION = array();

// Nếu có cookie session, hãy xóa nó đi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Cuối cùng, hủy session
session_destroy();

// Chuyển hướng người dùng về trang chủ
header("Location: /");
exit();