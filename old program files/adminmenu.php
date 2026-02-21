<?php
include 'adminhead.php';
date_default_timezone_set("Asia/Kuala_Lumpur");
session_start();

$connect->query("TRUNCATE TABLE `orderlist2`");
$connect->query("INSERT INTO `orderlist2` (SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUMQTY) SELECT SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUM(QTY) AS SUMQTY FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND BARCODE <> 'PT' GROUP BY SALNUM,ACCODE ORDER BY SALNUM DESC");
$connect->query("UPDATE orderlist2 AS b INNER JOIN MEMBER AS g ON b.ACCODE = g.ACCODE SET b.HP = g.HP");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="refresh" content="300">
<title>Live View</title>
<link rel="icon" href="../logo/logo.png">

<!-- Fontawesome -->
<link href="../asset2/fontawesome5/css/all.css" rel="stylesheet">

<!-- Bootstrap core CSS -->
<link href="../asset2/bootstrap5/css/bootstrap.min.css" rel="stylesheet">
<script src="../asset2/bootstrap5/js/bootstrap.bundle.min.js"></script>

<!-- Jquery JS -->
<script src="../asset2/css/jquery.min.1.7.js"></script>
<script type="text/javascript" src="../asset2/css/jquery.js"></script>

<!-- Datatable -->
<link rel="stylesheet" type="text/css" href="../asset2/datatable3/DataTables-1.12.1/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="../asset2/datatable3/Buttons-2.2.3/css/buttons.dataTables.min.css">

<script type="text/javascript" charset="utf8" src="../asset2/datatable3/jquery-3.5.1.js"></script>
<script type="text/javascript" charset="utf8" src="../asset2/datatable3/DataTables-1.12.1/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="../asset2/datatable3/Buttons-2.2.3/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" charset="utf8" src="../asset2/datatable3/JSZip-2.5.0/jszip.min.js"></script>
<script type="text/javascript" charset="utf8" src="../asset2/datatable3/pdfmake-0.1.36/pdfmake.min.js"></script>
<script type="text/javascript" charset="utf8" src="../asset2/datatable3/pdfmake-0.1.36/vfs_fonts.js"></script>
<script type="text/javascript" charset="utf8" src="../asset2/datatable3/Buttons-2.2.3/js/buttons.html5.min.js"></script>
<script type="text/javascript" charset="utf8" src="../asset2/datatable3/Buttons-2.2.3/js/buttons.print.min.js"></script>

<!-- Sweetalert2 -->
<script src="../asset2/sweetalert2/sweetalert2.all.min.js"></script>

<style>
body {
  font-family: 'Poppins', sans-serif;
}

<?php include ('../fontstyle2.php'); ?>

th {
  border-top: 1px solid #dddddd;
  border-bottom: 1px solid #dddddd;
  border-right: 1px solid #dddddd;
}
 
th:first-child {
  border-left: 1px solid #dddddd;
}
</style>
</head>

<body>

<?php
$rownumber = '1';
$query56 = $connect->query("SELECT * FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0' GROUP BY SALNUM,ACCODE");
if($query56->num_rows > 0){
	while($row = $query56->fetch_assoc()){
		$countid = $rownumber++;
	}
}else{
	$countid = '';
}

if(isset($_POST['noted'])){
	$connect->query("UPDATE `orderlist` SET SOUND = '1' WHERE SOUND = '0' ");
	echo "<script language='JavaScript'>window.location.href='adminmenu.php';</script>";
}
?>

<div class="container-fluid mt-3 mb-4">
    <div class="row">
        <div class="col-12 col-lg-4">
            Live View : <label id="seconds">30</label> seconds
        </div>

        <div class="col-12 col-lg-6 text-lg-end">Click button to turn off notification</div>
	
        <div class="col-12 col-lg-2 text-end">
            <form method="POST" action="" enctype="multipart/form-data">
                <button type="submit" name="noted" class="btn btn-primary position-relative">
                    Notification
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $countid; ?>
                        <span class="visually-hidden">unread messages</span>
                    </span>
                </button>
            </form>
        </div>
	</div>

    <div class="row">
        <div class="col-12 col-lg-12">
			<div class="table-responsive">
				<table id="table_id" class="table cell-border hover nowrap compact" style="width:100%;font-size:12px" >
					<thead class="table-dark">
						<tr>
							<th style="width:1%;font-weight:normal">No</th>
							<th style="font-weight:normal">Date</th>
							<th style="font-weight:normal">Time</th>
							<th style="font-weight:normal">OrderNo</th>
							<th style="font-weight:normal">Name</th>
							<th style="font-weight:normal">Contact</th>
							<th style="font-weight:normal">Qty/Ctn</th>
							<th style="font-weight:normal">To Remark</th>
							<th style="font-weight:normal">Remark</th>
							<th style="width:1%;font-weight:normal">Action</th>
						</tr>
					</thead>
				</table>
			</div>
	    </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="exampleModalAdd3" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-lg-4">
                    Remark
                    </div>
                    <div class="col-12 col-lg-8 mb-1">
                    <input type="text" id="remark" class="form-control" >
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-lg-4">
                    Transaction No
                    </div>
                    <div class="col-12 col-lg-8 mb-1">
                    <input type="text" id="rowtransno" class="form-control" >
                    </div>
                </div>
                <input type="hidden" id="pid" class="form-control">
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-primary w-100" onclick="successbtn();">Save</button>
			</div>
        </div>
    </div>
</div>

