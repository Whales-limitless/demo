<?php
/**
 * migratedel.php
 *
 * Migrate data from old delivery SQL dump (u921536699_parkwaydeliver.sql)
 * into the new del_ prefixed tables in pw_main database.
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

set_time_limit(300);
ini_set('memory_limit', '256M');

include(__DIR__ . '/staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$sqlFile = __DIR__ . '/delivery/u921536699_parkwaydeliver.sql';

// ---------- helpers ----------

function out($msg) {
    if (php_sapi_name() === 'cli') {
        echo $msg . "\n";
    } else {
        echo htmlspecialchars($msg) . "<br>\n";
        ob_flush(); flush();
    }
}

function extractInserts($sql, $tableName) {
    // Match INSERT INTO `tableName` (...) VALUES (...),(...);
    // The SQL dump may have multi-value inserts split across lines
    $pattern = '/INSERT\s+INTO\s+`' . preg_quote($tableName, '/') . '`\s*\(([^)]+)\)\s*VALUES\s*/i';
    $rows = [];
    $columns = [];

    // Split SQL into statements
    $pos = 0;
    $len = strlen($sql);
    while (($start = stripos($sql, "INSERT INTO `$tableName`", $pos)) !== false) {
        // Find the end of this INSERT statement (ends with ;)
        $end = strpos($sql, ";\n", $start);
        if ($end === false) $end = $len;
        $stmt = substr($sql, $start, $end - $start);
        $pos = $end + 1;

        // Extract column names
        if (empty($columns)) {
            if (preg_match('/\(([^)]+)\)\s*VALUES/i', $stmt, $m)) {
                $columns = array_map(function($c) {
                    return trim($c, " `\t\n\r");
                }, explode(',', $m[1]));
            }
        }

        // Extract values portion
        $valuesPos = stripos($stmt, 'VALUES');
        if ($valuesPos === false) continue;
        $valuesStr = substr($stmt, $valuesPos + 6);

        // Parse individual row tuples
        $i = 0;
        $vLen = strlen($valuesStr);
        while ($i < $vLen) {
            // Skip whitespace and commas
            while ($i < $vLen && ($valuesStr[$i] === ' ' || $valuesStr[$i] === ',' || $valuesStr[$i] === "\n" || $valuesStr[$i] === "\r" || $valuesStr[$i] === "\t")) $i++;
            if ($i >= $vLen || $valuesStr[$i] !== '(') break;

            // Find matching closing paren, respecting quotes
            $depth = 0;
            $rowStart = $i;
            $inStr = false;
            $strChar = '';
            while ($i < $vLen) {
                $ch = $valuesStr[$i];
                if ($inStr) {
                    if ($ch === '\\') { $i++; } // skip escaped char
                    elseif ($ch === $strChar) { $inStr = false; }
                } else {
                    if ($ch === '\'' || $ch === '"') { $inStr = true; $strChar = $ch; }
                    elseif ($ch === '(') { $depth++; }
                    elseif ($ch === ')') { $depth--; if ($depth === 0) { $i++; break; } }
                }
                $i++;
            }
            $rowStr = substr($valuesStr, $rowStart, $i - $rowStart);
            $rows[] = $rowStr;
        }
    }

    return ['columns' => $columns, 'rows' => $rows];
}

function parseRowValues($rowStr) {
    // Strip outer parens
    $rowStr = trim($rowStr);
    if ($rowStr[0] === '(') $rowStr = substr($rowStr, 1);
    if (substr($rowStr, -1) === ')') $rowStr = substr($rowStr, 0, -1);

    $values = [];
    $i = 0;
    $len = strlen($rowStr);

    while ($i < $len) {
        // Skip whitespace
        while ($i < $len && ($rowStr[$i] === ' ' || $rowStr[$i] === "\t")) $i++;
        if ($i >= $len) break;

        if ($rowStr[$i] === '\'') {
            // Quoted string value
            $i++; // skip opening quote
            $val = '';
            while ($i < $len) {
                if ($rowStr[$i] === '\\' && $i + 1 < $len) {
                    $next = $rowStr[$i + 1];
                    if ($next === '\'') { $val .= '\''; $i += 2; }
                    elseif ($next === '\\') { $val .= '\\'; $i += 2; }
                    elseif ($next === 'n') { $val .= "\n"; $i += 2; }
                    elseif ($next === 'r') { $val .= "\r"; $i += 2; }
                    elseif ($next === 't') { $val .= "\t"; $i += 2; }
                    elseif ($next === '0') { $val .= "\0"; $i += 2; }
                    else { $val .= $next; $i += 2; }
                } elseif ($rowStr[$i] === '\'' && $i + 1 < $len && $rowStr[$i + 1] === '\'') {
                    $val .= '\''; $i += 2;
                } elseif ($rowStr[$i] === '\'') {
                    $i++; break;
                } else {
                    $val .= $rowStr[$i]; $i++;
                }
            }
            $values[] = $val;
        } elseif (strtoupper(substr($rowStr, $i, 4)) === 'NULL') {
            $values[] = null;
            $i += 4;
        } else {
            // Unquoted value (number etc)
            $start = $i;
            while ($i < $len && $rowStr[$i] !== ',') $i++;
            $values[] = trim(substr($rowStr, $start, $i - $start));
        }

        // Skip comma separator
        while ($i < $len && ($rowStr[$i] === ',' || $rowStr[$i] === ' ' || $rowStr[$i] === "\t")) {
            if ($rowStr[$i] === ',') { $i++; break; }
            $i++;
        }
    }

    return $values;
}

