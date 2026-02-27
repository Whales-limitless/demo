<?php
//Make sure to include this file after dbconnection file
$validate_username = $_COOKIE["parkwaydelivery_driver"] ?? '';
//check if cookie value exists in sysfile as a delivery user
$validation_stmt = $connect->prepare("SELECT `USERNAME` FROM `sysfile` WHERE `USERNAME` = ? AND `TYPE` = 'D' LIMIT 1");
$validation_stmt->bind_param("s", $validate_username);
$validation_stmt->execute();
$validation_sql = $validation_stmt->get_result();
//if it is not inside, proceed to logout, login page dont need validation. others need.
if($validation_sql->num_rows == 0){
	header("location: logout.php");
	exit;
}
$validation_stmt->close();
?>