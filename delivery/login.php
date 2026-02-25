<?php
include "dbconnection.php";
if(isset($_POST["submit"])){
	//check database if user exists
	$username = $_POST["username"];
	$password = $_POST["password"];
	$check = $connect->query("SELECT * FROM users WHERE USERNAME = '$username' AND PASSWORD = '$password'");
	if($check->num_rows > 0){
		//user exists
		$checkrow = $check->fetch_assoc();
		$sys_username = $checkrow["USERNAME"];

		setcookie("parkwaydelivery_user", $sys_username, time() + (86400 * 30), "/"); // 86400 = 1 day

		header("location: index.php");
	}else{
		echo "<script type='text/javascript'>alert('User not found.');</script>";

	}
}
?>
<!DOCTYPE html>
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
		<main>
			<div class="container">
				<div class="row justify-content-center">
					<div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
					<br>
						<div class="card mb-3">
							<div class="card-body">
								<div class="pt-4 pb-2">
									<h5 class="card-title text-center pb-0 fs-4">Parkway Delivery</h5>
									<p class="text-center small">Enter your username & password to login</p>
								</div>
								<form method="post" class="row g-3">
									<div class="col-12">
										<label for="yourUsername" class="form-label">Username</label>
										<div class="input-group">
										
											<input type="text" name="username" class="form-control rounded" id="yourUsername" autocomplete="off" required>
											<div class="invalid-feedback">Please enter your username.</div>
										</div>
									</div>
									<div class="col-12">
										<label for="yourPassword" class="form-label">Password</label>
										<input type="password" name="password" class="form-control rounded" id="yourPassword" autocomplete="off" required>
										<div class="invalid-feedback">Please enter your password!</div>
									</div>
									<div class="col-12">
										<button class="btn btn-primary w-100" type="submit" name="submit">Login</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</main>
		<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script>
		<script src="assets/dashboard.js"></script>
	</body>
</html>