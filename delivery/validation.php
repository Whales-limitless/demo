<?php
//Make sure to include this file after dbconnection file
$validate_username = $_COOKIE["parkwaydelivery_user"];
//check if cookie is inside database
$validation_sql = $connect->query("SELECT * FROM users WHERE USERNAME = '$validate_username'");
//if it is not inside, proceed to logout, login page dont need validation. others need.
if($validation_sql->num_rows == 0){
	header("location: logout.php");	
}
?>