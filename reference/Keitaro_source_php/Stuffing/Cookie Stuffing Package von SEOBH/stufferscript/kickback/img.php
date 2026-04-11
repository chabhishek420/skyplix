<?php
require('config.php');
$kickback=$maindomain;
$kickback.="t/".$_REQUEST['campaign']."/".$_REQUEST['img'].".".$_REQUEST['ext'];
header("Location: ".$kickback);
?>