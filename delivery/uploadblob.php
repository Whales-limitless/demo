<?php
$db = new mysqli("localhost", "smposmy_userdb", "6VHPCz218a8trlA3Ok", "parkwaydelivery_main");
if(isset($_POST["submit"])){
$image = file_get_contents($_FILES['images']['tmp_name']);
$query = "INSERT INTO blobtest (BLOBIMG) VALUES(?)";
$stmt = $db->prepare($query);
$stmt->bind_param('s', $image);
$stmt->execute();
}

$id = "1";
$sql = "SELECT * FROM blobtest WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_array();
echo '<img src="data:image/jpeg;base64,'.base64_encode($row['BLOBIMG']).'"/>';
?>

<form method="post" enctype="multipart/form-data">
	<label>Select Image File:</label>
	<input type="file" name="images">
	<input type="submit" name="submit" value="Upload">
</form>