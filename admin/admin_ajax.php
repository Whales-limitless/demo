<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

// Auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized";
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === "done") {
    $delid = $connect->real_escape_string($_POST['id'] ?? '');

    $sqlord = $connect->query("SELECT SDATE, ACCODE FROM `orderlist` WHERE SALNUM = '$delid' LIMIT 1");

    if ($sqlord && $sqlord->num_rows > 0) {
        $row = $sqlord->fetch_assoc();
        $status = 'DONE';

        $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '$status' WHERE SALNUM = '$delid'");
        $affected1 = $connect->affected_rows;

        if ($result1 && $affected1 > 0) {
            echo "Saved.";
        } elseif ($result1 && $affected1 == 0) {
            echo "Error: No rows updated. Order may already be DONE.";
        } else {
            echo "Error: " . $connect->error;
        }
    } else {
        echo "Error: Order not found.";
    }

} elseif ($action === "delete") {
    $delid = $connect->real_escape_string($_POST['id'] ?? '');

    $sqlord = $connect->query("SELECT SDATE, ACCODE FROM `orderlist` WHERE SALNUM = '$delid' LIMIT 1");

    if ($sqlord && $sqlord->num_rows > 0) {
        $row = $sqlord->fetch_assoc();
        $status = 'DELETED';

        $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '$status' WHERE SALNUM = '$delid'");
        $affected1 = $connect->affected_rows;

        if ($result1 && $affected1 > 0) {
            echo "Deleted.";
        } elseif ($result1 && $affected1 == 0) {
            echo "Error: No rows updated. Order may already be DELETED.";
        } else {
            echo "Error: " . $connect->error;
        }
    } else {
        echo "Error: Order not found.";
    }

} elseif ($action === "detail") {
    $delid = $connect->real_escape_string($_POST['id'] ?? '');

    $sqlord = $connect->query("SELECT ADMINRMK, TRANSNO FROM `orderlist` WHERE SALNUM = '$delid' LIMIT 1");

    if ($sqlord && $sqlord->num_rows > 0) {
        $row = $sqlord->fetch_assoc();
        echo ($row['ADMINRMK'] ?? '') . "|" . ($row['TRANSNO'] ?? '');
    }

} elseif ($action === "success") {
    $remark = $connect->real_escape_string($_POST['remark'] ?? '');
    $transno = $connect->real_escape_string($_POST['rowtransno'] ?? '');
    $delid = $connect->real_escape_string($_POST['pid'] ?? '');

    $sqlord = $connect->query("SELECT SDATE, ACCODE FROM `orderlist` WHERE SALNUM = '$delid' LIMIT 1");

    if ($sqlord && $sqlord->num_rows > 0) {
        $row = $sqlord->fetch_assoc();
        $status = 'PAYMENT';

        $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '$status', TRANSNO = '$transno', ADMINRMK = '$remark' WHERE SALNUM = '$delid'");
        $affected1 = $connect->affected_rows;

        if ($result1 && $affected1 > 0) {
            echo "Saved.";
        } elseif ($result1 && $affected1 == 0) {
            echo "Error: No rows updated.";
        } else {
            echo "Error: " . $connect->error;
        }
    } else {
        echo "Error: Order not found.";
    }
}
?>
