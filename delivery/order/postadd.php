<?php
include "../dbconnection.php";
$name = $_POST["name"];
$email = $_POST["email"];
$address = $_POST["address"];
$postcode = $_POST["postcode"];
$state = $_POST["state"];
$area = $_POST["area"];
$hp = $_POST["hp"];
$sql = $connect->query("INSERT INTO customer (NAME, EMAIL, ADDRESS, POSTCODE, STATE, AREA, HP) VALUES ('$name', '$email', '$address', '$postcode', '$state', '$area', '$hp')");
?>