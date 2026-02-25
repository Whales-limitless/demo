<?php
include "../dbconnection.php";
include "../validation.php";

$getsdate = isset($_GET['s']) ? $_GET['s'] : date('Y-m-01');
$getedate = isset($_GET['e']) ? $_GET['e'] : date('Y-m-d');
$getstatus = isset($_GET['st']) ? $_GET['st'] : '';


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
						<h1 class="h2">Deliver Order</h1>
						<div class="btn-toolbar mb-2 mb-md-0">
							<!--
   <div class="btn-group me-2">
 <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
 <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
   </div>-->
							<button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='addorder.php'">Add Order</button>
						</div>
					</div>
					
					<div class="row">
						<div class="col-lg-2">
						Start Date
						<input type="date" id="sdate" class="form-control" style="padding:0px" value="<?php echo $getsdate; ?>" >
						</div>
						
						<div class="col-lg-2">
						End Date
						<input type="date" id="edate" class="form-control" style="padding:0px" value="<?php echo $getedate; ?>" >
						</div>
						
						<div class="col-lg-2">
						Status
						<select id="status" class="form-select" style="padding:0px">
						  <option hidden><?php echo $getstatus; ?></option>
						  <option value="">All</option>
						  <option value="O">Ordered</option>
						  <option value="A">Assigned</option>
						  <option value="D">Done</option>
						  <option value="C">Completed</option>
						</select>
						</div>
						
						<div class="col-lg-2"><br>
						 <button onclick="myFunction()" class="btn btn-success btn-sm" >Search</button> 
						</div>
					</div>
					
					<table id="example" class="table table-striped" style="width:100%">
						<thead>
							<tr>
								<th>Delivery Date</th>
								<th>Order No</th>
								<th>Driver Name</th>
								<th>Customer Name</th>
								<th>Address</th>
								<th>Status</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							if($getstatus == ""){
							$sql = $connect->query("SELECT * FROM orderlist WHERE DELDATE >= '$getsdate' AND DELDATE <= '$getedate' ORDER BY DELDATE ASC");
							}
							if($getstatus == "O"){
							$sql = $connect->query("SELECT * FROM orderlist WHERE DELDATE >= '$getsdate' AND DELDATE <= '$getedate' AND STATUS = '' ORDER BY DELDATE ASC");
							}
							if($getstatus == "A"){
							$sql = $connect->query("SELECT * FROM orderlist WHERE DELDATE >= '$getsdate' AND DELDATE <= '$getedate' AND STATUS = 'A' ORDER BY DELDATE ASC");
							}
							if($getstatus == "D"){
							$sql = $connect->query("SELECT * FROM orderlist WHERE DELDATE >= '$getsdate' AND DELDATE <= '$getedate' AND STATUS = 'D' ORDER BY DELDATE ASC");
							}
							if($getstatus == "C"){
							$sql = $connect->query("SELECT * FROM orderlist WHERE DELDATE >= '$getsdate' AND DELDATE <= '$getedate' AND STATUS = 'C' ORDER BY DELDATE ASC");
							}
							while($row = $sql->fetch_assoc()){
								$custcode = $row["CUSTOMERCODE"];
								$cust = $connect->query("SELECT * FROM customer WHERE CODE = '$custcode'");
								$custrow = $cust->fetch_assoc();
							?>
							<tr>
								<td><?php echo $row["DELDATE"]; ?></td>
								<td><?php echo $row["ORDNO"]; ?></td>
								<td><?php echo $row["DRIVER"]; ?></td>
								<td><?php echo $row["CUSTOMER"]; ?></td>
								<td><?php echo $custrow["ADDRESS"]; ?></td>
							
								<td>
									<?php

								if($row["STATUS"] == ""){
									echo '<span class="badge bg-danger">Order</span>';
								}
								if($row["STATUS"] == "A"){
									echo '<span class="badge bg-secondary">Assigned</span>';
								}
								if($row["STATUS"] == "D"){
									echo '<span class="badge bg-secondary">Done</span>';
								}
								if($row["STATUS"] == "C"){
									echo '<span class="badge bg-secondary">Completed</span>';
								}

									?>
								</td>
								<td>
									<div class="btn-group" role="group" aria-label="Basic mixed styles example">
										<!--<button type="button" class="btn btn-success">Edit</button>-->
										<button type="button" class="btn btn-success btn-sm" onclick="window.location.href='vieworder.php?id=<?php echo $row["ID"]; ?>'">View</button>
										<button type="button" class="btn btn-warning btn-sm" onclick="window.location.href='editorder.php?orderno=<?php echo $row["ORDNO"]; ?>'" >Edit</button>
										<button type="button" class="btn btn-danger btn-sm" onclick="if (confirm('Delete selected item?')){del(<?php echo $row["ID"]; ?>);}else{};">Delete</button>
									</div>
								</td>
							</tr>
							<?php
							}
							?>
						</tbody>

					</table>
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
		
		<script>
		function myFunction() {
			var s = document.getElementById("sdate").value;
			var e = document.getElementById("edate").value;
			var st = document.getElementById("status").value;
			
		  location.href = '?s='+s+'&e='+e+'&st='+st;
		}
		</script>
	</body>
</html>
