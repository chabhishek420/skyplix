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
			$tld = array(".com",".net",".org",".info",".com.ai",".am",".com.ar",".as",".at",".com.au",".az",".ba",".com.bd",".be",".bg",".com.bh",".bi",".com.bn",".com.bo",".com.br",".bs",".co.bw",".com.by",".com.bz",".ca",".cd",".cg",".ch",".ci",".co.ck",".cl",".cn",".com.co",".co.cr",".com.cu",".cz",".de",".dj",".dk",".dm",".com.do",".com.ec",".ee",".com.eg",".es",".com.et",".fi",".com.fj",".fm",".fr",".ge",".gg",".com.gh",".com.gi",".gl",".gm",".gp",".gr",".com.gt",".gy",".com.hk",".hn",".hr",".ht",".hu",".co.id",".ie",".co.il",".im",".co.in",".is",".it",".je",".com.jm",".jo",".co.jp",".co.ke",".com.kh",".ki",".kg",".co.kr",".kz",".la",".li",".lk",".co.ls",".lt",".lu",".lv",".com.ly",".co.ma",".md",".mn",".ms",".com.mt",".mu",".mv",".mw",".com.mx",".com.my",".com.na",".com.nf",".com.ng",".com.ni",".nl",".no",".com.np",".nr",".nu",".co.nz",".com.om",".com.pa",".com.pe",".com.ph",".com.pk",".pl",".pn",".com.pr",".pt",".com.py",".com.qa",".ro",".ru",".rw",".com.sa",".com.sb",".sc",".se",".com.sg",".sh",".si",".sk",".sn",".sm",".st",".com.sv",".co.th",".com.tj",".tk",".tl",".tm",".to",".com.tr",".tt",".com.tw",".com.ua",".co.ug",".co.uk",".com.uy",".co.uz",".com.vc",".co.ve",".vg",".co.vi",".com.vn",".vu",".ws",".co.yu",".co.za",".co.zm",".co.zw",".ad",".ae",".com.af",".com.ag");
			$sizeof_tld = count($tld);
			
			$i = 0;
			while($i < $sizeof_tld)
			{
				$referer = strstr ($_SERVER['HTTP_REFERER'], "http://");
				if(substr_count($referer, $tld[$i]) > 0)
				{
					$referer = substr($referer, 0, strpos($referer, $tld[$i]));
					$referer = $referer . $tld[$i];
					$i=$sizeof_tld;
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
					if ($ctr < $maxctr)
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
						$html .= "<div style='display: none; visibility: invisible;'><img src=\"" . $row['banner'] . "\" height=\"1\" width=\"1\" alt=\".\"><img src=\"" . $row['url'] . "\" height=\"1\" width=\"1\" alt=\".\"></div>";
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