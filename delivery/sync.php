<?php

include "dbconnection.php";

$orderlist = $connect->query("SELECT * FROM orderlist");
while($orderlistrow = $orderlist->fetch_assoc()){
	$img1name = $orderlistrow["IMG1"];
	$getimg1 = file_get_contents("/uploads/$img1name");
	$connect->query("INSERT INTO blobtest (BLOBIMG) VALUES ('$getimg1')");
}
?>