<?php
include "config.php";
include "locations.php";

if(!$_SERVER['HTTP_REFERER']){
	header("HTTP/1.0 404 Not Found");
} else {
	header("Referer: $referer");
	header("Location: $url");
}