// ---------- main ----------

if (!php_sapi_name() === 'cli') {
    echo "<!DOCTYPE html><html><head><title>Delivery Migration</title></head><body style='font-family:monospace;padding:20px;'>";
}

out("=== Delivery Data Migration ===");
out("");

if (!file_exists($sqlFile)) {
    out("ERROR: SQL file not found: $sqlFile");
    exit(1);
}

out("Reading SQL dump file...");
$sql = file_get_contents($sqlFile);
out("File size: " . number_format(strlen($sql)) . " bytes");
out("");

// Table mapping: old_table => [new_table, column_map]
// column_map: old_col => new_col (or null to skip)
$tableMigrations = [
    'location' => [
        'target' => 'del_location',
        'columns' => [
            'ID' => null, // skip, auto-increment in new table
            'NAME' => 'NAME',
            'POSTCODE' => 'POSTCODE',
            'DISTANT' => 'DISTANT',
            'RETAIL' => 'RETAIL',
        ],
    ],
    'customer' => [
        'target' => 'del_customer',
        'columns' => [
            'ID' => null,
            'CODE' => 'CODE',
            'HP' => 'HP',
            'NAME' => 'NAME',
            'LOCATION' => 'LOCATION',
            'ADDRESS' => 'ADDRESS',
            'POSTCODE' => null, // not in new table
            'STATE' => null,    // not in new table
            'AREA' => null,     // not in new table
            'EMAIL' => 'EMAIL',
        ],
    ],
    'driver' => [
        'target' => 'del_driver',
        'columns' => [
            'ID' => null,
            'CODE' => 'CODE',
            'HP' => 'HP',
            'NAME' => 'NAME',
            'ADDRESS' => 'ADDRESS',
            'POSTCODE' => 'POSTCODE',
            'STATE' => 'STATE',
            'AREA' => 'AREA',
            'EMAIL' => 'EMAIL',
            'USERNAME' => 'USERNAME',
            'PASSWORD' => 'PASSWORD',
        ],
    ],
    'orderlist' => [
        'target' => 'del_orderlist',
        'columns' => [
            'ID' => null,
            'ORDNO' => 'ORDNO',
            'DELDATE' => 'DELDATE',
            'DRIVERCODE' => 'DRIVERCODE',
            'DRIVER' => 'DRIVER',
            'CUSTOMERCODE' => 'CUSTOMERCODE',
            'CUSTOMER' => 'CUSTOMER',
            'LOCATION' => 'LOCATION',
            'DISTANT' => 'DISTANT',
            'RETAIL' => 'RETAIL',
            'REMARK' => 'REMARK',
            'IMG1' => 'IMG1',
            'IMG2' => 'IMG2',
            'IMG3' => 'IMG3',
            'DONEDATETIME' => 'DONEDATETIME',
            'STATUS' => 'STATUS',
            'IMG1BLOB' => null, // skip blob data
        ],
    ],
    'orderlistdesc' => [
        'target' => 'del_orderlistdesc',
        'columns' => [
            'ID' => null,
            'ORDERNO' => 'ORDERNO',
            'PDESC' => 'PDESC',
            'QTY' => 'QTY',
            'UOM' => 'UOM',
        ],
    ],
    'orderlisttemp' => [
        'target' => 'del_orderlisttemp',
        'columns' => [
            'ID' => null,
            'ORDERNO' => 'ORDERNO',
            'PDESC' => 'PDESC',
            'QTY' => 'QTY',
            'UOM' => 'UOM',
        ],
    ],
    'sign' => [
        'target' => 'del_sign',
        'columns' => [
            'ID' => null,
            'ORDNO' => 'ORDNO',
            'CREATEON' => null, // not in new table
        ],
    ],
    'uom' => [
        'target' => 'del_uom',
        'columns' => [
            'ID' => null,
            'PDESC' => 'PDESC',
        ],
    ],
];

