<?php 
include "../../staff/dbconnection.php";
include "validation.php";
$getid = $_GET["id"];
$drivercode = $_COOKIE["parkwaydelivery_driver"];

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

		<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<!-- Bootstrap core CSS -->
		<!-- Custom styles for this template -->
		<link href="../assets/dashboard.css" rel="stylesheet">
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
		//include "navbar.php";
		if(isset($_POST["done"])){
			$donedatetime = date("Y-m-d H:i:s");
			$sql = $connect->query("UPDATE del_orderlist SET STATUS = 'D', DONEDATETIME = '$donedatetime' WHERE ID = '$getid'");
			if($sql){
		?>
		<script>
			Swal.fire({
				icon: 'success',
				title: 'Job Done!',
				confirmButtonText: 'Ok',
			}).then((result) => {
				/* Read more about isConfirmed, isDenied below */
				if (result.isConfirmed) {
					window.location.href='index.php';
				}
			})
		</script>
		<?php
			}
		}
		?>

		<div class="container-fluid">
			<div class="row">
				<form method="POST">
					<center>
						<br><br>
						<main class="px-3">
							<img src="../assets/icon/check-circle.svg" style="width:20%;">
							<h1>Upload Successful</h1>
							<p class="lead"></p>
							<p class="lead">
								<button class="btn btn-success btn-lg" type="submit" name="done" id="done">Job Done</button>
							</p>
						</main>
					</center>
				</form>
			</div>
		</div>

		<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script>
		<script src="../assets/dashboard.js"></script>

	</body>
</html>
