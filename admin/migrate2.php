<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

// =====================================================================
// --- SQL FILE IMPORT (line-by-line for large files) ---
// =====================================================================
function importSqlFile($connect, $filePath) {
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    $fileSize = filesize($filePath);
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'Cannot open file.', 'executed' => 0, 'failed' => 0, 'skipped' => 0];
    }

    $connect->query("SET FOREIGN_KEY_CHECKS = 0");
    $connect->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $connect->query("SET AUTOCOMMIT = 0");
    $connect->query("START TRANSACTION");

    $stmt = '';
    $executed = 0;
    $failed = 0;
    $skipped = 0;
    $errors = [];
    $inBlockComment = false;

    while (($line = fgets($handle)) !== false) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;

        if ($inBlockComment) {
            if (strpos($trimmed, '*/') !== false) $inBlockComment = false;
            continue;
        }
        if (strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) continue;
        if (strpos($trimmed, '/*') === 0 && strpos($trimmed, '/*!') !== 0) {
            if (strpos($trimmed, '*/') === false) $inBlockComment = true;
            continue;
        }

        $upper = strtoupper($trimmed);
        if (preg_match('/^(CREATE\s+DATABASE|USE\s+`|DROP\s+DATABASE)/i', $upper)) {
            $skipped++;
            continue;
        }

        $stmt .= $line;

        if (preg_match('/;\s*$/', $trimmed)) {
            $sql = trim($stmt);
            $stmt = '';
            if ($sql === '' || $sql === ';') continue;

            if ($connect->query($sql)) {
                $executed++;
                if ($executed % 500 === 0) {
                    $connect->query("COMMIT");
                    $connect->query("START TRANSACTION");
                }
            } else {
                $err = $connect->error;
                if (strpos($err, 'already exists') !== false || strpos($err, 'Duplicate entry') !== false) {
                    $skipped++;
                } else {
                    $failed++;
                    if (count($errors) < 20) {
                        $errors[] = mb_substr($err, 0, 200);
                    }
                }
            }
        }
    }

    $sql = trim($stmt);
    if ($sql !== '' && $sql !== ';') {
        if ($connect->query($sql)) $executed++;
        else $failed++;
    }

    $connect->query("COMMIT");
    $connect->query("SET FOREIGN_KEY_CHECKS = 1");
    $connect->query("SET AUTOCOMMIT = 1");
    fclose($handle);

    return [
        'success' => true,
        'executed' => $executed,
        'failed' => $failed,
        'skipped' => $skipped,
        'errors' => $errors,
        'file_size' => $fileSize
    ];
}

// =====================================================================
// --- Scan for .sql files in root folder ---
// =====================================================================
$rootDir = realpath(__DIR__ . '/..');
$sqlFiles = [];
foreach (glob($rootDir . '/*.sql') as $f) {
    $sqlFiles[] = [
        'name' => basename($f),
        'path' => $f,
        'size' => filesize($f),
        'modified' => filemtime($f)
    ];
}
usort($sqlFiles, function($a, $b) { return $b['modified'] - $a['modified']; });

// =====================================================================
// --- Get current counts ---
// =====================================================================
function getTableCount($connect, $table) {
    $r = $connect->query("SELECT COUNT(*) AS cnt FROM `$table`");
    return $r ? (int)$r->fetch_assoc()['cnt'] : 0;
}

$currentCatGroup = getTableCount($connect, 'cat_group');
$currentCategory = getTableCount($connect, 'category');
$currentProducts = getTableCount($connect, 'PRODUCTS');

// =====================================================================
// --- SHOW MAIN PAGE ---
// =====================================================================
if ($action === '') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Migrate Data - Category & Products</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .count-badge { font-size: 1.1em; }
</style>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:750px;">
    <h2 class="mb-2">Migrate Data</h2>
    <p class="text-muted mb-4">Import category, sub-category, and product data from old system SQL file.</p>

    <!-- Current Data Summary -->
    <div class="card mb-3">
        <div class="card-header bg-info text-white"><strong>Current Database Records</strong></div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-4">
                    <div class="count-badge fw-bold"><?php echo number_format($currentCatGroup); ?></div>
                    <div class="text-muted small">Categories</div>
                </div>
                <div class="col-4">
                    <div class="count-badge fw-bold"><?php echo number_format($currentCategory); ?></div>
                    <div class="text-muted small">Sub-Categories</div>
                </div>
                <div class="col-4">
                    <div class="count-badge fw-bold"><?php echo number_format($currentProducts); ?></div>
                    <div class="text-muted small">Products</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Option 1: Import SQL File -->
    <div class="card mb-3">
        <div class="card-header bg-dark text-white"><strong>Option 1: Import from SQL File</strong></div>
        <div class="card-body">
            <?php if (empty($sqlFiles)): ?>
                <p class="text-muted mb-0">No <code>.sql</code> files found in root folder.<br>Place your migration SQL file there and refresh.</p>
            <?php else: ?>
                <p class="small text-muted mb-3">Select a SQL file containing <code>category</code>, <code>cat_group</code>, and <code>PRODUCTS</code> data. Tables will be cleared before import.</p>
                <form method="post" id="importForm">
                    <input type="hidden" name="action" value="import_sql">
                    <div class="mb-3">
                    <?php foreach ($sqlFiles as $i => $sf): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="sql_file" id="sf_<?php echo $i; ?>" value="<?php echo htmlspecialchars($sf['name']); ?>">
                            <label class="form-check-label" for="sf_<?php echo $i; ?>">
                                <strong><?php echo htmlspecialchars($sf['name']); ?></strong>
                                <span class="text-muted small">(<?php echo number_format($sf['size'] / 1048576, 1); ?> MB, <?php echo date('Y-m-d H:i', $sf['modified']); ?>)</span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="clear_first" id="clearFirst" value="1" checked>
                        <label class="form-check-label" for="clearFirst">Clear existing data before import (recommended)</label>
                    </div>

                    <button type="submit" class="btn btn-danger" onclick="
                        if (!document.querySelector('input[name=sql_file]:checked')) { alert('Please select a SQL file.'); return false; }
                        if (!confirm('This will import category, sub-category, and product data. Continue?')) return false;
                        this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Importing...'; this.form.submit();
                    ">Import SQL File</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Option 2: Clear All Data -->
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark"><strong>Option 2: Clear All Data</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-3">Remove all categories, sub-categories, and products. Use this to start fresh before importing.</p>
            <form method="post">
                <input type="hidden" name="action" value="clear_all">
                <button type="submit" class="btn btn-warning" onclick="return confirm('This will DELETE all categories, sub-categories, and products. Are you sure?')">
                    Clear All Data
                </button>
            </form>
        </div>
    </div>

    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
