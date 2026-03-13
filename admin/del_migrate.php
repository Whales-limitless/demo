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
$results = [];

// =====================================================================
// --- TABLE MAPPING: old parkwaydeliver -> new pw_main (del_ prefix) ---
// =====================================================================
$tableMappings = [
    'customer' => [
        'target' => 'del_customer',
        'label'  => 'Customers',
        'columns' => [
            // old columns => new columns (mapped)
            'ID'       => 'ID',
            'CODE'     => 'CODE',
            'HP'       => 'HP',
            'NAME'     => 'NAME',
            'LOCATION' => 'LOCATION',
            'ADDRESS'  => 'ADDRESS',
            'EMAIL'    => 'EMAIL',
        ],
        // old table has POSTCODE, STATE, AREA that new table doesn't have - skip them
    ],
    'driver' => [
        'target' => 'del_driver',
        'label'  => 'Drivers',
        'columns' => [
            'ID'       => 'ID',
            'CODE'     => 'CODE',
            'HP'       => 'HP',
            'NAME'     => 'NAME',
            'ADDRESS'  => 'ADDRESS',
            'POSTCODE' => 'POSTCODE',
            'STATE'    => 'STATE',
            'AREA'     => 'AREA',
            'EMAIL'    => 'EMAIL',
            'USERNAME' => 'USERNAME',
            'PASSWORD' => 'PASSWORD',
        ],
    ],
    'location' => [
        'target' => 'del_location',
        'label'  => 'Locations',
        'columns' => [
            'ID'      => 'ID',
            'NAME'    => 'NAME',
            'POSTCODE'=> 'POSTCODE',
            'DISTANT' => 'DISTANT',
            'RETAIL'  => 'RETAIL',
        ],
    ],
    'orderlist' => [
        'target' => 'del_orderlist',
        'label'  => 'Delivery Orders',
        'columns' => [
            'ID'           => 'ID',
            'ORDNO'        => 'ORDNO',
            'DELDATE'      => 'DELDATE',
            'DRIVERCODE'   => 'DRIVERCODE',
            'DRIVER'       => 'DRIVER',
            'CUSTOMERCODE' => 'CUSTOMERCODE',
            'CUSTOMER'     => 'CUSTOMER',
            'LOCATION'     => 'LOCATION',
            'DISTANT'      => 'DISTANT',
            'RETAIL'       => 'RETAIL',
            'REMARK'       => 'REMARK',
            'IMG1'         => 'IMG1',
            'IMG2'         => 'IMG2',
            'IMG3'         => 'IMG3',
            'DONEDATETIME' => 'DONEDATETIME',
            'STATUS'       => 'STATUS',
        ],
        // old table has IMG1BLOB - skip it
    ],
    'orderlistdesc' => [
        'target' => 'del_orderlistdesc',
        'label'  => 'Order Items',
        'columns' => [
            'ID'     => 'ID',
            'ORDERNO'=> 'ORDERNO',
            'PDESC'  => 'PDESC',
            'QTY'    => 'QTY',
            'UOM'    => 'UOM',
        ],
        // new table has INSTALL, INSTALL_IMG columns - they'll default to empty
    ],
    'sign' => [
        'target' => 'del_sign',
        'label'  => 'Signatures',
        'columns' => [
            'ID'   => 'ID',
            'ORDNO'=> 'ORDNO',
        ],
        // old table has CREATEON - skip it
    ],
    'uom' => [
        'target' => 'del_uom',
        'label'  => 'Units of Measure',
        'columns' => [
            'ID'    => 'ID',
            'PDESC' => 'PDESC',
        ],
    ],
];

