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
$orderId = intval($_POST['id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['error' => 'Invalid order ID.']);
    exit;
}

// Ensure uploads directory exists
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($action === 'upload') {
    $updated = false;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    for ($i = 1; $i <= 3; $i++) {
        $fileKey = 'image' . $i;
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES[$fileKey]['tmp_name'];
            $mimeType = mime_content_type($tmpName);

            if (!in_array($mimeType, $allowedTypes)) {
                continue;
            }

            // Compress and save as JPEG
            $fileName = 'n' . $i . uniqid() . '.jpg';
            $filePath = $uploadDir . $fileName;

            $src = null;
            switch ($mimeType) {
                case 'image/jpeg': $src = imagecreatefromjpeg($tmpName); break;
                case 'image/png': $src = imagecreatefrompng($tmpName); break;
                case 'image/gif': $src = imagecreatefromgif($tmpName); break;
                case 'image/webp': $src = imagecreatefromwebp($tmpName); break;
            }

            if ($src) {
                imagejpeg($src, $filePath, 75);
                imagedestroy($src);

                $imgCol = 'IMG' . $i;
                $stmt = $connect->prepare("UPDATE `del_orderlist` SET `$imgCol` = ? WHERE `ID` = ?");
                $stmt->bind_param("si", $fileName, $orderId);
                $stmt->execute();
                $stmt->close();
                $updated = true;
            }
        }
    }

    if ($updated) {
        echo json_encode(['success' => 'Photos uploaded successfully.']);
    } else {
        echo json_encode(['error' => 'No valid photos were uploaded.']);
    }

} elseif ($action === 'done') {
    // Check at least one image exists
    $stmt = $connect->prepare("SELECT `IMG1`, `IMG2`, `IMG3` FROM `del_orderlist` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['error' => 'Order not found.']);
        exit;
    }

    if (empty($row['IMG1']) && empty($row['IMG2']) && empty($row['IMG3'])) {
        echo json_encode(['error' => 'Please upload at least one photo before marking as done.']);
        exit;
    }

    $doneDateTime = date('Y-m-d H:i:s');
    $stmt = $connect->prepare("UPDATE `del_orderlist` SET `STATUS` = 'D', `DONEDATETIME` = ? WHERE `ID` = ?");
    $stmt->bind_param("si", $doneDateTime, $orderId);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Delivery marked as done.']);
    } else {
        echo json_encode(['error' => 'Failed to update status.']);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
