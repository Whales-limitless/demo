<?php
include "../dbconnection.php";
$id = $_POST["id"];

$connect->query("DELETE FROM orderlisttemp WHERE ID = '$id'");
?>