<?php
session_start();
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
    $stmt = $connect->prepare("SELECT * FROM `del_customer` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) { echo json_encode($result->fetch_assoc()); }
    else { echo json_encode(['error' => 'Customer not found.']); }
    $stmt->close();

} elseif ($action === 'create') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') { echo json_encode(['error' => 'Name is required.']); exit; }

    $stmt = $connect->prepare("INSERT INTO `del_customer` (`CODE`,`NAME`,`LOCATION`,`ADDRESS`,`EMAIL`,`HP`) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $code, $name, $location, $address, $email, $phone);
    if ($stmt->execute()) { echo json_encode(['success' => 'Customer created successfully.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($id <= 0 || $name === '') { echo json_encode(['error' => 'Invalid data.']); exit; }

    $stmt = $connect->prepare("UPDATE `del_customer` SET `NAME`=?,`LOCATION`=?,`ADDRESS`=?,`EMAIL`=?,`HP`=? WHERE `ID`=?");
    $stmt->bind_param("sssssi", $name, $location, $address, $email, $phone, $id);
    if ($stmt->execute()) { echo json_encode(['success' => 'Customer updated successfully.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("DELETE FROM `del_customer` WHERE `ID` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { echo json_encode(['success' => 'Customer deleted.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
