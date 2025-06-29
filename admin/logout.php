<?php
session_start();
// Hủy tất cả các biến session
$_SESSION = array();
// Hủy session
session_destroy();
// Chuyển hướng về trang đăng nhập admin
header("Location: /admin/login.php");
exit();