<?php
ob_start();
session_start();
require("db.php");
require('config.php');

$useragent=$_SERVER['HTTP_USER_AGENT'];
$referer=$_SERVER['HTTP_REFERER'];
$ip=$_SERVER['REMOTE_ADDR'];


$campaign=$_GET['campaign'];
$img=$_GET['img'];
$ext=$_GET['ext'];
$go=$_GET['r'];

$detlog=true;

$uares=mysql_query("SELECT * FROM `useragents` WHERE `useragent`='".$useragent."' LIMIT 1");
if(mysql_num_rows($uares)==0){
	mysql_query("INSERT INTO `useragents`(`useragent`) VALUES('".$useragent."')");
	$useragent=mysql_insert_id();
}else{
	$uarow=mysql_fetch_array($uares);
	$useragent=$uarow['id'];
}


$kickback=$ssldomain.$campaign."/".$img.".".$ext;

function increase($table,$row,$id){
	return mysql_query("UPDATE `".$table."` SET `".$row."`=`".$row."`+1 WHERE `id`='".$id."'");
}

function addlog($str){
	global $memberid,$campaignid,$useragent,$referer,$ip,$detlog;
	if($detlog==true){
		$r=mysql_query("SELECT * FROM `loginfo` WHERE `campaignid`='".$campaignid."' AND `ip`='".$ip."'");
		if(mysql_num_rows($r)>0){
			$sub=mysql_fetch_array($r);
			$id=$sub['id'];
			mysql_query("UPDATE `loginfo` SET `timestamp`='".mktime()."' WHERE `campaignid`='".$campaignid."' AND `ip`='".$ip."'");
		}else{
			mysql_query("INSERT INTO `loginfo`(`memberid`,`campaignid`,`timestamp`,`ip`,`useragent`) VALUES('".$memberid."','".$campaignid."','".mktime()."','".$ip."','".$useragent."')");
			$id=mysql_insert_id();
		}
		mysql_query("INSERT INTO `logs`(`infoid`,`timestamp`,`referer`,`action`) VALUES('".$id."','".mktime()."','".$referer."','".$str."')");
		return mysql_insert_id();
	}
	return false;
}

function get_hit(){
	global $campaignid,$ip;
	$r=mysql_query("SELECT * FROM `loginfo` WHERE `campaignid`='".$campaignid."' AND `ip`='".$ip."'");
	if(mysql_num_rows($r)>0){
		$sub=mysql_fetch_array($r);
		return $sub['hit'];
	}else{
		return 0;
	}
}

function set_hit($timestamp){
	global $campaignid,$ip;
	if(mysql_query("UPDATE `loginfo` SET `hit`='".$timestamp."' WHERE `campaignid`='".$campaignid."' AND `ip`='".$ip."'")){
		return true;
	}else{
		return false;
	}
}

function get_stuff(){
	global $campaignid,$ip;
	$r=mysql_query("SELECT * FROM `loginfo` WHERE `campaignid`='".$campaignid."' AND `ip`='".$ip."'");
	if(mysql_num_rows($r)>0){
		$sub=mysql_fetch_array($r);
		return $sub['stuff'];
	}else{
		return 0;
	}
}

function set_stuff($timestamp){
	global $campaignid,$ip;
	if(mysql_query("UPDATE `loginfo` SET `stuff`='".$timestamp."' WHERE `campaignid`='".$campaignid."' AND `ip`='".$ip."'")){
		return true;
	}else{
		return false;
	}
}

function image($img,$ext){
	switch(strtolower($ext)){
		case "jpg":
			header("Content-type: image/jpeg");
			break;
		case "gif":
			header("Content-type: image/gif");
			break;
		case "png":
			header("Content-type: image/png");
			break;
	}
	readfile('./files/'.$img.".".$ext);
}


$result=mysql_query("SELECT * FROM `campaigns` WHERE `tag`='".$campaign."'");

if(mysql_num_rows($result)>0){
	$row=mysql_fetch_array($result);
	$memberid=$row['memberid'];
	$campaignid=$row['id'];
	$ctr=$row['ctr'];
	
	if($row['logging']==0){
		$detlog=false;
	}
	
	if($row['randomize']==1){
		$linkres=mysql_query("SELECT * FROM `links` WHERE `campaignid`='".$campaignid."' AND `enabled`='1' ORDER BY RAND() LIMIT 1");
	}else{
		$linkres=mysql_query("SELECT * FROM `links` WHERE `campaignid`='".$campaignid."' AND `enabled`='1' ORDER BY `stuffs` ASC LIMIT 1");
	}
	$linkrow=mysql_fetch_array($linkres);
	
	if($row['enabled']==1){
		
		if($go==true){
			if(empty($referer)){
				addlog('%Q');
				if(get_hit()>mktime()-60){
					set_stuff(mktime());
					
					$urlres=mysql_query("SELECT * FROM `linkinfo` WHERE `id`='".$linkrow['infoid']."' LIMIT 1");
					$urlrow=mysql_fetch_array($urlres);
					
					addlog('%S '.$urlrow['name']);
					increase('links','stuffs',$linkrow['id']);
					
					header("Location: ".$urlrow['url']);
					
					exit;
				}else{
					addlog('%K');
					increase('links','blocks',$linkrow['id']);
				}
			}else{
				addlog('%B');
				increase('links','blocks',$linkrow['id']);
			}
		}else{
			addlog('%H');
			increase('links','hits',$linkrow['id']);
			if(get_stuff()<mktime()-86400){
				$allowed=false;
				$r=mysql_query("SELECT * FROM `allowrefs` WHERE `infoid`='".$row['allowrefs']."' AND `enabled`='1'");
				while($sub=mysql_fetch_array($r)){
					if(strstr($referer,$sub['ref'])){
						$allowed=true;
					}
				}
				$blocked=false;
				$r=mysql_query("SELECT * FROM `blockrefs` WHERE `infoid`='".$row['blockrefs']."' AND `enabled`='1'");
				while($sub=mysql_fetch_array($r)){
					if(strstr($referer,$sub['ref'])){
						$blocked=true;
					}
				}
				if($allowed==true&&$blocked==false){
					$ipok=true;
					$r=mysql_query("SELECT * FROM `blockips` WHERE `infoid`='".$row['blockips']."'");
					while($sub=mysql_fetch_array($r)){
						if(strstr($sub['ip'],'-')){
							$ip_exp=explode("-",$sub['ip']);
							$ip_low=ip2long($ip_exp[0]);
							$ip_high=ip2long($ip_exp[1]);
							$ip_long=ip2long($ip);
							if($ip_long>$ip_low&&$ip_long<$ip_high){
								$ipok=false;
							}
						}else{
							if(strstr($ip,$sub['ip'])){
								$ipok=false;
							}
						}
					}
					if($ipok==true){
						if(mt_rand(0,100)<=$ctr){
							set_hit(mktime());
							header("Location: ".$kickback);
							exit;
						}else{
							addlog('%R');
							increase('links','blocks',$linkrow['id']);
						}
					}else{
						addlog('%P');
						increase('links','blocks',$linkrow['id']);
					}
				}else{
					addlog('%E');
					increase('links','blocks',$linkrow['id']);
				}
			}else{
				addlog('%U');
				increase('links','blocks',$linkrow['id']);
			}
		}
	}else{
		addlog('%C');
		increase('links','blocks',$linkrow['id']);
	}
	
}

addlog('%I');

image($img,$ext);

ob_end_flush();
?>