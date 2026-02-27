<?php 
	include "../../staff/dbconnection.php";
	
	$ordno1 = $_POST['ordno'];
	$result = array();
	$imagedata = base64_decode($_POST['img_data']);
	
	// Sanitize filename by replacing problematic characters
	$filename = preg_replace('/[\/\\\\:*?"<>|]/', '_', $ordno1);
	
	//Location to where you want to created sign image
	$file_name = 'sign/doc_signs/'.$filename.'.png';
	file_put_contents($file_name,$imagedata);
	$result['status'] = 1;
	$result['file_name'] = $file_name;
	
	$query11 = $connect->query("SELECT ORDNO FROM `del_sign` WHERE ORDNO = '".$ordno1."'");
	if($query11->num_rows > 0){
		while($row = $query11->fetch_assoc()){
			//DO NOTHING
		}
	}else{
		$connect->query("INSERT INTO `del_sign` (ORDNO) VALUES ('".$ordno1."')");
	}
	
	echo json_encode($result['status']);
?>