<?php
include "../dbconnection.php";
$code = uniqid();
$name = $_POST["name"];
$email = $_POST["email"];
$address = $_POST["address"];
$location = $_POST["location"];
$hp = $_POST["hp"];
$sql = $connect->query("INSERT INTO customer (CODE, NAME, EMAIL, LOCATION, ADDRESS, HP) VALUES ('$code', '$name', '$email', '$location', '$address', '$hp')");
?>