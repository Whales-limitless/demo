<?php
include "../dbconnection.php";


$customercode = $_POST["customer"];
$deldate = $_POST["deldate"];
$remark = $_POST["remark"];
$ordno = $_POST["orderno"];

$cust = $connect->query("SELECT * FROM customer WHERE CODE = '$customercode'");
$custrow = $cust->fetch_assoc();
$customer = $custrow["NAME"];

$driv = $connect->query("SELECT * FROM driver WHERE CODE = '$drivercode'");
$drivrow = $driv->fetch_assoc();
$driver = $drivrow["NAME"];

$location = $_POST["location"];
$distant = $_POST["distant"];
$retail = $_POST["retail"];


    $checkord = $connect->query('SELECT ORDNO FROM `orderlist` WHERE ORDNO = "'.$ordno.'" ');
	if($checkord->num_rows>0){
		while($row = $checkord->fetch_assoc()){
            echo 'fail'; 
        }
    }else{
        $connect->query("INSERT INTO orderlist (ORDNO, DELDATE, DRIVERCODE, DRIVER, CUSTOMERCODE, CUSTOMER, LOCATION, DISTANT, RETAIL, REMARK) VALUES ('$ordno', '$deldate', '$drivercode', '$driver', '$customercode', '$customer', '$location', '$distant', '$retail', '$remark')");
		//insert desc
		$connect->query("INSERT INTO orderlistdesc SELECT * FROM orderlisttemp WHERE orderlisttemp.ORDERNO = '$ordno'");
		//$connect->query("DELETE FROM orderlisttemp WHERE ORDERNO = '$ordno'");
		
		echo "INSERT INTO orderlistdesc SELECT * FROM orderlisttemp WHERE orderlisttemp.ORDERNO = '$ordno'";
    }

//$connect->query("UPDATE parafile SET ORDNO = ORDNO + 1");
?>