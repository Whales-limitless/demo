<?php
include "../dbconnection.php";

$getorderno = $_GET["orderno"] ?? "";
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
		<meta name="generator" content="Hugo 0.84.0">
		<title>Dashboard Template · Bootstrap v5.0</title>

		<link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<!-- Bootstrap core CSS -->
		<!-- Custom styles for this template -->
		<link href="../assets/dashboard.css" rel="stylesheet">

		<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">

		<link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">

		<script src="https://code.jquery.com/jquery-3.5.1.js"></script>

		<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>

		<script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>

		<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
		<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

		<style>
			.bd-placeholder-img {
				font-size: 1.125rem;
				text-anchor: middle;
				-webkit-user-select: none;
				-moz-user-select: none;
				user-select: none;
			}

			@media (min-width: 768px) {
				.bd-placeholder-img-lg {
					font-size: 3.5rem;
				}
			}
			.dataTables_filter {
				float: right !important;
			}

			.dataTables_paginate {
				float: right !important;
			}

			.select2-selection {
				-webkit-box-shadow: 0;
				box-shadow: 0;
				background-color: #fff;
				border: 0;
				border-radius: 0;
				color: #555555;
				font-size: 14px;
				outline: 0;
				min-height: 37.5px;
				text-align: left;
			}

			.select2-selection__rendered {
				margin: 5px;
			}

			.select2-selection__arrow {
				margin: 5px;
			}
		</style>

	</head>
	<body>
		<?php
		include "../navbar.php";
		?>

		<div class="container-fluid">
			<div class="row">
				<?php
				include "../sidebar.php";
				?>

				<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
					<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
						<h1 class="h2">Add Order</h1>
						<div class="btn-toolbar mb-2 mb-md-0">
							<button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='deliverorder.php';">Check Order</button>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2 mb-2">
							<b>Delivery Date</b>
							<input type="date" class="form-control" name="deldate" id="deldate" value="<?php echo date("Y-m-d"); ?>" >
						</div>
						<div class="col-md-2 mb-2">
							<b>Order No</b>
							<input type="text" class="form-control" name="orderno" id="orderno" value="<?php echo $getorderno; ?>" onchange="lockorder()"  <?php if($getorderno != ""){echo "disabled";} ?>>
						</div>
						<div class="col-md-4 mb-2">
							<b>Customer</b>
							<select class="customer w-100" name="customer" id="customer">
								<option disabled selected value="<?php echo isset($m1) ? $m1 : '' ?>" ><?php echo isset($m1a) ? $m1a : '' ?></option>
								<?php
								$driver = $connect->query("SELECT * FROM customer ORDER BY NAME ASC");
								while($driverrow = $driver->fetch_assoc()){
								?>
								<option value="<?php echo $driverrow["CODE"]; ?>"><?php echo $driverrow["NAME"]; ?></option>
								<?php
								}
								?>
							</select>
						</div>
						<div class="col-md-3 mb-2">
							<b>Deliver To</b>
							<input type="text" name="remark" id="remark" class="form-control">
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-1">
							<label>Location</label>
							<input type="text" name="location" id="location" class="form-control" readonly>
						</div>
						<div class="col-md-3">
							<label>Address</label>
							<input type="text" name="address" id="address" class="form-control" readonly>
						</div>
						<div class="col-md-1">
							<label>Distant</label>
							<input type="text" name="distant" id="distant" class="form-control" readonly>
						</div>
						<div class="col-md-1">
							<label>Commission</label>
							<input type="text" name="retail" id="retail" class="form-control"  readonly>
						</div>

						<div class="col-md-1">
							<br>
							<button class="btn btn-dark"  onclick="save()">Save</button>
						</div>
					</div>

					<br>
					<?php
					if($getorderno != ""){
					?>
					<div class="row">
						<div class="col-12 col-lg-12">
							<div class="table-responsive">
								<div id="mydiv">
									<table class="table table-bordered table-sm" id="option_table" style="width:100%">
										<thead>
											<tr>
												<th style="width:10%;">NO.</th>
												<th style="width:70%;">DESCRIPTION</th>
												<th style="width:10%;">QTY</th>
												<th style="width:10%;">UOM</th>
												<th style="width:10%;"></th>
											</tr>
											<tr>
												<th></th>
												<th><input type="text" name="txtpdesc" id="pdesc" class="form-control form-control-sm" /></th>
												<th><input type="number" name="txtqtyin" id="qty" class="form-control form-control-sm" value="1" min="1"/></th>
												<th>
													<select class="form-control" id="uom" name="uom">
														<?php
														$uom = $connect->query("SELECT * FROM uom");
														while($uomrow = $uom->fetch_assoc()){
														?>
														<option><?php echo $uomrow["PDESC"]; ?></option>
														<?php
														}
														?>
													</select>
												</th>
												<th><button type="button" onclick="add()" class="btn btn-primary btn-sm w-100">Add</button></th>
											</tr>
										</thead>
										<tbody>
											<?php
						$num = 0;
						$temp = $connect->query("SELECT * FROM orderlisttemp WHERE ORDERNO = '$getorderno'");
						while($temprow = $temp->fetch_assoc()){
							$num++;
											?>
											<tr>
												<td><?php echo $num; ?></td>
												<td><?php echo $temprow["PDESC"]; ?></td>
												<td><?php echo $temprow["QTY"]; ?></td>
												<td><?php echo $temprow["UOM"]; ?></td>
												<td><button type="button" onclick="del('<?php echo $temprow["ID"]; ?>')" class="btn btn-danger btn-sm w-100">Delete</button></td>
											</tr>
											<?php
						}
											?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					<?php
					}
					?>
					<!--
  <table class="table" style="width:100%">
   <thead>
 <tr>
  <th>Delivery Date</th>
  <th>Driver Name</th>
  <th>Customer Name</th>
  <th>Location</th>
  <th>Distant</th>
  <th>Retail(Commission)</th>
  <th>Status</th>
  <th>Action</th>
 </tr>
   </thead>
   <tbody>
 <?php
