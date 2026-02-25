<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $ordno = trim($_POST['ordno'] ?? '');
    $imgData = $_POST['img_data'] ?? '';

    if ($ordno === '' || $imgData === '') {
        echo json_encode(['error' => 'Missing data.']);
        exit;
    }

    // Ensure signatures directory exists
    $sigDir = __DIR__ . '/uploads/signatures/';
    if (!is_dir($sigDir)) {
        mkdir($sigDir, 0755, true);
    }

    // Decode base64 image
    $imgData = str_replace('data:image/png;base64,', '', $imgData);
    $imgData = str_replace(' ', '+', $imgData);
    $decodedData = base64_decode($imgData);

    if ($decodedData === false) {
        echo json_encode(['error' => 'Invalid image data.']);
        exit;
    }

    // Save file
    $safeOrdno = preg_replace('/[\/\\\\:*?"<>|]/', '_', $ordno);
    $filePath = $sigDir . $safeOrdno . '.png';
    file_put_contents($filePath, $decodedData);

    // Insert/update del_sign record
    $stmt = $connect->prepare("SELECT `ORDNO` FROM `del_sign` WHERE `ORDNO` = ? LIMIT 1");
    $stmt->bind_param("s", $ordno);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$exists) {
        $stmt = $connect->prepare("INSERT INTO `del_sign` (`ORDNO`) VALUES (?)");
        $stmt->bind_param("s", $ordno);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid action.']);
}
