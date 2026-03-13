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

// Helper: process an image from raw binary data (decoded from base64 or temp file)
// Returns the saved filename on success, or null on failure
function processImageData($imageData, $prefix, $uploadDir) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    // Detect MIME type from binary data
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);

    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }

    // Create GD image from string data
    $src = @imagecreatefromstring($imageData);
    if (!$src) {
        return null;
    }

    // Resize if too large (max 800px on longest side)
    $maxDim = 800;
    $origW = imagesx($src);
    $origH = imagesy($src);
    if ($origW > $maxDim || $origH > $maxDim) {
        if ($origW >= $origH) {
            $newW = $maxDim;
            $newH = intval($origH * $maxDim / $origW);
        } else {
            $newH = $maxDim;
            $newW = intval($origW * $maxDim / $origH);
        }
        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);
        $src = $resized;
    }

    $fileName = $prefix . uniqid() . '.jpg';
    $filePath = $uploadDir . $fileName;
    $writeOk = imagejpeg($src, $filePath, 85);
    imagedestroy($src);

    if (!$writeOk || !file_exists($filePath)) {
        return null;
    }

    return $fileName;
}

// Helper: get image data from either $_FILES or base64 POST field
// Returns raw binary image data or null
function getImageData($fileKey, $base64Key) {
    // Method 1: Standard file upload via $_FILES
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $data = file_get_contents($_FILES[$fileKey]['tmp_name']);
        if ($data !== false && strlen($data) > 0) {
            return $data;
        }
    }

    // Method 2: Base64-encoded data sent as POST field (used by offline sync)
    if (!empty($_POST[$base64Key])) {
        $base64 = $_POST[$base64Key];
        // Strip data URL prefix if present (e.g., "data:image/jpeg;base64,")
        if (strpos($base64, ',') !== false) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }
        $data = base64_decode($base64, true);
        if ($data !== false && strlen($data) > 0) {
            return $data;
        }
    }

    return null;
}

