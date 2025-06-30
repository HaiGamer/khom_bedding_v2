<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { exit(json_encode(['success' => false])); }
require_once __DIR__ . '/../../../core/db_connect.php';

// Lấy ngày bắt đầu và kết thúc từ request, nếu không có thì mặc định 7 ngày trước
$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('-6 days'));
$end_date = $_GET['end'] ?? date('Y-m-d');

// Lấy dữ liệu doanh thu từ CSDL
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as order_date, SUM(order_total) as daily_revenue
    FROM orders
    WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY order_date
    ORDER BY order_date ASC
");
$stmt->execute([$start_date, $end_date]);
$revenue_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Xử lý dữ liệu để đảm bảo mọi ngày trong khoảng đều có giá trị (0 nếu không có doanh thu)
$chart_labels = [];
$chart_values = [];
$period = new DatePeriod(
     new DateTime($start_date),
     new DateInterval('P1D'),
     new DateTime($end_date . ' +1 day') // Thêm 1 ngày để bao gồm cả ngày kết thúc
);

foreach ($period as $date) {
    $day_key = $date->format('Y-m-d');
    $chart_labels[] = $date->format('d/m');
    $chart_values[] = (float)($revenue_data[$day_key] ?? 0);
}

echo json_encode([
    'success' => true,
    'labels' => $chart_labels,
    'values' => $chart_values
]);