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
    $stmt = $connect->prepare("SELECT * FROM `del_driver` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) { echo json_encode($result->fetch_assoc()); }
    else { echo json_encode(['error' => 'Driver not found.']); }
    $stmt->close();

} elseif ($action === 'create') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $username === '') { echo json_encode(['error' => 'Name and username are required.']); exit; }

    $stmt = $connect->prepare("INSERT INTO `del_driver` (`CODE`,`NAME`,`EMAIL`,`ADDRESS`,`POSTCODE`,`STATE`,`AREA`,`HP`,`USERNAME`,`PASSWORD`) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssss", $code, $name, $email, $address, $postcode, $state, $area, $phone, $username, $password);
    if ($stmt->execute()) { echo json_encode(['success' => 'Driver created successfully.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($id <= 0 || $name === '') { echo json_encode(['error' => 'Invalid data.']); exit; }

    $stmt = $connect->prepare("UPDATE `del_driver` SET `NAME`=?,`EMAIL`=?,`ADDRESS`=?,`POSTCODE`=?,`STATE`=?,`AREA`=?,`HP`=?,`USERNAME`=?,`PASSWORD`=? WHERE `ID`=?");
    $stmt->bind_param("sssssssssi", $name, $email, $address, $postcode, $state, $area, $phone, $username, $password, $id);
    if ($stmt->execute()) { echo json_encode(['success' => 'Driver updated successfully.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("DELETE FROM `del_driver` WHERE `ID` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { echo json_encode(['success' => 'Driver deleted.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'list') {
    $list = [];
    $result = $connect->query("SELECT `ID`, `CODE`, `NAME` FROM `del_driver` ORDER BY `NAME` ASC");
    if ($result) { while ($r = $result->fetch_assoc()) { $list[] = $r; } }
    echo json_encode($list);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
