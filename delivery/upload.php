
<?php
include "dbconnection.php";

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

		// Save image 
		imagejpeg($image, $destination, $quality); 

		// Return compressed image 
		return $destination; 
	}

	function convert_filesize($bytes, $decimals = 2) { 
		$size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB'); 
		$factor = floor((strlen($bytes) - 1) / 3); 
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor]; 
	}

	// File upload path 
	$uploadPath = "uploads/"; 
	$statusMsg = ''; 
	$status = 'danger'; 

	// If file upload form is submitted 
	if(isset($_POST["submit"])){ 
		// Check whether user inputs are empty 
		if(!empty($_FILES["image"]["name"])) { 
			// File info 
			$fileName = basename($_FILES["image"]["name"]); 
			$imageUploadPath = $uploadPath . $fileName; 
			$fileType = pathinfo($imageUploadPath, PATHINFO_EXTENSION); 
		
			// Allow certain file formats 
			$allowTypes = array('jpg','png','jpeg','gif'); 
			if(in_array($fileType, $allowTypes)){ 
				// Image temp source and size 
				$imageTemp = $_FILES["image"]["tmp_name"]; 
				$imageSize = convert_filesize($_FILES["image"]["size"]); 

				// Compress size and upload image 
				$compressedImage = compressImage($imageTemp, $imageUploadPath, 75); 
				
				if($compressedImage){ 
					$compressedImageSize = filesize($compressedImage); 
					$compressedImageSize = convert_filesize($compressedImageSize); 
					
					
					$connect->query("INSERT INTO blobtest (ID, BLOBIMG) VALUES ('1', '$compressedImage')");

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
	}
	
	echo $statusMsg;

 if(!empty($compressedImage)){
?>
	<p><b>Original Image Size:</b> <?php echo $imageSize; ?></p>
	<p><b>Compressed Image Size:</b> <?php echo $compressedImageSize; ?></p>
	<img src="<?php echo $compressedImage; ?>"/>
<?php
	}
}
?>

<form method="post" enctype="multipart/form-data">
	<label>Select Image File:</label>
	<input type="file" name="image">
	<input type="submit" name="submit" value="Upload">
</form>