<?php 
include "../../staff/dbconnection.php";
include "validation.php";

$drivercode = $_COOKIE["parkwaydelivery_driver"];
$orderid = $_GET["id"];

$check = $connect->query("SELECT * FROM del_orderlist WHERE ID = '$orderid'");
$checkrow = $check->fetch_assoc();
$img1 = $checkrow["IMG1"];
$img2 = $checkrow["IMG2"];
$img3 = $checkrow["IMG3"];


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
		include "navbar.php";
		?>
<?php
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
				<?php
				include "sidebar.php";
				?>

				<?php
				if(isset($_POST["submit"])){

					function compressImage($source, $destination, $quality) { 
						// Get image info 
						$imgInfo = getimagesize($source); 
						$mime = $imgInfo['mime']; 

						// Create a new image from file 
						switch($mime){ 
							case 'image/jpeg': 
								$image = imagecreatefromjpeg($source); 
								break; 
							case 'image/png': 
								$image = imagecreatefrompng($source); 
								break; 
							case 'image/gif': 
								$image = imagecreatefromgif($source); 
								break; 
							default: 
								$image = imagecreatefromjpeg($source); 
						} 

						// Resize if too large (max 1200px on longest side)
						$maxDim = 1200;
						$origW = imagesx($image);
						$origH = imagesy($image);
						if ($origW > $maxDim || $origH > $maxDim) {
							if ($origW >= $origH) {
								$newW = $maxDim;
								$newH = intval($origH * $maxDim / $origW);
							} else {
								$newH = $maxDim;
								$newW = intval($origW * $maxDim / $origH);
							}
							$resized = imagecreatetruecolor($newW, $newH);
							imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
							imagedestroy($image);
							$image = $resized;
						}

						// Save image
						imagejpeg($image, $destination, $quality);
						imagedestroy($image);

						// Return compressed image
						return $destination;
					}

					function convert_filesize($bytes, $decimals = 2) { 
						$size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB'); 
						$factor = floor((strlen($bytes) - 1) / 3); 
						return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor]; 
					}

					// File upload path 
					$uploadPath = "../uploads/"; 
					$statusMsg = ''; 
					$status = 'danger'; 

					// If file upload form is submitted 
					if(isset($_POST["submit"])){ 
						// Check whether user inputs are empty 
						/////////////////////////////////////////////////image1
						if(!empty($_FILES["image1"]["name"])) { 
							// File info 
							$fileName1 = "n1".uniqid().basename($_FILES["image1"]["name"]); 
							$imageUploadPath = $uploadPath . $fileName1; 
							$fileType = pathinfo($imageUploadPath, PATHINFO_EXTENSION); 

							// Allow certain file formats 
							$allowTypes = array('jpg','png','jpeg','gif'); 
							if(in_array($fileType, $allowTypes)){ 
								// Image temp source and size 
								$imageTemp = $_FILES["image1"]["tmp_name"]; 
								$imageSize = convert_filesize($_FILES["image1"]["size"]); 

								// Compress size and upload image 
								$compressedImage = compressImage($imageTemp, $imageUploadPath, 40); 

								if($compressedImage){ 
									$compressedImageSize = filesize($compressedImage); 
									$compressedImageSize = convert_filesize($compressedImageSize); 

									$status = 'success'; 
									$statusMsg = "Image compressed successfully."; 
								}else{ 
									$statusMsg = "Image compress failed!"; 
								} 
							}else{ 
								$statusMsg = 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.'; 
							} 
						}else{ 
							$statusMsg = 'Please select an image file to upload.'; 
						}
						/////////////////////////////////////////////////////image1

						/////////////////////////////////////////////////image2
						if(!empty($_FILES["image2"]["name"])) { 
							// File info 
							$fileName2 = "n2".uniqid().basename($_FILES["image2"]["name"]);  
							$imageUploadPath = $uploadPath . $fileName2; 
							$fileType = pathinfo($imageUploadPath, PATHINFO_EXTENSION); 

							// Allow certain file formats 
							$allowTypes = array('jpg','png','jpeg','gif'); 
							if(in_array($fileType, $allowTypes)){ 
								// Image temp source and size 
								$imageTemp = $_FILES["image2"]["tmp_name"]; 
								$imageSize = convert_filesize($_FILES["image2"]["size"]); 

								// Compress size and upload image 
								$compressedImage = compressImage($imageTemp, $imageUploadPath, 40); 

								if($compressedImage){ 
									$compressedImageSize = filesize($compressedImage); 
									$compressedImageSize = convert_filesize($compressedImageSize); 

									$status = 'success'; 
									$statusMsg = "Image compressed successfully."; 
								}else{ 
									$statusMsg = "Image compress failed!"; 
								} 
							}else{ 
								$statusMsg = 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.'; 
							} 
						}else{ 
							$statusMsg = 'Please select an image file to upload.'; 
						}
						/////////////////////////////////////////////////////image2

						/////////////////////////////////////////////////image3
						if(!empty($_FILES["image3"]["name"])) { 
							// File info 
							$fileName3 = "n3".uniqid().basename($_FILES["image3"]["name"]); 
							$imageUploadPath = $uploadPath . $fileName3; 
							$fileType = pathinfo($imageUploadPath, PATHINFO_EXTENSION); 

							// Allow certain file formats 
							$allowTypes = array('jpg','png','jpeg','gif'); 
							if(in_array($fileType, $allowTypes)){ 
								// Image temp source and size 
								$imageTemp = $_FILES["image3"]["tmp_name"]; 
								$imageSize = convert_filesize($_FILES["image3"]["size"]); 

								// Compress size and upload image 
								$compressedImage = compressImage($imageTemp, $imageUploadPath, 40); 

								if($compressedImage){ 
									$compressedImageSize = filesize($compressedImage); 
									$compressedImageSize = convert_filesize($compressedImageSize); 

									$status = 'success'; 
									$statusMsg = "Image compressed successfully."; 
								}else{ 
									$statusMsg = "Image compress failed!"; 
								} 
							}else{ 
								$statusMsg = 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.'; 
							} 
						}else{ 
							$statusMsg = 'Please select an image file to upload.'; 
						}
						/////////////////////////////////////////////////////image3
					}

					//sql

					if(!empty($_FILES["image1"]["name"])) { 
						$sql1 = $connect->query("UPDATE del_orderlist SET IMG1 = '$fileName1' WHERE ID = '$orderid'");
					}
					if(!empty($_FILES["image2"]["name"])) { 
						$sql2 = $connect->query("UPDATE del_orderlist SET IMG2 = '$fileName2' WHERE ID = '$orderid'");	
					}
					if(!empty($_FILES["image3"]["name"])) { 
						$sql3 = $connect->query("UPDATE del_orderlist SET IMG3 = '$fileName3' WHERE ID = '$orderid'");	
					}

					//check if there is atleast 1 pic
					$check = $connect->query("SELECT * FROM del_orderlist WHERE ID = '$orderid' AND IMG1 <> '' OR IMG2 <> '' OR IMG3 <> ''");
					if($check->num_rows > 0){
						echo '<script>window.location.href="workdone.php?id='.$orderid.'";</script>';
					}else{
						echo '<script>alert("No Image to upload");</script>';
					}
				}
				?>


				<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
					<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
						<h1 class="h2"><button onclick="window.location.href='index.php';" class="btn btn-dark">Back</button> Take Picture</h1>
						<div class="btn-toolbar mb-2 mb-md-0">

						</div>
					</div>
					<form method="post" enctype="multipart/form-data">
						<div class="row align-items-start">
							<div class="col">
								<div class="card" style="height:150px;object-fit:cover;overflow:hidden;">
									<div class="card-body">
										<?php
										if($img1 != ""){
										?>
										<input type="file" name="image1" onchange="preview1()">
										<img id="thumb1" src="../uploads/<?php echo $img1; ?>" style="height:120px;object-fit:cover;"/>
										<?php
										}else{
										?>
										<input type="file" name="image1" onchange="preview1()">
										<img id="thumb1" src="" style="height:120px;object-fit:cover;overflow:hidden;"/>
										<?php
										}
										?>
									</div>
								</div>
							</div>
							<div class="col">
								<div class="card" style="height:150px;object-fit:cover;overflow:hidden;">
									<div class="card-body">
										<?php
										if($img2 != ""){
										?>
										<input type="file" name="image2" onchange="preview2()">
										<img id="thumb2" src="../uploads/<?php echo $img2; ?>" style="height:120px;object-fit:cover;"/>
										<?php
										}else{
										?>
										<input type="file" name="image2" onchange="preview2()">
										<img id="thumb2" src="" style="height:120px;object-fit:cover;"/>
										<?php
										}
										?>
									</div>
								</div>
							</div>
							<div class="col">
								<div class="card" style="height:150px;object-fit:cover;overflow:hidden;">
									<div class="card-body">
										<?php
										if($img3 != ""){
										?>
										<input type="file" name="image3" onchange="preview3()">
										<img id="thumb3" src="../uploads/<?php echo $img3; ?>" style="height:120px;object-fit:cover;"/>
										<?php
										}else{
										?>
										<input type="file" name="image3" onchange="preview3()">
										<img id="thumb3" src="" style="height:120px;object-fit:cover;"/>
										<?php
										}
										?>
									</div>
								</div>
							</div>
						</div>
						<br>
						
						<div class="row align-items-start">
							<div class="col">
								<input type="submit" name="submit" value="Upload" class="btn btn-dark">
								<button class="btn btn-success" type="submit" name="done" id="done">Job Done</button>
							</div>
						</div>
						
					</form>
					<!--<canvas class="my-4 w-100" id="myChart" width="900" height="380"></canvas>-->

				</main>
			</div>
		</div>


		<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script>
		<script src="../assets/dashboard.js"></script>
		<script>
			function preview1() {
				thumb1.src=URL.createObjectURL(event.target.files[0]);
			}
			function preview2() {
				thumb2.src=URL.createObjectURL(event.target.files[0]);
			}
			function preview3() {
				thumb3.src=URL.createObjectURL(event.target.files[0]);
			}
		</script>
	</body>
</html>

