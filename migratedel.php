<?php
/**
 * migratedel.php
 *
 * Migrate data from old delivery SQL dump (u921536699_parkwaydeliver.sql)
 * into the new del_ prefixed tables in pw_main database.
 *
 * Approach: Create temporary staging tables with old names prefixed _mig_,
 * let MySQL parse the SQL dump natively, then copy data with column mapping.
 *
 * Tables migrated:
 *   customer       → del_customer
 *   driver         → del_driver
 *   location       → del_location
 *   orderlist      → del_orderlist
 *   orderlistdesc  → del_orderlistdesc
 *   orderlisttemp  → del_orderlisttemp
 *   sign           → del_sign
 *   uom            → del_uom
 *
 * Skipped (redundant):
 *   blobtest, ordertemp, ordertempedit, outlet, parafile,
 *   product, ptype, users
 *
 * Usage: Open this file in a browser or run via CLI: php migratedel.php
 */

set_time_limit(600);
ini_set('memory_limit', '512M');

include(__DIR__ . '/staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$sqlFile = __DIR__ . '/delivery/u921536699_parkwaydeliver.sql';

// Tables we want to migrate
$migrateTables = ['customer', 'driver', 'location', 'orderlist', 'orderlistdesc', 'orderlisttemp', 'sign', 'uom'];

// ---------- helpers ----------

function out($msg) {
    if (php_sapi_name() === 'cli') {
        echo $msg . "\n";
    } else {
        echo htmlspecialchars($msg) . "<br>\n";
        if (ob_get_level()) ob_flush();
        flush();
    }
}

// ---------- main ----------

if (php_sapi_name() !== 'cli') {
    echo "<!DOCTYPE html><html><head><title>Delivery Migration</title></head><body style='font-family:monospace;padding:20px;line-height:1.8;'>";
}

out("=== Delivery Data Migration ===");
out("");

if (!file_exists($sqlFile)) {
    out("ERROR: SQL file not found: $sqlFile");
    exit(1);
}

out("Step 1: Reading SQL dump file...");
$sql = file_get_contents($sqlFile);
$fileSize = strlen($sql);
out("  File size: " . number_format($fileSize) . " bytes");
out("");

// -----------------------------------------------------------
// Step 2: Extract CREATE TABLE and INSERT INTO for each table
//         Rewrite table names to _mig_ prefix
// -----------------------------------------------------------
out("Step 2: Extracting and rewriting SQL for staging tables...");

$stagingSQL = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($migrateTables as $tbl) {
    $staging = '_mig_' . $tbl;

    // Drop staging table if exists
    $stagingSQL .= "DROP TABLE IF EXISTS `$staging`;\n";

    // Extract CREATE TABLE
    $createPattern = '/CREATE TABLE `' . preg_quote($tbl, '/') . '` \(.*?\) ENGINE=\w+[^;]*;/s';
    if (preg_match($createPattern, $sql, $m)) {
        // Rewrite table name
        $createStmt = str_replace("CREATE TABLE `$tbl`", "CREATE TABLE `$staging`", $m[0]);
        $stagingSQL .= $createStmt . "\n\n";
        out("  CREATE TABLE `$staging` - OK");
    } else {
        out("  CREATE TABLE `$tbl` - NOT FOUND, skipping");
        continue;
    }

    // Extract all INSERT INTO statements for this table
    // Use word boundary matching to avoid partial matches (e.g. 'sign' in 'signature')
    $offset = 0;
    $insertCount = 0;
    $needle = "INSERT INTO `$tbl`";
    while (($pos = strpos($sql, $needle, $offset)) !== false) {
        // Find the end of this INSERT statement (semicolon followed by newline)
        $end = strpos($sql, ";\n", $pos);
        if ($end === false) $end = $fileSize;
        $insertStmt = substr($sql, $pos, $end - $pos + 1);

        // Rewrite table name
        $insertStmt = preg_replace(
            '/INSERT INTO `' . preg_quote($tbl, '/') . '`/',
            "INSERT INTO `$staging`",
            $insertStmt,
            1
        );

        $stagingSQL .= $insertStmt . "\n";
        $insertCount++;
        $offset = $end + 1;
    }
    out("  INSERT INTO `$staging` - $insertCount statement(s)");

    // Extract ALTER TABLE for primary key and auto_increment
    $alterPattern = '/ALTER TABLE `' . preg_quote($tbl, '/') . '`\s+ADD PRIMARY KEY[^;]*;/s';
    if (preg_match($alterPattern, $sql, $m)) {
        $stagingSQL .= str_replace("ALTER TABLE `$tbl`", "ALTER TABLE `$staging`", $m[0]) . "\n";
    }

    $alterPattern2 = '/ALTER TABLE `' . preg_quote($tbl, '/') . '`\s+MODIFY[^;]*AUTO_INCREMENT[^;]*;/s';
    if (preg_match($alterPattern2, $sql, $m)) {
        $stagingSQL .= str_replace("ALTER TABLE `$tbl`", "ALTER TABLE `$staging`", $m[0]) . "\n";
    }

    $stagingSQL .= "\n";
}

// Free the large SQL string
unset($sql);
out("");

// -----------------------------------------------------------
// Step 3: Execute staging SQL via multi_query
// -----------------------------------------------------------
out("Step 3: Importing data into staging tables (MySQL parsing)...");
out("  Executing SQL (" . number_format(strlen($stagingSQL)) . " bytes)...");

$success = $connect->multi_query($stagingSQL);

if (!$success) {
    out("  ERROR on first statement: " . $connect->error);
} else {
    // Consume all results from multi_query
    $stmtNum = 0;
    do {
        $stmtNum++;
        if ($connect->errno) {
            out("  WARNING at statement #$stmtNum: " . $connect->error);
        }
        // Store result if any (some statements return results, most don't)
        if ($result = $connect->store_result()) {
            $result->free();
        }
    } while ($connect->more_results() && $connect->next_result());
}

// Free staging SQL
unset($stagingSQL);

out("  Import complete. Processed $stmtNum statement(s).");
out("");

// -----------------------------------------------------------
// Step 4: Verify staging tables and show row counts
// -----------------------------------------------------------
out("Step 4: Verifying staging tables...");
foreach ($migrateTables as $tbl) {
    $staging = '_mig_' . $tbl;
    $result = $connect->query("SELECT COUNT(*) AS cnt FROM `$staging`");
    if ($result) {
        $cnt = $result->fetch_assoc()['cnt'];
        out("  $staging: $cnt rows");
    } else {
        out("  $staging: ERROR - " . $connect->error);
    }
}
out("");

// -----------------------------------------------------------
// Step 5: Copy data from staging tables to del_ tables
// -----------------------------------------------------------
out("Step 5: Copying data to del_ tables...");

$copyMap = [
    'location' => [
        'target' => 'del_location',
        'select' => '`NAME`, `POSTCODE`, `DISTANT`, `RETAIL`',
        'into'   => '`NAME`, `POSTCODE`, `DISTANT`, `RETAIL`',
    ],
    'customer' => [
        'target' => 'del_customer',
        'select' => '`CODE`, `HP`, `NAME`, `LOCATION`, `ADDRESS`, `EMAIL`',
        'into'   => '`CODE`, `HP`, `NAME`, `LOCATION`, `ADDRESS`, `EMAIL`',
    ],
    'driver' => [
        'target' => 'del_driver',
        'select' => '`CODE`, `HP`, `NAME`, `ADDRESS`, `POSTCODE`, `STATE`, `AREA`, `EMAIL`, `USERNAME`, `PASSWORD`',
        'into'   => '`CODE`, `HP`, `NAME`, `ADDRESS`, `POSTCODE`, `STATE`, `AREA`, `EMAIL`, `USERNAME`, `PASSWORD`',
    ],
    'orderlist' => [
        'target' => 'del_orderlist',
        'select' => '`ORDNO`, CASE WHEN `DELDATE` = \'\' OR `DELDATE` = \'0000-00-00\' THEN NULL ELSE `DELDATE` END, `DRIVERCODE`, `DRIVER`, `CUSTOMERCODE`, `CUSTOMER`, `LOCATION`, `DISTANT`, `RETAIL`, `REMARK`, `STATUS`, `IMG1`, `IMG2`, `IMG3`, CASE WHEN `DONEDATETIME` = \'0000-00-00 00:00:00\' THEN NULL ELSE `DONEDATETIME` END',
        'into'   => '`ORDNO`, `DELDATE`, `DRIVERCODE`, `DRIVER`, `CUSTOMERCODE`, `CUSTOMER`, `LOCATION`, `DISTANT`, `RETAIL`, `REMARK`, `STATUS`, `IMG1`, `IMG2`, `IMG3`, `DONEDATETIME`',
    ],
    'orderlistdesc' => [
        'target' => 'del_orderlistdesc',
        'select' => '`ORDERNO`, `PDESC`, `QTY`, `UOM`',
        'into'   => '`ORDERNO`, `PDESC`, `QTY`, `UOM`',
    ],
    'orderlisttemp' => [
        'target' => 'del_orderlisttemp',
        'select' => '`ORDERNO`, `PDESC`, `QTY`, `UOM`',
        'into'   => '`ORDERNO`, `PDESC`, `QTY`, `UOM`',
    ],
    'sign' => [
        'target' => 'del_sign',
        'select' => '`ORDNO`',
        'into'   => '`ORDNO`',
    ],
    'uom' => [
        'target' => 'del_uom',
        'select' => '`PDESC`',
        'into'   => '`PDESC`',
    ],
];

$totalMigrated = 0;

foreach ($copyMap as $oldTbl => $cfg) {
    $staging = '_mig_' . $oldTbl;
    $target = $cfg['target'];

    // Clear target table
    $connect->query("DELETE FROM `$target`");
    $connect->query("ALTER TABLE `$target` AUTO_INCREMENT = 1");

    // Copy with column mapping
    $copySql = "INSERT INTO `$target` ({$cfg['into']}) SELECT {$cfg['select']} FROM `$staging`";
    if ($connect->query($copySql)) {
        $affected = $connect->affected_rows;
        out("  $staging → $target: $affected rows copied");
        $totalMigrated += $affected;
    } else {
        out("  $staging → $target: ERROR - " . $connect->error);
    }
}
out("");

// -----------------------------------------------------------
// Step 6: Cleanup staging tables
// -----------------------------------------------------------
out("Step 6: Cleaning up staging tables...");
foreach ($migrateTables as $tbl) {
    $staging = '_mig_' . $tbl;
    $connect->query("DROP TABLE IF EXISTS `$staging`");
}
out("  Done.");
out("");

// -----------------------------------------------------------
// Final summary
// -----------------------------------------------------------
out("=== Migration Complete ===");
out("Total rows migrated: $totalMigrated");
out("");

// Show final del_ table counts
out("Final table counts:");
$delTables = ['del_location', 'del_customer', 'del_driver', 'del_orderlist', 'del_orderlistdesc', 'del_orderlisttemp', 'del_sign', 'del_uom'];
foreach ($delTables as $t) {
    $result = $connect->query("SELECT COUNT(*) AS cnt FROM `$t`");
    if ($result) {
        out("  $t: " . $result->fetch_assoc()['cnt'] . " rows");
    }
}

if (php_sapi_name() !== 'cli') {
    echo "</body></html>";
}
