<?php
include "../dbconnection.php";
$id = $_POST["id"];
$sql = $connect->query("DELETE FROM users WHERE ID = '$id'");

?>