<?php
include "../dbconnection.php";
$code = uniqid();
$username = $_POST["username"];
$password = $_POST["password"];
$pin = $_POST["pin"];

$sql = $connect->query("INSERT INTO users (CODE, USERNAME, PASSWORD, PIN) VALUES ('$code', '$username', '$password', '$pin')");
?>