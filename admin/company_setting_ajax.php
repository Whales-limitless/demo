<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $perm = $_SESSION['admin_permission'] ?? 'FULL';
    if ($perm !== 'FULL') {
        echo json_encode(['error' => 'You do not have permission to edit company settings.']);
        exit;
    }

    $name  = trim($_POST['business_name'] ?? '');
    $reg   = trim($_POST['business_register_no'] ?? '');
    $a1    = trim($_POST['address_line1'] ?? '');
    $a2    = trim($_POST['address_line2'] ?? '');
    $a3    = trim($_POST['address_line3'] ?? '');
    $tel   = trim($_POST['tel_no'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '') {
        echo json_encode(['error' => 'Business name is required.']);
        exit;
    }

    // Make sure singleton row exists
    $check = $connect->query("SELECT `id` FROM `company_setting` WHERE `id` = 1 LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        $connect->query("INSERT INTO `company_setting` (`id`) VALUES (1)");
    }

    $stmt = $connect->prepare("UPDATE `company_setting` SET
        `business_name` = ?,
        `business_register_no` = ?,
        `address_line1` = ?,
        `address_line2` = ?,
        `address_line3` = ?,
        `tel_no` = ?,
        `email` = ?
        WHERE `id` = 1");
    $stmt->bind_param("sssssss", $name, $reg, $a1, $a2, $a3, $tel, $email);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => 'Company setting saved.']);
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(['error' => 'Failed: ' . $err]);
    }
    exit;
}

echo json_encode(['error' => 'Unknown action.']);
