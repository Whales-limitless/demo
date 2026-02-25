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

    $sql = "SELECT o.* FROM `del_orderlist` o $where ORDER BY o.DELDATE DESC, o.ID DESC";
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
        $istmt = $connect->prepare("INSERT INTO `del_orderlistdesc` (`ORDERNO`,`PDESC`,`QTY`,`UOM`) VALUES (?,?,?,?)");
        foreach ($items as $item) {
            $pdesc = trim($item['desc'] ?? '');
            $qty = trim($item['qty'] ?? '');
            $uom = trim($item['uom'] ?? '');
            if ($pdesc !== '') {
                $istmt->bind_param("ssss", $ordno, $pdesc, $qty, $uom);
                $istmt->execute();
            }
        }
        $istmt->close();
    }

    echo json_encode(['success' => 'Order created successfully.']);

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
