<?php 
include "dbconnection.php";
include "validation.php";
$ordno = $_GET["ordno"];
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
		<meta name="generator" content="Hugo 0.84.0">
		<title>View Picture</title>

		<link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<!-- Bootstrap core CSS -->
		<!-- Custom styles for this template -->
		<link href="assets/dashboard.css" rel="stylesheet">
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
						<h1 class="h2">View Picture from Order No: <?php echo $ordno; ?></h1>
						<div class="btn-toolbar mb-2 mb-md-0">
							<button class="btn btn-secondary btn-sm" onclick="window.location.href='index.php';">Back</button>
						</div>
					</div>
					<?php
					$pic = $connect->query("SELECT * FROM orderlist WHERE ORDNO = '$ordno'");
					while($picrow = $pic->fetch_assoc()){
						
						
					?>
					<?php
						if($picrow["IMG1"] != ""){
					?>
					<div class="card" style="width: 18rem;">
						<img src="uploads/<?php echo $picrow["IMG1"]; ?>" class="img-fluid rounded float-start" alt="...">
					</div>
					<?php
						}	
					?>
					<?php
						if($picrow["IMG2"] != ""){
					?>
					<div class="card" style="width: 18rem;">
						<img src="uploads/<?php echo $picrow["IMG2"]; ?>" class="img-fluid rounded float-start" alt="...">
					</div>
					<?php
						}	
					?>
					<?php
						if($picrow["IMG3"] != ""){
					?>
					<div class="card" style="width: 18rem;">
						<img src="uploads/<?php echo $picrow["IMG3"]; ?>" class="img-fluid rounded float-start" alt="...">
					</div>
					<?php
						}	
					?>
					<?php
					}
					?>
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

			function delpic(){
				$.ajax({
					type: "POST",
					url: "delpic.php",
					data: {

					},
					success: function(data) {
						alert("Deleted old picture successfully");
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
