<?php
include "../../staff/dbconnection.php";
include "validation.php";
$ordno = $_GET["ordno"];

$sql = $connect->query("SELECT * FROM `del_orderlist` WHERE ORDNO = '$ordno'");
while($row = $sql->fetch_assoc()){
	$deldate = $row["DELDATE"];
	$ordno = $row["ORDNO"];
	$driver = $row["DRIVER"];
	$customer = $row["CUSTOMER"];
	$location = $row["LOCATION"];
	$distant = $row["DISTANT"];
	$retail = $row["RETAIL"];
	$customerc = $row["CUSTOMERCODE"];
}

$sql2 = $connect->query("SELECT HP,ADDRESS FROM `del_customer` WHERE CODE= '".$customerc."'");
while($row = $sql2->fetch_assoc()){
	$hp = $row["HP"];
	$addr = $row["ADDRESS"];
}

$query12 = $connect->query("SELECT ORDNO FROM `del_sign` WHERE ORDNO = '".$_GET['ordno']."'");
if($query12->num_rows > 0){
	while($row = $query12->fetch_assoc()){
		$chk = 'Y';
	}
}else{
	$chk = 'N';
}

// Sanitize filename for image display (same as in signsave.php)
$sanitized_ordno = preg_replace('/[\/\\\\:*?"<>|]/', '_', $_GET['ordno']);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Deliver Order</title>

<link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- Bootstrap core CSS -->
<!-- Custom styles for this template -->
<link href="../assets/dashboard.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">

