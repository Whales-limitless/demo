<?php
include 'adminheader.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

// Fix for emoji/special characters - set charset to utf8mb4
mysqli_set_charset($connect, "utf8mb4");
if(isset($conndelivery) && $conndelivery) {
    mysqli_set_charset($conndelivery, "utf8mb4");
}

$get_id = $_GET['salnum'] ?? '0';

// PHP 8.x FIX: Use null coalescing operator
$get_deldate = $_GET["deldate"] ?? null;
$get_deltime = $_GET["deltime"] ?? null;

//update view when this page is viewed
//$padded_login_outlet = sprintf("%02d", $login_outlet);
$padded_login_outlet = '';
$view_item = date("Y-m-d H:i:s");
//$update_admin_live = mysqli_query($connect, "UPDATE delivery_status SET view_item = '$view_item' WHERE salnum = '$get_id'");
$view_update = mysqli_query($connect, "UPDATE orderlist$padded_login_outlet SET view_status = '1' WHERE SALNUM = $get_id");
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<style>
			.officecopy{
				display:none;
			}
			td, th {
				vertical-align: top;
			}
			.card {
				box-shadow: 0 4px 8px 0 rgba(0,0,0,0.4);
				transition: 0.3s;
				margin-bottom:1%;
				padding: 10px;
			}
            .rack-info {
                font-size: 12px;
                color: #666;
                font-style: italic;
            }
			@media print {
				.hideprint {
					display: none;
				}
				.officecopy{
					display: block;
				}
			}
			@media only screen and (max-width: 600px) {
				.btn_print {

				}
			}
		</style>

	</head>

	<body>

		<div class="container print card" >

			<div class="hideprint">
				<div style="float:right;">
					<div class="form-group col-md-1" style="padding:0px">
						<a href="adminmenu.php" class="btn btn-default pbtn" >Back</a>
					</div>
				</div>
				<div style="float:right;margin-right:5px;">
					<a href="adminmenu_detail1.php?salnum=<?php echo $get_id?>"
					   class="btn btn-default btn_print">Print Receipt</a>	
				</div>
				<div style="float:right;margin-right:5px;">
					<div class="form-group col-md-1" style="padding:0px">
						<form method="POST">
							<input type="submit" value="Print A4" class="btn btn-info" name="submit_print" onclick="window.print();">
						</form>
						<!--<button type="button" class="btn btn-success pbtn" onClick="window.print();">Print</button>-->
					</div>
				</div>	
			</div>


			<?php
	//echo $get_id;
	if(isset($_POST["submit_print"])){
		$print_sql = "UPDATE orderlist$padded_login_outlet SET PRINT = PRINT + 1 WHERE SALNUM = $get_id";
		$print_query = mysqli_query($connect, $print_sql);
	}  
			?>    

			<?php
			// PHP 8.x FIX: Initialize all variables with default values to prevent errors
			$raccode = $roworderid = $rowdate = $rowtrack = $rowname = $rowoutlet = $rowttime = $rowptype = $rowstatus = $rowto = '';
			$rowphone = $rowemail = $rowaddress = $mer_name = $mer_addr = $mer_cont = '';
			$del_address = 'N/A'; // Default value if not found
			$rowdelfee = 0; // Default value for delivery fee

			$getdata = mysqli_query($connect, "SELECT * from orderlist$padded_login_outlet where SALNUM = '".$get_id."' LIMIT 1");
			if ($getdata && mysqli_num_rows($getdata) > 0) {
				$row = mysqli_fetch_array($getdata);
				$raccode    = $row['ACCODE'] ?? '';
				$roworderid = $row['SALNUM'] ?? '';
				$rowdate    = $row['SDATE'] ?? '';
				$rowtrack   = $row['TRANSNO'] ?? '';
				$rowname    = $row['NAME'] ?? '';
				$rowoutlet  = $row['OUTLET'] ?? '';
				$rowttime   = $row['TTIME'] ?? '';
				$rowdelfee  = $row['DELIFEE'] ?? 0;
				$rowptype	= $row['PTYPE'] ?? '';
				$rowstatus	= $row['STATUS'] ?? '';
				$rowto		= $row['TXTTO'] ?? '';

				$query_contact = mysqli_query($connect, "SELECT * FROM MEMBER WHERE ACCODE = '".$raccode."' ");
				if ($contact_row = mysqli_fetch_array($query_contact)){
					$rowphone = $contact_row['HP'] ?? '';
					$rowemail = $contact_row['EMAIL'] ?? '';
					$rowaddress = ($contact_row['ADD1'] ?? '').' '.($contact_row['ADD2'] ?? '').' '.($contact_row['ADD3'] ?? '');
				}

				$get_merchant = $connect->query('SELECT * FROM outlet WHERE CODE = "'.$rowoutlet.'"');
				if($get_merchant && $m_row = $get_merchant->fetch_assoc()){
					$mer_name = $m_row['PDESC'] ?? '';
					$mer_addr = $m_row['ADDRESS'] ?? '';
					$mer_cont = $m_row['CONTACT'] ?? '';
				}

				// PHP 8.x FIX: Check if $rowdate is not empty before strtotime
				if (!empty($rowdate)) {
					$tbl_date = 'D' . date("ymd", strtotime($rowdate));
					// Check if the second database connection exists before trying to use it
					if (isset($conndelivery) && $conndelivery) {
						try {
							// Attempt the query that might fail
							$query_delivery = mysqli_query($conndelivery, "SELECT * FROM `$tbl_date` WHERE SALNUM = '$roworderid'");
							
							// This code will only run if the query above did NOT throw an error
							if ($query_delivery && $del_row = mysqli_fetch_array($query_delivery)){
								$del_address = $del_row["DELADDRESS"] ?? 'N/A';
							}
						} catch (mysqli_sql_exception $e) {
							// The query failed (e.g., table doesn't exist), but we caught the error.
							// The script will NOT crash.
							// We do nothing here, so $del_address keeps its default 'N/A' value.
						}
					}
				}

			} else {
				// This runs if the order ID itself was not found.
				echo "<h1>Order not found.</h1>";
				// We stop the script here to prevent further errors.
				exit();
			}
			?>
			<div class="clearfix"></div>

			<table width="100%" border=0>
				<tr>
					<td style="text-align:left;width:25%;"><img src="../logo/logo.png" style="width:70px;height:70px;"></td>
					<td style="text-align:center;width:50%;">
						<p><?php echo $mer_name;?></p>
						<p><?php echo $mer_addr;?></p>
						<p>Contact No: <?php echo $mer_cont;?></p>
					</td>
					<td style="text-align:right;width:25%;">Sales Order</td>
				</tr>
			</table>

			<hr style="border-width: 1px 1px 0;border-style: solid;border-color: black;margin-left: auto;margin-right: auto;">

			<table width="100%" border=0>
				<tr>
					<td style="width:13%;font-size:22px">To</td>
					<td style="width:2%;">: </td>
					<th style="width:85%;font-size:22px"><?php echo $rowto;?></th>
				</tr>
				<tr>
					<td style="width:13%;">Order ID</td>
					<td style="width:2%;">: </td>
					<th style="width:35%;"><?php echo $roworderid;?></th>
					<td style="width:13%;">Address</td>
					<td style="width:2%;">: </td>
					<td style="width:35%;"><?php echo $del_address;?></td>
				</tr>
				<tr>
					<td style="width:13;">Date</td>
					<td style="width:2%;">: </td>
					<td style="width:35%;"><?php echo !empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : '';?></td>
					<td style="width:13%;">Time</td>
					<td style="width:2%;">: </td>
					<td style="width:35%;"><?php echo $rowttime;?></td>
				</tr>
				<tr>
					<td style="width:13%;">Customer</td>
					<td style="width:2%;">: </td>
					<td style="width:35%;"><?php echo $rowname;?></td>
					<td style="width:13%;">Email</td>
					<td style="width:2%;">: </td>
					<td style="width:35%;"><?php echo $rowemail;?></td>
				</tr>
				<tr>
					<td style="width:13%;">Handphone</td>
					<td style="width:2%;">: </td>
					<td style="width:35%;"><?php echo $rowphone;?></td>
					<td style="width:13%;"></td>
					<td style="width:2%;"></td>
					<td style="width:35%;"></td>
				</tr>
			</table>
			<br>
			<table class="" style="width:100%">
				<tr class="info" style="border-top:1px solid black;border-bottom:1px solid black;">
					<td style="width:5%;">S/N</td>
					<td style="width:15%;">Barcode</td>
					<td style="width:40%;">Item</td>
					<td style="width:10%;text-align:right">Qty</td>
					<td style="width:10%;text-align:right">Price</td>
					<td style="width:10%;text-align:right">Disc</td>
					<td style="width:10%;text-align:right">Amt</td>
				</tr>

				<?php 
				$numrow = '1';
				$sum	= '0';
				$discount = '0';
				$order_item = mysqli_query($connect, "SELECT * from `orderlist$padded_login_outlet` where SALNUM = '".$roworderid."' AND PDESC <> 'USE POINTS' ");
				while ($row = mysqli_fetch_array($order_item)){
					$rowpcode       = $row['BARCODE'] ?? '';
					$rowquantity    = $row['QTY'] ?? 0;
					$rowpdesc       = $row['PDESC'] ?? '';
					$rowprice       = $row['RETAIL'] ?? 0;
					$rowtotal       = $row['AMOUNT'] ?? 0;
					$rowdisc		= $rowtotal-($rowquantity*$rowprice);
                    
                    // Get rack information
                    $rack_info = "";
                    $rack_query = mysqli_query($connect, "SELECT rack FROM PRODUCTS WHERE barcode = '$rowpcode' LIMIT 1");
                    if($rack_row = mysqli_fetch_array($rack_query)){
                        if(!empty($rack_row['rack'])) {
                            $rack_info = "Rack: " . $rack_row['rack'];
                        }
                    }
				?>      
				<tr>
					<td><?php echo $numrow; $numrow++; ?></td>
					<td><?php echo $rowpcode; ?></td>
					<td>
                        <?php echo $rowpdesc; ?>
                        <?php if(!empty($rack_info)): ?>
                        <div class="rack-info"><?php echo $rack_info; ?></div>
                        <?php endif; ?>
                    </td>
					<td style="text-align:right"><?php echo $rowquantity; ?></td>
					<td style="text-align:right"><?php echo number_format($rowprice, 2, '.', ''); ?></td>
					<td style="text-align:right"><?php echo number_format($rowdisc, 2, '.', ''); ?></td>
					<td style="text-align:right"><?php echo number_format($rowtotal, 2, '.', ''); ?></td>
				</tr>
				<?php
					$sum = $sum + $rowtotal;
					$discount = $discount + $rowdisc;
				}
				?>
				<tr>
					<td>&nbsp;</td>
				</tr>
				<tr style="border-top:1px solid black;">
					<td style="text-align:right" colspan="6">Discount</td>
					<td style="text-align:right;"><?php echo number_format($discount, 2, '.', ''); ?></td>
				</tr>
				<tr>
					<td style="text-align:right" colspan="6">Delivery Fee</td>
					<td style="text-align:right;"><?php echo number_format($rowdelfee, 2, '.', ''); ?></td>
				</tr>
				<tr>
					<th style="text-align:right" colspan="6">Total (RM)</th>
					<th style="text-align:right;border-top:1px solid black;"><?php echo number_format($sum+$rowdelfee, 2, '.', ''); ?></th>
				</tr>
			</table>

			<br><br>
			<hr style="border-width: 1px 1px 0;border-style: solid;border-color: black;margin-left: auto;margin-right: auto;">
			<p>
				Order date:
				<?php echo (!empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : '') . ' ' . $rowttime; ?><br>
				
				Payment type:
				<?php
					if($rowptype=='CS'){
						echo 'Cash';
					}elseif($rowptype=='SnP'){
						echo 'Senangpay';
					}elseif($rowptype=='SnPR'){
						echo 'Senangpay Ins';
					}
				?>
				<br>
				
				Status:
				<?php
					if($rowstatus=='PAYMENT'){
						echo 'Paid';
					}elseif($rowstatus=='PENDING'){
						echo 'Unpaid';
					}elseif($rowstatus=='FAILED'){
						echo 'Unpaid';
					}elseif($rowstatus=='DONE'){
						echo 'Paid';
					}elseif($rowstatus=='DELETED'){
						echo 'Unpaid';
					}else{
						echo 'Unpaid';
					}
				?>
			</p>

			<!-- go new page if item reached max -->
			<div class="officecopy" style="<?php /*if($numrow > 10){*/echo 'page-break-before: always';/*}*/ ?>">
				<table width="100%" border=0>
					<tr>
						<td style="text-align:left;width:25%;"><img src="../logo/logo.png" style="width:70px;height:70px;"></td>
						<td style="text-align:center;width:50%;">
							<p><?php echo $mer_name;?></p>
							<p><?php echo $mer_addr;?></p>
							<p>Contact No: <?php echo $mer_cont;?></p>
						</td>
						<td style="text-align:right;width:25%;">Sales Order</td>
					</tr>
				</table>
				<hr style="border-width: 1px 1px 0;border-style: solid;border-color: black;margin-left: auto;margin-right: auto;">

				<table width="100%" border=0>
					<tr>
						<td style="width:13%;font-size:22px">To</td>
						<td style="width:2%;">: </td>
						<th style="width:85%;font-size:22px"><?php echo $rowto;?></th>
					</tr>
					<tr>
						<td style="width:13%;">Order ID</td>
						<td style="width:2%;">: </td>
						<th style="width:35%;"><?php echo $roworderid;?></th>
						<td style="width:13%;">Address</td>
						<td style="width:2%;">: </td>
						<td style="width:35%;"><?php echo $del_address;?></td>
					</tr>
					<tr>
						<td style="width:13;">Date</td>
						<td style="width:2%;">: </td>
						<td style="width:35%;"><?php echo !empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : '';?></td>
						<td style="width:13%;">Time</td>
						<td style="width:2%;">: </td>
						<td style="width:35%;"><?php echo $rowttime;?></td>
					</tr>
					<tr>
						<td style="width:13%;">Customer</td>
						<td style="width:2%;">: </td>
						<td style="width:35%;"><?php echo $rowname;?></td>
						<td style="width:13%;">Email</td>
						<td style="width:2%;">: </td>
						<td style="width:35%;"><?php echo $rowemail;?></td>
					</tr>
					<tr>
						<td style="width:13%;">Handphone</td>
						<td style="width:2%;">: </td>
						<td style="width:35%;"><?php echo $rowphone;?></td>
						<td style="width:13%;"></td>
						<td style="width:2%;"></td>
						<td style="width:35%;"></td>
					</tr>
				</table>
				<br>
				<table class="" style="width:100%">
					<tr class="info" style="border-top:1px solid black;border-bottom:1px solid black;">
						<td style="width:5%;">S/N</td>
						<td style="width:15%;">Barcode</td>
						<td style="width:40%;">Item</td>
						<td style="width:10%;text-align:right">Qty</td>
						<td style="width:10%;text-align:right">Price</td>
						<td style="width:10%;text-align:right">Disc</td>
						<td style="width:10%;text-align:right">Amt</td>
					</tr>

					<?php 
					$numrow = '1';
					$sum	= '0';
					$discount = '0';
					$order_item = mysqli_query($connect, "SELECT * from `orderlist$padded_login_outlet` where SALNUM = '".$roworderid."' AND PDESC <> 'USE POINTS' ");
					while ($row = mysqli_fetch_array($order_item)){
						$rowpcode       = $row['BARCODE'] ?? '';
						$rowquantity    = $row['QTY'] ?? 0;
						$rowpdesc       = $row['PDESC'] ?? '';
						$rowprice       = $row['RETAIL'] ?? 0;
						$rowtotal       = $row['AMOUNT'] ?? 0;
						$rowdisc		= $rowtotal-($rowquantity*$rowprice);
                        
                        // Get rack information
                        $rack_info = "";
                        $rack_query = mysqli_query($connect, "SELECT rack FROM PRODUCTS WHERE barcode = '$rowpcode' LIMIT 1");
                        if($rack_row = mysqli_fetch_array($rack_query)){
                            if(!empty($rack_row['rack'])) {
                                $rack_info = "Rack: " . $rack_row['rack'];
                            }
                        }
					?>      
					<tr>
						<td><?php echo $numrow; $numrow++; ?></td>
						<td><?php echo $rowpcode; ?></td>
						<td>
                            <?php echo $rowpdesc; ?>
                            <?php if(!empty($rack_info)): ?>
                            <div class="rack-info"><?php echo $rack_info; ?></div>
                            <?php endif; ?>
                        </td>
						<td style="text-align:right"><?php echo $rowquantity; ?></td>
						<td style="text-align:right"><?php echo number_format($rowprice, 2, '.', ''); ?></td>
						<td style="text-align:right"><?php echo number_format($rowdisc, 2, '.', ''); ?></td>
						<td style="text-align:right"><?php echo number_format($rowtotal, 2, '.', ''); ?></td>
					</tr>
					<?php
						$sum = $sum + $rowtotal;
						$discount = $discount + $rowdisc;
					}
					?>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr style="border-top:1px solid black;">
						<td style="text-align:right" colspan="6">Discount</td>
						<td style="text-align:right;"><?php echo number_format($discount, 2, '.', ''); ?></td>
					</tr>
					<tr>
						<td style="text-align:right" colspan="6">Delivery Fee</td>
						<td style="text-align:right;"><?php echo number_format($rowdelfee, 2, '.', ''); ?></td>
					</tr>
					<tr>
						<th style="text-align:right" colspan="6">Total (RM)</th>
						<th style="text-align:right;border-top:1px solid black;"><?php echo number_format($sum+$rowdelfee, 2, '.', ''); ?></th>
					</tr>
				</table>

				<!--<div style="padding-bottom:20px;"></div>-->
				<table width="100%">
					<tr>
						<td style="width:70%;">
							OFFICE COPY
						</td>
						<td style="width:30%;text-align:center;">
							<br>
							<br>
							<hr style="border-width: 1px 1px 0;border-style: solid;border-color: black;margin-left: auto;margin-right: auto;">
							Customer Signature
						</td>
					</tr>
				</table>
				<hr style="border-width: 1px 1px 0;border-style: solid;border-color: black;margin-left: auto;margin-right: auto;">
				<p>Order date:
					<?php echo (!empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : '') . ' ' . $rowttime; ?></p>
			</div>
		</div>

	</body>
</html>