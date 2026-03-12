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

// Auto-generate next unique code (USR0001, USR0002, ...)
function generateCode($connect) {
    $result = $connect->query("SELECT `USERNAME` FROM `sysfile` WHERE `USERNAME` LIKE 'USR%' ORDER BY `USERNAME` DESC LIMIT 1");
    $next = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $num = intval(substr($row['USERNAME'], 3));
        $next = $num + 1;
    }
    $code = 'USR' . str_pad($next, 4, '0', STR_PAD_LEFT);

    // Ensure uniqueness
    $chk = $connect->prepare("SELECT `ID` FROM `sysfile` WHERE `USERNAME` = ? LIMIT 1");
    $chk->bind_param("s", $code);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $chk->close();
        // Fallback: find max numeric and increment
        $all = $connect->query("SELECT `USERNAME` FROM `sysfile` WHERE `USERNAME` LIKE 'USR%' ORDER BY `USERNAME` ASC");
        $max = 0;
        if ($all) {
            while ($r = $all->fetch_assoc()) {
                $n = intval(substr($r['USERNAME'], 3));
                if ($n > $max) $max = $n;
            }
        }
        $code = 'USR' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $chk->close();
    }
    return $code;
}

if ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT `ID`,`USER1`,`USER_NAME`,`USERNAME`,`TYPE`,`OUTLET` FROM `sysfile` WHERE `ID` = ? LIMIT 1");
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
    $type     = trim($_POST['type'] ?? 'S');
    $branch   = trim($_POST['branch'] ?? '');

    if ($username === '' || $password === '') {
        echo json_encode(['error' => 'Username and password are required.']);
        exit;
    }

    if ($branch === '') {
        echo json_encode(['error' => 'Branch is required.']);
        exit;
    }

    // Validate type
    if (!in_array($type, ['A', 'S', 'D'])) {
        $type = 'S';
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

    // Auto-generate code
    $code = generateCode($connect);

    $stmt = $connect->prepare("INSERT INTO `sysfile` (`USER1`,`USER2`,`USER_NAME`,`USERNAME`,`TYPE`,`STATUS`,`OUTLET`) VALUES (?,?,?,?,?,'Y',?)");
    $stmt->bind_param("ssssss", $username, $password, $name, $code, $type, $branch);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'User created. Code: ' . $code]);
    } else {
        echo json_encode(['error' => 'Failed to create user: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'update') {
    $id       = intval($_POST['id'] ?? 0);
    $password = trim($_POST['password'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $type     = trim($_POST['type'] ?? 'S');
    $branch   = trim($_POST['branch'] ?? '');

    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid user ID.']);
        exit;
    }

    if ($branch === '') {
        echo json_encode(['error' => 'Branch is required.']);
        exit;
    }

    if (!in_array($type, ['A', 'S', 'D'])) {
        $type = 'S';
    }

    if ($password !== '') {
        $stmt = $connect->prepare("UPDATE `sysfile` SET `USER2`=?, `USER_NAME`=?, `TYPE`=?, `OUTLET`=? WHERE `ID`=?");
        $stmt->bind_param("ssssi", $password, $name, $type, $branch, $id);
    } else {
        $stmt = $connect->prepare("UPDATE `sysfile` SET `USER_NAME`=?, `TYPE`=?, `OUTLET`=? WHERE `ID`=?");
        $stmt->bind_param("sssi", $name, $type, $branch, $id);
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
