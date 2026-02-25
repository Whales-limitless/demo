<?php
	mysqli_report(MYSQLI_REPORT_OFF);

	$_dbHost    = 'localhost';
	$_dbUser    = 'pwuser';
	$_dbPasswrd = 'Pwuser@123#';
	$_dbName    = 'pw_main';

	$connect = null;

	$connect = @mysqli_connect($_dbHost, $_dbUser, $_dbPasswrd, $_dbName);
	if (!$connect) {
		die('Database connection failed: ' . mysqli_connect_error());
	}

	mysqli_set_charset($connect, "utf8mb4");

	if (!mysqli_select_db($connect, $_dbName)) {
		echo 'Database selection failed.';
	}

	function clean($connect, $str) {
		$str = trim($str);
		return mysqli_real_escape_string($connect, $str);
	}
?>