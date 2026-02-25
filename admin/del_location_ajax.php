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
    $stmt = $connect->prepare("SELECT * FROM `del_location` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) { echo json_encode($result->fetch_assoc()); }
    else { echo json_encode(['error' => 'Location not found.']); }
    $stmt->close();

} elseif ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $distant = trim($_POST['distant'] ?? '');
    $retail = trim($_POST['retail'] ?? '');
    if ($name === '') { echo json_encode(['error' => 'Name is required.']); exit; }
    $stmt = $connect->prepare("INSERT INTO `del_location` (`NAME`,`POSTCODE`,`DISTANT`,`RETAIL`) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $name, $postcode, $distant, $retail);
    if ($stmt->execute()) { echo json_encode(['success' => 'Location created successfully.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $distant = trim($_POST['distant'] ?? '');
    $retail = trim($_POST['retail'] ?? '');
    if ($id <= 0 || $name === '') { echo json_encode(['error' => 'Invalid data.']); exit; }
    $stmt = $connect->prepare("UPDATE `del_location` SET `NAME`=?,`POSTCODE`=?,`DISTANT`=?,`RETAIL`=? WHERE `ID`=?");
    $stmt->bind_param("ssssi", $name, $postcode, $distant, $retail, $id);
    if ($stmt->execute()) { echo json_encode(['success' => 'Location updated successfully.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("DELETE FROM `del_location` WHERE `ID` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { echo json_encode(['success' => 'Location deleted.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'list') {
    $list = [];
    $result = $connect->query("SELECT * FROM `del_location` ORDER BY `NAME` ASC");
    if ($result) { while ($r = $result->fetch_assoc()) { $list[] = $r; } }
    echo json_encode($list);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
