<?php
include "../dbconnection.php";
$orderno = $_POST["orderno"];
$pdesc = $_POST["pdesc"];
$qty = $_POST["qty"];
$uom = $_POST["uom"];

$connect->query("INSERT INTO orderlisttemp (ORDERNO, PDESC, QTY, UOM) VALUES ('$orderno', '$pdesc', '$qty', '$uom')");
?>