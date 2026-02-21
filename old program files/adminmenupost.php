<?php
include('../dbconnection.php'); 
date_default_timezone_set("Asia/Kuala_Lumpur");

// Set charset for emoji/special characters
$connect->set_charset("utf8mb4");
if(isset($connsales)) $connsales->set_charset("utf8mb4");
if(isset($connmember)) $connmember->set_charset("utf8mb4");
if(isset($conndelivery)) $conndelivery->set_charset("utf8mb4");

if($_POST['action'] == "done"){
    $delid = $_POST['id'];
    
    // Initialize variables
    $rowsdate = '';
    $rowaccode = '';
    
    $sqlord = $connect->query("SELECT SDATE,ACCODE FROM `orderlist` WHERE SALNUM = '".$connect->real_escape_string($delid)."' LIMIT 1");
    
    if($sqlord && $sqlord->num_rows > 0){
        $row = $sqlord->fetch_assoc();
        $rowsdate   = date('ymd',strtotime($row['SDATE']));
        $rowaccode  = $row['ACCODE'];
    } else {
        echo "Error: Order not found for SALNUM: ".$delid;
        exit;
    }

    $status     = 'DONE';
    $tblsdate   = 'S'.$rowsdate;
    $tblmem     = 'M'.$rowaccode;
    $tblsdeli   = 'D'.$rowsdate;

    // Update orderlist - remove ACCODE condition to update ALL rows with this SALNUM
    $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '".$status."' WHERE SALNUM = '".$connect->real_escape_string($delid)."'");
    $affected1 = $connect->affected_rows;
    
    // Update sales table
    if(isset($connsales)) {
        @$connsales->query("UPDATE `".$tblsdate."` SET STATUS = '".$status."' WHERE SALNUM = '".$connsales->real_escape_string($delid)."'");
    }

    // Update member table
    if(isset($connmember)) {
        @$connmember->query("UPDATE `".$tblmem."` SET STATUS = '".$status."' WHERE SALNUM = '".$connmember->real_escape_string($delid)."'");
    }

    // Update delivery table
    if(isset($conndelivery)) {
        @$conndelivery->query("UPDATE `".$tblsdeli."` SET STATUS = '".$status."' WHERE SALNUM = '".$conndelivery->real_escape_string($delid)."'");
    }
    
    if($result1 && $affected1 > 0){
        echo "Saved.";
    } else if($result1 && $affected1 == 0){
        echo "Error: No rows updated. SALNUM=".$delid." may already be DONE or not exist.";
    } else {
        echo "Error: ".$connect->error;
    }

}elseif($_POST['action'] == "delete"){
    $delid = $_POST['id'];
    
    // Initialize variables
    $rowsdate = '';
    $rowaccode = '';
    
    $sqlord = $connect->query("SELECT SDATE,ACCODE FROM `orderlist` WHERE SALNUM = '".$connect->real_escape_string($delid)."' LIMIT 1");
    
    if($sqlord && $sqlord->num_rows > 0){
        $row = $sqlord->fetch_assoc();
        $rowsdate   = date('ymd',strtotime($row['SDATE']));
        $rowaccode  = $row['ACCODE'];
    } else {
        echo "Error: Order not found for SALNUM: ".$delid;
        exit;
    }

    $status     = 'DELETED';
    $tblsdate   = 'S'.$rowsdate;
    $tblmem     = 'M'.$rowaccode;
    $tblsdeli   = 'D'.$rowsdate;

    // Update orderlist - remove ACCODE condition to update ALL rows with this SALNUM
    $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '".$status."' WHERE SALNUM = '".$connect->real_escape_string($delid)."'");
    $affected1 = $connect->affected_rows;

    // Update sales table
    if(isset($connsales)) {
        @$connsales->query("UPDATE `".$tblsdate."` SET STATUS = '".$status."' WHERE SALNUM = '".$connsales->real_escape_string($delid)."'");
    }

    // Update member table
    if(isset($connmember)) {
        @$connmember->query("UPDATE `".$tblmem."` SET STATUS = '".$status."' WHERE SALNUM = '".$connmember->real_escape_string($delid)."'");
    }

    // Update delivery table
    if(isset($conndelivery)) {
        @$conndelivery->query("UPDATE `".$tblsdeli."` SET STATUS = '".$status."' WHERE SALNUM = '".$conndelivery->real_escape_string($delid)."'");
    }

    if($result1 && $affected1 > 0){
        echo "Deleted.";
    } else if($result1 && $affected1 == 0){
        echo "Error: No rows updated. SALNUM=".$delid." may already be DELETED or not exist.";
    } else {
        echo "Error: ".$connect->error;
    }

}elseif($_POST['action'] == "detail"){
    $delid = $_POST['id'];
    
    $sqlord = $connect->query("SELECT ADMINRMK,TRANSNO FROM `orderlist` WHERE SALNUM = '".$connect->real_escape_string($delid)."' LIMIT 1");
    
    if($sqlord && $sqlord->num_rows > 0){
        $row = $sqlord->fetch_assoc();
        echo ($row['ADMINRMK'] ?? '')."|".($row['TRANSNO'] ?? '');
    }

}elseif($_POST['action'] == "success"){
    $remark     = $_POST['remark'];
    $transno    = $_POST['rowtransno'];
    $delid      = $_POST['pid'];

    // Initialize variables
    $rowsdate = '';
    $rowaccode = '';

    $sqlord = $connect->query("SELECT SDATE,ACCODE FROM `orderlist` WHERE SALNUM = '".$connect->real_escape_string($delid)."' LIMIT 1");
    
    if($sqlord && $sqlord->num_rows > 0){
        $row = $sqlord->fetch_assoc();
        $rowsdate   = date('ymd',strtotime($row['SDATE']));
        $rowaccode  = $row['ACCODE'];
    } else {
        echo "Error: Order not found for SALNUM: ".$delid;
        exit;
    }

    $status     = 'PAYMENT';
    $tblsdate   = 'S'.$rowsdate;
    $tblmem     = 'M'.$rowaccode;
    $tblsdeli   = 'D'.$rowsdate;

    // Update orderlist - remove ACCODE condition to update ALL rows with this SALNUM
    $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '".$status."', TRANSNO = '".$connect->real_escape_string($transno)."', ADMINRMK = '".$connect->real_escape_string($remark)."' WHERE SALNUM = '".$connect->real_escape_string($delid)."'");
    $affected1 = $connect->affected_rows;

    // Update sales table
    if(isset($connsales)) {
        @$connsales->query("UPDATE `".$tblsdate."` SET STATUS = '".$status."', TRANSNO = '".$connsales->real_escape_string($transno)."' WHERE SALNUM = '".$connsales->real_escape_string($delid)."'");
    }

    // Update member table
    if(isset($connmember)) {
        @$connmember->query("UPDATE `".$tblmem."` SET STATUS = '".$status."', TRANSNO = '".$connmember->real_escape_string($transno)."' WHERE SALNUM = '".$connmember->real_escape_string($delid)."'");
    }

    // Update delivery table
    if(isset($conndelivery)) {
        @$conndelivery->query("UPDATE `".$tblsdeli."` SET STATUS = '".$status."', TRANSNO = '".$conndelivery->real_escape_string($transno)."' WHERE SALNUM = '".$conndelivery->real_escape_string($delid)."'");
    }
    
    if($result1 && $affected1 > 0){
        echo "Saved.";
    } else if($result1 && $affected1 == 0){
        echo "Error: No rows updated. SALNUM=".$delid." may already have this status.";
    } else {
        echo "Error: ".$connect->error;
    }
}
?>