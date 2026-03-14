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

if ($action === 'list') {
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? '';

    $where = "WHERE o.DELDATE >= ? AND o.DELDATE <= ?";
    $params = [$startDate, $endDate];
    $types = "ss";

    if ($status !== '') {
        $realStatus = $status === 'O' ? '' : $status;
        $where .= " AND o.STATUS = ?";
        $params[] = $realStatus;
        $types .= "s";
    }

    $sql = "SELECT o.*, c.ADDRESS AS CUST_ADDRESS FROM `del_orderlist` o LEFT JOIN `del_customer` c ON o.CUSTOMERCODE = c.CODE $where ORDER BY o.DELDATE DESC, o.ID DESC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($r = $result->fetch_assoc()) { $orders[] = $r; }
    $stmt->close();
    echo json_encode(['orders' => $orders]);

} elseif ($action === 'create') {
    $ordno = trim($_POST['ordno'] ?? '');
    $deldate = trim($_POST['deldate'] ?? '');
    $customercode = trim($_POST['customercode'] ?? '');
    $customer = trim($_POST['customer'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $distant = trim($_POST['distant'] ?? '');
    $retail = trim($_POST['retail'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $items = $_POST['items'] ?? [];

    if ($ordno === '' || $deldate === '' || $customercode === '') {
        echo json_encode(['error' => 'Order No, Date and Customer are required.']);
        exit;
    }

    // Auto-populate distance and commission from location master if not provided
    if ($location !== '' && ($distant === '' || $retail === '')) {
        $locStmt = $connect->prepare("SELECT `DISTANT`, `RETAIL` FROM `del_location` WHERE `NAME` = ? LIMIT 1");
        $locStmt->bind_param("s", $location);
        $locStmt->execute();
        $locResult = $locStmt->get_result();
        if ($locResult->num_rows > 0) {
            $locRow = $locResult->fetch_assoc();
            if ($distant === '') { $distant = $locRow['DISTANT']; }
            if ($retail === '') { $retail = $locRow['RETAIL']; }
        }
        $locStmt->close();
    }

    // Check duplicate
    $chk = $connect->prepare("SELECT ID FROM `del_orderlist` WHERE `ORDNO` = ? LIMIT 1");
    $chk->bind_param("s", $ordno);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Order number already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    // Insert order
    $stmt = $connect->prepare("INSERT INTO `del_orderlist` (`ORDNO`,`DELDATE`,`CUSTOMERCODE`,`CUSTOMER`,`LOCATION`,`DISTANT`,`RETAIL`,`REMARK`,`STATUS`) VALUES (?,?,?,?,?,?,?,?,'')");
    $stmt->bind_param("ssssssss", $ordno, $deldate, $customercode, $customer, $location, $distant, $retail, $remark);
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Failed to create order: ' . $connect->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Insert items
    if (is_array($items) && count($items) > 0) {
        $istmt = $connect->prepare("INSERT INTO `del_orderlistdesc` (`ORDERNO`,`PDESC`,`QTY`,`UOM`,`INSTALL`) VALUES (?,?,?,?,?)");
        foreach ($items as $item) {
            $pdesc = trim($item['desc'] ?? '');
            $qty = trim($item['qty'] ?? '');
            $uom = trim($item['uom'] ?? '');
            $install = trim($item['install'] ?? 'N');
            if ($pdesc !== '') {
                $istmt->bind_param("sssss", $ordno, $pdesc, $qty, $uom, $install);
                $istmt->execute();
            }
        }
        $istmt->close();
    }

    echo json_encode(['success' => 'Order created successfully.']);

} elseif ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }

    $stmt = $connect->prepare("SELECT o.*, c.HP AS CUST_PHONE, c.ADDRESS AS CUST_ADDRESS FROM `del_orderlist` o LEFT JOIN `del_customer` c ON o.CUSTOMERCODE = c.CODE WHERE o.ID = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) {
        $order = $r->fetch_assoc();
        $stmt->close();
        // Get items
        $istmt = $connect->prepare("SELECT * FROM `del_orderlistdesc` WHERE `ORDERNO` = ?");
        $istmt->bind_param("s", $order['ORDNO']);
        $istmt->execute();
        $ir = $istmt->get_result();
        $items = [];
        while ($row = $ir->fetch_assoc()) { $items[] = $row; }
        $istmt->close();
        echo json_encode(['order' => $order, 'items' => $items]);
    } else {
        $stmt->close();
        echo json_encode(['error' => 'Order not found.']);
    }

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $ordno = trim($_POST['ordno'] ?? '');
    $deldate = trim($_POST['deldate'] ?? '');
    $customercode = trim($_POST['customercode'] ?? '');
    $customer = trim($_POST['customer'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $distant = trim($_POST['distant'] ?? '');
    $retail = trim($_POST['retail'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $items = $_POST['items'] ?? [];

    if ($id <= 0 || $ordno === '' || $deldate === '' || $customercode === '') {
        echo json_encode(['error' => 'Order No, Date and Customer are required.']);
        exit;
    }

    // Auto-populate distance and commission from location master if not provided
    if ($location !== '' && ($distant === '' || $retail === '')) {
        $locStmt = $connect->prepare("SELECT `DISTANT`, `RETAIL` FROM `del_location` WHERE `NAME` = ? LIMIT 1");
        $locStmt->bind_param("s", $location);
        $locStmt->execute();
        $locResult = $locStmt->get_result();
        if ($locResult->num_rows > 0) {
            $locRow = $locResult->fetch_assoc();
            if ($distant === '') { $distant = $locRow['DISTANT']; }
            if ($retail === '') { $retail = $locRow['RETAIL']; }
        }
        $locStmt->close();
    }

    // Get current ordno for the record
    $chk = $connect->prepare("SELECT ORDNO FROM `del_orderlist` WHERE `ID` = ? LIMIT 1");
    $chk->bind_param("i", $id);
    $chk->execute();
    $cr = $chk->get_result();
    if ($cr->num_rows === 0) {
        echo json_encode(['error' => 'Order not found.']);
        $chk->close();
        exit;
    }
    $oldOrdno = $cr->fetch_assoc()['ORDNO'];
    $chk->close();

    // Check duplicate ordno if changed
    if ($ordno !== $oldOrdno) {
        $dup = $connect->prepare("SELECT ID FROM `del_orderlist` WHERE `ORDNO` = ? AND `ID` != ? LIMIT 1");
        $dup->bind_param("si", $ordno, $id);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'Order number already exists.']);
            $dup->close();
            exit;
        }
        $dup->close();
    }

    // Update order
    $stmt = $connect->prepare("UPDATE `del_orderlist` SET `ORDNO`=?, `DELDATE`=?, `CUSTOMERCODE`=?, `CUSTOMER`=?, `LOCATION`=?, `DISTANT`=?, `RETAIL`=?, `REMARK`=? WHERE `ID`=?");
    $stmt->bind_param("ssssssssi", $ordno, $deldate, $customercode, $customer, $location, $distant, $retail, $remark, $id);
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Failed to update order: ' . $connect->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Delete old items and re-insert
    $ds = $connect->prepare("DELETE FROM `del_orderlistdesc` WHERE `ORDERNO` = ?");
    $ds->bind_param("s", $oldOrdno);
    $ds->execute();
    $ds->close();

    if (is_array($items) && count($items) > 0) {
        $istmt = $connect->prepare("INSERT INTO `del_orderlistdesc` (`ORDERNO`,`PDESC`,`QTY`,`UOM`,`INSTALL`) VALUES (?,?,?,?,?)");
        foreach ($items as $item) {
            $pdesc = trim($item['desc'] ?? '');
            $qty = trim($item['qty'] ?? '');
            $uom = trim($item['uom'] ?? '');
            $install = trim($item['install'] ?? 'N');
            if ($pdesc !== '') {
                $istmt->bind_param("sssss", $ordno, $pdesc, $qty, $uom, $install);
                $istmt->execute();
            }
        }
        $istmt->close();
    }

    echo json_encode(['success' => 'Order updated successfully.']);

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }

    // Get ordno first for deleting items
    $stmt = $connect->prepare("SELECT ORDNO FROM `del_orderlist` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) {
        $ordno = $r->fetch_assoc()['ORDNO'];
        $stmt->close();
        // Delete items
        $ds = $connect->prepare("DELETE FROM `del_orderlistdesc` WHERE `ORDERNO` = ?");
        $ds->bind_param("s", $ordno);
        $ds->execute();
        $ds->close();
        // Delete order
        $do = $connect->prepare("DELETE FROM `del_orderlist` WHERE `ID` = ?");
        $do->bind_param("i", $id);
        $do->execute();
        $do->close();
        echo json_encode(['success' => 'Order deleted.']);
    } else {
        $stmt->close();
        echo json_encode(['error' => 'Order not found.']);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