$totalMigrated = 0;
$errors = [];

foreach ($tableMigrations as $oldTable => $config) {
    $newTable = $config['target'];
    $colMap = $config['columns'];

    out("--- Migrating: $oldTable → $newTable ---");

    // Extract INSERT data from SQL dump
    $data = extractInserts($sql, $oldTable);
    $oldCols = $data['columns'];
    $rowStrs = $data['rows'];

    if (empty($oldCols) || empty($rowStrs)) {
        out("  No data found for table '$oldTable'. Skipping.");
        out("");
        continue;
    }

    out("  Found " . count($rowStrs) . " row(s) with columns: " . implode(', ', $oldCols));

    // Build index mapping: old column index → new column name
    $indexMap = [];
    foreach ($oldCols as $idx => $oldCol) {
        if (isset($colMap[$oldCol]) && $colMap[$oldCol] !== null) {
            $indexMap[$idx] = $colMap[$oldCol];
        }
    }

    $newCols = array_values($indexMap);
    $colIndices = array_keys($indexMap);
    $placeholders = implode(',', array_fill(0, count($newCols), '?'));
    $colList = implode(',', array_map(function($c) { return "`$c`"; }, $newCols));
    $insertSQL = "INSERT INTO `$newTable` ($colList) VALUES ($placeholders)";

    // Clear existing data in target table
    $connect->query("DELETE FROM `$newTable`");
    $connect->query("ALTER TABLE `$newTable` AUTO_INCREMENT = 1");

    // Insert in batches
    $inserted = 0;
    $batchErrors = 0;

    foreach ($rowStrs as $rowStr) {
        $allVals = parseRowValues($rowStr);

        // Pick only the mapped columns
        $vals = [];
        foreach ($colIndices as $idx) {
            $vals[] = isset($allVals[$idx]) ? $allVals[$idx] : '';
        }

        // Handle special conversions
        for ($i = 0; $i < count($newCols); $i++) {
            $col = $newCols[$i];
            $v = $vals[$i];

            // DELDATE: old table uses text, new uses date
            if ($col === 'DELDATE' && $v !== null && $v !== '' && $v !== '0000-00-00') {
                // Try to parse date
                $ts = strtotime($v);
                if ($ts !== false) {
                    $vals[$i] = date('Y-m-d', $ts);
                }
            }

            // DONEDATETIME: handle zero/empty datetime
            if ($col === 'DONEDATETIME') {
                if ($v === '' || $v === '0000-00-00 00:00:00' || $v === null) {
                    $vals[$i] = null;
                }
            }
        }

        // Build type string
        $types = '';
        $bindVals = [];
        for ($i = 0; $i < count($vals); $i++) {
            if ($vals[$i] === null) {
                $types .= 's';
                $bindVals[] = null;
            } else {
                $types .= 's';
                $bindVals[] = (string)$vals[$i];
            }
        }

        $stmt = $connect->prepare($insertSQL);
        if (!$stmt) {
            $batchErrors++;
            if ($batchErrors <= 3) {
                $errors[] = "$newTable: prepare failed - " . $connect->error;
            }
            continue;
        }

        $stmt->bind_param($types, ...$bindVals);
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $batchErrors++;
            if ($batchErrors <= 3) {
                $errors[] = "$newTable: " . $stmt->error . " (row: " . substr($rowStr, 0, 100) . "...)";
            }
        }
        $stmt->close();
    }

    out("  Inserted: $inserted / " . count($rowStrs));
    if ($batchErrors > 0) {
        out("  Errors: $batchErrors");
    }
    out("");
    $totalMigrated += $inserted;
}

out("=== Migration Complete ===");
out("Total rows migrated: $totalMigrated");

if (!empty($errors)) {
    out("");
    out("=== Errors (first few) ===");
    foreach (array_slice($errors, 0, 10) as $e) {
        out("  - $e");
    }
}

if (php_sapi_name() !== 'cli') {
    echo "</body></html>";
}