if ($action === 'upload') {
    $updated = false;
    $errors = [];

    for ($i = 1; $i <= 3; $i++) {
        $fileKey = 'image' . $i;
        $base64Key = 'image' . $i . '_base64';

        $imageData = getImageData($fileKey, $base64Key);
        if ($imageData === null) continue;

        $fileName = processImageData($imageData, 'n' . $i, $uploadDir);
        if ($fileName) {
            $imgCol = 'IMG' . $i;
            $stmt = $connect->prepare("UPDATE `del_orderlist` SET `$imgCol` = ? WHERE `ID` = ?");
            $stmt->bind_param("si", $fileName, $orderId);
            $stmt->execute();
            $stmt->close();
            $updated = true;
        } else {
            $errors[] = 'Photo ' . $i . ': invalid image format or corrupted data';
        }
    }

    if ($updated) {
        echo json_encode(['success' => 'Photos uploaded successfully.']);
    } else {
        $errMsg = 'No valid photos were uploaded.';
        if (!empty($errors)) {
            $errMsg .= ' Details: ' . implode('; ', $errors);
        }
        // Add diagnostic info for debugging
        $diag = [];
        for ($i = 1; $i <= 3; $i++) {
            $fk = 'image' . $i;
            $b64k = 'image' . $i . '_base64';
            if (isset($_FILES[$fk])) {
                $diag[] = $fk . ': error=' . $_FILES[$fk]['error'] . ', size=' . ($_FILES[$fk]['size'] ?? 0);
            }
            if (isset($_POST[$b64k])) {
                $diag[] = $b64k . ': len=' . strlen($_POST[$b64k]);
            }
        }
        if (!empty($diag)) {
            $errMsg .= ' [' . implode(', ', $diag) . ']';
        }
        if (empty($_FILES) && empty(array_filter($_POST, function($k) { return strpos($k, '_base64') !== false; }, ARRAY_FILTER_USE_KEY))) {
            $errMsg .= ' [No files or base64 data received. Check PHP upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size') . ']';
        }
        echo json_encode(['error' => $errMsg]);
    }

} elseif ($action === 'upload_install') {
    // Upload installation photos for individual items
    $updated = false;
    $errors = [];

    // Get the order number for this order
    $stmt = $connect->prepare("SELECT `ORDNO` FROM `del_orderlist` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $orderRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$orderRow) {
        echo json_encode(['error' => 'Order not found.']);
        exit;
    }

    $ordno = $orderRow['ORDNO'];

    // Get all install items for this order
    $stmt2 = $connect->prepare("SELECT `ID` FROM `del_orderlistdesc` WHERE `ORDERNO` = ? AND `INSTALL` = 'Y'");
    $stmt2->bind_param("s", $ordno);
    $stmt2->execute();
    $instResult = $stmt2->get_result();
    $validIds = [];
    while ($ir = $instResult->fetch_assoc()) { $validIds[] = intval($ir['ID']); }
    $stmt2->close();

    // Process install photos from both $_FILES and base64 POST fields
    $processedIds = [];

    // Collect all item IDs to check (from $_FILES keys and $_POST base64 keys)
    foreach ($_FILES as $fileKey => $fileData) {
        if (strpos($fileKey, 'install_img_') === 0) {
            $itemId = intval(str_replace('install_img_', '', $fileKey));
            if (in_array($itemId, $validIds)) $processedIds[$itemId] = true;
        }
    }
    foreach ($_POST as $postKey => $postVal) {
        if (strpos($postKey, 'install_img_') === 0 && strpos($postKey, '_base64') !== false) {
            $itemId = intval(str_replace(['install_img_', '_base64'], '', $postKey));
            if (in_array($itemId, $validIds)) $processedIds[$itemId] = true;
        }
    }

    foreach (array_keys($processedIds) as $itemId) {
        $fileKey = 'install_img_' . $itemId;
        $base64Key = 'install_img_' . $itemId . '_base64';

        $imageData = getImageData($fileKey, $base64Key);
        if ($imageData === null) continue;

        $fileName = processImageData($imageData, 'inst' . $itemId . '_', $uploadDir);
        if ($fileName) {
            $stmtUp = $connect->prepare("UPDATE `del_orderlistdesc` SET `INSTALL_IMG` = ? WHERE `ID` = ?");
            $stmtUp->bind_param("si", $fileName, $itemId);
            $stmtUp->execute();
            $stmtUp->close();
            $updated = true;
        } else {
            $errors[] = 'Install item ' . $itemId . ': invalid image';
        }
    }

    if ($updated) {
        echo json_encode(['success' => 'Installation photos uploaded successfully.']);
    } else {
        $errMsg = 'No valid installation photos were uploaded.';
        if (!empty($errors)) {
            $errMsg .= ' Details: ' . implode('; ', $errors);
        }
        echo json_encode(['error' => $errMsg]);
    }

} elseif ($action === 'upload_single') {
    // Upload a SINGLE delivery photo (used by offline sync for stability)
    // Each photo is synced individually to avoid exceeding POST size limits
    $imgNum = intval($_POST['image_num'] ?? 0);
    if ($imgNum < 1 || $imgNum > 3) {
        echo json_encode(['error' => 'Invalid image number. Must be 1, 2, or 3.']);
        exit;
    }

    $imageData = getImageData('image', 'image_base64');
    if ($imageData === null) {
        echo json_encode(['error' => 'No image data received. [image_base64 len=' . strlen($_POST['image_base64'] ?? '') . ', FILES=' . (isset($_FILES['image']) ? 'yes,err=' . $_FILES['image']['error'] : 'no') . ']']);
        exit;
    }

    $fileName = processImageData($imageData, 'n' . $imgNum, $uploadDir);
    if ($fileName) {
        $imgCol = 'IMG' . $imgNum;
        $stmt = $connect->prepare("UPDATE `del_orderlist` SET `$imgCol` = ? WHERE `ID` = ?");
        $stmt->bind_param("si", $fileName, $orderId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => 'Photo ' . $imgNum . ' uploaded successfully.']);
    } else {
        echo json_encode(['error' => 'Photo ' . $imgNum . ': invalid image format or corrupted data (size=' . strlen($imageData) . ' bytes)']);
    }

} elseif ($action === 'upload_install_single') {
    // Upload a SINGLE installation photo (used by offline sync for stability)
    $itemId = intval($_POST['item_id'] ?? 0);
    if ($itemId <= 0) {
        echo json_encode(['error' => 'Invalid item ID.']);
        exit;
    }

    // Verify the item belongs to this order
    $stmt = $connect->prepare("SELECT `ORDNO` FROM `del_orderlist` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $orderRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$orderRow) {
        echo json_encode(['error' => 'Order not found.']);
        exit;
    }

    $ordno = $orderRow['ORDNO'];
    $stmt2 = $connect->prepare("SELECT `ID` FROM `del_orderlistdesc` WHERE `ORDERNO` = ? AND `ID` = ? AND `INSTALL` = 'Y' LIMIT 1");
    $stmt2->bind_param("si", $ordno, $itemId);
    $stmt2->execute();
    $validItem = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    if (!$validItem) {
        echo json_encode(['error' => 'Installation item not found or does not belong to this order.']);
        exit;
    }

    $imageData = getImageData('image', 'image_base64');
    if ($imageData === null) {
        echo json_encode(['error' => 'No image data received for install item ' . $itemId . '.']);
        exit;
    }

    $fileName = processImageData($imageData, 'inst' . $itemId . '_', $uploadDir);
    if ($fileName) {
        $stmtUp = $connect->prepare("UPDATE `del_orderlistdesc` SET `INSTALL_IMG` = ? WHERE `ID` = ?");
        $stmtUp->bind_param("si", $fileName, $itemId);
        $stmtUp->execute();
        $stmtUp->close();
        echo json_encode(['success' => 'Installation photo uploaded for item ' . $itemId . '.']);
    } else {
        echo json_encode(['error' => 'Install item ' . $itemId . ': invalid image format or corrupted data.']);
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
