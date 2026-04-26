<?php
require_once __DIR__ . '/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$userType = $_SESSION['user_type'] ?? 'S';
if ($userType !== 'D' && $userType !== 'A') {
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$userCode = $_SESSION['user_code'] ?? '';
$userName = $_SESSION['user_name'] ?? '';

// Auto-create the inst_job table if it doesn't exist
$connect->query("CREATE TABLE IF NOT EXISTS `inst_job` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `USERCODE` varchar(50) NOT NULL,
  `USERNAME` varchar(80) NOT NULL,
  `IMAGE` varchar(200) NOT NULL DEFAULT '',
  `REMARK` text NOT NULL,
  `STATUS` varchar(1) NOT NULL DEFAULT 'P',
  `REJECT_REASON` text NOT NULL,
  `APPROVE_REASON` text NOT NULL,
  `COMMISSION` double(10,2) NOT NULL DEFAULT 0.00,
  `SUBMIT_DATETIME` datetime NOT NULL,
  `REVIEWED_BY` varchar(50) NOT NULL DEFAULT '',
  `REVIEWED_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_user` (`USERCODE`),
  KEY `idx_status` (`STATUS`),
  KEY `idx_submit` (`SUBMIT_DATETIME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function processInstImage($imageData, $uploadDir) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);
    if (!in_array($mimeType, $allowedTypes)) return null;

    $src = @imagecreatefromstring($imageData);
    if (!$src) return null;

    $maxDim = 1024;
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

    $fileName = 'instjob_' . uniqid() . '.jpg';
    $filePath = $uploadDir . $fileName;
    $writeOk = imagejpeg($src, $filePath, 85);
    imagedestroy($src);

    if (!$writeOk || !file_exists($filePath)) return null;
    return $fileName;
}

function getImageBinary($fileKey, $base64Key) {
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $data = file_get_contents($_FILES[$fileKey]['tmp_name']);
        if ($data !== false && strlen($data) > 0) return $data;
    }
    if (!empty($_POST[$base64Key])) {
        $base64 = $_POST[$base64Key];
        if (strpos($base64, ',') !== false) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }
        $data = base64_decode($base64, true);
        if ($data !== false && strlen($data) > 0) return $data;
    }
    return null;
}

$action = $_POST['action'] ?? '';

if ($action === 'submit') {
    $remark = trim($_POST['remark'] ?? '');
    if ($userCode === '') {
        echo json_encode(['error' => 'Your account is not linked to a user code.']);
        exit;
    }

    $imageData = getImageBinary('image', 'image_base64');
    if ($imageData === null) {
        echo json_encode(['error' => 'Please attach an installation photo.']);
        exit;
    }

    $fileName = processInstImage($imageData, $uploadDir);
    if (!$fileName) {
        echo json_encode(['error' => 'Invalid image format or corrupted data.']);
        exit;
    }

    $submitDateTime = date('Y-m-d H:i:s');
    $stmt = $connect->prepare("INSERT INTO `inst_job` (USERCODE, USERNAME, IMAGE, REMARK, STATUS, REJECT_REASON, APPROVE_REASON, COMMISSION, SUBMIT_DATETIME) VALUES (?, ?, ?, ?, 'P', '', '', 0.00, ?)");
    $stmt->bind_param("sssss", $userCode, $userName, $fileName, $remark, $submitDateTime);
    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['success' => 'Installation job submitted.', 'id' => $newId]);
    } else {
        $stmt->close();
        echo json_encode(['error' => 'Failed to save: ' . $connect->error]);
    }

} elseif ($action === 'history') {
    $sql = "SELECT * FROM `inst_job` WHERE USERCODE = ? ORDER BY SUBMIT_DATETIME DESC, ID DESC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $userCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
