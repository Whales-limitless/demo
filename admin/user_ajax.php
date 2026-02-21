<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT `ID`,`USER1`,`USER_NAME`,`TYPE`,`STATUS`,`OUTLET`,`LEVEL`,`DEPT` FROM `sysfile` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'User not found.']);
    }
    $stmt->close();

} elseif ($action === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $type     = trim($_POST['type'] ?? 'U');
    $status   = trim($_POST['status'] ?? 'Y');
    $outlet   = trim($_POST['outlet'] ?? '');
    $level    = floatval($_POST['level'] ?? 0);
    $dept     = trim($_POST['dept'] ?? '');

    if ($username === '' || $password === '') {
        echo json_encode(['error' => 'Username and password are required.']);
        exit;
    }

    // Check duplicate username
    $chk = $connect->prepare("SELECT `ID` FROM `sysfile` WHERE `USER1` = ? LIMIT 1");
    $chk->bind_param("s", $username);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Username already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    $stmt = $connect->prepare("INSERT INTO `sysfile` (`USER1`,`USER2`,`USER_NAME`,`TYPE`,`STATUS`,`OUTLET`,`LEVEL`,`DEPT`,`PUSHID`) VALUES (?,?,?,?,?,?,?,?,'')");
    $stmt->bind_param("ssssssds", $username, $password, $name, $type, $status, $outlet, $level, $dept);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'User created successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to create user: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'update') {
    $id       = intval($_POST['id'] ?? 0);
    $password = trim($_POST['password'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $type     = trim($_POST['type'] ?? 'U');
    $status   = trim($_POST['status'] ?? 'Y');
    $outlet   = trim($_POST['outlet'] ?? '');
    $level    = floatval($_POST['level'] ?? 0);
    $dept     = trim($_POST['dept'] ?? '');

    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid user ID.']);
        exit;
    }

    if ($password !== '') {
        $stmt = $connect->prepare("UPDATE `sysfile` SET `USER2`=?, `USER_NAME`=?, `TYPE`=?, `STATUS`=?, `OUTLET`=?, `LEVEL`=?, `DEPT`=? WHERE `ID`=?");
        $stmt->bind_param("sssssdsi", $password, $name, $type, $status, $outlet, $level, $dept, $id);
    } else {
        $stmt = $connect->prepare("UPDATE `sysfile` SET `USER_NAME`=?, `TYPE`=?, `STATUS`=?, `OUTLET`=?, `LEVEL`=?, `DEPT`=? WHERE `ID`=?");
        $stmt->bind_param("ssssdsi", $name, $type, $status, $outlet, $level, $dept, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => 'User updated successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to update user: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid user ID.']);
        exit;
    }

    // Prevent deleting admin users
    $chk = $connect->prepare("SELECT `TYPE` FROM `sysfile` WHERE `ID` = ? LIMIT 1");
    $chk->bind_param("i", $id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row) {
        echo json_encode(['error' => 'User not found.']);
        exit;
    }

    if (($row['TYPE'] ?? '') === 'A') {
        echo json_encode(['error' => 'Admin users cannot be deleted.']);
        exit;
    }

    $stmt = $connect->prepare("DELETE FROM `sysfile` WHERE `ID` = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'User deleted successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to delete user: ' . $connect->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
