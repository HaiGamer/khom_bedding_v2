<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { exit(json_encode(['success' => false, 'message' => 'Unauthorized'])); }
require_once __DIR__ . '/../../../core/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'add_attribute':
            $stmt = $pdo->prepare("INSERT INTO attributes (name) VALUES (?)");
            $stmt->execute([$data['name']]);
            break;
        case 'edit_attribute':
            $stmt = $pdo->prepare("UPDATE attributes SET name = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['id']]);
            break;
        case 'delete_attribute':
            $stmt = $pdo->prepare("DELETE FROM attributes WHERE id = ?");
            $stmt->execute([$data['id']]);
            break;
        case 'add_value':
            $stmt = $pdo->prepare("INSERT INTO attribute_values (attribute_id, value) VALUES (?, ?)");
            $stmt->execute([$data['attribute_id'], $data['value']]);
            break;
        case 'delete_value':
            $stmt = $pdo->prepare("DELETE FROM attribute_values WHERE id = ?");
            $stmt->execute([$data['id']]);
            break;
        default: throw new Exception("Hành động không hợp lệ.");
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}