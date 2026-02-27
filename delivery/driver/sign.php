<?php
include "../../staff/dbconnection.php";
include "validation.php";
$ordno = $_GET["ordno"];

$sql = $connect->query("SELECT * FROM `del_orderlist` WHERE ID= '".$_GET["id"]."'");
while($row = $sql->fetch_assoc()){
	$deldate = $row["DELDATE"];
	$ordno = $row["ORDNO"];
	$driver = $row["DRIVER"];
	$customer = $row["CUSTOMER"];
	$location = $row["LOCATION"];
	$distant = $row["DISTANT"];
	$retail = $row["RETAIL"];
	$customerc = $row["CUSTOMERCODE"];
}

$sql2 = $connect->query("SELECT HP,ADDRESS FROM `del_customer` WHERE CODE= '".$customerc."'");
while($row = $sql2->fetch_assoc()){
	$hp = $row["HP"];
	$addr = $row["ADDRESS"];
}
							
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Deliver Order</title>

<link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- Bootstrap core CSS -->
<!-- Custom styles for this template -->
<link href="../assets/dashboard.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">

<!--sign doc-->
<link href="sign/css/jquery.signaturepad.css" rel="stylesheet">
<script src="sign/js/numeric-1.2.6.min.js"></script> 
<script src="sign/js/bezier.js"></script>
<script src="sign/js/jquery.signaturepad.js"></script>
<script type='text/javascript' src="https://github.com/niklasvh/html2canvas/releases/download/0.4.1/html2canvas.js"></script>
<script src="sign/js/json2.min.js"></script>

<!--<script>
window.onload = function() { window.print(); }
</script>-->

<style>
/* Change to uppercase */
.uppercase {
    text-transform: uppercase;
}

#signArea{
	width:304px;
	margin: 25px 0px;
}
.sign-container {
	width: 60%;
	margin: auto;
}
.sign-preview {
	width: 150px;
	height: 50px;
	border: solid 1px #CFCFCF;
	margin: 10px 5px;
}
.tag-ingo {
	font-family: cursive;
	font-size: 12px;
	text-align: left;
	font-style: oblique;
}
</style>
</head>
<body>
<input type="hidden" id="ordno" value="<?php echo $_GET['ordno']; ?>">
<div class="container">
	<br>
	<div id="signArea">
		<div class="sig sigWrapper" style="height:auto;">
			<div class="typed"></div>
			<canvas class="sign-pad" id="sign-pad" width="300" height="100"></canvas>
		</div>
	</div>
	<div class="row">
		<div class="col-12 col-md-2 mb-2">
			<input type="button" id="btnClearSign" class="btn btn-primary w-100" value="Clear" >
		</div>
		<div class="col-12 col-md-2 mb-2">
			<button type="button" id="save" class="btn btn-md btn-success w-100">Save</button>
		</div>
		<div class="col-12 col-md-2">
			<a href="vieworder.php?ordno=<?php echo $ordno; ?>" class="btn btn-md btn-danger w-100">
				Cancel
			</a>
		</div>
	</div>
</div>
</body>
	
<script>
$(document).ready(function() {
	$('#signArea').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:90});
});

$("#save").click(function(e){
	var ordno = document.getElementById("ordno").value;
	html2canvas([document.getElementById('sign-pad')], {
		onrendered: function (canvas) {
			var canvas_img_data = canvas.toDataURL('image/png');
			var img_data = canvas_img_data.replace(/^data:image\/(png|jpg);base64,/, "");
			//ajax call to save image inside folder
			$.ajax({
				url: 'signsave.php',
				data: { img_data:img_data,ordno:ordno },
				type: 'post',
				dataType: 'json',
				success: function (response) {
				   if(response == '1'){
					   alert("Success");
					   window.location.href="vieworder.php?ordno="+ordno;
				   }else{
					   alert("Failed");
				   }
				}
			});
		}
	});
});

$("#btnClearSign").click(function(e){
$('#signArea').signaturePad().clearCanvas();
});
</script> 
	
</html>