// =====================================================================
// --- HANDLE MIGRATION ---
// =====================================================================
if ($action === 'migrate') {
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    $selectedTables = $_POST['tables'] ?? [];
    $clearFirst = isset($_POST['clear_first']);

    if (empty($selectedTables)) {
        $results[] = ['fail', 'No tables selected for migration.'];
    } else {
        // Connect to old delivery database
        $oldConnect = new mysqli('localhost', 'u921536699_parkwaydeliver', 'Parkway@123#', 'u921536699_parkwaydeliver');
        if ($oldConnect->connect_error) {
            $results[] = ['fail', 'Cannot connect to old delivery database: ' . $oldConnect->connect_error];
        } else {
            $oldConnect->set_charset("utf8mb4");
            $results[] = ['ok', 'Connected to old delivery database (u921536699_parkwaydeliver)'];

            $connect->query("SET FOREIGN_KEY_CHECKS = 0");
            $connect->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

            foreach ($selectedTables as $oldTable) {
                if (!isset($tableMappings[$oldTable])) {
                    $results[] = ['fail', "Unknown table: $oldTable"];
                    continue;
                }

                $mapping = $tableMappings[$oldTable];
                $targetTable = $mapping['target'];
                $label = $mapping['label'];
                $colMap = $mapping['columns'];

                $results[] = ['info', "--- Migrating: $label ($oldTable -> $targetTable) ---"];

                // Check if old table exists
                $check = $oldConnect->query("SHOW TABLES LIKE '$oldTable'");
                if (!$check || $check->num_rows === 0) {
                    $results[] = ['fail', "Source table `$oldTable` not found in old database"];
                    continue;
                }

                // Check if target table exists
                $check2 = $connect->query("SHOW TABLES LIKE '$targetTable'");
                if (!$check2 || $check2->num_rows === 0) {
                    $results[] = ['fail', "Target table `$targetTable` not found in current database"];
                    continue;
                }

                // Count source rows
                $cntRes = $oldConnect->query("SELECT COUNT(*) AS cnt FROM `$oldTable`");
                $srcCount = $cntRes ? $cntRes->fetch_assoc()['cnt'] : 0;
                $results[] = ['info', "Source: $srcCount rows in `$oldTable`"];

                if ($srcCount == 0) {
                    $results[] = ['skip', "No data to migrate for `$oldTable`"];
                    continue;
                }

                // Clear target table if requested
                if ($clearFirst) {
                    $connect->query("DELETE FROM `$targetTable`");
                    $results[] = ['ok', "Cleared `$targetTable`"];
                    // Reset auto-increment
                    $connect->query("ALTER TABLE `$targetTable` AUTO_INCREMENT = 1");
                }

                // Count existing rows in target before insert
                $existBefore = $connect->query("SELECT COUNT(*) AS cnt FROM `$targetTable`");
                $beforeCount = $existBefore ? $existBefore->fetch_assoc()['cnt'] : 0;

                // Build SELECT from old, INSERT into new
                $oldCols = array_keys($colMap);
                $newCols = array_values($colMap);
                $oldColList = '`' . implode('`,`', $oldCols) . '`';
                $newColList = '`' . implode('`,`', $newCols) . '`';

                // Read data from old DB in batches
                $batchSize = 500;
                $offset = 0;
                $inserted = 0;
                $skipped = 0;
                $errors = [];

                $connect->begin_transaction();

                while (true) {
                    $sql = "SELECT $oldColList FROM `$oldTable` ORDER BY `ID` LIMIT $batchSize OFFSET $offset";
                    $result = $oldConnect->query($sql);

                    if (!$result || $result->num_rows === 0) break;

                    while ($row = $result->fetch_assoc()) {
                        // Build INSERT with proper escaping
                        $values = [];
                        foreach ($oldCols as $col) {
                            $val = $row[$col] ?? '';
                            $values[] = "'" . $connect->real_escape_string($val) . "'";
                        }
                        $valStr = implode(',', $values);

                        $insertSql = "INSERT INTO `$targetTable` ($newColList) VALUES ($valStr)";
                        if ($connect->query($insertSql)) {
                            $inserted++;
                        } else {
                            // If duplicate ID, try without ID (let auto-increment)
                            if ($connect->errno === 1062) {
                                $skipped++;
                            } else {
                                if (count($errors) < 5) {
                                    $errors[] = $connect->error;
                                }
                                $skipped++;
                            }
                        }
                    }

                    $offset += $batchSize;
                    $result->free();
                }

                $connect->commit();

                $results[] = ['ok', "Inserted: $inserted rows into `$targetTable`"];
                if ($skipped > 0) {
                    $results[] = ['skip', "Skipped: $skipped rows (duplicates or errors)"];
                }
                if (!empty($errors)) {
                    foreach ($errors as $err) {
                        $results[] = ['fail', "Error: $err"];
                    }
                }

                // Verify final count
                $finalRes = $connect->query("SELECT COUNT(*) AS cnt FROM `$targetTable`");
                $finalCount = $finalRes ? $finalRes->fetch_assoc()['cnt'] : 0;
                $results[] = ['ok', "Final count in `$targetTable`: $finalCount rows"];
            }

            $connect->query("SET FOREIGN_KEY_CHECKS = 1");
            $oldConnect->close();
            $results[] = ['ok', 'Migration completed!'];
        }
    }
}

