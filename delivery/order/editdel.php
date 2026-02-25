<?php
include "../dbconnection.php";
$id = $_POST["id"];

$connect->query("DELETE FROM orderlistdesc WHERE ID = '$id'");
?>