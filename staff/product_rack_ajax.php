<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once 'dbconnection.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'update_rack') {
    $id = intval($_POST['id'] ?? 0);
    $rack = trim($_POST['rack'] ?? '');
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        exit;
    }
    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `rack` = ? WHERE `id` = ?");
    $stmt->bind_param("si", $rack, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'rack' => $rack]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
    $stmt->close();

} elseif ($action === 'rack_list') {
    $result = $connect->query("SELECT `id`, `code`, `description` FROM `rack` WHERE `status`='ACTIVE' ORDER BY `code` ASC");
    $racks = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $racks[] = $r;
        }
    }
    echo json_encode($racks);

} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
