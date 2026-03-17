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

// ===================== TREND CONFIG CRUD =====================

if ($action === 'list') {
    $rows = [];
    $result = $connect->query("SELECT * FROM `product_trend_config` ORDER BY `is_active` DESC, `created_at` DESC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

} elseif ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT * FROM `product_trend_config` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Config not found.']);
    }
    $stmt->close();

} elseif ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $dateFrom = trim($_POST['date_from'] ?? '');
    $dateTo = trim($_POST['date_to'] ?? '');
    $greenMin = intval($_POST['green_min'] ?? 50);
    $yellowMin = intval($_POST['yellow_min'] ?? 10);
    $redMin = intval($_POST['red_min'] ?? 1);

    if ($name === '' || $dateFrom === '' || $dateTo === '') {
        echo json_encode(['error' => 'Name, date from, and date to are required.']);
        exit;
    }

    if ($dateFrom > $dateTo) {
        echo json_encode(['error' => 'Date from must be before date to.']);
        exit;
    }

    if ($greenMin <= $yellowMin || $yellowMin <= $redMin || $redMin < 1) {
        echo json_encode(['error' => 'Thresholds must be: Green > Yellow > Red >= 1.']);
        exit;
    }

    $stmt = $connect->prepare("INSERT INTO `product_trend_config` (`name`, `date_from`, `date_to`, `green_min`, `yellow_min`, `red_min`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiii", $name, $dateFrom, $dateTo, $greenMin, $yellowMin, $redMin);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Trend config created.', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $dateFrom = trim($_POST['date_from'] ?? '');
    $dateTo = trim($_POST['date_to'] ?? '');
    $greenMin = intval($_POST['green_min'] ?? 50);
    $yellowMin = intval($_POST['yellow_min'] ?? 10);
    $redMin = intval($_POST['red_min'] ?? 1);

    if ($id <= 0 || $name === '' || $dateFrom === '' || $dateTo === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    if ($dateFrom > $dateTo) {
        echo json_encode(['error' => 'Date from must be before date to.']);
        exit;
    }

    if ($greenMin <= $yellowMin || $yellowMin <= $redMin || $redMin < 1) {
        echo json_encode(['error' => 'Thresholds must be: Green > Yellow > Red >= 1.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `product_trend_config` SET `name`=?, `date_from`=?, `date_to`=?, `green_min`=?, `yellow_min`=?, `red_min`=? WHERE `id`=?");
    $stmt->bind_param("sssiiii", $name, $dateFrom, $dateTo, $greenMin, $yellowMin, $redMin, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Trend config updated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID.']);
        exit;
    }
    $stmt = $connect->prepare("DELETE FROM `product_trend_config` WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Trend config deleted.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'activate') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID.']);
        exit;
    }

    // Deactivate all first, then activate the selected one
    $connect->query("UPDATE `product_trend_config` SET `is_active` = 0");
    $stmt = $connect->prepare("UPDATE `product_trend_config` SET `is_active` = 1 WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Trend config activated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'deactivate') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID.']);
        exit;
    }
    $stmt = $connect->prepare("UPDATE `product_trend_config` SET `is_active` = 0 WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Trend config deactivated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

// ===================== TREND PREVIEW / ANALYTICS =====================

} elseif ($action === 'preview') {
    // Preview trend distribution for a config
    $id = intval($_POST['id'] ?? 0);
    $dateFrom = trim($_POST['date_from'] ?? '');
    $dateTo = trim($_POST['date_to'] ?? '');
    $greenMin = intval($_POST['green_min'] ?? 50);
    $yellowMin = intval($_POST['yellow_min'] ?? 10);
    $redMin = intval($_POST['red_min'] ?? 1);

    // If id given, load from DB
    if ($id > 0 && $dateFrom === '') {
        $stmt = $connect->prepare("SELECT * FROM `product_trend_config` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $cfg = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$cfg) {
            echo json_encode(['error' => 'Config not found.']);
            exit;
        }
        $dateFrom = $cfg['date_from'];
        $dateTo = $cfg['date_to'];
        $greenMin = (int)$cfg['green_min'];
        $yellowMin = (int)$cfg['yellow_min'];
        $redMin = (int)$cfg['red_min'];
    }

    if ($dateFrom === '' || $dateTo === '') {
        echo json_encode(['error' => 'Date range required.']);
        exit;
    }

    // Get total active products
    $totalRes = $connect->query("SELECT COUNT(*) AS cnt FROM `PRODUCTS` WHERE `checked` = 'Y'");
    $totalProducts = $totalRes ? (int)$totalRes->fetch_assoc()['cnt'] : 0;

    // Get order quantities per barcode in date range
    $stmt = $connect->prepare("
        SELECT BARCODE, SUM(ABS(QTY)) AS total_ordered
        FROM `orderlist`
        WHERE `SDATE` BETWEEN ? AND ?
          AND `STATUS` != 'DELETED'
        GROUP BY `BARCODE`
    ");
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();

    $orderMap = [];
    while ($r = $result->fetch_assoc()) {
        $orderMap[$r['BARCODE']] = (int)$r['total_ordered'];
    }
    $stmt->close();

    // Classify products
    $green = 0;
    $yellow = 0;
    $red = 0;
    $black = 0;

    // Get all active product barcodes
    $prodRes = $connect->query("SELECT `barcode` FROM `PRODUCTS` WHERE `checked` = 'Y'");
    if ($prodRes) {
        while ($p = $prodRes->fetch_assoc()) {
            $qty = $orderMap[$p['barcode']] ?? 0;
            if ($qty >= $greenMin) {
                $green++;
            } elseif ($qty >= $yellowMin) {
                $yellow++;
            } elseif ($qty >= $redMin) {
                $red++;
            } else {
                $black++;
            }
        }
    }

    // Top movers
    $topMovers = [];
    $stmt = $connect->prepare("
        SELECT o.BARCODE, p.`name`, SUM(ABS(o.QTY)) AS total_ordered
        FROM `orderlist` o
        INNER JOIN `PRODUCTS` p ON o.`BARCODE` = p.`barcode`
        WHERE o.`SDATE` BETWEEN ? AND ?
          AND o.`STATUS` != 'DELETED'
          AND p.`checked` = 'Y'
        GROUP BY o.`BARCODE`, p.`name`
        ORDER BY total_ordered DESC
        LIMIT 10
    ");
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $topRes = $stmt->get_result();
    while ($r = $topRes->fetch_assoc()) {
        $topMovers[] = $r;
    }
    $stmt->close();

    echo json_encode([
        'total_products' => $totalProducts,
        'green' => $green,
        'yellow' => $yellow,
        'red' => $red,
        'black' => $black,
        'top_movers' => $topMovers,
        'date_from' => $dateFrom,
        'date_to' => $dateTo
    ]);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
