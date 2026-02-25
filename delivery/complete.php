<?php
include "dbconnection.php";
$id = $_POST["id"];
$connect->query("UPDATE orderlist SET STATUS = 'C' WHERE ID = '$id'");
?>