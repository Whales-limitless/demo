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
    $search = trim($_POST['search'] ?? '');
    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 50);
    if ($limit <= 0 || $limit > 200) $limit = 50;
    if ($offset < 0) $offset = 0;

    $where = "";
    $params = [];
    $types = "";

    if ($search !== '') {
        $like = '%' . $search . '%';
        $where = "WHERE (`CODE` LIKE ? OR `NAME` LIKE ? OR `LOCATION` LIKE ? OR `ADDRESS` LIKE ? OR `HP` LIKE ? OR `EMAIL` LIKE ?)";
        $params = [$like, $like, $like, $like, $like, $like];
        $types = "ssssss";
    }

    // Get total count
    $cntSql = "SELECT COUNT(*) AS total FROM `del_customer` $where";
    $cntStmt = $connect->prepare($cntSql);
    if ($types !== '') $cntStmt->bind_param($types, ...$params);
    $cntStmt->execute();
    $total = $cntStmt->get_result()->fetch_assoc()['total'];
    $cntStmt->close();

    // Get page
    $sql = "SELECT * FROM `del_customer` $where ORDER BY `NAME` ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $customers = [];
    while ($r = $result->fetch_assoc()) { $customers[] = $r; }
    $stmt->close();

    echo json_encode(['customers' => $customers, 'total' => (int)$total, 'offset' => $offset, 'limit' => $limit]);

} elseif ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT * FROM `del_customer` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) { echo json_encode($result->fetch_assoc()); }
    else { echo json_encode(['error' => 'Customer not found.']); }
    $stmt->close();

} elseif ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') { echo json_encode(['error' => 'Name is required.']); exit; }

    // Auto-generate unique customer code
    $r = $connect->query("SELECT CODE FROM `del_customer` WHERE CODE LIKE 'CUST%' ORDER BY ID DESC LIMIT 1");
    $nextNum = 1;
    if ($r && $r->num_rows > 0) {
        $lastCode = $r->fetch_assoc()['CODE'];
        $num = intval(substr($lastCode, 4));
        if ($num > 0) $nextNum = $num + 1;
    }
    $code = 'CUST' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

    // Ensure uniqueness
    $chk = $connect->prepare("SELECT ID FROM `del_customer` WHERE `CODE` = ? LIMIT 1");
    $chk->bind_param("s", $code);
    $chk->execute();
    while ($chk->get_result()->num_rows > 0) {
        $nextNum++;
        $code = 'CUST' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        $chk->bind_param("s", $code);
        $chk->execute();
    }
    $chk->close();

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
