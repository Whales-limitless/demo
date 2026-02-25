<?php
include "../dbconnection.php";

if($_POST['action'] == "add"){
    $txtorderno = $_POST['txtorderno'];
	$txtpdesc = $_POST['txtpdesc'];
	$txtqtyin = $_POST['txtqtyin'];

    $connect->query('INSERT INTO `orderlistdesc` (ORDERNO,PDESC,QTY) VALUES ("'.$txtorderno.'","'.$txtpdesc.'","'.$txtqtyin.'")');

    $rownum1 = '1';
    $select = $connect->query('SELECT * FROM `orderlistdesc` WHERE ORDERNO = "'.$txtorderno.'" ORDER BY ID');
    while($row = $select->fetch_assoc()){
        echo '<tr>
            <td class="hideextra">'.$rownum1.'</td>
            <td class="hideextra">'.$row['PDESC'].'</td>
            <td class="hideextra" style="text-align:right">'.$row['QTY'].'</td>
            <td class="hideextra" style="text-align:center"><button type="button" class="btn btn-danger btn-sm" onclick="delButton('.$row['ID'].')">Delete</button></td>
        </tr>';

        $rownum1++;
    }
}elseif($_POST['action'] == "delete"){
	$orderno = $_POST['orderno'];
	$txtid = $_POST['txtid'];
	
	$connect->query('DELETE FROM `orderlistdesc` WHERE ID = "'.$txtid.'" AND ORDERNO = "'.$orderno.'" ');

	$rownum1 = '1';
    $select = $connect->query('SELECT * FROM `orderlistdesc` WHERE ORDERNO = "'.$orderno.'" ORDER BY ID');
    while($row = $select->fetch_assoc()){
        echo '<tr>
            <td class="hideextra">'.$rownum1.'</td>
            <td class="hideextra">'.$row['PDESC'].'</td>
            <td class="hideextra" style="text-align:right">'.$row['QTY'].'</td>
            <td class="hideextra" style="text-align:center"><button type="button" class="btn btn-danger btn-sm" onclick="delButton('.$row['ID'].')">Delete</button></td>
        </tr>';

        $rownum1++;
    }
}
?>
