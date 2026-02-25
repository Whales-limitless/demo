<?php
//clear cookie to logout user

unset($_COOKIE['parkwaydelivery_user']); 
setcookie('parkwaydelivery_user', null, -1, '/'); 

unset($_COOKIE['parkwaydelivery_user_pin']); 
setcookie('parkwaydelivery_user_pin', null, -1, '/'); 

header("location: login.php");
?>
