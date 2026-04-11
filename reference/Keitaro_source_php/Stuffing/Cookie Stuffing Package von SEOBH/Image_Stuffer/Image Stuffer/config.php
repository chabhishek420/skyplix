<?php
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'cookie';
$adminpassword= 'default';

$link = mysql_connect($host, $user, $password);
if (!$link) {
    die('keine Verbindung möglich: ' . mysql_error());
}
$db_selected = mysql_select_db($db, $link);
if (!$db_selected) {
    die ('Kann ' . $db . ' nicht benutzen : ' . mysql_error());
}