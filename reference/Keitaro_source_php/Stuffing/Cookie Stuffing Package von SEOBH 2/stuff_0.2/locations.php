<?
$case = $_GET['site'];

$sql = sprintf("SELECT * FROM cookies WHERE alias = '%s' AND active = 1 LIMIT 1", $case);

$query = mysql_query($sql);
$location = mysql_fetch_array($query);

if($location)
{	
	$ip = $_SERVER["REMOTE_ADDR"];
	$sql = sprintf("SELECT id from stats 
										WHERE ip = '%s' 
										AND c_id = %s 
										AND date >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)"
					, $ip
					, $location['id']);

	$result = mysql_query($sql);
	$num_rows = mysql_num_rows($result); 
	
	$session_name = 'session-' . md5($location['name']);
	
	if(!$num_rows && $_COOKIE[$session_name] != 1)
	{
		setcookie($session_name, 1, time()+3600*24*7);
		$sql = sprintf("INSERT INTO stats (c_id, referer, ip) VALUES (%s,'%s','%s')", 
								$location['id'], 
								$_SERVER['HTTP_REFERER'],
								$ip);
		mysql_query($sql);
	}
	else {
		
		header('Content-Type: image/png');
		readfile('images/' . $location['img']);
		die();		
	}
	$url 		= $location['url'];
	$referer 	= $location['referer'];
}
else {
		
		header('Content-Type: image/png');
		readfile('images/fsk18.gif');
		die();		
	}
