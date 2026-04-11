<?php

include "config.php";

function bhcookie()
{
	//check for browser type
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	
	//set safe browser types
	$browser[0] = "msie 6";
	$browser[1] = "msie 7";
	$browser[2] = "firefox";
	$browser[3] = "safari";
	
	$i = 0;
	$user_agent_ok = 0;
	while($i < 4)
	{
		if(strpos($useragent, $browser[$i]) > 0)
		{
			$user_agent_ok = 1;
			$i=4;
		}
		$i++;
	}
	
	if ($user_agent_ok == 1)
	{
		//check referer
		$safe = 0;
		$query  = "SELECT * FROM safe_referers";
		$result = mysql_query($query);
		
		//check for blank referer
		if ($_SERVER['HTTP_REFERER'] != ""  AND (substr_count($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) == 0))
		{
			//strip down referer information to http://subdomain.domain.tld
			$tld[0] = ".com";
			$tld[1] = ".net";
			$tld[2] = ".org";
			$tld[3] = ".info";
			$i = 0;
			while($i < 4)
			{
				$referer = strstr ($_SERVER['HTTP_REFERER'], "http://");
				if(substr_count($referer, $tld[$i]) > 0)
				{
					$referer = substr($referer, 0, strpos($referer, $tld[$i]));
					$referer = $referer . $tld[$i];
					$i=4;
				}
				$i++;
			}
			//get contents of safe_referer list in database, compare it against referer
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				//if referer is safe, set the safe key to to TRUE
				if (substr_count($row['safe_referers'], $referer) > 0)
				{
					$safe = 1;
				}
			}
			if ($safe == 1)
			{
				//select all affiliate links from database
				$query  = "SELECT * FROM affiliate_links, tracking WHERE affiliate_links.id = tracking.link_id";
				$result = mysql_query($query);
				$html = "";
				//loop through all affilaite links
				while($row = mysql_fetch_array($result, MYSQL_ASSOC))
				{
				
					$banner_impressions = $row['impressions'] + 1;
					$url_clicks = $row['cookies'];
					$ctr = $url_clicks/$banner_impressions;
					$maxctr = (rand((($row[maxctr]*100) - 5), (($row[maxctr]*100) + 5))/100);
					//check if ctr is less than maxctr as specified in database.  If true, send them a image cookie
					if ($ctr < $row['maxctr'])
					{
						$update = "UPDATE tracking SET cookies = \"" . ($url_clicks+1) . "\" WHERE link_id = \"" . $row['link_id'] . "\"";
						if (!mysql_query($update))
						{
							die('Error: ' . mysql_error());
						}
						$update = "UPDATE tracking SET impressions = \"" . $banner_impressions . "\" WHERE link_id = \"" . $row['link_id'] . "\"";
						if (!mysql_query($update))
						{
							die('Error: ' . mysql_error());
						}
						$html .= "<div style='display: none; visibility: invisible;'><img src=\"" . $row['url'] . "\" height=\"1\" width=\"1\" alt=\".\"><img src=\"" . $row['banner'] . "\" height=\"1\" width=\"1\" alt=\".\"></div>";
					}
					//if false, send them a banner to keep CTR in check
					else
					{
						$update = "UPDATE tracking SET impressions = \"" . $banner_impressions . "\" WHERE link_id = \"" . $row['link_id'] . "\"";
						if (!mysql_query($update))
						{
							die('Error: ' . mysql_error());
						}
						$html .= "<div style='display: none; visibility: invisible;'><img src=\"" . $row['banner'] . "\" height=\"1\" width=\"1\" alt=\".\"></div>";
					}
				}
				echo $html;
			}
			else
			{	
				//if your  here, its because the affiliate was not on the safe list.  Add them to the normal referer list.
				$sql="INSERT INTO referers (referers) VALUES (\"" . mysql_real_escape_string($referer) . "\")";
				if (!mysql_query($sql))
				{
					die('Error: ' . mysql_error());
				}
			}
		}
	}
}

?>