<?php
include "../dbconnection.php";
$id = $_POST["id"];
$name = $_POST["name"];
$email = $_POST["email"];
$address = $_POST["address"];
$location = $_POST["location"];
$hp = $_POST["hp"];

$sql = $connect->query("UPDATE customer SET NAME = '$name', EMAIL = '$email', LOCATION = '$location', ADDRESS = '$address', HP = '$hp' WHERE ID = '$id'");

?>