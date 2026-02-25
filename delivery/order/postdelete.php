<?php
include "../dbconnection.php";
$id = $_POST["id"];

$sel = $connect->query("SELECT ORDNO FROM orderlist WHERE ID = '$id'");
$selrow = $sel->fetch_assoc();
$selordno = $selrow["ORDNO"];

$connect->query("DELETE FROM orderlistdesc WHERE ORDERNO = '$selordno'");

$connect->query("DELETE FROM orderlisttemp WHERE ORDERNO = '$selordno'");

$sql = $connect->query("DELETE FROM orderlist WHERE ID = '$id'");

?>