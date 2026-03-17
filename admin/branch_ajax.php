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

// Ensure branch table exists
$connect->query("CREATE TABLE IF NOT EXISTS `branch` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_branch_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_POST['action'] ?? '';

// Auto-generate next unique branch code (BR0001, BR0002, ...)
function generateBranchCode($connect) {
    $result = $connect->query("SELECT `code` FROM `branch` WHERE `code` LIKE 'BR%' ORDER BY `code` DESC LIMIT 1");
    $next = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $num = intval(substr($row['code'], 2));
        $next = $num + 1;
    }
    return 'BR' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

if ($action === 'list') {
    $branches = [];
    $result = $connect->query("SELECT * FROM `branch` ORDER BY `name` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $branches[] = $r;
        }
    }
    echo json_encode(['branches' => $branches]);

} elseif ($action === 'create') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        echo json_encode(['error' => 'Branch name is required.']);
        exit;
    }

    // Check duplicate name
    $chk = $connect->prepare("SELECT `id` FROM `branch` WHERE `name` = ? LIMIT 1");
    $chk->bind_param("s", $name);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'A branch with this name already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    $code = generateBranchCode($connect);

    $stmt = $connect->prepare("INSERT INTO `branch` (`code`, `name`) VALUES (?, ?)");
    $stmt->bind_param("ss", $code, $name);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Branch created successfully.', 'code' => $code, 'id' => $connect->insert_id]);
    } else {
        echo json_encode(['error' => 'Failed to create branch: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($id <= 0 || $name === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    // Check duplicate name (exclude current)
    $chk = $connect->prepare("SELECT `id` FROM `branch` WHERE `name` = ? AND `id` != ? LIMIT 1");
    $chk->bind_param("si", $name, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'A branch with this name already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    $stmt = $connect->prepare("UPDATE `branch` SET `name` = ? WHERE `id` = ?");
    $stmt->bind_param("si", $name, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Branch updated successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to update branch: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid branch ID.']);
        exit;
    }

    // Check if any users are assigned to this branch
    $chk = $connect->prepare("SELECT `code` FROM `branch` WHERE `id` = ? LIMIT 1");
    $chk->bind_param("i", $id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row) {
        echo json_encode(['error' => 'Branch not found.']);
        exit;
    }

    $branchCode = $row['code'];
    $userChk = $connect->prepare("SELECT COUNT(*) AS cnt FROM `sysfile` WHERE `OUTLET` = ?");
    $userChk->bind_param("s", $branchCode);
    $userChk->execute();
    $userRow = $userChk->get_result()->fetch_assoc();
    $userChk->close();

    if (intval($userRow['cnt']) > 0) {
        echo json_encode(['error' => 'Cannot delete branch. ' . $userRow['cnt'] . ' user(s) are assigned to it.']);
        exit;
    }

    $stmt = $connect->prepare("DELETE FROM `branch` WHERE `id` = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Branch deleted successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to delete branch: ' . $connect->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