</body>
</html>
<?php
    exit;
}

// =====================================================================
// --- HANDLE CLEAR ALL ---
// =====================================================================
if ($action === 'clear_all') {
    $results = [];
    $connect->query("SET FOREIGN_KEY_CHECKS = 0");

    foreach (['category', 'cat_group', 'PRODUCTS'] as $tbl) {
        if ($connect->query("TRUNCATE TABLE `$tbl`")) {
            $results[] = ['ok', "Cleared `$tbl`"];
        } else {
            $results[] = ['fail', "Failed to clear `$tbl`: " . $connect->error];
        }
    }

    $connect->query("SET FOREIGN_KEY_CHECKS = 1");

    showResults('Clear Data Results', $results);
    exit;
}

// =====================================================================
// --- HANDLE IMPORT SQL ---
// =====================================================================
if ($action === 'import_sql') {
    $results = [];
    $selectedFile = $_POST['sql_file'] ?? '';
    $clearFirst = isset($_POST['clear_first']) && $_POST['clear_first'] === '1';

    if ($selectedFile === '') {
        $results[] = ['fail', 'No SQL file selected.'];
        showResults('Import Results', $results);
        exit;
    }

    $filePath = $rootDir . '/' . basename($selectedFile);
    if (!file_exists($filePath)) {
        $results[] = ['fail', 'File not found: ' . basename($selectedFile)];
        showResults('Import Results', $results);
        exit;
    }

    // Clear existing data if requested
    if ($clearFirst) {
        $connect->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach (['category', 'cat_group', 'PRODUCTS'] as $tbl) {
            if ($connect->query("TRUNCATE TABLE `$tbl`")) {
                $results[] = ['ok', "Cleared `$tbl`"];
            } else {
                $results[] = ['fail', "Failed to clear `$tbl`: " . $connect->error];
            }
        }
        $connect->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // Import the SQL file
    $results[] = ['info', 'Importing: ' . basename($selectedFile) . ' (' . number_format(filesize($filePath) / 1048576, 1) . ' MB)'];

    $startTime = microtime(true);
    $result = importSqlFile($connect, $filePath);
    $elapsed = round(microtime(true) - $startTime, 1);

    if ($result['success']) {
        $results[] = ['ok', "Import completed in {$elapsed}s — Executed: {$result['executed']}, Skipped: {$result['skipped']}, Failed: {$result['failed']}"];
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $err) {
                $results[] = ['fail', 'Error: ' . $err];
            }
        }
    } else {
        $results[] = ['fail', 'Import failed: ' . ($result['error'] ?? 'Unknown error')];
    }

    // Show final counts
    $results[] = ['info', '--- Final Record Counts ---'];
    $results[] = ['info', 'Categories (cat_group): ' . number_format(getTableCount($connect, 'cat_group'))];
    $results[] = ['info', 'Sub-Categories (category): ' . number_format(getTableCount($connect, 'category'))];
    $results[] = ['info', 'Products (PRODUCTS): ' . number_format(getTableCount($connect, 'PRODUCTS'))];

    showResults('Import Results', $results);
    exit;
}

// =====================================================================
// --- RESULTS DISPLAY FUNCTION ---
// =====================================================================
function showResults($title, $results) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($title); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:750px;">
    <h2 class="mb-4"><?php echo htmlspecialchars($title); ?></h2>
    <table class="table table-bordered">
        <thead class="table-dark"><tr><th style="width:80px">Status</th><th>Detail</th></tr></thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr class="<?php echo $r[0]==='ok' ? 'table-success' : ($r[0]==='info' ? 'table-info' : ($r[0]==='skip' ? 'table-secondary' : 'table-danger')); ?>">
                <td><strong><?php echo strtoupper($r[0]); ?></strong></td>
                <td><?php echo htmlspecialchars($r[1]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-primary me-2">Back to Dashboard</a>
    <a href="migrate2.php" class="btn btn-outline-secondary">Back to Migrate</a>
</div>
</body>
</html>
<?php
}
?>
