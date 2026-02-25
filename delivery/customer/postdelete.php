<?php
include "../dbconnection.php";
$id = $_POST["id"];
$sql = $connect->query("DELETE FROM customer WHERE ID = '$id'");

?>