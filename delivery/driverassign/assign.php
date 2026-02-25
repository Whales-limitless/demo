<?php
include "../dbconnection.php";
include "../validation.php";
$getcode = $_GET["code"];
$driversql = $connect->query("SELECT * FROM driver WHERE CODE = '$getcode'");
$driverrow = $driversql->fetch_assoc();
$drivername = $driverrow["NAME"];
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
						<h1 class="h2">Driver: <?php echo $drivername; ?></h1>
						<div class="btn-toolbar mb-2 mb-md-0">
							<!--
   <div class="btn-group me-2">
 <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
 <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
   </div>-->
							<button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='index.php'">Driver Assign</button>
						</div>
					</div>
					<div class="row align-items-start">
					<div class="col">
						<center><h4>Available Orders</h4></center>
					  <table class="table table-striped" style="width:100%">
								<thead>
									<tr>
										<th>Del.Date</th>
										<th>Customer</th>
										<th>Address</th>
										<th>Distant</th>
										<th>Commission</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$sql = $connect->query("SELECT * FROM orderlist WHERE DRIVERCODE = '' AND STATUS = '' ORDER BY DELDATE ASC");
									while($row = $sql->fetch_assoc()){
										$custcode = $row["CUSTOMERCODE"];
										$cust = $connect->query("SELECT * FROM customer WHERE CODE = '$custcode'");
										$custrow = $cust->fetch_assoc();
									?>
									<tr>
										<td><?php echo $row["DELDATE"]; ?></td>
										<td><?php echo $row["CUSTOMER"]; ?></td>
										<td><?php echo $custrow["ADDRESS"]; ?></td>
										<td><?php echo $row["DISTANT"]; ?></td>
										<td><?php echo $row["RETAIL"]; ?></td>
										<td>
											<div class="btn-group" role="group" aria-label="Basic mixed styles example">
												<button type="button" class="btn btn-success btn-sm" onclick="transfer1<?php echo $row["ID"]; ?>('<?php echo $row["ID"]; ?>');"><span data-feather="repeat"></span></button>

											</div>
										</td>
									</tr>
									<script>
										function transfer1<?php echo $row["ID"]; ?>(id){
											var code = '<?php echo $getcode; ?>';
											$.ajax({
												type: "POST",
												url: "transfer1.php",
												data: {
													id: id,
													code: code,
												},
												success: function(data) {
													window.location.reload();
												},
												complete: function(data) {

												},
												error: function(XMLHttpRequest, textStatus, errorThrown) {

												}
											});
										}
									</script>
									<?php
									}
									?>
								</tbody>

							</table>
					</div>
					<div class="col">
						<center><h4>Driver Assigned Orders</h4></center>
					  <table class="table table-striped" style="width:100%">
								<thead>
									<tr>
										<th>Del.Date</th>
										<th>Customer</th>
										<th>Address</th>
										<th>Distant</th>
										<th>Commission</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$sql = $connect->query("SELECT * FROM orderlist WHERE DRIVERCODE = '$getcode' AND STATUS = 'A' ORDER BY DELDATE ASC");
									while($row = $sql->fetch_assoc()){
										$custcode = $row["CUSTOMERCODE"];
										$cust = $connect->query("SELECT * FROM customer WHERE CODE = '$custcode'");
										$custrow = $cust->fetch_assoc();
									?>
									<tr>
										<td><?php echo $row["DELDATE"]; ?></td>
										<td><?php echo $row["CUSTOMER"]; ?></td>
										<td><?php echo $custrow["ADDRESS"]; ?></td>
										<td><?php echo $row["DISTANT"]; ?></td>
										<td><?php echo $row["RETAIL"]; ?></td>
										<td>
											<div class="btn-group" role="group" aria-label="Basic mixed styles example">
												<button type="button" class="btn btn-success btn-sm" onclick="transfer2<?php echo $row["ID"]; ?>('<?php echo $row["ID"]; ?>');"><span data-feather="repeat"></span></button>

											</div>
										</td>
									</tr>
								
									<script>
										function transfer2<?php echo $row["ID"]; ?>(id){
											var code = '<?php echo $getcode; ?>';

											$.ajax({
												type: "POST",
												url: "transfer2.php",
												data: {
													id: id,
													code: code,
												},
												success: function(data) {
													window.location.reload();
												},
												complete: function(data) {

												},
												error: function(XMLHttpRequest, textStatus, errorThrown) {

												}
											});
										}
									</script>
									<?php
									}
									?>
								</tbody>

							</table>
					</div>
					
				  </div>
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
		</script>
		<script>
			function add(){
				var name = document.getElementById("a1").value;
				var email = document.getElementById("a2").value;
				var address = document.getElementById("a3").value;
				var postcode = document.getElementById("a4").value;
				var state = document.getElementById("a5").value;
				var area = document.getElementById("a6").value;
				var hp = document.getElementById("a7").value;
				$.ajax({
					type: "POST",
					url: "postadd.php",
					data: {
						name: name,
						email: email,
						address: address,
						postcode: postcode,
						state: state,
						area: area,
						hp: hp,
					},
					success: function(data) {
						alert("Added successfully");
						window.location.reload();
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
					url: "postdelete.php",
					data: {
						id: id,
					},
					success: function(data) {
						alert("Deleted successfully");
						window.location.reload();
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
