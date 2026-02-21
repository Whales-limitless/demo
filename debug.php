<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);

echo "<h2>Database Diagnostic</h2>";

$connect = @mysqli_connect('localhost', 'pwuser', 'Pwuser@123#', 'pw_main');
if (!$connect) {
    echo "<p style='color:red;'>Connection failed: " . mysqli_connect_error() . "</p>";
    exit;
}
echo "<p style='color:green;'>Connected OK</p>";

echo "<h3>Tables in pw_main:</h3><ul>";
$r = mysqli_query($connect, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($r)) {
    echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    $tables[] = $row[0];
}
echo "</ul>";

foreach ($tables as $t) {
    echo "<h3>Columns in `$t`:</h3><ul>";
    $r = mysqli_query($connect, "DESCRIBE `$t`");
    while ($row = mysqli_fetch_assoc($r)) {
        echo "<li>" . htmlspecialchars($row['Field']) . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
}

echo "<h3>Test query: SELECT cat_code, cat_name FROM category</h3>";
$r = mysqli_query($connect, "SELECT cat_code, cat_name FROM category LIMIT 5");
if (!$r) {
    echo "<p style='color:red;'>Error: " . mysqli_error($connect) . "</p>";
} else {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($r)) print_r($row);
    echo "</pre>";
}

echo "<h3>Test query: SELECT id, name FROM PRODUCTS</h3>";
$r = mysqli_query($connect, "SELECT id, name FROM PRODUCTS LIMIT 5");
if (!$r) {
    echo "<p style='color:red;'>Error: " . mysqli_error($connect) . "</p>";
} else {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($r)) print_r($row);
    echo "</pre>";
}

mysqli_close($connect);
?>
