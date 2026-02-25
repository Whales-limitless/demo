<?php 
include "dbconnection.php";
include "validation.php";
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
		<meta name="generator" content="Hugo 0.84.0">
		<title>Dashboard</title>

		<link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<script>
			window.Promise ||
				document.write(
				'<script src="https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js"><\/script>'
			)
			window.Promise ||
				document.write(
				'<script src="https://cdn.jsdelivr.net/npm/eligrey-classlist-js-polyfill@1.2.20171210/classList.min.js"><\/script>'
			)
			window.Promise ||
				document.write(
				'<script src="https://cdn.jsdelivr.net/npm/findindex_polyfill_mdn"><\/script>'
			)
		</script>
		<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
		<script>
			// Replace Math.random() with a pseudo-random number generator to get reproducible results in e2e tests
			// Based on https://gist.github.com/blixt/f17b47c62508be59987b
			var _seed = 42;
			Math.random = function() {
				_seed = _seed * 16807 % 2147483647;
				return (_seed - 1) / 2147483646;
			};
		</script>
		<!-- Bootstrap core CSS -->
		<!-- Custom styles for this template -->
		<link href="assets/dashboard.css" rel="stylesheet">
		<style>
			#chart {
				max-width: 650px;
				margin: 35px auto;
			}

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
		</style>



	</head>
	<body>
		<?php
		include "navbar.php";
		?>

		<div class="container-fluid">
			<div class="row">
				<?php
				include "sidebar.php";
				?>

				<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
					<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
						<h1 class="h2">Dashboard <span class="badge bg-primary">Done</span></h1>
						<div class="btn-toolbar mb-2 mb-md-0">
							<!--<button class="btn btn-secondary btn-sm" onclick="delpic()">Delete Old Picture</button>-->
						</div>
					</div>

					<!--<canvas class="my-4 w-100" id="myChart" width="900" height="380"></canvas>-->
					<div id="mydiv">
						<h6>Active Orders <span class="badge bg-danger">Live</span></h6>
						<div class="table-responsive">
							<table class="table table-striped table-sm">
								<thead>
									<tr>
										<th scope="col">Delivery Date</th>
										<th scope="col">Order No.</th>
										<th scope="col">Driver</th>
										<th scope="col">Customer</th>
										<th scope="col">Location</th>
										<th scope="col">Customer Address</th>
										<th scope="col">Distant</th>
										<th scope="col">Commission</th>
										<th scope="col">Status</th>
										<th scope="col"></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$sql = $connect->query("SELECT orderlist.*, customer.ADDRESS FROM orderlist LEFT JOIN customer ON orderlist.CUSTOMERCODE = customer.CODE WHERE orderlist.STATUS = 'D' ORDER BY orderlist.DELDATE ASC");
									while($row = $sql->fetch_assoc()){
										if($row["STATUS"] == ""){
											$badge = '<span class="badge bg-danger">Order</span>';
										}
										if($row["STATUS"] == "A"){
											$badge = '<span class="badge bg-secondary">Assigned</span>';
										}
										if($row["STATUS"] == "D"){
											$badge = '<span class="badge bg-success">Done</span>';
										}
									?>
									<tr>
										<td><?php echo $row["DELDATE"]; ?></td>
										<td><?php echo $row["ORDNO"]; ?></td>
										<td><?php echo $row["DRIVER"]; ?></td>
										<td><?php echo $row["CUSTOMER"]; ?></td>
										<td><?php echo $row["LOCATION"]; ?></td>
										<td><?php echo $row["ADDRESS"]; ?></td>
										<td><?php echo $row["DISTANT"]; ?></td>
										<td><?php echo $row["RETAIL"]; ?></td>
										<td><?php echo $badge; ?></td>
										<td>
											<div class="btn-group" role="group" aria-label="Basic mixed styles example">
												<button class="btn btn-primary btn-sm" onclick="window.location.href='order/vieworder.php?id=<?php echo $row["ID"]; ?>';">View Detail</button>
												<button class="btn btn-dark btn-sm" onclick="window.location.href='view.php?ordno=<?php echo $row["ORDNO"]; ?>';" <?php if($row["IMG1"] != "" || $row["IMG2"] != "" || $row["IMG1"] != ""){echo "";}else{echo "disabled";} ?>>View Image</button>
												<button class="btn btn-success btn-sm" <?php if($row["STATUS"] == "" || $row["STATUS"] == "A"){echo "disabled";} ?> onclick="complete('<?php echo $row["ID"]; ?>')">Complete</button>
											</div>
										</td>
									</tr>
									<!-- Modal -->
									<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
													<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
												</div>
												<div class="modal-body">
													<img src="uploads/<?php echo $row["IMG1"]; ?>" class="img-fluid rounded float-start" alt="...">
													<img src="uploads/<?php echo $row["IMG2"]; ?>" class="img-fluid rounded float-start" alt="...">
													<img src="uploads/<?php echo $row["IMG3"]; ?>" class="img-fluid rounded float-start" alt="...">
												</div>
												<div class="modal-footer">
													<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
													<button type="button" class="btn btn-primary">Save changes</button>
												</div>
											</div>
										</div>
									</div>
									<script>
										function complete(id){
											$.ajax({
												type: "POST",
												url: "complete.php",
												data: {
													id:id,
												},
												success: function(data) {
													alert("Completed successfully");
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
					<!--
					<div class="row">
						<div class="col"><div id="chart"></div></div>
						<div class="col"><div id="chart"></div></div>
						<div class="col"><div id="chart"></div></div>
					</div>-->
				
					
				</main>

			</div>
		</div>


		<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script>
		<script src="assets/dashboard.js"></script>

		<script>
			function refresh(){
				$("#mydiv").load(location.href + " #mydiv");
			}
			const myInterval = setInterval(function(){
				refresh() // this will run after every 5 seconds
			}, 5000);

			var options = {
				series: [{
					data: [400, 430, 448, 470, 540, 580, 690, 1100, 1200, 1380]
				}],
				chart: {
					type: 'bar',
					height: 350
				},
				plotOptions: {
					bar: {
						borderRadius: 4,
						horizontal: true,
					}
				},
				dataLabels: {
					enabled: false
				},
				xaxis: {
					categories: ['South Korea', 'Canada', 'United Kingdom', 'Netherlands', 'Italy', 'France', 'Japan',
								 'United States', 'China', 'Germany'
								],
				}
			};

			var chart = new ApexCharts(document.querySelector("#chart"), options);
			chart.render();


		</script>

	</body>
</html>