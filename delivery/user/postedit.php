<?php
include "../dbconnection.php";
$id = $_POST["id"];
$username = $_POST["username"];
$password = $_POST["password"];
$pin = $_POST["pin"];

$sql = $connect->query("UPDATE users SET USERNAME = '$username', PASSWORD = '$password', PIN = '$pin' WHERE ID = '$id'");

?>