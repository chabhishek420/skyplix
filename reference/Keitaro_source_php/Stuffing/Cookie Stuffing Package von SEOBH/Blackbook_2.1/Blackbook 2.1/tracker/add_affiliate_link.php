<?php

	include './config.php';


	if (isset($_POST['affiliate']) AND isset($_POST['aff_link']) AND isset($_POST['aff_banner']))
	{
	
		$sql="INSERT INTO affiliate_links (affiliate_id, url, banner) VALUES (\"" . $_POST['affiliate'] . "\", \"" . $_POST['aff_link'] . "\", \"" . $_POST['aff_banner'] . "\")";
		if (!mysql_query($sql))
		{
			die('Error: ' . mysql_error());
		}
		else
		{
			$query = "SELECT * FROM `affiliate_links` ORDER BY id DESC LIMIT 1";
			$result = mysql_query($query);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
				
			$sql="INSERT INTO tracking (link_id, cookies, impressions, maxctr) VALUES (\"" . $row['id'] . "\",\"" . 0 . "\",\"" . 0 . "\",\"" . $_POST['ctr'] . "\")";
			if (!mysql_query($sql))
			{
				die('Error: ' . mysql_error());
			}
			else
			{
				$html = "<html><head><meta http-equiv=\"refresh\" content=5;url=\"index.php\"></head><body> Added Successfully, Redirecting in 5</body></html>";
				echo $html;
			}
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
		$html .= "<div class=\"blackbook4\">";
		$html .= "<table><tr><td align=right>";
		$html .= "<form name=\"add_affiliate_link\" action=\"add_affiliate_link.php\" method=\"post\">";
		$html .= "Please select name of affiliate: <select name=\"affiliate\">";
		$query  = "SELECT * FROM affiliates";
		$result = mysql_query($query);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$html .= "<option value=\"" . $row['id'] . "\">" . $row['affiliate_name'] . "</option>";
		} 
		$html .= "</select><br />";
		$html .= "Please input your affiliate link: <input type=\"text\" size=50 maxlength=300 name=\"aff_link\"><br />";
		$html .= "Please input your affiliate banner: <input type=\"text\" size=50 maxlength=300 name=\"aff_banner\"><br />";
		
		$i = 0;
		
		$html .= "Please select ctr: <select name=\"ctr\">";
		while($i < 1)
		{
			$html .= "<option value=\"" . $i . "\">" . $i . "</option>";
			$i = $i + .01;
		} 
		$html .= "</select><br />";
		$html .= "<input type=\"submit\" value=\"Submit\"></form>";
		$html .= "</td></tr></table></div>";
		$html .= "</body></html>";

		echo $html;
	}



?>