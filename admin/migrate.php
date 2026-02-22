<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

$results = [];
$importResults = [];
$action = $_POST['action'] ?? '';

// =====================================================================
// --- SQL FILE IMPORT (from root folder) ---
// =====================================================================
function importSqlFile($connect, $filePath) {
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    $fileSize = filesize($filePath);
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return ['success' => false, 'error' => 'Cannot open file.', 'executed' => 0, 'failed' => 0, 'skipped' => 0];
    }

    // Disable foreign key checks during import for speed and to avoid ordering issues
    $connect->query("SET FOREIGN_KEY_CHECKS = 0");
    $connect->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $connect->query("SET AUTOCOMMIT = 0");

    $stmt = '';
    $executed = 0;
    $failed = 0;
    $skipped = 0;
    $errors = [];
    $inBlockComment = false;

    while (($line = fgets($handle)) !== false) {
        $trimmed = trim($line);

        // Skip empty lines
        if ($trimmed === '') continue;

        // Handle block comments
        if ($inBlockComment) {
            if (strpos($trimmed, '*/') !== false) {
                $inBlockComment = false;
            }
            continue;
        }

        // Skip single-line comments
        if (strpos($trimmed, '--') === 0) continue;
        if (strpos($trimmed, '#') === 0) continue;

        // Start of block comment (not MySQL conditional)
        if (strpos($trimmed, '/*') === 0 && strpos($trimmed, '/*!') !== 0) {
            if (strpos($trimmed, '*/') === false) {
                $inBlockComment = true;
            }
            continue;
        }

        // Skip database-level statements (we import into current DB)
        $upper = strtoupper($trimmed);
        if (preg_match('/^(CREATE\s+DATABASE|USE\s+`|DROP\s+DATABASE)/i', $upper)) {
            $skipped++;
            continue;
        }

        $stmt .= $line;

        // Check if statement is complete (ends with ;)
        if (preg_match('/;\s*$/', $trimmed)) {
            $sql = trim($stmt);
            $stmt = '';

            if ($sql === '' || $sql === ';') continue;

            if ($connect->query($sql)) {
                $executed++;
                // Commit in batches of 500 for performance
                if ($executed % 500 === 0) {
                    $connect->query("COMMIT");
                    $connect->query("START TRANSACTION");
                }
            } else {
                $err = $connect->error;
                // Ignore duplicate/exists errors silently
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

    // Execute any remaining statement
    $sql = trim($stmt);
    if ($sql !== '' && $sql !== ';') {
        if ($connect->query($sql)) {
            $executed++;
        } else {
            $failed++;
        }
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

// --- Scan for .sql files in root folder ---
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
// --- SHOW FILE PICKER (no action yet) ---
// =====================================================================
if ($action === '') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Database Migration</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:700px;">
    <h2 class="mb-2">Database Migration</h2>
    <p class="text-muted mb-4">Import old SQL data and run migrations to restructure the database for the new inventory program.</p>

    <!-- Step 1: Optional SQL Import -->
    <div class="card mb-3">
        <div class="card-header bg-dark text-white"><strong>Step 1: Import SQL File (Optional)</strong></div>
        <div class="card-body">
            <?php if (empty($sqlFiles)): ?>
                <p class="text-muted mb-0">No <code>.sql</code> files found in root folder (<code><?php echo htmlspecialchars($rootDir); ?></code>).<br>Place your SQL dump file there and refresh.</p>
            <?php else: ?>
                <p class="small text-muted mb-3">Select a SQL file from your project root to import directly. This is much faster than phpMyAdmin for large files.</p>
                <form method="post" id="importForm">
                    <input type="hidden" name="action" value="import_and_migrate">
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
                    <button type="submit" class="btn btn-danger" onclick="return confirm('This will import the selected SQL file into your database. Tables with the same name will be overwritten. Continue?')">
                        Import SQL &amp; Run Migration
                    </button>
                    <span class="text-muted small ms-2">Imports data, then cleans up unused columns</span>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 2: Migrate Only -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white"><strong>Step 2: Run Migration Only</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-3">Skip import. Only create new tables, alter existing tables, and clean up unused columns.</p>
            <form method="post">
                <input type="hidden" name="action" value="migrate_only">
                <button type="submit" class="btn btn-primary">Run Migration Only</button>
            </form>
        </div>
    </div>

    <!-- Step 3: Rebuild Tables -->
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark"><strong>Step 3: Rebuild Tables (Fix "Table Full" Errors)</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-3">Rebuilds PRODUCTS and/or orderlist by creating a lean copy with only needed columns + indexes, then swaps. Fixes "table full" and corrupt index errors. Run one at a time.</p>
            <div class="d-flex gap-2 flex-wrap">
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="rebuild_products">
                    <button type="submit" class="btn btn-warning" onclick="this.disabled=true;this.innerHTML='Rebuilding…';this.form.submit();return true;">Rebuild PRODUCTS</button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="rebuild_orderlist">
                    <button type="submit" class="btn btn-warning" onclick="this.disabled=true;this.innerHTML='Rebuilding…';this.form.submit();return true;">Rebuild orderlist</button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="rebuild_both">
                    <button type="submit" class="btn btn-outline-warning text-dark" onclick="this.disabled=true;this.innerHTML='Rebuilding…';this.form.submit();return true;">Rebuild Both</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Step 4: Optimize -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white"><strong>Step 4: Optimize Database</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-3">Add indexes for fast product search (name, barcode), optimize tables, and analyze table statistics. Run this after import/migration to ensure the best search and browsing performance with large data.</p>
            <form method="post">
                <input type="hidden" name="action" value="optimize">
                <button type="submit" class="btn btn-success">Optimize</button>
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
// --- HANDLE REBUILD TABLES ---
// =====================================================================
if ($action === 'rebuild_products' || $action === 'rebuild_orderlist' || $action === 'rebuild_both') {
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    $rebuildResults = [];

    function rebuildTable($connect, $tableName, $createSql, $columns, &$out) {
        $tmpName = $tableName . '_new';

        // Check source table exists
        $check = $connect->query("SHOW TABLES LIKE '$tableName'");
        if (!$check || $check->num_rows === 0) {
            $out[] = ['fail', "`$tableName` table not found"];
            return false;
        }

        // Count rows
        $cntRes = $connect->query("SELECT COUNT(*) AS cnt FROM `$tableName`");
        $rowCount = $cntRes ? $cntRes->fetch_assoc()['cnt'] : '?';
        $out[] = ['info', "`$tableName`: $rowCount rows to copy"];

        // Drop temp table if leftover from a previous failed attempt
        $connect->query("DROP TABLE IF EXISTS `$tmpName`");

        // Step 1: Create new lean table
        if (!$connect->query($createSql)) {
            $out[] = ['fail', "Create `$tmpName` failed: " . $connect->error];
            return false;
        }
        $out[] = ['ok', "Created `$tmpName` with indexes"];

        // Step 2: Copy data
        $colList = '`' . implode('`,`', $columns) . '`';
        $copySql = "INSERT INTO `$tmpName` ($colList) SELECT $colList FROM `$tableName`";
        if (!$connect->query($copySql)) {
            $out[] = ['fail', "Copy data to `$tmpName` failed: " . $connect->error];
            $connect->query("DROP TABLE IF EXISTS `$tmpName`");
            return false;
        }

        // Verify row count matches
        $newCnt = $connect->query("SELECT COUNT(*) AS cnt FROM `$tmpName`");
        $newCount = $newCnt ? $newCnt->fetch_assoc()['cnt'] : 0;
        $out[] = ['ok', "Copied $newCount rows to `$tmpName`"];

        if ((int)$newCount !== (int)$rowCount) {
            $out[] = ['fail', "Row count mismatch! Original: $rowCount, New: $newCount. Aborting — old table preserved."];
            $connect->query("DROP TABLE IF EXISTS `$tmpName`");
            return false;
        }

        // Step 3: Drop old table
        if (!$connect->query("DROP TABLE `$tableName`")) {
            $out[] = ['fail', "Drop old `$tableName` failed: " . $connect->error];
            return false;
        }
        $out[] = ['ok', "Dropped old `$tableName`"];

        // Step 4: Rename
        if (!$connect->query("RENAME TABLE `$tmpName` TO `$tableName`")) {
            $out[] = ['fail', "Rename `$tmpName` to `$tableName` failed: " . $connect->error];
            return false;
        }
        $out[] = ['ok', "Renamed `$tmpName` -> `$tableName`"];

        // Analyze
        $connect->query("ANALYZE TABLE `$tableName`");
        $out[] = ['ok', "`$tableName` rebuild complete"];
        return true;
    }

    // --- PRODUCTS rebuild ---
    if ($action === 'rebuild_products' || $action === 'rebuild_both') {
        $rebuildResults[] = ['info', '--- PRODUCTS ---'];
        rebuildTable($connect, 'PRODUCTS',
            "CREATE TABLE `PRODUCTS_new` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `cat_code` VARCHAR(50) DEFAULT '',
                `sub_code` VARCHAR(50) DEFAULT '',
                `barcode` VARCHAR(50) DEFAULT '',
                `code` VARCHAR(50) DEFAULT '',
                `cat` VARCHAR(50) DEFAULT '',
                `sub_cat` VARCHAR(50) DEFAULT '',
                `name` VARCHAR(255) DEFAULT '',
                `description` TEXT,
                `img1` VARCHAR(255) DEFAULT '',
                `qoh` DOUBLE DEFAULT 0,
                `uom` VARCHAR(20) DEFAULT '',
                `checked` VARCHAR(5) DEFAULT 'Y',
                `stkcode` VARCHAR(50) DEFAULT '',
                `rack` VARCHAR(70) DEFAULT '',
                INDEX `idx_products_barcode` (`barcode`),
                INDEX `idx_products_name` (`name`),
                INDEX `idx_products_cat_code` (`cat_code`),
                INDEX `idx_products_checked` (`checked`),
                INDEX `idx_products_search` (`name`, `barcode`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            ['id','cat_code','sub_code','barcode','code','cat','sub_cat','name','description','img1','qoh','uom','checked','stkcode','rack'],
            $rebuildResults
        );
    }

    // --- orderlist rebuild ---
    if ($action === 'rebuild_orderlist' || $action === 'rebuild_both') {
        $rebuildResults[] = ['info', '--- orderlist ---'];
        rebuildTable($connect, 'orderlist',
            "CREATE TABLE `orderlist_new` (
                `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `OUTLET` VARCHAR(20) DEFAULT '',
                `SDATE` DATE DEFAULT NULL,
                `ACCODE` VARCHAR(20) DEFAULT '',
                `NAME` VARCHAR(100) DEFAULT '',
                `SALNUM` VARCHAR(50) DEFAULT '',
                `BARCODE` VARCHAR(50) DEFAULT '',
                `PDESC` VARCHAR(100) DEFAULT '',
                `QTY` DOUBLE(8,2) DEFAULT 0,
                `PTYPE` VARCHAR(20) DEFAULT '',
                `TRANSNO` VARCHAR(50) DEFAULT '',
                `TDATE` DATE DEFAULT NULL,
                `TTIME` TIME DEFAULT NULL,
                `STATUS` VARCHAR(20) DEFAULT '',
                `PRINT` VARCHAR(5) DEFAULT '',
                `view_status` VARCHAR(20) DEFAULT '',
                `ADMINRMK` VARCHAR(500) DEFAULT '',
                `SOUND` VARCHAR(5) DEFAULT '',
                `TXTTO` VARCHAR(100) DEFAULT '',
                INDEX `idx_orderlist_barcode` (`BARCODE`),
                INDEX `idx_orderlist_sdate_status` (`SDATE`, `STATUS`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            ['ID','OUTLET','SDATE','ACCODE','NAME','SALNUM','BARCODE','PDESC','QTY','PTYPE','TRANSNO','TDATE','TTIME','STATUS','PRINT','view_status','ADMINRMK','SOUND','TXTTO'],
            $rebuildResults
        );
    }

    // --- Show results ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Rebuild Tables Results</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:700px;">
    <h2 class="mb-4">Rebuild Tables Results</h2>
    <table class="table table-bordered">
        <thead class="table-dark"><tr><th style="width:80px">Status</th><th>Detail</th></tr></thead>
        <tbody>
        <?php foreach ($rebuildResults as $r): ?>
            <tr class="<?php echo $r[0]==='ok' ? 'table-success' : ($r[0]==='info' ? 'table-info' : ($r[0]==='skip' ? 'table-secondary' : 'table-danger')); ?>">
                <td><strong><?php echo strtoupper($r[0]); ?></strong></td>
                <td><?php echo htmlspecialchars($r[1]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-primary me-2">Back to Dashboard</a>
    <a href="migrate.php" class="btn btn-outline-secondary">Run Again</a>
</div>
</body>
</html>
<?php
    exit;
}

// =====================================================================
// --- HANDLE OPTIMIZE ---
// =====================================================================
if ($action === 'optimize') {
    $optimizeResults = [];

    // Helper: add index if it doesn't already exist
    function addIndexIfMissing($connect, $table, $indexName, $indexSql, &$out) {
        $check = $connect->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
        if ($check && $check->num_rows > 0) {
            $out[] = ['skip', "Index `$indexName` on `$table` already exists"];
        } else {
            if ($connect->query($indexSql)) {
                $out[] = ['ok', "Added index `$indexName` on `$table`"];
            } else {
                $err = $connect->error;
                if (strpos($err, 'Duplicate') !== false || strpos($err, 'already exists') !== false) {
                    $out[] = ['skip', "Index `$indexName` on `$table` already exists"];
                } else {
                    $out[] = ['fail', "Index `$indexName` on `$table`: $err"];
                }
            }
        }
    }

    // ------ PRODUCTS table indexes ------
    $prodCheck = $connect->query("SHOW TABLES LIKE 'PRODUCTS'");
    if ($prodCheck && $prodCheck->num_rows > 0) {
        addIndexIfMissing($connect, 'PRODUCTS', 'idx_products_barcode',
            "ALTER TABLE `PRODUCTS` ADD INDEX `idx_products_barcode` (`barcode`)", $optimizeResults);

        addIndexIfMissing($connect, 'PRODUCTS', 'idx_products_name',
            "ALTER TABLE `PRODUCTS` ADD INDEX `idx_products_name` (`name`)", $optimizeResults);

        addIndexIfMissing($connect, 'PRODUCTS', 'idx_products_cat_code',
            "ALTER TABLE `PRODUCTS` ADD INDEX `idx_products_cat_code` (`cat_code`)", $optimizeResults);

        addIndexIfMissing($connect, 'PRODUCTS', 'idx_products_checked',
            "ALTER TABLE `PRODUCTS` ADD INDEX `idx_products_checked` (`checked`)", $optimizeResults);

        addIndexIfMissing($connect, 'PRODUCTS', 'idx_products_search',
            "ALTER TABLE `PRODUCTS` ADD INDEX `idx_products_search` (`name`, `barcode`)", $optimizeResults);
    } else {
        $optimizeResults[] = ['skip', 'PRODUCTS table not found'];
    }

    // ------ category table indexes ------
    $catCheck = $connect->query("SHOW TABLES LIKE 'category'");
    if ($catCheck && $catCheck->num_rows > 0) {
        addIndexIfMissing($connect, 'category', 'idx_category_cat_code',
            "ALTER TABLE `category` ADD INDEX `idx_category_cat_code` (`cat_code`)", $optimizeResults);
    }

    // ------ orderlist table indexes ------
    $olCheck = $connect->query("SHOW TABLES LIKE 'orderlist'");
    if ($olCheck && $olCheck->num_rows > 0) {
        addIndexIfMissing($connect, 'orderlist', 'idx_orderlist_barcode',
            "ALTER TABLE `orderlist` ADD INDEX `idx_orderlist_barcode` (`BARCODE`)", $optimizeResults);

        addIndexIfMissing($connect, 'orderlist', 'idx_orderlist_sdate_status',
            "ALTER TABLE `orderlist` ADD INDEX `idx_orderlist_sdate_status` (`SDATE`, `STATUS`)", $optimizeResults);
    }

    // ------ stock_take_item table indexes ------
    $stiCheck = $connect->query("SHOW TABLES LIKE 'stock_take_item'");
    if ($stiCheck && $stiCheck->num_rows > 0) {
        addIndexIfMissing($connect, 'stock_take_item', 'idx_sti_barcode',
            "ALTER TABLE `stock_take_item` ADD INDEX `idx_sti_barcode` (`barcode`)", $optimizeResults);

        addIndexIfMissing($connect, 'stock_take_item', 'idx_sti_stock_take_id',
            "ALTER TABLE `stock_take_item` ADD INDEX `idx_sti_stock_take_id` (`stock_take_id`)", $optimizeResults);
    }

    // ------ stockadj table indexes ------
    $saCheck = $connect->query("SHOW TABLES LIKE 'stockadj'");
    if ($saCheck && $saCheck->num_rows > 0) {
        addIndexIfMissing($connect, 'stockadj', 'idx_stockadj_barcode',
            "ALTER TABLE `stockadj` ADD INDEX `idx_stockadj_barcode` (`BARCODE`)", $optimizeResults);
    }

    // ------ OPTIMIZE & ANALYZE key tables ------
    $tablesToOptimize = ['PRODUCTS', 'category', 'orderlist', 'stock_take_item', 'stockadj', 'sysfile'];
    foreach ($tablesToOptimize as $tbl) {
        $tblCheck = $connect->query("SHOW TABLES LIKE '$tbl'");
        if ($tblCheck && $tblCheck->num_rows > 0) {
            $connect->query("OPTIMIZE TABLE `$tbl`");
            if ($connect->query("ANALYZE TABLE `$tbl`")) {
                $optimizeResults[] = ['ok', "OPTIMIZE + ANALYZE `$tbl`"];
            } else {
                $optimizeResults[] = ['fail', "ANALYZE `$tbl`: " . $connect->error];
            }
        }
    }

    // --- Show results ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Database Optimization Results</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:700px;">
    <h2 class="mb-4">Database Optimization Results</h2>
    <table class="table table-bordered">
        <thead class="table-dark"><tr><th style="width:80px">Status</th><th>Detail</th></tr></thead>
        <tbody>
        <?php foreach ($optimizeResults as $r): ?>
            <tr class="<?php echo $r[0]==='ok' ? 'table-success' : ($r[0]==='skip' ? 'table-secondary' : 'table-danger'); ?>">
                <td><strong><?php echo strtoupper($r[0]); ?></strong></td>
                <td><?php echo htmlspecialchars($r[1]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-primary me-2">Back to Dashboard</a>
    <a href="migrate.php" class="btn btn-outline-secondary">Run Again</a>
</div>
</body>
</html>
<?php
    exit;
}

// =====================================================================
// --- HANDLE IMPORT ---
// =====================================================================
if ($action === 'import_and_migrate') {
    $selectedFile = $_POST['sql_file'] ?? '';
    if ($selectedFile === '') {
        $importResults[] = ['fail', 'No SQL file selected.'];
    } else {
        $filePath = $rootDir . '/' . basename($selectedFile);
        if (!file_exists($filePath)) {
            $importResults[] = ['fail', 'File not found: ' . basename($selectedFile)];
        } else {
            $importResults[] = ['info', 'Importing: ' . basename($selectedFile) . ' (' . number_format(filesize($filePath) / 1048576, 1) . ' MB)'];

            $startTime = microtime(true);
            $result = importSqlFile($connect, $filePath);
            $elapsed = round(microtime(true) - $startTime, 1);

            if ($result['success']) {
                $importResults[] = ['ok', "Import completed in {$elapsed}s - Executed: {$result['executed']}, Skipped: {$result['skipped']}, Failed: {$result['failed']}"];
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $err) {
                        $importResults[] = ['fail', 'Import error: ' . $err];
                    }
                }
            } else {
                $importResults[] = ['fail', 'Import failed: ' . ($result['error'] ?? 'Unknown error')];
            }
        }
    }
}

// =====================================================================
// --- RUN MIGRATIONS (always runs after import or on migrate_only) ---
// =====================================================================

function runMigration($connect, $label, $sql) {
    global $results;
    if ($connect->query($sql)) {
        $results[] = ['ok', $label];
    } else {
        $err = $connect->error;
        if (strpos($err, 'already exists') !== false || strpos($err, 'Duplicate column') !== false) {
            $results[] = ['skip', $label . ' (already exists)'];
        } else {
            $results[] = ['fail', $label . ': ' . $err];
        }
    }
}

// =====================================================================
// --- BASE TABLES (required for system to function) ---
// =====================================================================

// --- sysfile (User/Admin/Staff authentication) ---
runMigration($connect, 'Create sysfile table', "
CREATE TABLE IF NOT EXISTS `sysfile` (
  `ID` INT AUTO_INCREMENT PRIMARY KEY,
  `USER1` VARCHAR(100) NOT NULL,
  `USER2` VARCHAR(100) NOT NULL,
  `USER_NAME` VARCHAR(100) DEFAULT '',
  `USERNAME` VARCHAR(20) DEFAULT '',
  `TYPE` VARCHAR(5) DEFAULT 'S',
  `STATUS` VARCHAR(5) DEFAULT 'Y',
  `OUTLET` VARCHAR(20) DEFAULT 'MAIN'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Seed default admin user if sysfile is empty
$sysCheck = $connect->query("SELECT COUNT(*) AS cnt FROM `sysfile`");
if ($sysCheck && $sysCheck->fetch_assoc()['cnt'] == 0) {
    $connect->query("INSERT INTO `sysfile` (`USER1`,`USER2`,`USER_NAME`,`USERNAME`,`TYPE`,`STATUS`,`OUTLET`) VALUES ('admin','admin','Administrator','ADM0001','A','Y','MAIN')");
    $results[] = ['ok', 'Seed: default admin user created (admin/admin)'];
} else {
    $results[] = ['skip', 'Seed: sysfile already has users'];
}

// --- stockadj (Stock adjustment log) ---
runMigration($connect, 'Create stockadj table', "
CREATE TABLE IF NOT EXISTS `stockadj` (
  `ID` INT AUTO_INCREMENT PRIMARY KEY,
  `ACCODE` VARCHAR(20) DEFAULT '',
  `USER` VARCHAR(50) DEFAULT '',
  `OUTLET` VARCHAR(20) DEFAULT '',
  `SDATE` DATE DEFAULT NULL,
  `STIME` TIME DEFAULT NULL,
  `SALNUM` VARCHAR(50) DEFAULT '',
  `BARCODE` VARCHAR(50) NOT NULL,
  `PDESC` VARCHAR(100) DEFAULT '',
  `QTYADJ` DOUBLE(8,2) DEFAULT 0,
  `REMARK` VARCHAR(500) DEFAULT '',
  `LOSS_REASON` ENUM('SPOILAGE','DAMAGE','THEFT','EXPIRED','OTHER','ADJUSTMENT') DEFAULT 'ADJUSTMENT'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- parafile (System counters) ---
runMigration($connect, 'Create parafile table', "
CREATE TABLE IF NOT EXISTS `parafile` (
  `ID` INT AUTO_INCREMENT PRIMARY KEY,
  `PO_NUM` VARCHAR(8) NOT NULL DEFAULT '',
  `GRN_NUM` VARCHAR(8) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Seed parafile with initial row if empty
$paraCheck = $connect->query("SELECT COUNT(*) AS cnt FROM `parafile`");
if ($paraCheck && $paraCheck->fetch_assoc()['cnt'] == 0) {
    $connect->query("INSERT INTO `parafile` (`PO_NUM`,`GRN_NUM`) VALUES ('','')");
    $results[] = ['ok', 'Seed: parafile initial row created'];
} else {
    $results[] = ['skip', 'Seed: parafile already has data'];
}

// --- MEMBER (Customer/member contacts for orders) ---
runMigration($connect, 'Create MEMBER table', "
CREATE TABLE IF NOT EXISTS `MEMBER` (
  `ID` INT AUTO_INCREMENT PRIMARY KEY,
  `ACCODE` VARCHAR(20) NOT NULL DEFAULT '',
  `HP` VARCHAR(50) DEFAULT '',
  `EMAIL` VARCHAR(100) DEFAULT '',
  `ADD1` VARCHAR(255) DEFAULT '',
  `ADD2` VARCHAR(255) DEFAULT '',
  `ADD3` VARCHAR(255) DEFAULT '',
  UNIQUE KEY `uq_accode` (`ACCODE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- outlet (Outlet/branch info for orders) ---
runMigration($connect, 'Create outlet table', "
CREATE TABLE IF NOT EXISTS `outlet` (
  `ID` INT AUTO_INCREMENT PRIMARY KEY,
  `CODE` VARCHAR(20) NOT NULL DEFAULT '',
  `PDESC` VARCHAR(100) DEFAULT '',
  `ADDRESS` TEXT,
  `CONTACT` VARCHAR(100) DEFAULT '',
  UNIQUE KEY `uq_code` (`CODE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// =====================================================================
// --- FEATURE TABLES ---
// =====================================================================

// --- Supplier Master ---
runMigration($connect, 'Create supplier table', "
CREATE TABLE IF NOT EXISTS `supplier` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT '',
  `phone` VARCHAR(50) DEFAULT '',
  `email` VARCHAR(100) DEFAULT '',
  `address` TEXT,
  `payment_terms` VARCHAR(50) DEFAULT '',
  `lead_time_days` INT DEFAULT 0,
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Purchase Order Header ---
runMigration($connect, 'Create purchase_order table', "
CREATE TABLE IF NOT EXISTS `purchase_order` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `po_number` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_id` INT NOT NULL,
  `order_date` DATE NOT NULL,
  `expected_date` DATE DEFAULT NULL,
  `status` ENUM('DRAFT','APPROVED','PARTIALLY_RECEIVED','RECEIVED','CLOSED','CANCELLED') DEFAULT 'DRAFT',
  `total_amount` DOUBLE(15,2) DEFAULT 0.00,
  `remark` TEXT,
  `created_by` VARCHAR(50) DEFAULT '',
  `approved_by` VARCHAR(50) DEFAULT '',
  `approved_date` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Purchase Order Line Items ---
runMigration($connect, 'Create purchase_order_item table', "
CREATE TABLE IF NOT EXISTS `purchase_order_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `po_id` INT NOT NULL,
  `barcode` VARCHAR(50) NOT NULL,
  `product_desc` VARCHAR(100) DEFAULT '',
  `qty_ordered` DOUBLE(8,2) NOT NULL,
  `qty_received` DOUBLE(8,2) DEFAULT 0.00,
  `unit_cost` DOUBLE(10,2) NOT NULL,
  `uom` VARCHAR(20) DEFAULT '',
  `remark` VARCHAR(200) DEFAULT '',
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Goods Receiving Note Header ---
runMigration($connect, 'Create grn table', "
CREATE TABLE IF NOT EXISTS `grn` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `grn_number` VARCHAR(50) NOT NULL UNIQUE,
  `po_id` INT DEFAULT NULL,
  `supplier_id` INT NOT NULL,
  `receive_date` DATE NOT NULL,
  `received_by` VARCHAR(50) DEFAULT '',
  `remark` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order`(`id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- GRN Line Items ---
runMigration($connect, 'Create grn_item table', "
CREATE TABLE IF NOT EXISTS `grn_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `grn_id` INT NOT NULL,
  `po_item_id` INT DEFAULT NULL,
  `barcode` VARCHAR(50) NOT NULL,
  `product_desc` VARCHAR(100) DEFAULT '',
  `qty_received` DOUBLE(8,2) NOT NULL,
  `qty_rejected` DOUBLE(8,2) DEFAULT 0.00,
  `unit_cost` DOUBLE(10,2) DEFAULT 0.00,
  `batch_no` VARCHAR(16) DEFAULT '',
  `exp_date` DATE DEFAULT NULL,
  `rack_location` VARCHAR(70) DEFAULT '',
  `remark` VARCHAR(200) DEFAULT '',
  FOREIGN KEY (`grn_id`) REFERENCES `grn`(`id`),
  FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_item`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Stock Take Session ---
runMigration($connect, 'Create stock_take table', "
CREATE TABLE IF NOT EXISTS `stock_take` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_code` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(200) DEFAULT '',
  `type` ENUM('FULL','PARTIAL') DEFAULT 'FULL',
  `filter_cat` VARCHAR(50) DEFAULT NULL,
  `filter_location` VARCHAR(70) DEFAULT NULL,
  `status` ENUM('OPEN','IN_PROGRESS','COMPLETED') DEFAULT 'OPEN',
  `created_by` VARCHAR(50) DEFAULT '',
  `completed_by` VARCHAR(50) DEFAULT '',
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Stock Take Line Items ---
runMigration($connect, 'Create stock_take_item table', "
CREATE TABLE IF NOT EXISTS `stock_take_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `stock_take_id` INT NOT NULL,
  `barcode` VARCHAR(50) NOT NULL,
  `product_desc` VARCHAR(100) DEFAULT '',
  `system_qty` DOUBLE(8,2) NOT NULL,
  `counted_qty` DOUBLE(8,2) DEFAULT NULL,
  `variance` DOUBLE(8,2) DEFAULT NULL,
  `adj_applied` TINYINT(1) DEFAULT 0,
  `remark` VARCHAR(200) DEFAULT '',
  `counted_by` VARCHAR(50) DEFAULT '',
  `counted_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`stock_take_id`) REFERENCES `stock_take`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- ALTER existing tables ---

// Add LOSS_REASON to stockadj
$colCheck3 = $connect->query("SHOW COLUMNS FROM `stockadj` LIKE 'LOSS_REASON'");
if ($colCheck3 && $colCheck3->num_rows === 0) {
    runMigration($connect, 'Add LOSS_REASON to stockadj', "ALTER TABLE `stockadj` ADD COLUMN `LOSS_REASON` ENUM('SPOILAGE','DAMAGE','THEFT','EXPIRED','OTHER','ADJUSTMENT') DEFAULT 'ADJUSTMENT'");
} else {
    $results[] = ['skip', 'Add LOSS_REASON to stockadj (already exists)'];
}

// Add PO/GRN counter fields to parafile
$colCheck4 = $connect->query("SHOW COLUMNS FROM `parafile` LIKE 'PO_NUM'");
if ($colCheck4 && $colCheck4->num_rows === 0) {
    runMigration($connect, 'Add PO_NUM to parafile', "ALTER TABLE `parafile` ADD COLUMN `PO_NUM` VARCHAR(8) NOT NULL DEFAULT ''");
} else {
    $results[] = ['skip', 'Add PO_NUM to parafile (already exists)'];
}

$colCheck5 = $connect->query("SHOW COLUMNS FROM `parafile` LIKE 'GRN_NUM'");
if ($colCheck5 && $colCheck5->num_rows === 0) {
    runMigration($connect, 'Add GRN_NUM to parafile', "ALTER TABLE `parafile` ADD COLUMN `GRN_NUM` VARCHAR(8) NOT NULL DEFAULT ''");
} else {
    $results[] = ['skip', 'Add GRN_NUM to parafile (already exists)'];
}

// --- Alter stock_take status enum to add DRAFT, SUBMITTED, APPROVED ---
runMigration($connect, 'Alter stock_take status enum', "
ALTER TABLE `stock_take` MODIFY COLUMN `status` ENUM('OPEN','IN_PROGRESS','COMPLETED','DRAFT','SUBMITTED','APPROVED') DEFAULT 'DRAFT'
");

// Add submitted_by and submitted_at columns to stock_take
$colCheck6 = $connect->query("SHOW COLUMNS FROM `stock_take` LIKE 'submitted_by'");
if ($colCheck6 && $colCheck6->num_rows === 0) {
    runMigration($connect, 'Add submitted_by to stock_take', "ALTER TABLE `stock_take` ADD COLUMN `submitted_by` VARCHAR(50) DEFAULT '' AFTER `completed_at`");
} else {
    $results[] = ['skip', 'Add submitted_by to stock_take (already exists)'];
}

$colCheck7 = $connect->query("SHOW COLUMNS FROM `stock_take` LIKE 'submitted_at'");
if ($colCheck7 && $colCheck7->num_rows === 0) {
    runMigration($connect, 'Add submitted_at to stock_take', "ALTER TABLE `stock_take` ADD COLUMN `submitted_at` DATETIME DEFAULT NULL AFTER `submitted_by`");
} else {
    $results[] = ['skip', 'Add submitted_at to stock_take (already exists)'];
}

$colCheck8 = $connect->query("SHOW COLUMNS FROM `stock_take` LIKE 'approved_by'");
if ($colCheck8 && $colCheck8->num_rows === 0) {
    runMigration($connect, 'Add approved_by to stock_take', "ALTER TABLE `stock_take` ADD COLUMN `approved_by` VARCHAR(50) DEFAULT '' AFTER `submitted_at`");
} else {
    $results[] = ['skip', 'Add approved_by to stock_take (already exists)'];
}

$colCheck9 = $connect->query("SHOW COLUMNS FROM `stock_take` LIKE 'approved_at'");
if ($colCheck9 && $colCheck9->num_rows === 0) {
    runMigration($connect, 'Add approved_at to stock_take', "ALTER TABLE `stock_take` ADD COLUMN `approved_at` DATETIME DEFAULT NULL AFTER `approved_by`");
} else {
    $results[] = ['skip', 'Add approved_at to stock_take (already exists)'];
}

// --- Product Category ---
runMigration($connect, 'Create product_category table', "
CREATE TABLE IF NOT EXISTS `product_category` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Product Sub Category ---
runMigration($connect, 'Create product_sub_category table', "
CREATE TABLE IF NOT EXISTS `product_sub_category` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_cat_sub` (`category_id`, `name`),
  FOREIGN KEY (`category_id`) REFERENCES `product_category`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Product UOM ---
runMigration($connect, 'Create product_uom table', "
CREATE TABLE IF NOT EXISTS `product_uom` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Rack ---
runMigration($connect, 'Create rack table', "
CREATE TABLE IF NOT EXISTS `rack` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(200) DEFAULT '',
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Rack-Product Mapping ---
runMigration($connect, 'Create rack_product table', "
CREATE TABLE IF NOT EXISTS `rack_product` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rack_id` INT NOT NULL,
  `barcode` VARCHAR(50) NOT NULL,
  `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_rack_barcode` (`rack_id`, `barcode`),
  FOREIGN KEY (`rack_id`) REFERENCES `rack`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Product Trend Config ---
runMigration($connect, 'Create product_trend_config table', "
CREATE TABLE IF NOT EXISTS `product_trend_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `date_from` DATE NOT NULL,
  `date_to` DATE NOT NULL,
  `green_min` INT NOT NULL DEFAULT 50,
  `yellow_min` INT NOT NULL DEFAULT 10,
  `red_min` INT NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// =====================================================================
// --- CLEANUP: Drop unused columns from legacy/imported tables ---
// =====================================================================
// When importing old parkdeptmain data, tables come with many columns
// that are no longer used in the new inventory program. This section
// automatically detects and drops those unused columns.

// Define the columns each table SHOULD keep (used by the new program)
$keepColumns = [
    'PRODUCTS' => ['id', 'cat_code', 'sub_code', 'barcode', 'code', 'cat', 'sub_cat', 'name', 'description', 'img1', 'qoh', 'uom', 'checked', 'stkcode', 'rack'],
    'orderlist' => ['ID', 'OUTLET', 'SDATE', 'ACCODE', 'NAME', 'SALNUM', 'BARCODE', 'PDESC', 'QTY', 'PTYPE', 'TRANSNO', 'TDATE', 'TTIME', 'STATUS', 'PRINT', 'view_status', 'ADMINRMK', 'SOUND', 'TXTTO'],
    'stockadj' => ['ID', 'ACCODE', 'USER', 'OUTLET', 'SDATE', 'STIME', 'SALNUM', 'BARCODE', 'PDESC', 'QTYADJ', 'REMARK', 'LOSS_REASON'],
    'sysfile' => ['ID', 'USER1', 'USER2', 'USER_NAME', 'USERNAME', 'TYPE', 'STATUS', 'OUTLET'],
];

foreach ($keepColumns as $table => $keep) {
    // Check if table exists
    $tableCheck = $connect->query("SHOW TABLES LIKE '$table'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        continue; // Table doesn't exist, skip
    }

    // Get current columns
    $colResult = $connect->query("SHOW COLUMNS FROM `$table`");
    if (!$colResult) continue;

    $currentCols = [];
    while ($col = $colResult->fetch_assoc()) {
        $currentCols[] = $col['Field'];
    }

    // Find columns to drop (case-insensitive comparison)
    $keepLower = array_map('strtolower', $keep);
    $dropCols = [];
    foreach ($currentCols as $col) {
        if (!in_array(strtolower($col), $keepLower)) {
            $dropCols[] = $col;
        }
    }

    if (empty($dropCols)) {
        $results[] = ['skip', "Cleanup $table: no unused columns found"];
    } else {
        // Batch all DROP COLUMNs into a single ALTER TABLE statement
        // This rebuilds the table only ONCE instead of once per column,
        // which avoids "table is full" errors on limited disk space.
        $dropParts = array_map(function($col) { return "DROP COLUMN `$col`"; }, $dropCols);
        $sql = "ALTER TABLE `$table` " . implode(', ', $dropParts);
        if ($connect->query($sql)) {
            $results[] = ['ok', "Cleanup $table: dropped " . count($dropCols) . " unused columns (" . implode(', ', $dropCols) . ")"];
        } else {
            $err = $connect->error;
            if (strpos($err, "check that column/key exists") !== false) {
                $results[] = ['skip', "Cleanup $table: columns already removed"];
            } else {
                $results[] = ['fail', "Cleanup $table: " . $err];
            }
        }
    }
}

// --- Drop unused legacy tables ---
$dropTables = ['cat_group', 'stockin', 'stockout'];
foreach ($dropTables as $dropTable) {
    $tableCheck = $connect->query("SHOW TABLES LIKE '$dropTable'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        if ($connect->query("DROP TABLE `$dropTable`")) {
            $results[] = ['ok', "Cleanup: dropped unused table `$dropTable`"];
        } else {
            $results[] = ['fail', "Cleanup: drop table `$dropTable` failed: " . $connect->error];
        }
    } else {
        $results[] = ['skip', "Cleanup: table `$dropTable` does not exist"];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Database Migration</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:700px;">
    <h2 class="mb-4">Database Migration Results</h2>

    <?php if (!empty($importResults)): ?>
    <h5 class="mt-3">SQL Import</h5>
    <table class="table table-bordered mb-4">
        <thead class="table-dark"><tr><th style="width:80px">Status</th><th>Detail</th></tr></thead>
        <tbody>
        <?php foreach ($importResults as $r): ?>
            <tr class="<?php echo $r[0]==='ok' ? 'table-success' : ($r[0]==='info' ? 'table-info' : ($r[0]==='skip' ? 'table-secondary' : 'table-danger')); ?>">
                <td><strong><?php echo strtoupper($r[0]); ?></strong></td>
                <td><?php echo htmlspecialchars($r[1]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h5>Migrations &amp; Cleanup</h5>
    <table class="table table-bordered">
        <thead class="table-dark"><tr><th style="width:80px">Status</th><th>Migration</th></tr></thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr class="<?php echo $r[0]==='ok' ? 'table-success' : ($r[0]==='skip' ? 'table-secondary' : 'table-danger'); ?>">
                <td><strong><?php echo strtoupper($r[0]); ?></strong></td>
                <td><?php echo htmlspecialchars($r[1]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-primary me-2">Back to Dashboard</a>
    <a href="migrate.php" class="btn btn-outline-secondary">Run Again</a>
</div>
</body>
</html>
