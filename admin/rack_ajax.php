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

// ===================== RACK CRUD =====================

if ($action === 'list') {
    $page = max(1, intval($_POST['page'] ?? 1));
    $perPage = max(10, min(100, intval($_POST['per_page'] ?? 50)));
    $search = trim($_POST['search'] ?? '');
    $statusFilter = trim($_POST['status'] ?? '');

    $where = "1=1";
    $params = [];
    $types = "";

    if ($search !== '') {
        $where .= " AND (LOWER(r.`code`) LIKE ? OR LOWER(r.`description`) LIKE ?
                    OR EXISTS (
                        SELECT 1 FROM `rack_product` rp
                        JOIN `PRODUCTS` p ON rp.`barcode` = p.`barcode`
                        WHERE rp.`rack_id` = r.`id`
                        AND (p.`barcode` LIKE ? OR LOWER(p.`name`) LIKE ?)
                    ))";
        $like = '%' . strtolower($search) . '%';
        $params = array_merge($params, [$like, $like, $like, $like]);
        $types .= "ssss";
    }
    if ($statusFilter === 'active') {
        $where .= " AND r.`status` = 'ACTIVE'";
    } elseif ($statusFilter === 'inactive') {
        $where .= " AND r.`status` = 'INACTIVE'";
    }

    // Count total
    $countSql = "SELECT COUNT(*) AS cnt FROM `rack` r WHERE $where";
    $countStmt = $connect->prepare($countSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['cnt'];
    $countStmt->close();

    $pages = max(1, ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    // Fetch page with product counts
    $sql = "SELECT r.`id`, r.`code`, r.`description`, r.`status`, r.`created_at`,
                   (SELECT COUNT(*) FROM `rack_product` WHERE `rack_id` = r.`id`) AS product_count
            FROM `rack` r
            WHERE $where
            ORDER BY r.`status` DESC, r.`code` ASC
            LIMIT ?, ?";
    $stmt = $connect->prepare($sql);
    $fetchTypes = $types . "ii";
    $fetchParams = array_merge($params, [$offset, $perPage]);
    $stmt->bind_param($fetchTypes, ...$fetchParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $racks = [];
    while ($r = $result->fetch_assoc()) {
        $racks[] = $r;
    }
    $stmt->close();

    // If searching, find which products matched for each rack
    if ($search !== '' && !empty($racks)) {
        $rackIds = array_column($racks, 'id');
        $placeholders = implode(',', array_fill(0, count($rackIds), '?'));
        $matchTypes = str_repeat('i', count($rackIds)) . 'ss';
        $searchLike = '%' . strtolower($search) . '%';
        $matchParams = array_merge($rackIds, [$searchLike, $searchLike]);

        $matchSql = "SELECT rp.`rack_id`, p.`barcode`, p.`name`
                     FROM `rack_product` rp
                     JOIN `PRODUCTS` p ON rp.`barcode` = p.`barcode`
                     WHERE rp.`rack_id` IN ($placeholders)
                     AND (p.`barcode` LIKE ? OR LOWER(p.`name`) LIKE ?)
                     ORDER BY p.`name` ASC";
        $matchStmt = $connect->prepare($matchSql);
        $matchStmt->bind_param($matchTypes, ...$matchParams);
        $matchStmt->execute();
        $matchResult = $matchStmt->get_result();

        $matchMap = [];
        while ($m = $matchResult->fetch_assoc()) {
            $rid = $m['rack_id'];
            if (!isset($matchMap[$rid])) $matchMap[$rid] = [];
            if (count($matchMap[$rid]) < 3) {
                $matchMap[$rid][] = ['barcode' => $m['barcode'], 'name' => $m['name']];
            }
        }
        $matchStmt->close();

        for ($i = 0; $i < count($racks); $i++) {
            $rid = $racks[$i]['id'];
            $racks[$i]['matched_products'] = $matchMap[$rid] ?? [];
        }
    }

    echo json_encode([
        'racks' => $racks,
        'total' => (int)$total,
        'page' => (int)$page,
        'pages' => (int)$pages,
        'per_page' => (int)$perPage
    ]);

} elseif ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT * FROM `rack` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Rack not found.']);
    }
    $stmt->close();

} elseif ($action === 'create') {
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($code === '') {
        echo json_encode(['error' => 'Rack code is required.']);
        exit;
    }

    $stmt = $connect->prepare("INSERT INTO `rack` (`code`, `description`) VALUES (?, ?)");
    $stmt->bind_param("ss", $code, $description);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Rack created successfully.', 'id' => $stmt->insert_id]);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'Rack code already exists.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0 || $code === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `rack` SET `code`=?, `description`=? WHERE `id`=?");
    $stmt->bind_param("ssi", $code, $description, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Rack updated successfully.']);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'Rack code already exists.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid rack ID.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `rack` SET `status` = 'INACTIVE' WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Rack deactivated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'activate') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid rack ID.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `rack` SET `status` = 'ACTIVE' WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Rack activated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

// ===================== RACK-PRODUCT MAPPING =====================

} elseif ($action === 'rack_products') {
    $rackId = intval($_POST['rack_id'] ?? 0);
    if ($rackId <= 0) {
        echo json_encode(['error' => 'Invalid rack ID.']);
        exit;
    }

    $stmt = $connect->prepare("
        SELECT rp.`id` AS mapping_id, rp.`barcode`, rp.`assigned_at`,
               p.`name`, p.`cat`, p.`sub_cat`, COALESCE(p.`qoh`, 0) AS qoh, p.`uom`
        FROM `rack_product` rp
        LEFT JOIN `PRODUCTS` p ON rp.`barcode` = p.`barcode`
        WHERE rp.`rack_id` = ?
        ORDER BY p.`name` ASC
    ");
    $stmt->bind_param("i", $rackId);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($r = $result->fetch_assoc()) {
        $products[] = $r;
    }
    $stmt->close();
    echo json_encode($products);

} elseif ($action === 'add_product') {
    $rackId = intval($_POST['rack_id'] ?? 0);
    $barcode = trim($_POST['barcode'] ?? '');

    if ($rackId <= 0 || $barcode === '') {
        echo json_encode(['error' => 'Rack and product barcode are required.']);
        exit;
    }

    $stmt = $connect->prepare("INSERT INTO `rack_product` (`rack_id`, `barcode`) VALUES (?, ?)");
    $stmt->bind_param("is", $rackId, $barcode);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product added to rack.']);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'Product is already assigned to this rack.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'add_products') {
    $rackId = intval($_POST['rack_id'] ?? 0);
    $barcodes = json_decode($_POST['barcodes'] ?? '[]', true);

    if ($rackId <= 0 || empty($barcodes)) {
        echo json_encode(['error' => 'Rack and at least one barcode are required.']);
        exit;
    }

    $added = 0;
    $skipped = 0;
    $stmt = $connect->prepare("INSERT INTO `rack_product` (`rack_id`, `barcode`) VALUES (?, ?)");
    foreach ($barcodes as $bc) {
        $bc = trim($bc);
        if ($bc === '') continue;
        $stmt->bind_param("is", $rackId, $bc);
        if ($stmt->execute()) {
            $added++;
        } else {
            $skipped++;
        }
    }
    $stmt->close();

    echo json_encode(['success' => $added . ' product(s) added, ' . $skipped . ' skipped (already assigned).']);

} elseif ($action === 'remove_product') {
    $mappingId = intval($_POST['mapping_id'] ?? 0);
    if ($mappingId <= 0) {
        echo json_encode(['error' => 'Invalid mapping ID.']);
        exit;
    }

    $stmt = $connect->prepare("DELETE FROM `rack_product` WHERE `id` = ?");
    $stmt->bind_param("i", $mappingId);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product removed from rack.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'search_products') {
    $q = trim($_POST['q'] ?? '');
    $rackId = intval($_POST['rack_id'] ?? 0);
    if ($q === '') {
        echo json_encode([]);
        exit;
    }

    $like = '%' . $q . '%';
    // Search products not already in this rack
    $sql = "SELECT p.`barcode`, p.`name`, p.`cat`, COALESCE(p.`qoh`, 0) AS qoh
            FROM `PRODUCTS` p
            WHERE (p.`checked` = 'Y' OR p.`checked` IS NULL OR p.`checked` = '')
              AND (p.`barcode` LIKE ? OR LOWER(p.`name`) LIKE ?)";
    $params = [$like, '%' . strtolower($q) . '%'];
    $types = "ss";

    if ($rackId > 0) {
        $sql .= " AND p.`barcode` NOT IN (SELECT `barcode` FROM `rack_product` WHERE `rack_id` = ?)";
        $params[] = $rackId;
        $types .= "i";
    }

    $sql .= " ORDER BY p.`name` ASC LIMIT 20";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($r = $result->fetch_assoc()) {
        $products[] = $r;
    }
    $stmt->close();
    echo json_encode($products);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
