<?php
require_once __DIR__ . '/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once 'dbconnection.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'update_name') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        exit;
    }
    if ($name === '') {
        echo json_encode(['success' => false, 'error' => 'Product name cannot be empty']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `name` = ? WHERE `id` = ?");
    $stmt->bind_param("si", $name, $id);
    $updated = $stmt->execute();
    $stmt->close();

    if ($updated) {
        echo json_encode(['success' => true, 'name' => $name]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
