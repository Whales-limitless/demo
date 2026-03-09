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

// Support both form-encoded and JSON body
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    if ($jsonInput) { $_POST = array_merge($_POST, $jsonInput); }
}

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

} elseif ($action === 'get_items') {
    $ordno = trim($_POST['ordno'] ?? '');
    if ($ordno === '') { echo json_encode(['error' => 'Invalid order number.']); exit; }

    $items = [];
    $stmt = $connect->prepare("SELECT * FROM `del_orderlistdesc` WHERE `ORDERNO` = ? ORDER BY `PDESC` ASC");
    $stmt->bind_param("s", $ordno);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) { $items[] = $row; }
    $stmt->close();

    echo json_encode(['items' => $items]);

} elseif ($action === 'assign') {
    $id = intval($_POST['id'] ?? 0);
    $driverCode = trim($_POST['driver_code'] ?? '');

    if ($id <= 0 || $driverCode === '') { echo json_encode(['error' => 'Invalid data.']); exit; }

    // Get driver name from sysfile
    $driverName = '';
    $ds = $connect->prepare("SELECT `USER_NAME` AS `NAME` FROM `sysfile` WHERE `USERNAME` = ? AND `TYPE` = 'D' LIMIT 1");
    $ds->bind_param("s", $driverCode);
    $ds->execute();
    $dr = $ds->get_result();
    if ($dr->num_rows > 0) { $driverName = $dr->fetch_assoc()['NAME']; }
    $ds->close();

    // Update installation flags for items
    $installItems = $_POST['install_items'] ?? [];
    if (is_array($installItems) && count($installItems) > 0) {
        $ustmt = $connect->prepare("UPDATE `del_orderlistdesc` SET `INSTALL` = ? WHERE `ID` = ?");
        foreach ($installItems as $item) {
            $itemId = intval($item['id'] ?? 0);
            $install = ($item['install'] ?? '') === 'Y' ? 'Y' : '';
            if ($itemId > 0) {
                $ustmt->bind_param("si", $install, $itemId);
                $ustmt->execute();
            }
        }
        $ustmt->close();
    }

    // Assign order to driver
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