<!--<script>
window.onload = function() { window.print(); }
</script>-->
</head>
<body>
	<div id="wrap" class="d-none d-sm-block" >
        <div class="container">
            <br>
            
            <div class="d-print-none">
                <div class="row">
                    <div class="col-md-2">
                        <a href="index.php" class="btn btn-sm btn-success">
                            Back
                        </a>
                    </div>
                    <div class="col-md-2 offset-8 d-none d-sm-block" style="text-align:right">
                        <button type="button" class="btn btn-sm btn-primary" onclick="printpage()">
                            Print
                        </button>
                    </div>
                </div>
            </div>
			
			<div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 22.5%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 30%; display: table-cell">
                                </div>
                            </div>
                        </div>
                    </div>
					<div style="width: 55%; display: table-cell;">
					<center><b style="font-size:11px;">PARKWAY FURNITURE SDN BHD (CO. NO 771304-T)</b></center>
					<center><b style="font-size:11px;">TEL: 011-26114677/082-764677&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;HP:017-8129799</b></center>
					<center><font style="font-size:11px;">DELIVERY ORDER ( ) CUSTOMER COPY / COMPANY COPY ( )</font></center>
					</div>
                    <div style="width: 22.5%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 30%; display: table-cell;text-align:right">
								<b>Order No.: </b><?php echo $ordno; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
        </div>
		<br>

        <div class="container">
            <div class='table-responsive'>
                <table border="1" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th style="width:1%;padding-top:2px;padding-bottom:2px;text-align:center;border:1px solid black">NO.</th>
                            <th style="width:80%;padding-top:2px;padding-bottom:2px;text-align:center;border:1px solid black">DESCRIPTION</th>
                            <th style="width:19%;padding-top:2px;padding-bottom:2px;text-align:center;border:1px solid black">QUANTITY</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $query3  = "SELECT * FROM `del_orderlistdesc` WHERE ORDERNO = '".$ordno."'";
                        $result  = mysqli_query($connect,$query3);
                        
                        $num_rows = $result->num_rows;
                        if ($num_rows > 0){
                            $rownumber = "";
                            $rownumber = $rownumber+1;
                            while($row = mysqli_fetch_array($result)){
								?>
                                <tr>
                                    <td style="padding-top:2px;padding-bottom:2px;border:1px solid black"><?php echo $rownumber; $rownumber++ ?></td>
                                    <td style="padding-top:2px;padding-bottom:2px;border:1px solid black"><?php echo $row['PDESC']; ?></td>
                                    <td style="padding-top:2px;padding-bottom:2px;text-align:right;border:1px solid black"><?php echo $row['QTY'].' '.$row['UOM']; ?></td>
                                </tr>
								<?php
                            }
                        }
                        ?>
                
                    </tbody>
                </table>
            </div><br><br><br><br>
			
			<div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 20%; display: table-cell;border-bottom:1px solid black">
								<?php
								if($chk == 'Y'){
									?>
									<img src="sign/doc_signs/<?php echo $sanitized_ordno?>.png" style="width:100%" >
									<?php
								}
								?>
								
								<a href="sign.php?ordno=<?php echo $_GET['ordno']; ?>" class="btn btn-sm btn-success d-print-none" >Sign</a>
                                </div>
                            </div>
                        </div>
                    </div>
					<div style="width: 40%; display: table-cell;">
					</div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 20%; display: table-cell;border-bottom:1px solid black">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			
			<div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 70%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>ADDRESS: </b><?php echo $addr; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>PARKWAY FURNITURE S/B</b></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 70%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>NAME:</b> <?php echo $customer; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>DELIVERY DATE:</b> <?php echo date("d/m/y",strtotime($deldate)); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 70%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>TEL:</b> <?php echo $hp; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>DRIVER:</b> <?php echo $driver; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			
			<div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 70%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>PURCHASE DATE:</b></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>CHECKER:</b></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
	
	<div id="wrap" class="d-block d-sm-none">
        <div class="container">
            <br>
            
            <div class="d-print-none">
                <div class="row">
                    <div class="col-md-12">
                        <a href="index.php" class="btn btn-sm btn-success w-100">
                            Back
                        </a>
                    </div>
                </div>
            </div>
			
			<div style="width: 100%; display: table;">
                <div style="display: table-row">
					<div style="width: 100%; display: table-cell;">
					<b>PARKWAY FURNITURE SDN BHD (CO. NO 771304-T)</b><br>
					<b>TEL: 011-26114677/082-764677<br>HP:017-8129799</b><br>
					<font>DELIVERY ORDER ( ) CUSTOMER COPY / COMPANY COPY ( )</font><br>
					</div>
                </div>
            </div>
			
			<div style="width: 100%; display: table;">
                <div style="display: table-row">
                    <div style="width: 100%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;text-align:right">
								<b>Order No.: </b><?php echo $ordno; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<br>

        <div class="container">
            <div class='table-responsive'>
                <table border="1" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th style="width:1%;padding-top:2px;padding-bottom:2px;text-align:center;border:1px solid black">NO.</th>
                            <th style="width:80%;padding-top:2px;padding-bottom:2px;text-align:center;border:1px solid black">DESCRIPTION</th>
                            <th style="width:19%;padding-top:2px;padding-bottom:2px;text-align:center;border:1px solid black">QUANTITY</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $query3  = "SELECT * FROM `del_orderlistdesc` WHERE ORDERNO = '".$ordno."'";
                        $result  = mysqli_query($connect,$query3);
                        
                        $num_rows = $result->num_rows;
                        if ($num_rows > 0){
                            $rownumber = "";
                            $rownumber = $rownumber+1;
                            while($row = mysqli_fetch_array($result)){
								?>
                                <tr>
                                    <td style="padding-top:2px;padding-bottom:2px;border:1px solid black"><?php echo $rownumber; $rownumber++ ?></td>
                                    <td style="padding-top:2px;padding-bottom:2px;border:1px solid black"><?php echo $row['PDESC']; ?></td>
                                    <td style="padding-top:2px;padding-bottom:2px;text-align:right;border:1px solid black"><?php echo $row['QTY'].' '.$row['UOM']; ?></td>
                                </tr>
								<?php
                            }
                        }
                        ?>
                
                    </tbody>
                </table>
            </div><br><br><br><br>
			
			<div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 20%; display: table-cell;border-bottom:1px solid black">
								<?php
								if($chk == 'Y'){
									?>
									<img src="sign/doc_signs/<?php echo $sanitized_ordno?>.png" style="width:100%" >
									<?php
								}
								?>
								
								<a href="sign.php?ordno=<?php echo $_GET['ordno']; ?>" class="btn btn-sm btn-success d-print-none" >Sign</a>
                                </div>
                            </div>
                        </div>
                    </div>
					<div style="width: 40%; display: table-cell;">
					</div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 20%; display: table-cell;border-bottom:1px solid black">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			
			<div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 70%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>ADDRESS: </b><?php echo $addr; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>PARKWAY FURNITURE S/B</b></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 70%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>NAME:</b> <?php echo $customer; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>DELIVERY DATE:</b> <?php echo date("d/m/y",strtotime($deldate)); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 70%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>TEL:</b> <?php echo $hp; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>DRIVER:</b> <?php echo $driver; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			
			<div style="width: 100%; display: table;font-size:11px">
                <div style="display: table-row">
                    <div style="width: 70%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>PURCHASE DATE:</b></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="width: 30%; display: table-cell;">
                        <div style="width: 100%; display: table;">
                            <div style="display: table-row">
                                <div style="width: 100%; display: table-cell;">
                                    <label style="width:100%"><b>CHECKER:</b></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</body>
	
<script>
function printpage(){
    window.print();
}
</script>
	
</html>