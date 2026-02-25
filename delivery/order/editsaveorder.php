<?php
include "../dbconnection.php";


$customercode = $_POST["customer"];
$deldate = $_POST["deldate"];
$remark = $_POST["remark"];
$ordno = $_POST["orderno"];

$cust = $connect->query("SELECT * FROM customer WHERE CODE = '$customercode'");
$custrow = $cust->fetch_assoc();
$customer = $custrow["NAME"];

$driv = $connect->query("SELECT * FROM driver WHERE CODE = '$drivercode'");
$drivrow = $driv->fetch_assoc();
$driver = $drivrow["NAME"];

$location = $_POST["location"];
$distant = $_POST["distant"];
$retail = $_POST["retail"];


$connect->query("UPDATE orderlist SET DELDATE = '$deldate', CUSTOMERCODE = '$customercode', CUSTOMER = '$customer', REMARK = '$remark', LOCATION = '$location', DISTANT = '$distant', RETAIL = '$retail' WHERE ORDNO = '$ordno'");

//$connect->query("UPDATE parafile SET ORDNO = ORDNO + 1");
?>