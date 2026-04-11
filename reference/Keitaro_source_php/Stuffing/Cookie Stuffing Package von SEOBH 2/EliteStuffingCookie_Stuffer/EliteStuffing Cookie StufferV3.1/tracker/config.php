<?php

	$dbhostname="localhost";
	$dbusername="brank20";
	$dbpassword="kai123mana";
	$dbname="brank20_verybigincome";
	
	mysql_connect($dbhostname,$dbusername, $dbpassword) OR DIE ("<html><script language='JavaScript'<alert('Unable to connect to database! Please try again later.'),history.go(-1)</script></html>");
	mysql_select_db($dbname);

?>