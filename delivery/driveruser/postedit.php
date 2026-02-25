<?php
include "../dbconnection.php";
$id = $_POST["id"];
$name = $_POST["name"];
$email = $_POST["email"];
$address = $_POST["address"];
$postcode = $_POST["postcode"];
$state = $_POST["state"];
$area = $_POST["area"];
$hp = $_POST["hp"];
$username = $_POST["username"];
$password = $_POST["password"];

$sql = $connect->query("UPDATE driver SET NAME = '$name', EMAIL = '$email', ADDRESS = '$address', POSTCODE = '$postcode', STATE = '$state', AREA = '$area', HP = '$hp', USERNAME = '$username', PASSWORD = '$password' WHERE ID = '$id'");

?>