// =====================================================================
// --- HANDLE SQL FILE UPLOAD & IMPORT ---
// =====================================================================
if ($action === 'upload_import') {
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    if (!isset($_FILES['sqlfile']) || $_FILES['sqlfile']['error'] !== UPLOAD_ERR_OK) {
        $results[] = ['fail', 'No file uploaded or upload error.'];
    } else {
        $tmpFile = $_FILES['sqlfile']['tmp_name'];
        $fileName = $_FILES['sqlfile']['name'];
        $fileSize = $_FILES['sqlfile']['size'];
        $results[] = ['info', "File: $fileName (" . round($fileSize / 1024 / 1024, 2) . " MB)"];

        // Parse and execute the SQL file against pw_main, remapping table names
        $handle = fopen($tmpFile, 'r');
        if (!$handle) {
            $results[] = ['fail', 'Cannot open uploaded file.'];
        } else {
            $connect->query("SET FOREIGN_KEY_CHECKS = 0");
            $connect->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
            $connect->query("SET AUTOCOMMIT = 0");

            $stmt = '';
            $executed = 0;
            $failed = 0;
            $skipped = 0;
            $inBlockComment = false;

            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);
                if ($trimmed === '') continue;

                if ($inBlockComment) {
                    if (strpos($trimmed, '*/') !== false) $inBlockComment = false;
                    continue;
                }

                if (strpos($trimmed, '/*') === 0 && strpos($trimmed, '/*!') !== 0) {
                    if (strpos($trimmed, '*/') === false) $inBlockComment = true;
                    continue;
                }

                if (strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) continue;

                $stmt .= $line;

                if (substr(rtrim($stmt), -1) === ';') {
                    $query = trim($stmt);
                    $stmt = '';

                    // Skip CREATE TABLE, CREATE DATABASE, USE, DROP TABLE statements
                    $upperQ = strtoupper($query);
                    if (preg_match('/^(CREATE\s+TABLE|CREATE\s+DATABASE|USE\s+|DROP\s+TABLE|ALTER\s+TABLE|--)/i', $query)) {
                        $skipped++;
                        continue;
                    }

                    // Remap table names in INSERT statements
                    $query = preg_replace('/INSERT\s+INTO\s+`customer`/i', 'INSERT INTO `del_customer`', $query);
                    $query = preg_replace('/INSERT\s+INTO\s+`driver`/i', 'INSERT INTO `del_driver`', $query);
                    $query = preg_replace('/INSERT\s+INTO\s+`location`/i', 'INSERT INTO `del_location`', $query);
                    $query = preg_replace('/INSERT\s+INTO\s+`orderlistdesc`/i', 'INSERT INTO `del_orderlistdesc`', $query);
                    $query = preg_replace('/INSERT\s+INTO\s+`orderlisttemp`/i', 'INSERT INTO `del_orderlisttemp`', $query);
                    $query = preg_replace('/INSERT\s+INTO\s+`sign`/i', 'INSERT INTO `del_sign`', $query);
                    $query = preg_replace('/INSERT\s+INTO\s+`uom`/i', 'INSERT INTO `del_uom`', $query);

                    // Special handling for orderlist INSERT - remove IMG1BLOB column
                    if (preg_match('/INSERT\s+INTO\s+`orderlist`/i', $query)) {
                        // Remove IMG1BLOB from column list and values
                        // Column list: remove `, `IMG1BLOB``
                        $query = str_replace(', `IMG1BLOB`', '', $query);

                        // Remove trailing empty blob value from each row
                        // The IMG1BLOB is the last column, its value is always '' in the dump
                        $query = preg_replace("/,\s*''\)/", ')', $query);

                        // Remap table name
                        $query = preg_replace('/INSERT\s+INTO\s+`orderlist`/i', 'INSERT INTO `del_orderlist`', $query);
                    }

                    // Remove CREATEON from sign inserts
                    if (preg_match('/INSERT\s+INTO\s+`del_sign`/i', $query)) {
                        $query = str_replace(', `CREATEON`', '', $query);
                        // Remove the timestamp value (last value in each row tuple)
                        $query = preg_replace("/,\s*'[0-9]{4}-[0-9]{2}-[0-9]{2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2}'\)/", ')', $query);
                    }

                    // Remove extra columns from customer INSERT (POSTCODE, STATE, AREA)
                    if (preg_match('/INSERT\s+INTO\s+`del_customer`/i', $query)) {
                        // Old: ID, CODE, HP, NAME, LOCATION, ADDRESS, POSTCODE, STATE, AREA, EMAIL
                        // New: ID, CODE, HP, NAME, LOCATION, ADDRESS, EMAIL
                        // Remove POSTCODE, STATE, AREA from column list
                        $query = str_replace(', `POSTCODE`, `STATE`, `AREA`', '', $query);

                        // Remove the 3 values after ADDRESS from each value tuple
                        // Pattern: ..., 'ADDRESS_VAL', 'POSTCODE_VAL', 'STATE_VAL', 'AREA_VAL', 'EMAIL_VAL')
                        // We need to keep ADDRESS and EMAIL, remove POSTCODE, STATE, AREA values
                        // These are positions 6,7,8 (0-indexed) in a 10-column row
                        // Regex: match the 3 values between ADDRESS and EMAIL
                        $query = preg_replace_callback(
                            "/\((\d+),\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)'\)/",
                            function($m) {
                                // $m[1]=ID, $m[2]=CODE, $m[3]=HP, $m[4]=NAME, $m[5]=LOCATION,
                                // $m[6]=ADDRESS, $m[7]=POSTCODE(skip), $m[8]=STATE(skip), $m[9]=AREA(skip), $m[10]=EMAIL
                                return "(" . $m[1] . ", '" . $m[2] . "', '" . $m[3] . "', '" . $m[4] . "', '" . $m[5] . "', '" . $m[6] . "', '" . $m[10] . "')";
                            },
                            $query
                        );
                    }

                    // Skip non-delivery tables
                    if (preg_match('/INSERT\s+INTO\s+`(blobtest|outlet|parafile|product|ptype|users|ordertemp|ordertempedit)`/i', $query)) {
                        $skipped++;
                        continue;
                    }

                    // Skip SET and other utility statements
                    if (preg_match('/^(SET|COMMIT|START\s+TRANSACTION|\/\*!)/i', $query)) {
                        $skipped++;
                        continue;
                    }

                    if ($connect->query($query)) {
                        $executed++;
                    } else {
                        if ($connect->errno === 1062) {
                            $skipped++; // duplicate
                        } else {
                            $failed++;
                            if ($failed <= 5) {
                                $results[] = ['fail', "Error: " . $connect->error . " | Query start: " . substr($query, 0, 120)];
                            }
                        }
                    }
                }
            }

            fclose($handle);
            $connect->query("COMMIT");
            $connect->query("SET FOREIGN_KEY_CHECKS = 1");
            $connect->query("SET AUTOCOMMIT = 1");

            $results[] = ['ok', "Executed: $executed statements"];
            if ($skipped > 0) $results[] = ['skip', "Skipped: $skipped statements (DDL, duplicates, non-delivery tables)"];
            if ($failed > 0) $results[] = ['fail', "Failed: $failed statements"];

            // Show row counts for all delivery tables
            $delTables = ['del_customer', 'del_driver', 'del_location', 'del_orderlist', 'del_orderlistdesc', 'del_sign', 'del_uom'];
            foreach ($delTables as $dt) {
                $cnt = $connect->query("SELECT COUNT(*) AS cnt FROM `$dt`");
                $c = $cnt ? $cnt->fetch_assoc()['cnt'] : '?';
                $results[] = ['info', "`$dt`: $c rows"];
            }
        }
    }
}

