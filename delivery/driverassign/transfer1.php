<?php
include "../dbconnection.php";

$id = $_POST["id"];
$code = $_POST["code"];

$driv = $connect->query("SELECT * FROM driver WHERE CODE = '$code'");
$drivrow = $driv->fetch_assoc();
$driver = $drivrow["NAME"];

$connect->query("UPDATE orderlist SET DRIVERCODE = '$code', DRIVER = '$driver', STATUS = 'A' WHERE ID = '$id'");
?>