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

if ($action === 'list') {
    $status = $_POST['status'] ?? '';

    // Build query
    $where = "WHERE o.STATUS = ?";
    $params = [$status];
    $types = "s";

    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    $where .= " AND o.DELDATE >= ? AND o.DELDATE <= ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";

    $sql = "SELECT o.*, c.HP AS CUSTOMER_PHONE, c.ADDRESS AS CUST_ADDRESS FROM `del_orderlist` o LEFT JOIN `del_customer` c ON o.CUSTOMERCODE = c.CODE $where ORDER BY o.DELDATE DESC, o.ID DESC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($r = $result->fetch_assoc()) {
        $orders[] = $r;
    }
    $stmt->close();

    // Get counts for all tabs within the date range
    $counts = ['order' => 0, 'assigned' => 0, 'done' => 0, 'completed' => 0];
    $cntStmt = $connect->prepare("SELECT STATUS, COUNT(*) AS cnt FROM `del_orderlist` WHERE DELDATE >= ? AND DELDATE <= ? GROUP BY STATUS");
    $cntStmt->bind_param("ss", $startDate, $endDate);
    $cntStmt->execute();
    $cntResult = $cntStmt->get_result();
    while ($cr = $cntResult->fetch_assoc()) {
        $s = $cr['STATUS'];
        if ($s === '') $counts['order'] = (int)$cr['cnt'];
        elseif ($s === 'A') $counts['assigned'] = (int)$cr['cnt'];
        elseif ($s === 'D') $counts['done'] = (int)$cr['cnt'];
        elseif ($s === 'C') $counts['completed'] = (int)$cr['cnt'];
    }
    $cntStmt->close();

    echo json_encode(['orders' => $orders, 'counts' => $counts]);

} elseif ($action === 'complete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID.']);
        exit;
    }
    $stmt = $connect->prepare("UPDATE `del_orderlist` SET `STATUS` = 'C' WHERE `ID` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Order marked as completed.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'images') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT IMG1, IMG2, IMG3, ORDNO FROM `del_orderlist` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $ordno = $row['ORDNO'];

        // Ensure INSTALL_IMG column exists
        $connect->query("ALTER TABLE `del_orderlistdesc` ADD COLUMN `INSTALL_IMG` VARCHAR(200) NOT NULL DEFAULT '' AFTER `INSTALL`");

        // Fetch installation photos
        $instStmt = $connect->prepare("SELECT `PDESC`, `INSTALL_IMG` FROM `del_orderlistdesc` WHERE `ORDERNO` = ? AND `INSTALL` = 'Y' AND `INSTALL_IMG` != '' ORDER BY `PDESC` ASC");
        $instStmt->bind_param("s", $ordno);
        $instStmt->execute();
        $instResult = $instStmt->get_result();
        $installPhotos = [];
        while ($ir = $instResult->fetch_assoc()) {
            $installPhotos[] = ['PDESC' => $ir['PDESC'], 'INSTALL_IMG' => $ir['INSTALL_IMG']];
        }
        $instStmt->close();

        $row['install_photos'] = $installPhotos;
        unset($row['ORDNO']);
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Order not found.']);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
