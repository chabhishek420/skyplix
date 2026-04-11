<?php

	include './config.php';	
	
	
	if ($_POST['id'] > 0)
	{

		$query  = "SELECT * FROM referers WHERE referers.id = " . $_POST['id'];
		$result = mysql_query($query);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);

		
		$sql="INSERT INTO safe_referers (safe_referers) VALUES (\"" . $row['referers'] . "\")";
		if (!mysql_query($sql))
		{
			die('Error: ' . mysql_error());
		}
		else
		{
			$sql="DELETE FROM referers WHERE referers.referers = \"" . $row['referers'] . "\"";
			if (!mysql_query($sql))
			{
				die('Error: ' . mysql_error());
			}
			$html = "<html><head><meta http-equiv=\"refresh\" content=1;url=\"index.php\"></head><body> Added Successfully, Redirecting in 5</body></html>";
		}
	
	}
	else
	{
		$html = "<html><head><title>Blackbook - Cookie Stuffing Tracker</title>";
		$html .= "<link rel=\"stylesheet\" href=\"stylesheet.css\" type=\"text/css\" media=\"screen\" /></head>";
		$html .= "<body bgcolor=\"#000000\"><div class=\"blackbook1\">";
		$html .= "<img src=banner.jpg>";
		$html .= "</div>";
		$html .= "<div class=\"blackbook5\">";
		$html .= "<table width=600><tr>";
		$html .= "<td align=center><a href=\"index.php\">Home</a></td>";
		$html .= "<td align=center><a href=\"add_affiliate.php\"> Add a New Affiliate</a></td>";
		$html .= "<td align=center><a href=\"add_affiliate_link.php\"> Add a New Affiliate Link</a></td>";
		$html .= "<td align=center><a href=\"referers.php\"> View Referers List</td>";
		$html .= "</tr></table>";
		$html .= "</div>";		
		$html .= "<div class=\"blackbook3\">";
		$html .= "<table border=0 width=\"700\"><tr><td align=center>ID</td><td align=center>Referer</td><td align=center>Add to Safe List</td></tr>";
		
		$query  = "SELECT * FROM referers";
		$result = mysql_query($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$html .=	"<tr><td align=right width=25>" . $row['id'] . "</td><td align=right width=*>" . $row['referers'] . "</td><td valign=middle align=right width=200>";
			$html .= 	"<form name=\"referers\" action=\"referers.php\" method=\"post\"><input type=\"hidden\" name=\"id\" value=\"" . $row['id'] . "\"><input type=\"submit\" value=\"Add to Safe List\"></form></tr>";
		} 
		$html .= "</table>";
		$html .= "</div>";
		$html .= "</body></html>";
	}
	echo $html;
























?>