<?php 
include "../../staff/dbconnection.php";
include "validation.php";

$drivercode = $_COOKIE["parkwaydelivery_driver"];
$getfilter = $_GET["filter"] ?? "all";

$todaydate = date("Y-m-d");
$showdate = $todaydate;
if($getfilter == "today"){
	$showdate = $todaydate;
}
if($getfilter == "yesterday"){
	$showdate = date('Y-m-d', strtotime('-1 day', strtotime($todaydate)));
}


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
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
						<h1 class="h2">Dashboard</h1>
						<div class="btn-toolbar mb-2 mb-md-0">
							<select class="form-control" onchange="filter(this.value)">
								<option value="today" <?php if($getfilter == "today"){echo "selected";} ?>>Today</option>
								<option value="yesterday" <?php if($getfilter == "yesterday"){echo "selected";} ?>>Yesterday</option>
								<option value="all" <?php if($getfilter == "all"){echo "selected";} ?>>All</option>
							</select>
						</div>
					</div>

					<!--<canvas class="my-4 w-100" id="myChart" width="900" height="380"></canvas>-->
					<div id="mydiv">
						<!--<h6>Active Orders <span class="badge bg-danger">Live</span></h6>-->




						<?php
						if($getfilter == "all"){
						$sql = $connect->query("SELECT * FROM del_orderlist WHERE DRIVERCODE = '$drivercode' AND STATUS = 'A' ORDER BY DELDATE ASC");
						}else{
						$sql = $connect->query("SELECT * FROM del_orderlist WHERE DRIVERCODE = '$drivercode' AND STATUS = 'A' AND DELDATE = '$showdate' ORDER BY DELDATE ASC");	
						}
						while($row = $sql->fetch_assoc()){
							$ordno = $row["ORDNO"];
							$remark = $row["REMARK"];
							$custcode = $row["CUSTOMERCODE"];
							$cust = $connect->query("SELECT * FROM del_customer WHERE CODE = '$custcode'");
							$custrow = $cust->fetch_assoc();
							$custaddress = $custrow["ADDRESS"];
							$custhp = $custrow["HP"];
							if($row["STATUS"] == ""){
								$badge = '<span class="badge bg-secondary">Assigned</span>';
							}
							// Check if any items require installation
							$installCheck = $connect->query("SELECT COUNT(*) AS cnt FROM del_orderlistdesc WHERE ORDERNO = '$ordno' AND INSTALL = 'Y'");
							$hasInstall = ($installCheck && ($installRow = $installCheck->fetch_assoc()) && $installRow['cnt'] > 0);
						?>
						<div class="card text-center border border-dark">
							<div class="card-header">
								<span class="float-start"><h6>Deliver on: <?php echo $row["DELDATE"]; ?></h6></span>
								<span class="float-end">
									<?php if($custhp != ""){
									?>
									<h6><a href="https://wa.me/+<?php echo $custhp; ?>"><img src="../assets/icon/whatsapp.png" style="width:20px;"></a> <a style="color:black;" href="tel:<?php echo $custhp; ?>"><?php echo $custhp; ?></a></h6>
									<?php
									}					
									?>
								</span>
							</div>
							<div class="card-body">
								<h5 class="card-title"><?php echo $row["CUSTOMER"]; ?></h5>
								<p class="card-text">
									<?php
							if($remark == ""){
								echo $custaddress;
							}else{
								echo $remark;	
							}
									?>
								</p>
								<div class="btn-group" role="group" >

									<button type="submit" class="btn btn-primary btn-lg" onclick="window.location.href='work.php?id=<?php echo $row["ID"]; ?>';">Go</button>
									<button class="btn btn-warning btn-lg position-relative" data-bs-toggle="modal" data-bs-target="#exampleModal_<?php echo $ordno; ?>">Item<?php if($hasInstall){ ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;"><i class="fas fa-tools"></i> Install</span><?php } ?></button>
									<button class="btn btn-success btn-lg rounded-end" onclick="window.location.href='vieworder.php?ordno=<?php echo $ordno; ?>';">DO</button>
									<!-- Modal -->
									<div class="modal fade" id="exampleModal_<?php echo $ordno; ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title" id="exampleModalLabel">Item</h5>
													<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
												</div>
												<div class="modal-body">
													<?php if($hasInstall){ ?>
													<div class="alert alert-warning py-2 mb-3" style="font-size:13px;">
														<i class="fas fa-tools"></i> <strong>This order has items that require installation.</strong>
													</div>
													<?php } ?>
													<ul class="list-group">
														<?php
														$item = $connect->query("SELECT * FROM del_orderlistdesc WHERE ORDERNO = '$ordno' ORDER BY PDESC ASC");
														while($itemrow = $item->fetch_assoc()){
														?>
														<li class="list-group-item d-flex justify-content-between align-items-center">
															<div>
																<h6 class="mb-0"><?php echo $itemrow["PDESC"]; ?></h6>
																<?php if(isset($itemrow["INSTALL"]) && $itemrow["INSTALL"] === "Y"){ ?>
																<span class="badge bg-warning text-dark" style="font-size:10px;"><i class="fas fa-tools"></i> Installation Required</span>
																<?php } ?>
															</div>
															<h3><span class="badge bg-primary badge-lg rounded-pill"><?php echo $itemrow["QTY"] . " " . $itemrow["UOM"]; ?></span></h3>
														</li>
														<?php
														}
														?>
													</ul>
												</div>
												<div class="modal-footer">
													<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="card-footer text-muted">
								<h5>Order No: <?php echo $ordno; ?></h5>
							</div>
						</div>
						<br>
						<?php
						}
						?>
					</div>
				</main>
			</div>
		</div>
		<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script>
		<script src="../assets/dashboard.js"></script>
		<script>
			function filter(val){
				window.location.href='?filter='+val;
			}
		</script>
	</body>
</html>