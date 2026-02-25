<?php
include "../dbconnection.php";

$id = $_POST["id"];
$code = $_POST["code"];

$connect->query("UPDATE orderlist SET DRIVERCODE = '', DRIVER = '', STATUS = '' WHERE ID = '$id'");
?>