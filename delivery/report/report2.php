<?php
include "../dbconnection.php";
include "../validation.php";

$sdate	= isset($_GET['s']) ? $_GET['s'] : date("Y-m-01");
$edate	= isset($_GET['e']) ? $_GET['e'] : date("Y-m-d");
$did	= isset($_GET['d']) ? $_GET['d'] : '';
$lid	= isset($_GET['l']) ? $_GET['l'] : '';
$type	= isset($_GET['t']) ? $_GET['t'] : '1';

$sql11 = $connect->query("SELECT CODE,NAME FROM `driver` WHERE ID = '".$did."' ");
if($sql11->num_rows > 0){
	while($row = $sql11->fetch_assoc()){
		$dcode = $row['CODE'];
		$dname = $row['NAME'];
	}
}else{
	$dcode = '';
	$dname = '';
}

$sql21 = $connect->query("SELECT NAME FROM `location` WHERE ID = '".$lid."' ");
if($sql21->num_rows > 0){
	while($row = $sql21->fetch_assoc()){
		$lname = $row['NAME'];
	}
}else{
	$lname = '';
}

if($type == '1'){
	$tname = 'All';
}elseif($type == '2'){
	$tname = 'Distant';
}elseif($type == '3'){
	$tname = 'Commission';
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
		<title>Driver Summary Report</title>

		<link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<!-- Bootstrap core CSS -->
		<!-- Custom styles for this template -->
		<link href="../assets/dashboard.css" rel="stylesheet">

		<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">
		<link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
		<link href="https://cdn.datatables.net/buttons/2.3.2/css/buttons.bootstrap5.min.css">

		<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.bootstrap5.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.html5.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.print.min.js"></script>
		<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.colVis.min.js"></script>

		<!-- Select 2 -->
		<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
		<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
						<h1 class="h2">Driver Summary Report</h1>
						<div class="btn-toolbar mb-2 mb-md-0">

						</div>
					</div>

                    <div class="row">
                        <input type="hidden" id="ptitle" value="Driver Summary Report">
						<div class="col-md-2">
							<label>Start Date</label>
							<input type="date" class="form-control" id="sdate" value="<?php echo $sdate; ?>" style="padding:1px;">
						</div>
                        <div class="col-md-2">
							<label>End Date</label>
							<input type="date" class="form-control" id="edate" value="<?php echo $edate; ?>" style="padding:1px;">
						</div>
						<div class="col-md-2">
							Driver
							<select class="w-100" id="driver">
                                <option disabled selected value="<?php echo $did; ?>" ><?php echo $dname; ?></option>
								<?php
								$sql79 = $connect->query("SELECT ID,NAME FROM `driver` ORDER BY NAME ASC");
								while($row = $sql79->fetch_assoc()){
									?>
									<option value="<?php echo $row["ID"]; ?>"><?php echo $row["NAME"]; ?></option>
									<?php
								}
								?>
							</select>
						</div>
						<div class="col-md-2">
							Location
							<select class="w-100" id="location">
                                <option disabled selected value="<?php echo $lid; ?>" ><?php echo $lname; ?></option>
								<?php
								$sql89 = $connect->query("SELECT ID,NAME FROM `location` ORDER BY NAME ASC");
								while($row = $sql89->fetch_assoc()){
									?>
									<option value="<?php echo $row["ID"]; ?>"><?php echo $row["NAME"]; ?></option>
									<?php
								}
								?>
							</select>
						</div>
						<div class="col-md-2">
							Type
							<select id="type" class="form-select p-0" >
								<option hidden value="<?php echo $type; ?>" ><?php echo $tname; ?></option>
								<option value="1">All</option>
								<option value="2">Distant</option>
								<option value="3">Commission</option>
							</select>
						</div>
						<div class="col-md-1">
							<br>
							<button class="btn btn-dark btn-sm" onclick="submit()">Submit</button>
						</div>
					</div>
					<br>
					
					<table id="example" class="table table-striped table-hover" style="width:100%">
						<thead>
							<tr>
                                <th style="width:1%">S/N</th>
								<th>Driver</th>
								<th style="text-align:right">Total Order</th>
                                <?php
                                if($type == 1){
                                ?>
                                <th style="text-align:right">Total Distant</th>
                                <th style="text-align:right">Total Commission</th>
                                <?php
                                }
                                ?>
                                <?php
                                if($type == 2){
                                ?>
                                <th style="text-align:right">Total Distant</th>
                                <?php
                                }
                                ?>
                                <?php
                                if($type == 3){
                                ?>
                                <th style="text-align:right">Total Commission</th>
                                <?php
                                }
                                ?>
							</tr>
						</thead>
						<tbody>
							<?php
                            if ($did == ""){
								$ttxt1 = "";
							}else{
								$ttxt1 = "AND DRIVERCODE = '".$dcode."'";
							}

							if ($lid == ""){
								$ttxt2 = "";
							}else{
								$ttxt2 = "AND LOCATION = '".$lname."'";
							}

							$sql = $connect->query("SELECT DRIVER, COUNT(ORDNO) AS CORDNO, SUM(DISTANT) AS SDISTANT, SUM(RETAIL) AS SRETAIL FROM `orderlist` WHERE STATUS = 'C' AND DELDATE BETWEEN '".$sdate."' AND '".$edate."' $ttxt1 $ttxt2 GROUP BY DRIVERCODE");
							while($row = $sql->fetch_assoc()){
								?>
								<tr>
									<td style="width:1%"></td>
                                    <td><?php echo $row["DRIVER"]; ?></td>
									<td style="text-align:right"><?php echo $row["CORDNO"]; ?></td>
                                    <?php
                                    if($type == 1){
                                    ?>
                                    <td style="text-align:right"><?php echo $row["SDISTANT"]; ?></td>
                                    <td style="text-align:right"><?php echo $row["SRETAIL"]; ?></td>
                                    <?php
                                    }
                                    ?>
                                    <?php
                                    if($type == 2){
                                    ?>
                                    <td style="text-align:right"><?php echo $row["SDISTANT"]; ?></td>
                                    <?php
                                    }
                                    ?>
                                    <?php
                                    if($type == 3){
                                    ?>
                                    <td style="text-align:right"><?php echo $row["SRETAIL"]; ?></td>
                                    <?php
                                    }
                                    ?>
                                    </tr>
                                    <?php
                                }
                                ?>
						</tbody>
						<tfoot>
							<?php
							$sql12 = $connect->query("SELECT COUNT(ORDNO) AS CORDNO, SUM(DISTANT) AS SUMDISTANT, SUM(RETAIL) AS SUMRETAIL FROM `orderlist` WHERE STATUS = 'C' AND DELDATE BETWEEN '".$sdate."' AND '".$edate."' $ttxt1 $ttxt2 ");
							if($sql12->num_rows > 0){
								while($row = $sql12->fetch_assoc()){
                                    $countordno = number_format($row['CORDNO'],2);
									$sumdistant = number_format($row['SUMDISTANT'],2);
									$sumretail = number_format($row['SUMRETAIL'],2);
								}
							}
							?>
							<tr>
								<th style="width:1%;"></th>
								<th>Total</th>
								<th style="text-align:right"><?php echo $countordno; ?></th>
                                <?php
								if($type == 1){
								?>
								<th style="text-align:right"><?php echo $sumdistant; ?></th>
								<th style="text-align:right"><?php echo $sumretail; ?></th>
								<?php
								}
								?>
								<?php
								if($type == 2){
								?>
								<th style="text-align:right"><?php echo $sumdistant; ?></th>
								
								<?php
								}
								?>
								<?php
								if($type == 3){
								?>
								
								<th style="text-align:right"><?php echo $sumretail; ?></th>
								<?php
								}
								?>
							</tr>
						</tfoot>
					</table>
				</main>
			</div>
		</div>

		<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script>
		<script src="../assets/dashboard.js"></script>

		<script>
		$("#driver").select2({
			placeholder: "Select a driver",
			allowClear: true
		});

		$("#location").select2({
			placeholder: "Select a location",
			allowClear: true
		});
		</script>

        <!-- Datatable -->
        <script type="text/javascript">
        $(document).ready(function () {
            var ptitle = document.getElementById("ptitle").value;

            var t = $('#example').DataTable({
                columnDefs: [
                    {
                        searchable: false,
                        orderable: false,
                        targets: 0,
                    },
                ],

                order: [[1, 'desc']],
                dom: 'Bfrtip',
                buttons: [
                    { 
                        extend: 'copy', 
                        footer: true,
                        exportOptions: {
							<?php
							if($type == '1'){
								?>
								columns: [0, 1, 2, 3, 4]
								<?php
							}
							?>
							<?php
							if($type == '2'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
							<?php
							if($type == '3'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
                        } 
                    },
                    { 
                        extend: 'csv', 
                        footer: true,
                        exportOptions: {
							<?php
							if($type == '1'){
								?>
								columns: [0, 1, 2, 3, 4]
								<?php
							}
							?>
							<?php
							if($type == '2'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
							<?php
							if($type == '3'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
                        } 
                    },
                    { 
                        extend: 'excel', 
                        footer: true,
                        exportOptions: {
							<?php
							if($type == '1'){
								?>
								columns: [0, 1, 2, 3, 4]
								<?php
							}
							?>
							<?php
							if($type == '2'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
							<?php
							if($type == '3'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
                        } 
                    },
                    { 
                        extend: 'pdf', 
                        footer: true,
                        exportOptions: {
							<?php
							if($type == '1'){
								?>
								columns: [0, 1, 2, 3, 4]
								<?php
							}
							?>
							<?php
							if($type == '2'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
							<?php
							if($type == '3'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
                        }  
                    },
                    { 
                        extend: 'print', 
                        footer: true,
                        title: ptitle,
                        exportOptions: {
							<?php
							if($type == '1'){
								?>
								columns: [0, 1, 2, 3, 4]
								<?php
							}
							?>
							<?php
							if($type == '2'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
							<?php
							if($type == '3'){
								?>
								columns: [0, 1, 2, 3]
								<?php
							}
							?>
                        }  ,
                        customize: function ( doc ) {
                            $(doc.document.body).find('h1').css('font-size', '14pt');
                            $(doc.document.body).find('h1').css('text-align', 'left'); 
                        },
                    }
                ],

                pageLength: 50,
                searching: true
            });
        
            t.on('order.dt search.dt', function () {
                let i = 1;
        
                t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
                    this.data(i++);
                });
            }).draw();
        });
        </script>

		<script>
		function submit() {
			var sdate = document.getElementById("sdate").value;
			var edate = document.getElementById("edate").value;
			var driver = document.getElementById("driver").value;
			var location2 = document.getElementById("location").value;
			var type = document.getElementById("type").value;

			location.href = "?s="+sdate+"&e="+edate+"&d="+driver+"&l="+location2+"&t="+type;
		}
		</script>
	</body>
</html>
