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

if ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT * FROM `supplier` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Record not found.']);
    }
    $stmt->close();

} elseif ($action === 'list') {
    // Return all active suppliers (for dropdowns)
    $status = $_POST['status'] ?? 'ACTIVE';
    $stmt = $connect->prepare("SELECT `id`, `code`, `name` FROM `supplier` WHERE `status` = ? ORDER BY `name` ASC");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = [];
    while ($r = $result->fetch_assoc()) {
        $list[] = $r;
    }
    echo json_encode($list);
    $stmt->close();

} elseif ($action === 'create') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment = trim($_POST['payment_terms'] ?? '');
    $lead = intval($_POST['lead_time_days'] ?? 0);
    $status = trim($_POST['status'] ?? 'ACTIVE');

    if ($code === '' || $name === '') {
        echo json_encode(['error' => 'Code and name are required.']);
        exit;
    }

    // Check duplicate code
    $chk = $connect->prepare("SELECT `id` FROM `supplier` WHERE `code` = ? LIMIT 1");
    $chk->bind_param("s", $code);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Code already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    $stmt = $connect->prepare("INSERT INTO `supplier` (`code`,`name`,`contact_person`,`phone`,`email`,`address`,`payment_terms`,`lead_time_days`,`status`) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssis", $code, $name, $contact, $phone, $email, $address, $payment, $lead, $status);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Created successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to create: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment = trim($_POST['payment_terms'] ?? '');
    $lead = intval($_POST['lead_time_days'] ?? 0);
    $status = trim($_POST['status'] ?? 'ACTIVE');

    if ($id <= 0 || $name === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `supplier` SET `name`=?,`contact_person`=?,`phone`=?,`email`=?,`address`=?,`payment_terms`=?,`lead_time_days`=?,`status`=? WHERE `id`=?");
    $stmt->bind_param("ssssssisi", $name, $contact, $phone, $email, $address, $payment, $lead, $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Updated successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to update: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'delete') {
    // Soft delete: set status to INACTIVE
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `supplier` SET `status` = 'INACTIVE' WHERE `id` = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Deactivated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
