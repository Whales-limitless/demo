<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

// Auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(["draw" => 0, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []]);
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

$table = 'orderlist2';

// Columns definition matching the DataTable on dashboard.php
$columns = [
    0 => '',          // Row number (computed client-side)
    1 => 'SDATE',
    2 => 'TTIME',
    3 => 'SALNUM',
    4 => 'NAME',
    5 => 'HP',
    6 => 'SUMQTY',
    7 => 'TXTTO',
    8 => 'ADMINRMK',
    9 => ''           // Action buttons (computed client-side)
];

// DataTables request params
$draw    = isset($_GET['draw']) ? intval($_GET['draw']) : (isset($_POST['draw']) ? intval($_POST['draw']) : 0);
$start   = isset($_GET['start']) ? intval($_GET['start']) : (isset($_POST['start']) ? intval($_POST['start']) : 0);
$length  = isset($_GET['length']) ? intval($_GET['length']) : (isset($_POST['length']) ? intval($_POST['length']) : 100);
$search  = isset($_GET['search']['value']) ? $_GET['search']['value'] : (isset($_POST['search']['value']) ? $_POST['search']['value'] : '');

// Order
$orderCol = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : (isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 3);
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : (isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc');

// Validate order direction
$orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';

// Map column index to actual column name for ordering
$orderColumn = 'SALNUM';
if (isset($columns[$orderCol]) && $columns[$orderCol] !== '') {
    $orderColumn = $columns[$orderCol];
}

// Base query
$where = '';
if ($search !== '') {
    $searchEsc = $connect->real_escape_string($search);
    $where = " WHERE (SALNUM LIKE '%$searchEsc%' OR NAME LIKE '%$searchEsc%' OR HP LIKE '%$searchEsc%' OR TXTTO LIKE '%$searchEsc%' OR ADMINRMK LIKE '%$searchEsc%' OR SDATE LIKE '%$searchEsc%')";
}

// Total records (no filter)
$totalResult = $connect->query("SELECT COUNT(*) as cnt FROM `$table`");
$totalRecords = 0;
if ($totalResult) {
    $totalRecords = (int)$totalResult->fetch_assoc()['cnt'];
}

// Filtered records
$filteredResult = $connect->query("SELECT COUNT(*) as cnt FROM `$table`$where");
$filteredRecords = 0;
if ($filteredResult) {
    $filteredRecords = (int)$filteredResult->fetch_assoc()['cnt'];
}

// Data query
$sql = "SELECT * FROM `$table`$where ORDER BY `$orderColumn` $orderDir LIMIT $start, $length";
$dataResult = $connect->query($sql);

$data = [];
$rowNum = $start + 1;
if ($dataResult) {
    while ($row = $dataResult->fetch_assoc()) {
        $sdate = !empty($row['SDATE']) ? date('d/m/Y', strtotime($row['SDATE'])) : '';
        $ttime = $row['TTIME'] ?? '';

        $data[] = [
            $rowNum,
            $sdate,
            $ttime,
            $row['SALNUM'] ?? '',
            $row['NAME'] ?? '',
            $row['HP'] ?? '',
            $row['SUMQTY'] ?? '0',
            $row['TXTTO'] ?? '',
            $row['ADMINRMK'] ?? '',
            ''  // Action column rendered client-side
        ];
        $rowNum++;
    }
}

// Output JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "draw"            => $draw,
    "recordsTotal"    => $totalRecords,
    "recordsFiltered" => $filteredRecords,
    "data"            => $data
]);
?>