$sql = $connect->query("SELECT * FROM orderlist ORDER BY DELDATE ASC");
while($row = $sql->fetch_assoc()){
 ?>
 <tr>
  <td><?php echo $row["DELDATE"]; ?></td>
  <td><?php echo $row["DRIVER"]; ?></td>
  <td><?php echo $row["CUSTOMER"]; ?></td>
  <td><?php echo $row["LOCATION"]; ?></td>
  <td><?php echo $row["DISTANT"]; ?></td>
  <td><?php echo $row["RETAIL"]; ?></td>
  <td><?php echo $row["STATUS"]; ?></td>
  <td>
   <div class="btn-group" role="group" aria-label="Basic mixed styles example">
 <button type="button" class="btn btn-success">Edit</button>
 <button type="button" class="btn btn-danger" onclick="if (confirm('Delete selected item?')){del(<?php echo $row["ID"]; ?>);}else{};">Delete</button>
   </div>
  </td>
 </tr>
 <?php
}
 ?>
   </tbody>

  </table>-->
				</main>

			</div>
		</div>


		<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script>
		<script src="../assets/dashboard.js"></script>

		<script>
			$(document).ready(function () {
				$('#example').DataTable();

			});

			/*
			$(".driver").select2({
				placeholder: "Select a Driver",
				allowClear: true
			});*/
			
			$('.customer').select2();

		</script>
		<script>
			function save(){
				var remark = document.getElementById("remark").value;
				var orderno = document.getElementById("orderno").value; 
				var deldate = document.getElementById("deldate").value;
				//var driver = document.getElementById("driver").value;
				var customer = document.getElementById("customer").value;
				var location = document.getElementById("location").value;
				var distant = document.getElementById("distant").value;
				var retail = document.getElementById("retail").value;
				$.ajax({
					type: "POST",
					url: "saveorder.php",
					data: {
						remark: remark,
						orderno: orderno,
						deldate: deldate,
						//driver: driver,
						customer: customer,
						location: location,
						distant: distant,
						retail: retail,
					},
					success: function(data) {
						if(data == "fail"){
							alert("Order No already exists");	
							window.location.href='/parkwaydelivery/order/addorder.php';
						}else{
							alert("Saved successfully");
							window.location.href='/parkwaydelivery/order/deliverorder.php';
						}

					},
					complete: function(data) {

					},
					error: function(XMLHttpRequest, textStatus, errorThrown) {

					}
				});	
			}
		</script>
		<script>
			$("#customer").change(function(){
				var name = document.getElementById("customer").value;

				$.ajax({
					type: "POST",
					url: "getlocation.php",
					data: {
						name: name,
					},
					success: function(data) {
						var myarray = data.split("|");
						var location = myarray[0];
						var distant = myarray[1];
						var retail = myarray[2];
						var address = myarray[3];
						document.getElementById("location").value = location;
						document.getElementById("distant").value = distant;
						document.getElementById("retail").value = retail;
						document.getElementById("address").value = address;
					},
					complete: function(data) {

					},
					error: function(XMLHttpRequest, textStatus, errorThrown) {

					}
				});	  
			});
		</script>

		<script>
			function lockorder(){
				var orderno = document.getElementById("orderno").value;
				window.location.href='?orderno='+orderno;
			}

			function add(){
				var orderno = document.getElementById("orderno").value;
				var pdesc = document.getElementById("pdesc").value;
				var qty = document.getElementById("qty").value;
				var uom = document.getElementById("uom").value;
				$.ajax({
					type: "POST",
					url: "add.php",
					data: {
						orderno: orderno,
						pdesc: pdesc,
						qty: qty,
						uom: uom,
					},
					success: function(data) {
						$("#mydiv").load(location.href + " #mydiv");
					},
					complete: function(data) {

					},
					error: function(XMLHttpRequest, textStatus, errorThrown) {

					}
				});	
			}

			function del(id){

				$.ajax({
					type: "POST",
					url: "del.php",
					data: {
						id: id,

					},
					success: function(data) {
						$("#mydiv").load(location.href + " #mydiv");
					},
					complete: function(data) {

					},
					error: function(XMLHttpRequest, textStatus, errorThrown) {

					}
				});	
			}
		</script>
	</body>
</html>
