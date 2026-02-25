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

$sql = $connect->query("UPDATE driver SET NAME = '$name', EMAIL = '$email', ADDRESS = '$address', POSTCODE = '$postcode', STATE = '$state', AREA = '$area', HP = '$hp' WHERE ID = '$id'");

echo "UPDATE driver SET NAME = '$name', EMAIL = '$email', ADDRESS = '$address', POSTCODE = '$postcode', STATE = '$state', AREA = '$area', HP = '$hp' WHERE ID = '$id'";
?>