<?php
include "../dbconnection.php";
include "../validation.php";
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
						<h1 class="h2">Drivers</h1>
						<div class="btn-toolbar mb-2 mb-md-0">
							<!--
   <div class="btn-group me-2">
 <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
 <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
   </div>-->
							<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#adddriver">Add Driver</button>
						</div>
					</div>
					<div class="modal fade" id="adddriver" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title" id="exampleModalLabel">Add Driver</h5>
									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
								</div>
								<div class="modal-body">

									<div class="mb-3">
										<label for="a1" class="form-label">Name</label>
										<input type="text" class="form-control" id="a1">
									</div>
									<div class="mb-3">
										<label for="a2" class="form-label">Email</label>
										<input type="email" class="form-control" id="a2">
									</div>
									<div class="row">
										<div class="col-6">
											<div class="mb-3">
												<label for="a3" class="form-label">Address</label>
												<input type="text" class="form-control" id="a3">
											</div>
										</div>
										<div class="col-6">
											<div class="mb-3">
												<label for="a4" class="form-label">Postcode</label>
												<input type="text" class="form-control" id="a4">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col">
											<div class="mb-3">
												<label for="a6" class="form-label">State</label>
												<input type="text" class="form-control" id="a5">
											</div>
										</div>
										<div class="col">
											<div class="mb-3">
												<label for="a6" class="form-label">Area</label>
												<input type="text" class="form-control" id="a6">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-6">
											<div class="mb-3">
												<label for="a7" class="form-label">Phone</label>
												<input type="number" class="form-control" id="a7">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col">
											<div class="mb-3">
												<label for="a8" class="form-label">Username</label>
												<input type="text" class="form-control" id="a8">
											</div>
										</div>
										<div class="col">
											<div class="mb-3">
												<label for="a9" class="form-label">Password</label>
												<input type="password" class="form-control" id="a9">
											</div>
										</div>
									</div>
									<button type="button" class="btn btn-primary" onclick="add();">Submit</button>

								</div>

							</div>
						</div>
					</div>
					<table id="example" class="table table-striped" style="width:100%">
						<thead>
							<tr>
								<th>Name</th>
								<th>Address</th>
								<th>Postcode</th>
								<th>State</th>
								<th>Area</th>
								<th>Email</th>
								<th>Phone</th>
								<th>Username</th>
								<th>Password</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$sql = $connect->query("SELECT * FROM driver ORDER BY NAME ASC");
							while($row = $sql->fetch_assoc()){
							?>
							<tr>
								<td><?php echo $row["NAME"]; ?></td>
								<td><?php echo $row["ADDRESS"]; ?></td>
								<td><?php echo $row["POSTCODE"]; ?></td>
								<td><?php echo $row["STATE"]; ?></td>
								<td><?php echo $row["AREA"]; ?></td>
								<td><?php echo $row["EMAIL"]; ?></td>
								<td><?php echo $row["HP"]; ?></td>
								<td><?php echo $row["USERNAME"]; ?></td>
								<td><?php echo $row["PASSWORD"]; ?></td>
								<td>
									<div class="btn-group" role="group" aria-label="Basic mixed styles example">
										<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editdriver<?php echo $row["ID"]; ?>">Edit</button>
										<button type="button" class="btn btn-danger" onclick="if (confirm('Delete selected item?')){del(<?php echo $row["ID"]; ?>);}else{};">Delete</button>
									</div>
								</td>
							</tr>
							<div class="modal fade" id="editdriver<?php echo $row["ID"]; ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title" id="exampleModalLabel">Edit Driver(<?php echo $row["ID"]; ?>)</h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
										</div>
										<div class="modal-body">
											
											<div class="mb-3">
												<label for="a1" class="form-label">Name</label>
												<input type="text" class="form-control" id="e1-<?php echo $row["ID"]; ?>" value="<?php echo $row["NAME"]; ?>">
											</div>
											<div class="mb-3">
												<label for="a2" class="form-label">Email</label>
												<input type="email" class="form-control" id="e2-<?php echo $row["ID"]; ?>" value="<?php echo $row["EMAIL"]; ?>">
											</div>
											<div class="row">
												<div class="col-6">
													<div class="mb-3">
														<label for="a3" class="form-label">Address</label>
														<input type="text" class="form-control" id="e3-<?php echo $row["ID"]; ?>" value="<?php echo $row["ADDRESS"]; ?>">
													</div>
												</div>
												<div class="col-6">
													<div class="mb-3">
														<label for="a4" class="form-label">Postcode</label>
														<input type="text" class="form-control" id="e4-<?php echo $row["ID"]; ?>" value="<?php echo $row["POSTCODE"]; ?>">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col">
													<div class="mb-3">
														<label for="a6" class="form-label">State</label>
														<input type="text" class="form-control" id="e5-<?php echo $row["ID"]; ?>" value="<?php echo $row["STATE"]; ?>">
													</div>
												</div>
												<div class="col">
													<div class="mb-3">
														<label for="a6" class="form-label">Area</label>
														<input type="text" class="form-control" id="e6-<?php echo $row["ID"]; ?>" value="<?php echo $row["AREA"]; ?>">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-6">
													<div class="mb-3">
														<label for="a7" class="form-label">Phone</label>
														<input type="number" class="form-control" id="e7-<?php echo $row["ID"]; ?>" value="<?php echo $row["HP"]; ?>">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col">
													<div class="mb-3">
														<label for="e8" class="form-label">Username</label>
														<input type="text" class="form-control" id="e8-<?php echo $row["ID"]; ?>" value="<?php echo $row["USERNAME"]; ?>">
													</div>
												</div>
												<div class="col">
													<div class="mb-3">
														<label for="e9" class="form-label">Password</label>
														<input type="text" class="form-control" id="e9-<?php echo $row["ID"]; ?>" value="<?php echo $row["PASSWORD"]; ?>">
													</div>
												</div>
											</div>
											<button type="button" class="btn btn-primary" onclick="edit<?php echo $row["ID"]; ?>('<?php echo $row["ID"]; ?>');">Submit</button>

										</div>

									</div>
								</div>
							</div>
							<script>
								function edit<?php echo $row["ID"]; ?>(id){
									var name = document.getElementById("e1-<?php echo $row["ID"]; ?>").value;
									var email = document.getElementById("e2-<?php echo $row["ID"]; ?>").value;
									var address = document.getElementById("e3-<?php echo $row["ID"]; ?>").value;
									var postcode = document.getElementById("e4-<?php echo $row["ID"]; ?>").value;
									var state = document.getElementById("e5-<?php echo $row["ID"]; ?>").value;
									var area = document.getElementById("e6-<?php echo $row["ID"]; ?>").value;
									var hp = document.getElementById("e7-<?php echo $row["ID"]; ?>").value;
									var username = document.getElementById("e8-<?php echo $row["ID"]; ?>").value;
									var password = document.getElementById("e9-<?php echo $row["ID"]; ?>").value;
									$.ajax({
										type: "POST",
										url: "postedit.php",
										data: {
											id: id,
											name: name,
											email: email,
											address: address,
											postcode: postcode,
											state: state,
											area: area,
											hp: hp,
											username: username,
											password: password,
										},
										success: function(data) {
											//alert(data);
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
				var username = document.getElementById("a8").value;
				var password = document.getElementById("a9").value;
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
						username: username,
						password: password,
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
