<?php
/*
include "dbconnection.php";
$delb4 = date('Y-m-d', strtotime('-7 days'));

//$delb4 = "2022-01-06"; 
$ord = $connect->query("SELECT * FROM orderlist WHERE STATUS = 'C' AND DELDATE <= '$delb4'");
while($ordrow = $ord->fetch_assoc()){
	$img1 = $ordrow["IMG1"];
	$img2 = $ordrow["IMG2"];
	$img3 = $ordrow["IMG3"];
	if($img1 != ""){
		$filename = "uploads/$img1";
		if (file_exists($filename)) {
			unlink($filename);
			echo 'File '.$filename.' has been deleted';
		} else {
			echo 'Could not delete '.$filename.', file does not exist';
		}
	}
	if($img2 != ""){
		$filename = "uploads/$img2";
		if (file_exists($filename)) {
			unlink($filename);
			echo 'File '.$filename.' has been deleted';
		} else {
			echo 'Could not delete '.$filename.', file does not exist';
		}
	}
	if($img3 != ""){
		$filename = "uploads/$img3";
		if (file_exists($filename)) {
			unlink($filename);
			echo 'File '.$filename.' has been deleted';
		} else {
			echo 'Could not delete '.$filename.', file does not exist';
		}
	}
}

*/
?>