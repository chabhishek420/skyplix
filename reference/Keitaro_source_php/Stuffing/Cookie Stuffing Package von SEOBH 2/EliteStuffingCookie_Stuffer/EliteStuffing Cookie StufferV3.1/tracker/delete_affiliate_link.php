<?php

	include './config.php';


	if (isset($_POST['delete']))
	{
		$delete = "DELETE FROM tracking WHERE link_id = \"" . $_GET['id'] . "\"";
		if (!mysql_query($delete))
		{
			die('Error: ' . mysql_error());
		}
		else
		{
			$delete = "DELETE FROM affiliate_links WHERE id = \"" . $_GET['id'] . "\"";
			if (!mysql_query($delete))
			{
				die('Error: ' . mysql_error());
			}	
			else
			{
				$html = "<html><head><meta http-equiv=\"refresh\" content=5;url=\"index.php\"></head><body> Deleted Successfully, Redirecting in 5</body></html>";
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
		$html .= "<form name=\"delete_affiliate_link\" action=\"delete_affiliate_link.php?id=" . $_GET['id'] . "\" method=\"post\">";
		$html .= "Are you sure you want to delete the following affiliate link?";
		$html .= "<input type=\"hidden\" name=\"delete\" value=\"true\">";
		$html .= "<input type=\"submit\" value=\"YES\"></form>";
		$html .= "</td></tr></table></div>";
		$html .= "</body></html>";

		echo $html;
	}



?>