<?php
$query = $connect->query("SELECT * FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0' GROUP BY SALNUM,ACCODE ORDER BY SALNUM DESC ");
if($query->num_rows > 0){
?>
	<audio controls autoplay>
	<source src="sound/Melody-notification-sound.mp3" type="audio/mpeg">
	Your browser does not support the audio element.
	</audio>
<?php
}					
?>

<script type="text/javascript">
var secondsLabel = document.getElementById("seconds");
var totalSeconds = 300;
setInterval(setTime, 1000);

function setTime() {
  --totalSeconds;
  secondsLabel.innerHTML = pad(totalSeconds % 30);
}

function pad(val) {
  var valString = val + "";
  if (valString.length < 2) {
    return "0" + valString;
  } else {
    return valString;
  }
}
</script>

<script language="javascript" type="text/javascript">
setTimeout(function() {
    location.reload();
}, 30000);
</script>


<!--enter and next function-->
<script type="text/javascript">
$(document).ready(function(){
	$('#remark').bind("keydown", function(e){
		if (e.which == 13){
			e.preventDefault();
			$('#rowtransno').focus();
		}
	});
	$('#rowtransno').bind("keydown", function(e){
		if (e.which == 13){
			e.preventDefault();

            var remark      = document.getElementById("remark").value;
            var rowtransno  = document.getElementById("rowtransno").value;
            var pid         = document.getElementById("pid").value;
            
            $.ajax({
                type:'POST',
                url:'adminmenupost.php',
                data: { remark:remark, rowtransno:rowtransno, pid:pid, action:"success" },
                success:function(value){
                    if(value == "Saved."){
                        Swal.fire({
                            icon: 'success',
                            text: 'Success',
                            didClose: () => {
                                location.reload();
                            }
                        })
                    }else{
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!'
                        })
                    }
                }
            });
			
		}
	});
});
</script>

<script language="javascript" type="text/javascript">
function editbtn(id){
	$('#exampleModalAdd3').modal('show');
    $.ajax({
        type:'POST',
        url:'adminmenupost.php',
        data: { id:id, action:"detail" },
        success:function(data){
            var data = data.split("|");
			$('#remark').val(data[0]);
			$('#rowtransno').val(data[1]);
            $('#pid').val(id);
        }
    });
}

//autofocus modal
$('#exampleModalAdd3').on('shown.bs.modal', function () {
    $('#remark').trigger('focus')
});
</script>

<!-- Datatable -->
<script type="text/javascript">
$(document).ready(function () {
    var t = $('#table_id').DataTable({
        processing: true,
        serverSide: true,

        ajax:{
            url:"server_processing.php",
        },
		
		columnDefs: [
            {
                searchable: false,
                orderable: false,
                targets: 9,
                render: function(data, type, row, meta) {
                    var id = row[3];

                    if (type === 'display') {
                        data = '<td ><a href="adminmenu_detail.php?salnum='+id+'" class="btn btn-secondary btn-sm">' + "View" + '</a> <button type="button" onclick="donebtn('+id+');" class="btn btn-primary btn-sm">' + "Done" + '</button> <button type="button" onclick="editbtn('+id+');" class="btn btn-success btn-sm">' + "Success" + '</button> <button type="button" onclick="deletebtn('+id+');" class="btn btn-danger btn-sm">' + "Delete" + '</button></td>';
                    }
                    return data;
                }
            }
        ],

        order: [[0, 'asc']],
		
		dom: 'Bfrtip',
        buttons: [
            { 
                extend: 'copy', 
                footer: true,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7,8]
                } 
            },
            { 
                extend: 'csv', 
                footer: true,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7,8]
                } 
            },
            { 
                extend: 'excel', 
                footer: true,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7,8]
                } 
            },
            { 
                extend: 'pdf', 
                footer: true,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7,8]
                } 
            },
            { 
                extend: 'print', 
                footer: true,
                title: 'Product',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7,8]
                } ,
                customize: function ( doc ) {
                    $(doc.document.body).find('h1').css('font-size', '14pt');
                    $(doc.document.body).find('h1').css('text-align', 'left'); 
                },
            },
            'colvis'
        ],

		pageLength: 100,
        stateSave: true
    });
 
    t.on('order.dt search.dt', function () {
        let i = 1;
 
        t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
            this.data(i++);
        });
    }).draw();
});
</script>

<script type="text/javascript">
//Done button
function donebtn(id){
	$.ajax({
		type:'POST',
		url:'adminmenupost.php',
		data: { 
			id:id, 
			action:"done" 
		},
		success:function(data){
			if(data == 'Saved.'){
				location.reload();
			}else{
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: 'Something went wrong!'
				})
			}
		}
	});	
}
</script>

<script type="text/javascript">
//Delete button
function deletebtn(id){
    Swal.fire({
        title: 'Delete?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type:'POST',
                url:'adminmenupost.php',
                data: { id:id, action:"delete" },
                success:function(data){
                    if(data == 'Deleted.'){
                        Swal.fire({
                            icon: 'success',
                            text: 'Deleted',
                            didClose: () => {
                                location.reload();
                            }
                        })
                    }else{
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!'
                        })
                    }
                }
            });
        }
    })
}
</script>

<script type="text/javascript">
//Success button
function successbtn(){
    var remark      = document.getElementById("remark").value;
    var rowtransno  = document.getElementById("rowtransno").value;
    var pid         = document.getElementById("pid").value;
    
    $.ajax({
        type:'POST',
        url:'adminmenupost.php',
        data: { remark:remark, rowtransno:rowtransno, pid:pid, action:"success" },
        success:function(value){
            if(value == "Saved."){
                location.reload();
            }else{
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong!'
                })
            }
        }
    });
}
</script>

</body>
</html>