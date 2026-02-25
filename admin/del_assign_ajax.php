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

if ($action === 'load') {
    $driverCode = trim($_POST['driver_code'] ?? '');

    // Available orders (unassigned)
    $available = [];
    $stmt = $connect->prepare("SELECT o.* FROM `del_orderlist` o WHERE o.DRIVERCODE = '' AND o.STATUS = '' ORDER BY o.DELDATE DESC, o.ID DESC");
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) { $available[] = $row; }
    $stmt->close();

    // Assigned to this driver
    $assigned = [];
    $stmt2 = $connect->prepare("SELECT o.* FROM `del_orderlist` o WHERE o.DRIVERCODE = ? AND o.STATUS = 'A' ORDER BY o.DELDATE DESC, o.ID DESC");
    $stmt2->bind_param("s", $driverCode);
    $stmt2->execute();
    $r2 = $stmt2->get_result();
    while ($row = $r2->fetch_assoc()) { $assigned[] = $row; }
    $stmt2->close();

    echo json_encode(['available' => $available, 'assigned' => $assigned]);

} elseif ($action === 'assign') {
    $id = intval($_POST['id'] ?? 0);
    $driverCode = trim($_POST['driver_code'] ?? '');

    if ($id <= 0 || $driverCode === '') { echo json_encode(['error' => 'Invalid data.']); exit; }

    // Get driver name
    $driverName = '';
    $ds = $connect->prepare("SELECT NAME FROM `del_driver` WHERE `CODE` = ? LIMIT 1");
    $ds->bind_param("s", $driverCode);
    $ds->execute();
    $dr = $ds->get_result();
    if ($dr->num_rows > 0) { $driverName = $dr->fetch_assoc()['NAME']; }
    $ds->close();

    $stmt = $connect->prepare("UPDATE `del_orderlist` SET `DRIVERCODE` = ?, `DRIVER` = ?, `STATUS` = 'A' WHERE `ID` = ?");
    $stmt->bind_param("ssi", $driverCode, $driverName, $id);
    if ($stmt->execute()) { echo json_encode(['success' => 'Order assigned.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} elseif ($action === 'unassign') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }

    $empty = '';
    $stmt = $connect->prepare("UPDATE `del_orderlist` SET `DRIVERCODE` = ?, `DRIVER` = ?, `STATUS` = '' WHERE `ID` = ?");
    $stmt->bind_param("ssi", $empty, $empty, $id);
    if ($stmt->execute()) { echo json_encode(['success' => 'Order unassigned.']); }
    else { echo json_encode(['error' => 'Failed: ' . $connect->error]); }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