// =====================================================================
// --- HANDLE PREVIEW (count rows in old DB) ---
// =====================================================================
$preview = [];
if ($action === '' || $action === 'preview') {
    // Try to connect to old DB and show row counts
    $oldConnect = @new mysqli('localhost', 'u921536699_parkwaydeliver', 'Parkway@123#', 'u921536699_parkwaydeliver');
    $oldDbAvailable = !$oldConnect->connect_error;

    if ($oldDbAvailable) {
        $oldConnect->set_charset("utf8mb4");
        foreach ($tableMappings as $oldTable => $mapping) {
            $cnt = $oldConnect->query("SELECT COUNT(*) AS cnt FROM `$oldTable`");
            $srcCount = $cnt ? $cnt->fetch_assoc()['cnt'] : '?';

            $cnt2 = $connect->query("SELECT COUNT(*) AS cnt FROM `{$mapping['target']}`");
            $destCount = $cnt2 ? $cnt2->fetch_assoc()['cnt'] : '?';

            $preview[$oldTable] = [
                'label' => $mapping['label'],
                'target' => $mapping['target'],
                'srcCount' => $srcCount,
                'destCount' => $destCount,
            ];
        }
        $oldConnect->close();
    }
}

// =====================================================================
// --- HTML OUTPUT ---
// =====================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Delivery Data Migration</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:800px;">
    <h2 class="mb-2">Delivery Data Migration</h2>
    <p class="text-muted mb-4">Migrate data from old delivery database (<code>u921536699_parkwaydeliver</code>) into current system (<code>pw_main</code> del_ tables).</p>

    <?php if (!empty($results)): ?>
    <div class="card mb-4">
        <div class="card-header bg-dark text-white"><strong>Migration Results</strong></div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark"><tr><th style="width:80px">Status</th><th>Detail</th></tr></thead>
                <tbody>
                <?php foreach ($results as $r): ?>
                    <tr class="<?= $r[0]==='ok' ? 'table-success' : ($r[0]==='info' ? 'table-info' : ($r[0]==='skip' ? 'table-secondary' : 'table-danger')) ?>">
                        <td><strong><?= strtoupper($r[0]) ?></strong></td>
                        <td><?= htmlspecialchars($r[1]) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Option 1: Direct Migration (if old DB is accessible) -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white"><strong>Option 1: Direct Migration (Database to Database)</strong></div>
        <div class="card-body">
            <?php if (!empty($preview)): ?>
                <p class="small text-muted mb-3">Old delivery database is accessible. Select which tables to migrate directly.</p>
                <form method="post">
                    <input type="hidden" name="action" value="migrate">
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px"><input type="checkbox" id="selectAll" checked></th>
                                <th>Data</th>
                                <th>Old Table</th>
                                <th>New Table</th>
                                <th>Source Rows</th>
                                <th>Current Rows</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($preview as $oldTable => $info): ?>
                            <tr>
                                <td><input type="checkbox" name="tables[]" value="<?= $oldTable ?>" class="table-check" checked></td>
                                <td><?= $info['label'] ?></td>
                                <td><code><?= $oldTable ?></code></td>
                                <td><code><?= $info['target'] ?></code></td>
                                <td><span class="badge bg-info"><?= $info['srcCount'] ?></span></td>
                                <td><span class="badge bg-<?= $info['destCount'] > 0 ? 'warning' : 'secondary' ?>"><?= $info['destCount'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="clear_first" id="clearFirst" checked>
                        <label class="form-check-label" for="clearFirst">Clear target tables before inserting (recommended for clean migration)</label>
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="this.disabled=true;this.innerHTML='Migrating...';this.form.submit();return true;">
                        Migrate Selected Tables
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning mb-0">
                    Old delivery database (<code>u921536699_parkwaydeliver</code>) is not accessible. Use Option 2 (SQL file upload) instead.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Option 2: SQL File Upload -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white"><strong>Option 2: Upload SQL File</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-3">Upload the <code>u921536699_parkwaydeliver.sql</code> dump file. The system will automatically remap table names (e.g. <code>customer</code> -> <code>del_customer</code>) and adjust columns to match the new schema.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_import">
                <div class="mb-3">
                    <label for="sqlfile" class="form-label">Select SQL file:</label>
                    <input type="file" class="form-control" name="sqlfile" id="sqlfile" accept=".sql" required>
                </div>
                <p class="small text-muted">
                    <strong>What it does:</strong><br>
                    - Remaps table names: <code>customer</code> -> <code>del_customer</code>, <code>orderlist</code> -> <code>del_orderlist</code>, etc.<br>
                    - Removes extra columns (IMG1BLOB, POSTCODE/STATE/AREA from customer, CREATEON from sign)<br>
                    - Skips non-delivery tables (product, users, outlet, etc.)<br>
                    - Skips CREATE TABLE / ALTER TABLE / DROP statements (only processes INSERT)
                </p>
                <button type="submit" class="btn btn-success" onclick="this.disabled=true;this.innerHTML='Uploading & Importing...';this.form.submit();return true;">
                    Upload & Import
                </button>
            </form>
        </div>
    </div>

    <!-- Current Row Counts -->
    <div class="card mb-3">
        <div class="card-header bg-secondary text-white"><strong>Current Delivery Tables (pw_main)</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light"><tr><th>Table</th><th>Rows</th></tr></thead>
                <tbody>
                <?php
                $delTables = ['del_customer', 'del_driver', 'del_location', 'del_orderlist', 'del_orderlistdesc', 'del_orderlisttemp', 'del_sign', 'del_uom'];
                foreach ($delTables as $dt) {
                    $cnt = $connect->query("SELECT COUNT(*) AS cnt FROM `$dt`");
                    $c = $cnt ? $cnt->fetch_assoc()['cnt'] : '?';
                    echo "<tr><td><code>$dt</code></td><td>$c</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.table-check').forEach(cb => cb.checked = this.checked);
});
</script>
</body>
</html>
