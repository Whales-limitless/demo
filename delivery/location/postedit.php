<?php
include "../dbconnection.php";
$id = $_POST["id"];
$name = $_POST["name"];
$retail = $_POST["retail"];
$distant = $_POST["distant"];
$postcode = $_POST["postcode"];

$sql = $connect->query("UPDATE location SET NAME = '$name', DISTANT = '$distant', POSTCODE = '$postcode', RETAIL = '$retail' WHERE ID = '$id'");

?>