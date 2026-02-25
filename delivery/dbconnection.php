<?php
date_default_timezone_set("Asia/Kuala_Lumpur");
$_dbHost 	= 'localhost';
$_dbUser	= 'u921536699_parkwaydeliver';
$_dbPasswrd	= 'Parkway@123#';
$_dbName 	= 'u921536699_parkwaydeliver';

$connect = mysqli_connect($_dbHost,$_dbUser,$_dbPasswrd,$_dbName);

if(mysqli_connect_errno())
{
	echo 'Database connection failed.' . mysqli_connect_error();
}
//validate db
if(!mysqli_select_db($connect,$_dbName))
{
	echo 'Database selection failed.';
}
//Function to sanitize values received from the form. Prevents SQL injection
function clean($connect,$str)
{
	$str = trim($str);
	if(get_magic_quotes_gpc())
	{
		$str = stripslashes($str);
	}
	return mysqli_real_escape_string($connect,$str);
}	
$connect->set_charset("utf8mb4");
?>
