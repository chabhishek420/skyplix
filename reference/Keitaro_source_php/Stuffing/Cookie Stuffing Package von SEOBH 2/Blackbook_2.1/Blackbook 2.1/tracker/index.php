<?php	
	include './config.php';	
	
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
	$html .= "<table border=0 width=\"700\"><tr><td>Affiliate Name</td><td>Affiliate URL ID</td><td>Cookies Stuffed</td><td>Banner Impressions</td><td>CTR</td><td>Edit</td></tr>";
	
	$query  = "SELECT * FROM affiliate_links, tracking, affiliates WHERE affiliate_links.id = tracking.link_id AND affiliate_links.affiliate_id = affiliates.id";
	$result = mysql_query($query);
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$html .=	"<tr><td align=right>" . $row['affiliate_name'] . "</td><td align=right>" . $row['link_id'] . "</td><td align=right>" . $row['cookies'] . "</td><td align=right>" . $row['impressions'] . "</td><td align=right>" . round(($row['cookies']/($row['impressions']+1)),2) . "</td><td align=right>";
		$html .= 	"<a href=\"http://" . $_SERVER['SERVER_NAME'] . "/tracker/edit_affiliate_link.php?id=" . $row['link_id'] . "\">Edit Link</a></tr>";
	} 
	$html .= "</table>";
	$html .= "</div>";
	$html .= "</body></html>";

	echo $html;
?>