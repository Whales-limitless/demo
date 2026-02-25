<?php
include "../dbconnection.php";
$code = uniqid();
$name = $_POST["name"];
$email = $_POST["email"];
$address = $_POST["address"];
$postcode = $_POST["postcode"];
$state = $_POST["state"];
$area = $_POST["area"];
$hp = $_POST["hp"];
$username = $_POST["username"];
$password = $_POST["password"];
$sql = $connect->query("INSERT INTO driver (CODE, NAME, EMAIL, ADDRESS, POSTCODE, STATE, AREA, HP, USERNAME, PASSWORD) VALUES ('$code', '$name', '$email', '$address', '$postcode', '$state', '$area', '$hp', '$username', '$password')");
?>