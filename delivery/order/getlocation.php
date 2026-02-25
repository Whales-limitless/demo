<?php
include "../dbconnection.php";
$name = $_POST["name"];

$cus = $connect->query("SELECT LOCATION, ADDRESS FROM customer WHERE CODE = '$name'");
$cusrow = $cus->fetch_assoc();
$location = $cusrow["LOCATION"];
$address = $cusrow["ADDRESS"];

$sql = $connect->query("SELECT * FROM location WHERE NAME = '$location'");
$row = $sql->fetch_assoc();

echo $row["NAME"] . "|" . $row["DISTANT"] . "|" . $row["RETAIL"] . "|" . $address;

?>