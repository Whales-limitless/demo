<?php
//clear cookie to logout user

unset($_COOKIE['parkwaydelivery_driver']); 
setcookie('parkwaydelivery_driver', null, -1, '/'); 

unset($_COOKIE['parkwaydelivery_driver_pin']); 
setcookie('parkwaydelivery_driver_pin', null, -1, '/'); 

header("location: login.php");
?>
