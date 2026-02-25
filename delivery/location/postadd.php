<?php
include "../dbconnection.php";
$name = $_POST["name"];
$retail = $_POST["retail"];
$distant = $_POST["distant"];
$postcode = $_POST["postcode"];

$sql = $connect->query("INSERT INTO location (NAME, POSTCODE, DISTANT, RETAIL) VALUES ('$name', '$postcode', '$distant', '$retail')");
?>