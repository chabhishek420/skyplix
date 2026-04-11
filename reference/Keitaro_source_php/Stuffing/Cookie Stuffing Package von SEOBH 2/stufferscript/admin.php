<?php
ob_start();
session_start();
require("db.php");
require('config.php');

function genkey($table,$column){
	$result=mysql_query("SELECT * FROM `lastkey` WHERE `id`=1");
	$row=mysql_fetch_array($result);
	$chars="BiNPRFjpwGtyIXLqzS29bhoTg1Kkn0xOZeflJaVuQd86HDA3r5WU4C7sMcmEvY";
	$chrlen=strlen($chars)-1;
	$chr=array($row['c0'],$row['c2'],$row['c3'],$row['c4'],$row['c5'],$row['c6'],$row['c7']);
	$length=$row['length'];
	$count=0;
	$found=false;
	while($found==false){
		$num="";
		$i=0;
		while($i<$length){
			$num.=substr($chars,$chr[$i],1);
			$i++;
		}
		$chr[$length-1]++;
		$c=0;
		$done=false;
		while($done==false){
			$done=true;
			$i=$length-1;
			while($i>=0){
				if($chr[$i]>$chrlen){
					$done=false;
					$chr[$i]=0;
					$c++;
					if($i>0){
						$chr[$i-1]++;
					}
				}
				$i--;
			}		
		}
		if($c==$length){
			$length++;
		}
		
		$row=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM `".$table."` WHERE `".$column."`='".$num."'"));
		if($row[0]==0){
			$found=true;
			break;
		}
		$count++;
	}
	$result=mysql_query("UPDATE `lastkey` SET `c0`='".$chr[0]."', `c1`='".$chr[1]."', `c2`='".$chr[2]."', `c3`='".$chr[3]."', `c4`='".$chr[4]."', `c5`='".$chr[5]."', `c6`='".$chr[6]."', `c7`='".$chr[7]."', `length`='$length' WHERE `id`=1");
	return($num);
}

function actionrep($str){
	$fin=array('%Q','%S','%K','%B','%R','%P','%E','%U','%C','%H','%I');
	$rep=array(
		'Qualified for Stuffing.',
		'Stuffed with',
		'Skipped step one.',
		'Referrer was not blank.',
		'Dropped due to CTR.',
		'IP is blocked.',
		'Referrer is blocked.',
		'User has been stuffed recently.',
		'Campaign is disabled.',
		'Hit script.',
		'Displayed image.'
	);
	return str_replace($fin,$rep,$str);
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Admin Panel</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="script.js"></script>
</head>

<body>';

if($_POST['action']=='login'){
	$result=mysql_query("SELECT * FROM `members` WHERE `username`='".$_POST['user']."' AND `password`='".$_POST['pass']."' LIMIT 1");
	if(mysql_num_rows($result)>0){
		$row=mysql_fetch_array($result);
		$_SESSION['id']=$row['id'];
		$_SESSION['user']=$row['username'];
		$_SESSION['pass']=$row['password'];
	}
	header("Location: ?action=main");
}
$row=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM `members` WHERE `id`='".$_SESSION['id']."' AND `username`='".$_SESSION['user']."' AND `password`='".$_SESSION['pass']."' LIMIT 1"));
if($row[0]==1){
echo '<div id="wrapper">
	<div id="leftmenu">
    	<h2>Main Menu</h2>
        <ul>
        	<li><a href="?action=main">Dashboard</a></li>
            <li><a href="?action=getcode">Get Image Code</a></li>
            <li><a href="?action=campaigns">Campaigns</a></li>
            <li><a href="?action=allowrefs">Allow Referers</a></li>
            <li><a href="?action=blockrefs">Block Referers</a></li>
            <li><a href="?action=blockips">Blocked IPs</a></li>
            <li><a href="?action=links">Affiliate Links</a></li>
            <li><a href="?action=images">Images</a></li>
            <li><a href="?action=logs">Traffic Logs</a></li>
        </ul>
        <h2>Controls</h2>
        <ul>
        	<li><a href="?action=logout">Logout</a></li>
        </ul>
    </div>
    <div id="content">';
switch($_REQUEST['action']){
	default:
		$hits=0;
		$blocks=0;
		$stuffs=0;
		$result=mysql_query("SELECT * FROM `linkinfo` WHERE `memberid`='".$_SESSION['id']."'");
		while($row=mysql_fetch_array($result)){
			$linkres=mysql_query("SELECT * FROM `links` WHERE `infoid`='".$row['id']."'");
			while($linkrow=mysql_fetch_array($linkres)){
				$hits+=$linkrow['hits'];
				$blocks+=$linkrow['blocks'];
				$stuffs+=$linkrow['stuffs'];
			}
		}
		echo '        <table width="100%" border="0" cellpadding="4" cellspacing="0">
        <tr><th colspan="2"><h2>Dashboard</h2></th></tr>
        <tr class="odd"><td>Hits:</td><td>'; echo number_format($hits); echo '</td></tr>
        <tr class="even"><td>Blocks:</td><td>'; echo number_format($blocks); echo '</td></tr>
        <tr class="odd"><td>Stuffs:</td><td>'; echo number_format($stuffs); echo '</td></tr>
        </table>';
		break;
	case 'getcode':
		echo '		<form action="" method="post" enctype="multipart/form-data">
		<table width="100%" border="0" cellpadding="4" cellspacing="0">
		<tr><th colspan="2"><h2>Get Image Code</h2></th></tr>
		<tr class="odd">
			<td>Campaign: </td>
			<td>
			<select id="campaign" name="campaign" onchange="makecode(\''; echo $maindomain; echo '\')"><option value="-1" selected="selected">CHOOSE...</option>';
			$result=mysql_query("SELECT * FROM `campaigns` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
			while($row=mysql_fetch_array($result)){
				echo '<option value="'.$row['tag'].'">'.$row['name'].'</option>';
			}
			echo '			</select>
			</td>
		</tr>
		<tr class="even">
			<td>Image: </td>
			<td>
			<select id="image" name="image" onchange="makecode(\''; echo $maindomain; echo '\')">';
			$result=mysql_query("SELECT * FROM `images` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
			while($row=mysql_fetch_array($result)){
				echo '<option value="'.$row['tag'].'.'.$row['type'].'">'.$row['name'].'</option>';
			}
			echo '			</select>
			</td>
		</tr>
		<tr class="odd"><td>HTML: </td><td><input type="text" id="htmlbox" size="45" onclick="this.select()" /></td></tr>
		<tr class="even"><td>BBCODE: </td><td><input type="text" id="bbcodebox" size="45" onclick="this.select()" /></td></tr>
		</table>
		</form>';
		break;
	case 'campaigns':
		if($_REQUEST['edit']!=''){
			$result=mysql_query("SELECT * FROM `campaigns` WHERE `id`='".$_REQUEST['edit']."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
			if(mysql_num_rows($result)>0){
				$row=mysql_fetch_array($result);
				
				if($_POST['save']=='yes'){
					$campaign=$_POST['campaign'];
					$name=$_POST['name'];
					$enabled=$_POST['enabled'];
					$ctr=$_POST['ctr'];
					$randomize=$_POST['randomize'];
					$logging=$_POST['logging'];
					$ref_allow_list=$_POST['allowrefs'];
					$ref_allow_name=$_POST['allowrefname'];
					$ref_allow_refs=$_POST['arefs'];
					$ref_block_list=$_POST['blockrefs'];
					$ref_block_name=$_POST['blockrefname'];
					$ref_block_refs=$_POST['brefs'];
					$ip_block_list=$_POST['blockips'];
					$ip_block_name=$_POST['blockipname'];
					$ip_block_ips=$_POST['bips'];
					$links=$_POST['link'];
					$link_delete=$_POST['deletelink'];
					$link_enabled=$_POST['linkenabled'];
					$link_name=$_POST['linkname'];
					$link_url=$_POST['linkurl'];
					if($name!=""){
						if($ref_allow_list==-1&&$ref_allow_name!=''){
							mysql_query("INSERT INTO `allowrefinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$ref_allow_name."')");
							$ref_allow_list=mysql_insert_id();
							foreach($ref_allow_refs as $var=>$val){
								if($val!=''){
									mysql_query("INSERT INTO `allowrefs`(`infoid`,`ref`,`enabled`) VALUES('".$ref_allow_list."','".$val."','1')");
								}
							}
						}
						
						if($ref_block_list==-1&&$ref_block_name!=''){
							mysql_query("INSERT INTO `blockrefinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$ref_block_name."')");
							$ref_block_list=mysql_insert_id();
							foreach($ref_block_refs as $var=>$val){
								if($val!=''){
									mysql_query("INSERT INTO `blockrefs`(`infoid`,`ref`,`enabled`) VALUES('".$ref_block_list."','".$val."','1')");
								}
							}
						}
						
						if($ip_block_list==-1&&$ip_block_name!=''){
							mysql_query("INSERT INTO `blockipinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$ip_block_name."')");
							$ip_block_list=mysql_insert_id();
							foreach($ip_block_ips as $var=>$val){
								if($val!=''){
									mysql_query("INSERT INTO `blockips`(`infoid`,`ip`) VALUES('".$ip_block_list."','".$val."')");
								}
							}
						}
						
						mysql_query("UPDATE `campaigns` SET `name`='".$name."', "
														  ."`ctr`='".$ctr."', "
														  ."`randomize`='".$randomize."', "
														  ."`logging`='".$logging."', "
														  ."`allowrefs`='".$ref_allow_list."', "
														  ."`blockrefs`='".$ref_block_list."', "
														  ."`blockips`='".$ip_block_list."', "
														  ."`enabled`='".$enabled."' "
														  ."WHERE `id`='".$campaign."' AND `memberid`='".$_SESSION['id']."'");
														  
						$campaigncount=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM `campaigns` WHERE `id`='".$campaign."' AND `memberid`='".$_SESSION['id']."'"));

						if($campaigncount[0]>0){
							foreach($links as $var=>$val){
								if(isset($link_enabled[$var])){
									mysql_query("UPDATE `links` SET `enabled`='".$link_enabled[$var]."' WHERE `id`='".$val."' AND `campaignid`='".$campaign."'");
								}else{
									if($val==-1){
										if($link_name[$var]!=''&&$link_url[$var]!=''){
											mysql_query("INSERT INTO `linkinfo`(`memberid`,`name`,`url`) VALUES('".$_SESSION['id']."','".$link_name[$var]."','".$link_url[$var]."')");
											$val=mysql_insert_id();
										}
									}
									if($val!=-1){
										mysql_query("INSERT INTO `links`(`infoid`,`campaignid`,`hits`,`enabled`) VALUES('".$val."','".$campaign."','0','1')");
									}
								}
							}
						}
						
						foreach($link_delete as $var=>$val){
							if($val!=-1){
								$result=mysql_query("SELECT * FROM `links` WHERE `id`='".$val."' LIMIT 1");
								$row=mysql_fetch_array($result);
								$result=mysql_query("SELECT * FROM `linkinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
								if(mysql_num_rows($result)>0){
									mysql_query("DELETE FROM `links` WHERE `id`='".$val."'");
								}
							}
						}
						
						header("Location: ?action=campaigns");
						exit;
					}else{
						echo ' <p>You have forgotten to fill in some fields.</p> ';
					}
				}
				echo '	            <form action="" method="post">
	            <input type="hidden" name="campaign" value="'; echo $row['id']; echo '" />
	            <table width="100%" border="0" cellpadding="4" cellspacing="0">
	            <tr><th colspan="2"><h2>Edit Campaign '; echo $row['name']; echo '</h2></th></tr>
	            <tr class="odd"><td align="left" valign="top">Name:</td>
	            <td><input type="text" name="name" value="'; echo $row['name']; echo '" /></td></tr>
	            <tr class="even"><td align="left" valign="top">Enabled:</td>
	            <td><select name="enabled"><option value="0"'; echo ($row['enabled']==0)?' selected="selected"':'';echo '>Disabled</option><option value="1"'; echo ($row['enabled']==1)?' selected="selected"':'';echo '>Enabled</option></select></td></tr>
	            <tr class="odd"><td align="left" valign="top">CTR:</td>
	            <td><select name="ctr">'; $i=0; while($i<=100){echo '<option value="'.$i.'"'.(($row['ctr']==$i)?' selected="selected"':'').'>'.$i.'%</option>';$i++;} echo '</select></td></tr>
	            <tr class="even"><td align="left" valign="top">Randomize Rotation:</td>
	            <td><select name="randomize"><option value="0"'; echo ($row['randomize']=='0')?' selected="selected"':''; echo '>No</option><option value="1"'; echo ($row['randomize']=='1')?' selected="selected"':''; echo '>Yes</option></select></td></tr>
                <tr class="odd"><td align="left" valign="top">Detailed Logging:</td>
	            <td><select name="logging"><option value="0"'; echo ($row['logging']=='0')?' selected="selected"':''; echo '>No</option><option value="1"'; echo ($row['logging']=='1')?' selected="selected"':''; echo '>Yes</option></select></td></tr>
	            <tr class="even"><td align="left" valign="top">Allow Referer List:<br>(Entering &quot;/&quot; will allow all.)</td>
	            <td>
	            <select name="allowrefs" onchange="hide(\'nalref\',this.value);"><option value="-1" selected="selected">(New List)</option>';
	            $aresult=mysql_query("SELECT * FROM `allowrefinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
	            while($arow=mysql_fetch_array($aresult)){
	            	echo '<option value="'.$arow['id'].'"'.(($arow['id']==$row['allowrefs'])?' selected="selected"':'').'>'.$arow['name'].'</option>';
	            }
	            echo '	            </select>
	            <div id="nalref" style="display:none;">
	            <p>List Name: <input type="text" name="allowrefname" /></p>
	            <button onclick="addref(\'armore\',\'arefs\');return false;">Add Another</button><div id="armore"><p><input type="text" name="arefs[]" value="/" /></p></div>
	            </div>
	            </td></tr>
	            <tr class="odd"><td align="left" valign="top">Block Referer List:<br>(Entering &quot;/&quot; will block all.)</td>
	            <td>
	            <select name="blockrefs" onchange="hide(\'nblref\',this.value);"><option value="-1" selected="selected">(New List)</option>';
	            $aresult=mysql_query("SELECT * FROM `blockrefinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
	            while($arow=mysql_fetch_array($aresult)){
	            	echo '<option value="'.$arow['id'].'"'.(($arow['id']==$row['blockrefs'])?' selected="selected"':'').'>'.$arow['name'].'</option>';
	            }
	            echo '	            </select>
	            <div id="nblref" style="display:none;">
	            <p>List Name: <input type="text" name="blockrefname" /></p>
	            <button onclick="addref(\'brmore\',\'brefs\');return false;">Add Another</button><div id="brmore"><p><input type="text" name="brefs[]" value="*none*" /></p></div>
	            </div>
	            </td></tr>
	            <tr class="even"><td align="left" valign="top">Block IP List:</td>
	            <td>
	            <select name="blockips" onchange="hide(\'nbiref\',this.value);"><option value="-1" selected="selected">(New List)</option>';
	            $aresult=mysql_query("SELECT * FROM `blockipinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
	            while($arow=mysql_fetch_array($aresult)){
	            	echo '<option value="'.$arow['id'].'"'.(($arow['id']==$row['blockips'])?' selected="selected"':'').'>'.$arow['name'].'</option>';
	            }
	            echo '	            </select>
	            <div id="nbiref" style="display:none;">
	            <p>List Name: <input type="text" name="blockipname" /></p>
	            <button onclick="addref(\'bimore\',\'bips\');return false;">Add Another</button><div id="bimore"><p><input type="text" name="bips[]" value="0.0.0.0" /></p></div>
	            </div>
	            </td></tr>
	            <tr class="odd"><td align="left" valign="top">Affiliate Links:<br>(http:// or https:// required.)</td>
	            <td>
	            <button onclick="appendfrom(\'origlink\',\'linkmore\');return false;">Add Another</button>';
	            $aresult=mysql_query("SELECT * FROM `links` WHERE `campaignid`='".$row['id']."'");
	            while($arow=mysql_fetch_array($aresult)){
	            	echo '	            	<input type="hidden" id="deletelink'; echo $arow['id']; echo '" name="deletelink[]" value="-1" />
	            	<div>
	            	<p><button onclick="this.parentNode.innerHTML=\'\';$(\'deletelink'; echo $arow['id']; echo '\').value=\''; echo $arow['id']; echo '\';">Remove</button> <input type="hidden" name="link[]" value="'; echo $arow['id']; echo '" />';
		            $bresult=mysql_query("SELECT * FROM `linkinfo` WHERE `id`='".$arow['infoid']."' LIMIT 1");
		            $brow=mysql_fetch_array($bresult);
		            echo ' <strong>'.$brow['name'].'</strong> ';
		            echo '		            <select name="linkenabled[]"><option value="0"'; echo ($arow['enabled']==0)?' selected="selected"':''; echo '>Disabled</option><option value="1"'; echo ($arow['enabled']==1)?' selected="selected"':''; echo '>Enabled</option></select>
		            <input type="hidden" name="linkname[]" value="" /> <input type="hidden" name="linkurl[]" value="" />
		            </p>
		            </div>
	            	';
	            }
	            echo '	            <div id="origlink">
	            <p><select name="link[]"><option value="-1" selected="selected">(New Link)</option>';
	            $aresult=mysql_query("SELECT * FROM `linkinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
	            while($arow=mysql_fetch_array($aresult)){
	            	echo '<option value="'.$arow['id'].'">'.$arow['name'].'</option>';
	            }
	            echo '	            </select> IF NEW: <input type="text" name="linkname[]" value="" /> <input type="text" name="linkurl[]" value="" />
	            </p></div>
	            <div id="linkmore"></div>
	            </td></tr>
	            <tr class="even"><td colspan="2"><input type="hidden" name="save" value="yes" /><input type="submit" name="submit" value="Save Campaign" /></td></tr>
	            </table>
	            </form>';
			}else{
				header("Location: ?action=campaigns");
				exit;
			}
		}elseif($_POST['submit']=='Add New'){
			if($_POST['addnew']=='yes'){
				$name=$_POST['name'];
				$ctr=$_POST['ctr'];
				$randomize=$_POST['randomize'];
				$logging=$_POST['logging'];
				$ref_allow_list=$_POST['allowrefs'];
				$ref_allow_name=$_POST['allowrefname'];
				$ref_allow_refs=$_POST['arefs'];
				$ref_block_list=$_POST['blockrefs'];
				$ref_block_name=$_POST['blockrefname'];
				$ref_block_refs=$_POST['brefs'];
				$ip_block_list=$_POST['blockips'];
				$ip_block_name=$_POST['blockipname'];
				$ip_block_ips=$_POST['bips'];
				$links=$_POST['link'];
				$link_name=$_POST['linkname'];
				$link_url=$_POST['linkurl'];
				if($name!=""){
					if($ref_allow_list==-1&&$ref_allow_name!=''){
						mysql_query("INSERT INTO `allowrefinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$ref_allow_name."')");
						$ref_allow_list=mysql_insert_id();
						foreach($ref_allow_refs as $var=>$val){
							if($val!=''){
								mysql_query("INSERT INTO `allowrefs`(`infoid`,`ref`,`enabled`) VALUES('".$ref_allow_list."','".$val."','1')");
							}
						}
					}
					
					if($ref_block_list==-1&&$ref_block_name!=''){
						mysql_query("INSERT INTO `blockrefinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$ref_block_name."')");
						$ref_block_list=mysql_insert_id();
						foreach($ref_block_refs as $var=>$val){
							if($val!=''){
								mysql_query("INSERT INTO `blockrefs`(`infoid`,`ref`,`enabled`) VALUES('".$ref_block_list."','".$val."','1')");
							}
						}
					}
					
					if($ip_block_list==-1&&$ip_block_name!=''){
						mysql_query("INSERT INTO `blockipinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$ip_block_name."')");
						$ip_block_list=mysql_insert_id();
						foreach($ip_block_ips as $var=>$val){
							if($val!=''){
								mysql_query("INSERT INTO `blockips`(`infoid`,`ip`) VALUES('".$ip_block_list."','".$val."')");
							}
						}
					}
					
					$tag=genkey('campaigns','tag');
					mysql_query("INSERT INTO `campaigns`(`memberid`,`tag`,`name`,`ctr`,`randomize`,`logging`,`allowrefs`,`blockrefs`,`blockips`,`enabled`) VALUES('".$_SESSION['id']."','".$tag."','".$name."','".$ctr."','".$randomize."','".$logging."','".$ref_allow_list."','".$ref_block_list."','".$ip_block_list."','1')");
					$campaign=mysql_insert_id();
					
					foreach($links as $var=>$val){
						if($val==-1){
							mysql_query("INSERT INTO `linkinfo`(`memberid`,`name`,`url`) VALUES('".$_SESSION['id']."','".$link_name[$var]."','".$link_url[$var]."')");
							$val=mysql_insert_id();
						}
						mysql_query("INSERT INTO `links`(`infoid`,`campaignid`,`hits`,`enabled`) VALUES('".$val."','".$campaign."','0','1')");
					}
					
					header("Location: ?action=campaigns");
					exit;
				}else{
					echo ' <p>You have forgotten to fill in some fields.</p> ';
				}
			}
			echo '            <form action="" method="post">
            <table width="100%" border="0" cellpadding="4" cellspacing="0">
            <tr><th colspan="2"><h2>Add New Campaign</h2></th></tr>
            <tr class="odd"><td align="left" valign="top">Name:</td>
            <td><input type="text" name="name" value="" /></td></tr>
            <tr class="even"><td align="left" valign="top">CTR:</td>
            <td><select name="ctr">'; $i=0; while($i<=100){echo '<option value="'.$i.'">'.$i.'%</option>';$i++;} echo '</select></td></tr>
            <tr class="odd"><td align="left" valign="top">Randomize Rotation:</td>
            <td><select name="randomize"><option value="0" selected="selected">No</option><option value="1">Yes</option></select></td></tr>
            <tr class="even"><td align="left" valign="top">Detailed Logging:</td>
            <td><select name="logging"><option value="0">No</option><option value="1" selected="selected">Yes</option></select></td></tr>
            <tr class="odd"><td align="left" valign="top">Allow Referer List:<br>(Entering &quot;/&quot; will allow all.)</td>
            <td>
            <select name="allowrefs" onchange="hide(\'nalref\',this.value);"><option value="-1" selected="selected">(New List)</option>';
            $result=mysql_query("SELECT * FROM `allowrefinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
            while($row=mysql_fetch_array($result)){
            	echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
            }
            echo '            </select>
            <div id="nalref" style="display:block;">
            <p>List Name: <input type="text" name="allowrefname" /></p>
            <button onclick="addref(\'armore\',\'arefs\');return false;">Add Another</button><div id="armore"><p><input type="text" name="arefs[]" value="/" /></p></div>
            </div>
            </td></tr>
            <tr class="even"><td align="left" valign="top">Block Referer List:<br>(Entering &quot;/&quot; will block all.)</td>
            <td>
            <select name="blockrefs" onchange="hide(\'nblref\',this.value);"><option value="-1" selected="selected">(New List)</option>';
            $result=mysql_query("SELECT * FROM `blockrefinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
            while($row=mysql_fetch_array($result)){
            	echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
            }
            echo '            </select>
            <div id="nblref" style="display:block;">
            <p>List Name: <input type="text" name="blockrefname" /></p>
            <button onclick="addref(\'brmore\',\'brefs\');return false;">Add Another</button><div id="brmore"><p><input type="text" name="brefs[]" value="*none*" /></p></div>
            </div>
            </td></tr>
            <tr class="odd"><td align="left" valign="top">Block IP List:</td>
            <td>
            <select name="blockips" onchange="hide(\'nbiref\',this.value);"><option value="-1" selected="selected">(New List)</option>';
            $result=mysql_query("SELECT * FROM `blockipinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
            while($row=mysql_fetch_array($result)){
            	echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
            }
            echo '            </select>
            <div id="nbiref" style="display:block;">
            <p>List Name: <input type="text" name="blockipname" /></p>
            <button onclick="addref(\'bimore\',\'bips\');return false;">Add Another</button><div id="bimore"><p><input type="text" name="bips[]" value="0.0.0.0" /></p></div>
            </div>
            </td></tr>
            <tr class="even"><td align="left" valign="top">Affiliate Links:<br>(http:// or https:// required.)</td>
            <td>
            <button onclick="appendfrom(\'origlink\',\'linkmore\');return false;">Add Another</button>
            <div id="origlink">
            <p><select name="link[]"><option value="-1" selected="selected">(New Link)</option>';
            $result=mysql_query("SELECT * FROM `linkinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
            while($row=mysql_fetch_array($result)){
            	echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
            }
            echo '            </select> IF NEW: <input type="text" name="linkname[]" value="New Link Name" /> <input type="text" name="linkurl[]" value="http://" />
            </p></div>
            <div id="linkmore"></div>
            </td></tr>
            <tr class="odd"><td colspan="2"><input type="hidden" name="addnew" value="yes" /><input type="submit" name="submit" value="Add New" /></td></tr>
            </table>
            </form>';
		}elseif($_POST['submit']=='Go'){
			if($_POST['with']=='delete'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `campaigns` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("DELETE FROM `campaigns` WHERE `id`='".$row['id']."'");
						mysql_query("DELETE FROM `links` WHERE `campaignid`='".$row['id']."'");
						$logres=mysql_query("SELECT * FROM `loginfo` WHERE `campaignid`='".$row['id']."'");
						while($logrow=mysql_fetch_array($logres)){
							mysql_query("DELETE FROM `logs` WHERE `infoid`='".$logrow['id']."'");
						}
						mysql_query("DELETE FROM `loginfo` WHERE `campaignid`='".$row['id']."'");
					}
				}
				header("Location: ?action=campaigns");
				exit;
			}
			if($_POST['with']=='duplicate'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `campaigns` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						$tag=genkey('campaigns','tag');
						mysql_query("INSERT INTO `campaigns`(`memberid`,`tag`,`name`,`ctr`,`randomize`,`logging`,`allowrefs`,`blockrefs`,`blockips`,`enabled`) VALUES('".$_SESSION['id']."','".$tag."','".$row['name']." Copy','".$row['ctr']."','".$row['randomize']."','".$row['logging']."','".$row['allowrefs']."','".$row['blockrefs']."','".$row['blockips']."','".$row['enabled']."')");
						$campaign=mysql_insert_id();
						$linkres=mysql_query("SELECT * FROM `links` WHERE `campaignid`='".$row['id']."'");
						while($linkrow=mysql_fetch_array($linkres)){
							mysql_query("INSERT INTO `links`(`infoid`,`campaignid`,`hits`,`enabled`) VALUES('".$linkrow['infoid']."','".$campaign."','0','".$linkrow['enabled']."')");
						}
					}
				}
				header("Location: ?action=campaigns");
				exit;
			}
		}else{
			$result=mysql_query("SELECT * FROM `campaigns` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."' ORDER BY `id` ASC");
			echo '			<form action="" method="post" enctype="multipart/form-data">
			<table width="100%" border="0" cellpadding="4" cellspacing="0">
			<tr><th colspan="6"><h2>Campaigns</h2></th></tr>
			<tr><th>&nbsp;</th><th>Name</th><th>CTR</th><th>Status</th><th>Stats</th><th>&nbsp;</th></tr>';
			$class='odd';
			while($row=mysql_fetch_array($result)){
				echo '				<tr class="'; echo $class; echo '">
				<td>'; echo (($row['memberid']==$_SESSION['id'])?'<input type="checkbox" name="select[]" value="'.$row['id'].'" />':'&nbsp;'); echo '</td>
				<td><strong>'; echo $row['name']; echo '</strong><br />Handle: '; echo $row['tag']; echo '</td>
				<td>'; echo $row['ctr']; echo '%</td>
				<td>'; echo ($row['enabled']==1)?'Enabled':'Disabled'; echo '</td>
				<td>';
				$logresult=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM `loginfo` WHERE `campaignid`='".$row['id']."'"));
				$log_uniques=$logresult[0];
				$log_stuffs=0;
				$log_hits=0;
				$log_blocks=0;
				$logresult=mysql_query("SELECT * FROM `links` WHERE `campaignid`='".$row['id']."'");
				while($logrow=mysql_fetch_array($logresult)){
					$log_stuffs+=$logrow['stuffs'];
					$log_hits+=$logrow['hits'];
					$log_blocks+=$logrow['blocks'];
				}
				echo '				Uniques: '; echo number_format($log_uniques); echo '<br />
				Stuffs: '; echo number_format($log_stuffs); echo ' <a href="javascript:void(0);" onclick="toggle(\'afls_'; echo $row['id']; echo '\');return false;">+/-</a><br />
				<blockquote id="afls_'; echo $row['id']; echo '" style="display:none;">';
				$linkres=mysql_query("SELECT * FROM `links` WHERE `campaignid`='".$row['id']."' ORDER BY `hits` DESC");
				while($linkrow=mysql_fetch_array($linkres)){
					$linkname=mysql_fetch_array(mysql_query("SELECT `name` FROM `linkinfo` WHERE `id`='".$linkrow['infoid']."' LIMIT 1"));
					echo $linkname[0].': '.number_format($linkrow['stuffs']).'<br />';
				}
				echo '                </blockquote>
                Hits: '; echo number_format($log_hits); echo '<br />
                Blocks: '; echo number_format($log_blocks); echo '<br />
				<a href="?action=logs&amp;campaign='; echo $row['id']; echo '">View Logs</a>
				</td>
				<td><button onclick="location.href=\'?action=campaigns&amp;edit='; echo $row['id']; echo '\';return false;">Edit</button></td>
				</tr>';
				if($class=='even'){$class='odd';}else{$class='even';}
			}
			echo '			<tr class="'; echo $class; echo '"><td colspan="6"><input type="submit" name="submit" value="Add New" /> With Selected: <select name="with"><option value="duplicate">Duplicate</option><option value="delete">Delete</option></select> <input type="submit" name="submit" value="Go" /></td></tr>
			</table>
			</form>';
		}
		break;
	case 'allowrefs':
		if($_REQUEST['list']!=''){
			$list=$_REQUEST['list'];
			if($_POST['submit']=='Add New'){
				$ref=$_POST['ref'];
				if($ref!=''){
					mysql_query("INSERT INTO `allowrefs`(`infoid`,`ref`,`enabled`) VALUES('".$list."','".$ref."','1')");
				}
				header("Location: ?action=allowrefs&list=".$list);
				exit;
			}elseif($_REQUEST['enable']!=''){
				$enable=$_REQUEST['enable'];
				$result=mysql_query("SELECT * FROM `allowrefs` WHERE `id`='".$enable."' LIMIT 1");
				if(mysql_num_rows($result)>0){
					$row=mysql_fetch_array($result);
					$infores=mysql_query("SELECT * FROM `allowrefinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
					if(mysql_num_rows($infores)>0){
						mysql_query("UPDATE `allowrefs` SET `enabled`='1' WHERE `id`='".$row['id']."'");
					}
				}
				header("Location: ?action=allowrefs&list=".$list);
				exit;
			}elseif($_REQUEST['disable']!=''){
				$disable=$_REQUEST['disable'];
				$result=mysql_query("SELECT * FROM `allowrefs` WHERE `id`='".$disable."' LIMIT 1");
				if(mysql_num_rows($result)>0){
					$row=mysql_fetch_array($result);
					$infores=mysql_query("SELECT * FROM `allowrefinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
					if(mysql_num_rows($infores)>0){
						mysql_query("UPDATE `allowrefs` SET `enabled`='0' WHERE `id`='".$row['id']."'");
					}
				}
				header("Location: ?action=allowrefs&list=".$list);
				exit;
			}elseif($_POST['submit']=='Go'){
				if($_POST['with']=='delete'){
					foreach($_POST['select'] as $var=>$val){
						$result=mysql_query("SELECT * FROM `allowrefs` WHERE `id`='".$val."' LIMIT 1");
						if(mysql_num_rows($result)>0){
							$row=mysql_fetch_array($result);
							$infores=mysql_query("SELECT * FROM `allowrefinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
							if(mysql_num_rows($infores)>0){
								mysql_query("DELETE FROM `allowrefs` WHERE `id`='".$row['id']."'");
							}
						}
					}
					header("Location: ?action=allowrefs&list=".$list);
					exit;
				}
				if($_POST['with']=='edit'){
					if($_POST['save']=='Save Referer Allow(s)'){
						foreach($_POST['id'] as $var=>$val){
							$result=mysql_query("SELECT * FROM `allowrefs` WHERE `id`='".$val."' LIMIT 1");
							if(mysql_num_rows($result)>0){
								$row=mysql_fetch_array($result);
								$infores=mysql_query("SELECT * FROM `allowrefinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
								if(mysql_num_rows($infores)>0){
									mysql_query("UPDATE `allowrefs` SET `ref`='".$_POST['ref'][$var]."' WHERE `id`='".$row['id']."'");
								}
							}
						}
						header("Location: ?action=allowrefs&list=".$list);
						exit;
					}
					echo '					<form action="" method="post">
					<input type="hidden" name="submit" value="Go" />
					<input type="hidden" name="with" value="edit" />
					<p><input type="submit" name="save" value="Save Referer Allow(s)" /></p>';
					if(!is_array($_POST['select'])){
						header("Location: ?action=allowrefs&list=".$list);
						exit;
					}
					foreach($_POST['select'] as $var=>$val){
						$result=mysql_query("SELECT * FROM `allowrefs` WHERE `id`='".$val."' LIMIT 1");
						$row=mysql_fetch_array($result);
						echo '						<input type="hidden" name="id[]" value="'; echo $row['id']; echo '" />
						<table width="100%" border="0" cellpadding="4" cellspacing="0">
						<tr class="even"><td align="left" valign="top">Referer:</td>
						<td><input type="text" name="ref[]" value="'; echo $row['ref']; echo '" /></td></tr>
					  </table><br />';
					}
					echo '					<p><input type="submit" name="save" value="Save Referer Allow(s)" /></p>
					</form>';
				}
			}else{
				$result=mysql_query("SELECT * FROM `allowrefs` WHERE `infoid`='".$list."' ORDER BY `id` ASC");
				$listres=mysql_query("SELECT * FROM `allowrefinfo` WHERE `id`='".$list."' AND (`memberid`='".$_SESSION['id']."' OR `memberid`='1')");
				if(mysql_num_rows($listres)>0){
					$listrow=mysql_fetch_array($listres);
					echo '					<form action="" method="post" enctype="multipart/form-data">
					<table width="100%" border="0" cellpadding="4" cellspacing="0">
					<tr><th colspan="3"><h2>Allow Referer List '; echo $listrow['name']; echo '</h2></th></tr>
					<tr><th colspan="3">Referer: <input type="text" name="ref" value="example.org" /> <input type="submit" name="submit" value="Add New" /></th></tr>
					<tr><th>&nbsp;</th><th>Referer</th><th>&nbsp;</th></tr>';
					$class='odd';
					while($row=mysql_fetch_array($result)){
						echo '						<tr class="'; echo $class; echo '">
						<td>'; echo (($listrow['memberid']==$_SESSION['id'])?'<input type="checkbox" name="select[]" value="'.$row['id'].'" />':'&nbsp;'); echo '</td>
						<td><strong>'; echo $row['ref']; echo '</strong></td>
						<td>'; echo ($listrow['memberid']==$_SESSION['id'])?(($row['enabled']==0)?'<button onclick="location.href=\'?action=allowrefs&list='.$list.'&enable='.$row['id'].'\';return false;">Enable</button>':'<button onclick="location.href=\'?action=allowrefs&list='.$list.'&disable='.$row['id'].'\';return false;">Disable</button>'):(($row['enabled']==0)?'Disabled':'Enabled'); echo '</td>
		
						</tr>';
						if($class=='even'){$class='odd';}else{$class='even';}
					}
					echo '					<tr class="'; echo $class; echo '"><td colspan="3">With Selected: <select name="with"><option value="edit">Edit</option><option value="delete">Delete</option></select> <input type="submit" name="submit" value="Go" /></td></tr>
					</table>
					</form>';
				}
			}
		}else if($_POST['submit']=='Add New'){
			if($_POST['addnew']=='yes'){
				$name=$_POST['name'];
				$refs=$_POST['refs'];
				if($name!=""&&!empty($refs)){
					mysql_query("INSERT INTO `allowrefinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$name."')");
					$infoid=mysql_insert_id();
					foreach($refs as $ref){
						if(trim($ref)!=''){
							mysql_query("INSERT INTO `allowrefs`(`infoid`,`ref`,`enabled`) VALUES('".$infoid."','".trim($ref)."','1')");
						}
					}
					header("Location: ?action=allowrefs");
					exit;
				}else{
					echo ' <p>You have forgotten to fill in some fields.</p> ';
				}
			}
			echo '            <form action="" method="post">
            <table width="100%" border="0" cellpadding="4" cellspacing="0">
            <tr><th colspan="2"><h2>Add New Allow Referer List</h2></th></tr>
            <tr class="odd"><td align="left" valign="top">Name:</td>
            <td><input type="text" name="name" value="" /></td></tr>
            <tr class="even"><td align="left" valign="top">Referers:<br />(Entering "/" will allow all referers.)</td>
            <td><button onclick="addref(\'more\',\'refs\');return false;">Add Another</button><div id="more"><p><input type="text" name="refs[]" /></p></div></td></tr>
            <tr class="odd"><td colspan="2"><input type="hidden" name="addnew" value="yes" /><input type="submit" name="submit" value="Add New" /></td></tr>
            </table>
            </form>';
		}elseif($_POST['submit']=='Go'){
			if($_POST['with']=='delete'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `allowrefinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("DELETE FROM `allowrefinfo` WHERE `id`='".$row['id']."'");
						mysql_query("DELETE FROM `allowrefs` WHERE `infoid`='".$row['id']."'");
					}
				}
				header("Location: ?action=allowrefs");
				exit;
			}
			if($_POST['with']=='duplicate'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `allowrefinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("INSERT INTO `allowrefinfo`(`memberid`,`name`) VALUES('".$row['memberid']."','".($row['name'].' Copy')."')");
						$infoid=mysql_insert_id();
						$refres=mysql_query("SELECT * FROM `allowrefs` WHERE `infoid`='".$row['id']."'");
						while($refrow=mysql_fetch_array($refres)){
							mysql_query("INSERT INTO `allowrefs`(`infoid`,`ref`,`enabled`) VALUES('".$infoid."','".$refrow['ref']."','".$refrow['enabled']."')");
						}
					}
				}
				header("Location: ?action=allowrefs");
				exit;
			}
			if($_POST['with']=='edit'){
				if($_POST['save']=='Save Allow Referer List(s)'){
					foreach($_POST['id'] as $var=>$val){
						mysql_query("UPDATE `allowrefinfo` SET `name`='".$_POST['name'][$var]."' WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."'");
					}
					header("Location: ?action=allowrefs");
					exit;
				}
				echo '                <form action="" method="post">
                <input type="hidden" name="submit" value="Go" />
                <input type="hidden" name="with" value="edit" />
                <p><input type="submit" name="save" value="Save Allow Referer List(s)" /></p>';
                if(!is_array($_POST['select'])){
                    header("Location: ?action=allowrefs");
                    exit;
                }
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `allowrefinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					$row=mysql_fetch_array($result);
					echo '                    <input type="hidden" name="id[]" value="'; echo $row['id']; echo '" />
                    <table width="100%" border="0" cellpadding="4" cellspacing="0">
                    <tr><th colspan="2"><h2>Edit Allow Referer List '; echo $row['name']; echo '</h2></th></tr>
                    <tr class="odd"><td align="left" valign="top">Name:</td>
                    <td><input type="text" name="name[]" value="'; echo $row['name']; echo '" /></td></tr>
                  </table><br />';
				}
				echo '				<p><input type="submit" name="save" value="Save Allow Referer List(s)" /></p>
				</form>';
			}
		}else{
			$result=mysql_query("SELECT * FROM `allowrefinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."' ORDER BY `id` ASC");
			echo '			<form action="" method="post" enctype="multipart/form-data">
			<table width="100%" border="0" cellpadding="4" cellspacing="0">
			<tr><th colspan="4"><h2>Allow Referer Lists</h2></th></tr>
			<tr><th>&nbsp;</th><th>Name</th><th>Entries</th><th>&nbsp;</th></tr>';
			$class='odd';
			while($row=mysql_fetch_array($result)){
				echo '				<tr class="'; echo $class; echo '">
				<td>'; echo (($row['memberid']==$_SESSION['id'])?'<input type="checkbox" name="select[]" value="'.$row['id'].'" />':'&nbsp;'); echo '</td>
				<td><strong>'; echo $row['name']; echo '</strong></td>
				<td>'; $rowcount=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM `allowrefs` WHERE `infoid`='".$row['id']."'")); 
						  echo number_format($rowcount[0]); echo '</td>
                <td><button onclick="location.href=\'?action=allowrefs&amp;list='; echo $row['id']; echo '\';return false;">View / Edit</button></td>
				</tr>';
				if($class=='even'){$class='odd';}else{$class='even';}
			}
			echo '			<tr class="'; echo $class; echo '"><td colspan="4"><input type="submit" name="submit" value="Add New" /> With Selected: <select name="with"><option value="edit">Edit</option><option value="duplicate">Duplicate</option><option value="delete">Delete</option></select> <input type="submit" name="submit" value="Go" /></td></tr>
			</table>
			</form>';
		}
		break;
	case 'blockrefs':
		if($_REQUEST['list']!=''){
			$list=$_REQUEST['list'];
			if($_POST['submit']=='Add New'){
				$ref=$_POST['ref'];
				if($ref!=''){
					mysql_query("INSERT INTO `blockrefs`(`infoid`,`ref`,`enabled`) VALUES('".$list."','".$ref."','1')");
				}
				header("Location: ?action=blockrefs&list=".$list);
				exit;
			}elseif($_REQUEST['enable']!=''){
				$enable=$_REQUEST['enable'];
				$result=mysql_query("SELECT * FROM `blockrefs` WHERE `id`='".$enable."' LIMIT 1");
				if(mysql_num_rows($result)>0){
					$row=mysql_fetch_array($result);
					$infores=mysql_query("SELECT * FROM `blockrefinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
					if(mysql_num_rows($infores)>0){
						mysql_query("UPDATE `blockrefs` SET `enabled`='1' WHERE `id`='".$row['id']."'");
					}
				}
				header("Location: ?action=blockrefs&list=".$list);
				exit;
			}elseif($_REQUEST['disable']!=''){
				$disable=$_REQUEST['disable'];
				$result=mysql_query("SELECT * FROM `blockrefs` WHERE `id`='".$disable."' LIMIT 1");
				if(mysql_num_rows($result)>0){
					$row=mysql_fetch_array($result);
					$infores=mysql_query("SELECT * FROM `blockrefinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
					if(mysql_num_rows($infores)>0){
						mysql_query("UPDATE `blockrefs` SET `enabled`='0' WHERE `id`='".$row['id']."'");
					}
				}
				header("Location: ?action=blockrefs&list=".$list);
				exit;
			}elseif($_POST['submit']=='Go'){
				if($_POST['with']=='delete'){
					foreach($_POST['select'] as $var=>$val){
						$result=mysql_query("SELECT * FROM `blockrefs` WHERE `id`='".$val."' LIMIT 1");
						if(mysql_num_rows($result)>0){
							$row=mysql_fetch_array($result);
							$infores=mysql_query("SELECT * FROM `blockrefinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
							if(mysql_num_rows($infores)>0){
								mysql_query("DELETE FROM `blockrefs` WHERE `id`='".$row['id']."'");
							}
						}
					}
					header("Location: ?action=blockrefs&list=".$list);
					exit;
				}
				if($_POST['with']=='edit'){
					if($_POST['save']=='Save Referer Block(s)'){
						foreach($_POST['id'] as $var=>$val){
							$result=mysql_query("SELECT * FROM `blockrefs` WHERE `id`='".$val."' LIMIT 1");
							if(mysql_num_rows($result)>0){
								$row=mysql_fetch_array($result);
								$infores=mysql_query("SELECT * FROM `blockrefinfo` WHERE `id`='".$row['infoid']."' AND `memberid`='".$_SESSION['id']."'");
								if(mysql_num_rows($infores)>0){
									mysql_query("UPDATE `blockrefs` SET `ref`='".$_POST['ref'][$var]."' WHERE `id`='".$row['id']."'");
								}
							}
						}
						header("Location: ?action=blockrefs&list=".$list);
						exit;
					}
					echo '					<form action="" method="post">
					<input type="hidden" name="submit" value="Go" />
					<input type="hidden" name="with" value="edit" />
					<p><input type="submit" name="save" value="Save Referer Block(s)" /></p>';
					if(!is_array($_POST['select'])){
						header("Location: ?action=blockrefs&list=".$list);
						exit;
					}
					foreach($_POST['select'] as $var=>$val){
						$result=mysql_query("SELECT * FROM `blockrefs` WHERE `id`='".$val."' LIMIT 1");
						$row=mysql_fetch_array($result);
						echo '						<input type="hidden" name="id[]" value="'; echo $row['id']; echo '" />
						<table width="100%" border="0" cellpadding="4" cellspacing="0">
						<tr class="even"><td align="left" valign="top">Referer:</td>
						<td><input type="text" name="ref[]" value="'; echo $row['ref']; echo '" /></td></tr>
					  </table><br />';
					}
					echo '					<p><input type="submit" name="save" value="Save Referer Block(s)" /></p>
					</form>';
				}
			}else{
				$result=mysql_query("SELECT * FROM `blockrefs` WHERE `infoid`='".$list."' ORDER BY `id` ASC");
				$listres=mysql_query("SELECT * FROM `blockrefinfo` WHERE `id`='".$list."' AND (`memberid`='".$_SESSION['id']."' OR `memberid`='1')");
				if(mysql_num_rows($listres)>0){
					$listrow=mysql_fetch_array($listres);
					echo '					<form action="" method="post" enctype="multipart/form-data">
					<table width="100%" border="0" cellpadding="4" cellspacing="0">
					<tr><th colspan="3"><h2>Block Referer List '; echo $listrow['name']; echo '</h2></th></tr>
					<tr><th colspan="3">Referer: <input type="text" name="ref" value="example.org" /> <input type="submit" name="submit" value="Add New" /></th></tr>
					<tr><th>&nbsp;</th><th>Referer</th><th>&nbsp;</th></tr>';
					$class='odd';
					while($row=mysql_fetch_array($result)){
						echo '						<tr class="'; echo $class; echo '">
						<td>'; echo (($listrow['memberid']==$_SESSION['id'])?'<input type="checkbox" name="select[]" value="'.$row['id'].'" />':'&nbsp;'); echo '</td>
						<td><strong>'; echo $row['ref']; echo '</strong></td>
						<td>'; echo ($listrow['memberid']==$_SESSION['id'])?(($row['enabled']==0)?'<button onclick="location.href=\'?action=blockrefs&list='.$list.'&enable='.$row['id'].'\';return false;">Enable</button>':'<button onclick="location.href=\'?action=blockrefs&list='.$list.'&disable='.$row['id'].'\';return false;">Disable</button>'):(($row['enabled']==0)?'Disabled':'Enabled'); echo '</td>
		
						</tr>';
						if($class=='even'){$class='odd';}else{$class='even';}
					}
					echo '					<tr class="'; echo $class; echo '"><td colspan="3">With Selected: <select name="with"><option value="edit">Edit</option><option value="delete">Delete</option></select> <input type="submit" name="submit" value="Go" /></td></tr>
					</table>
					</form>';
				}
			}
		}else if($_POST['submit']=='Add New'){
			if($_POST['addnew']=='yes'){
				$name=$_POST['name'];
				$refs=$_POST['refs'];
				if($name!=""&&!empty($refs)){
					mysql_query("INSERT INTO `blockrefinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$name."')");
					$infoid=mysql_insert_id();
					foreach($refs as $ref){
						if(trim($ref)!=''){
							mysql_query("INSERT INTO `blockrefs`(`infoid`,`ref`,`enabled`) VALUES('".$infoid."','".trim($ref)."','1')");
						}
					}
					header("Location: ?action=blockrefs");
					exit;
				}else{
					echo ' <p>You have forgotten to fill in some fields.</p> ';
				}
			}
			echo '            <form action="" method="post">
            <table width="100%" border="0" cellpadding="4" cellspacing="0">
            <tr><th colspan="2"><h2>Add New Block Referer List</h2></th></tr>
            <tr class="odd"><td align="left" valign="top">Name:</td>
            <td><input type="text" name="name" value="" /></td></tr>
            <tr class="even"><td align="left" valign="top">Referers:<br />(Entering "/" will block all referers.)</td>
            <td><button onclick="addref(\'more\',\'refs\');return false;">Add Another</button><div id="more"><p><input type="text" name="refs[]" /></p></div></td></tr>
            <tr class="odd"><td colspan="2"><input type="hidden" name="addnew" value="yes" /><input type="submit" name="submit" value="Add New" /></td></tr>
            </table>
            </form>';
		}elseif($_POST['submit']=='Go'){
			if($_POST['with']=='delete'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `blockrefinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("DELETE FROM `blockrefinfo` WHERE `id`='".$row['id']."'");
						mysql_query("DELETE FROM `blockrefs` WHERE `infoid`='".$row['id']."'");
					}
				}
				header("Location: ?action=blockrefs");
				exit;
			}
			if($_POST['with']=='duplicate'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `blockrefinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("INSERT INTO `blockrefinfo`(`memberid`,`name`) VALUES('".$row['memberid']."','".($row['name'].' Copy')."')");
						$infoid=mysql_insert_id();
						$refres=mysql_query("SELECT * FROM `blockrefs` WHERE `infoid`='".$row['id']."'");
						while($refrow=mysql_fetch_array($refres)){
							mysql_query("INSERT INTO `blockrefs`(`infoid`,`ref`,`enabled`) VALUES('".$infoid."','".$refrow['ref']."','".$refrow['enabled']."')");
						}
					}
				}
				header("Location: ?action=blockrefs");
				exit;
			}
			if($_POST['with']=='edit'){
				if($_POST['save']=='Save Block Referer List(s)'){
					foreach($_POST['id'] as $var=>$val){
						mysql_query("UPDATE `blockrefinfo` SET `name`='".$_POST['name'][$var]."' WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."'");
					}
					header("Location: ?action=blockrefs");
					exit;
				}
				echo '                <form action="" method="post">
                <input type="hidden" name="submit" value="Go" />
                <input type="hidden" name="with" value="edit" />
                <p><input type="submit" name="save" value="Save Block Referer List(s)" /></p>';
                if(!is_array($_POST['select'])){
                    header("Location: ?action=blockrefs");
                    exit;
                }
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `blockrefinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					$row=mysql_fetch_array($result);
					echo '                    <input type="hidden" name="id[]" value="'; echo $row['id']; echo '" />
                    <table width="100%" border="0" cellpadding="4" cellspacing="0">
                    <tr><th colspan="2"><h2>Edit Block Referer List '; echo $row['name']; echo '</h2></th></tr>
                    <tr class="odd"><td align="left" valign="top">Name:</td>
                    <td><input type="text" name="name[]" value="'; echo $row['name']; echo '" /></td></tr>
                  </table><br />';
				}
				echo '				<p><input type="submit" name="save" value="Save Block Referer List(s)" /></p>
				</form>';
			}
		}else{
			$result=mysql_query("SELECT * FROM `blockrefinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."' ORDER BY `id` ASC");
			echo '			<form action="" method="post" enctype="multipart/form-data">
			<table width="100%" border="0" cellpadding="4" cellspacing="0">
			<tr><th colspan="4"><h2>Block Referer Lists</h2></th></tr>
			<tr><th>&nbsp;</th><th>Name</th><th>Entries</th><th>&nbsp;</th></tr>';
			$class='odd';
			while($row=mysql_fetch_array($result)){
				echo '				<tr class="'; echo $class; echo '">
				<td>'; echo (($row['memberid']==$_SESSION['id'])?'<input type="checkbox" name="select[]" value="'.$row['id'].'" />':'&nbsp;'); echo '</td>
				<td><strong>'; echo $row['name']; echo '</strong></td>
				<td>'; $rowcount=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM `blockrefs` WHERE `infoid`='".$row['id']."'")); 
						  echo number_format($rowcount[0]); echo '</td>
                <td><button onclick="location.href=\'?action=blockrefs&amp;list='; echo $row['id']; echo '\';return false;">View / Edit</button></td>
				</tr>';
				if($class=='even'){$class='odd';}else{$class='even';}
			}
			echo '			<tr class="'; echo $class; echo '"><td colspan="4"><input type="submit" name="submit" value="Add New" /> With Selected: <select name="with"><option value="edit">Edit</option><option value="duplicate">Duplicate</option><option value="delete">Delete</option></select> <input type="submit" name="submit" value="Go" /></td></tr>
			</table>
			</form>';
		}
		break;
	case 'blockips':
		if($_REQUEST['view']!=''){
			$result=mysql_query("SELECT * FROM `blockipinfo` WHERE `id`='".$_REQUEST['view']."' AND `memberid`='".$_SESSION['id']."'");
			if(mysql_num_rows($result)>0){
				$row=mysql_fetch_array($result);
				echo '                <table width="100%" border="0" cellpadding="4" cellspacing="0">
                <tr><th><h2>Viewing Block IP List '; echo $row['name']; echo '</h2></th></tr>';
				$iplist='';
				$result=mysql_query("SELECT * FROM `blockips` WHERE `infoid`='".$row['id']."'");
				while($row=mysql_fetch_array($result)){
					$iplist.=$row['ip']."\n";
				}
				echo '                <tr class="odd"><td><textarea cols="50" rows="25" readonly="readonly">'; echo $iplist; echo '</textarea></td></tr>
                </table>';
			}else{
				header("Location: ?action=blockips");
				exit;
			}
		}else if($_POST['submit']=='Add New'){
			if($_POST['addnew']=='yes'){
				$name=$_POST['name'];
				$ips=$_POST['ips'];
				if($name!=""&&$ips!=""){
					mysql_query("INSERT INTO `blockipinfo`(`memberid`,`name`) VALUES('".$_SESSION['id']."','".$name."')");
					$infoid=mysql_insert_id();
					$ips=explode("\n",$ips);
					foreach($ips as $ip){
						if(trim($ip)!=''){
							mysql_query("INSERT INTO `blockips`(`infoid`,`ip`) VALUES('".$infoid."','".trim($ip)."')");
						}
					}
					header("Location: ?action=blockips");
					exit;
				}else{
					echo ' <p>You have forgotten to fill in some fields.</p> ';
				}
			}
			echo '            <form action="" method="post">
            <table width="100%" border="0" cellpadding="4" cellspacing="0">
            <tr><th colspan="2"><h2>Add New Block IP List</h2></th></tr>
            <tr class="odd"><td align="left" valign="top">Name:</td>
            <td><input type="text" name="name" value="" /></td></tr>
            <tr class="even"><td align="left" valign="top">IP Addresses:<br />(One Per Line)</td>
            <td><textarea name="ips" cols="60" rows="10"></textarea></td></tr>
            <tr class="odd"><td colspan="2"><input type="hidden" name="addnew" value="yes" /><input type="submit" name="submit" value="Add New" /></td></tr>
            </table>
            </form>';
		}elseif($_POST['submit']=='Go'){
			if($_POST['with']=='delete'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `blockipinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("DELETE FROM `blockipinfo` WHERE `id`='".$row['id']."'");
						mysql_query("DELETE FROM `blockips` WHERE `infoid`='".$row['id']."'");
					}
				}
				header("Location: ?action=blockips");
				exit;
			}
			if($_POST['with']=='duplicate'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `blockipinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("INSERT INTO `blockipinfo`(`memberid`,`name`) VALUES('".$row['memberid']."','".($row['name'].' Copy')."')");
						$infoid=mysql_insert_id();
						$ipres=mysql_query("SELECT * FROM `blockips` WHERE `infoid`='".$row['id']."'");
						while($iprow=mysql_fetch_array($ipres)){
							mysql_query("INSERT INTO `blockips`(`infoid`,`ip`) VALUES('".$infoid."','".$iprow['ip']."')");
						}
					}
				}
				header("Location: ?action=blockips");
				exit;
			}
			if($_POST['with']=='edit'){
				if($_POST['save']=='Save Block IP List(s)'){
					foreach($_POST['id'] as $var=>$val){
						mysql_query("UPDATE `blockipinfo` SET `name`='".$_POST['name'][$var]."' WHERE `id`='".$val."'");
						mysql_query("DELETE FROM `blockips` WHERE `infoid`='".$val."'");
						$ips=explode("\n",$_POST['ips'][$var]);
						foreach($ips as $ip){
							if(trim($ip)!=''){
								mysql_query("INSERT INTO `blockips`(`infoid`,`ip`) VALUES('".$val."','".trim($ip)."')");
							}
						}
					}
					header("Location: ?action=blockips");
					exit;
				}
				echo '                <form action="" method="post">
                <input type="hidden" name="submit" value="Go" />
                <input type="hidden" name="with" value="edit" />
                <p><input type="submit" name="save" value="Save Block IP List(s)" /></p>';
                if(!is_array($_POST['select'])){
                    header("Location: ?action=blockips");
                    exit;
                }
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `blockipinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					$row=mysql_fetch_array($result);
					$iplist='';
					$ipres=mysql_query("SELECT * FROM `blockips` WHERE `infoid`='".$row['id']."'");
					while($iprow=mysql_fetch_array($ipres)){
						$iplist.=$iprow['ip']."\n";
					}
					echo '                    <input type="hidden" name="id[]" value="'; echo $row['id']; echo '" />
                    <table width="100%" border="0" cellpadding="4" cellspacing="0">
                    <tr><th colspan="2"><h2>Edit Block IP List '; echo $row['name']; echo '</h2></th></tr>
                    <tr class="odd"><td align="left" valign="top">Name:</td>
                    <td><input type="text" name="name[]" value="'; echo $row['name']; echo '" /></td></tr>
                    <tr class="even"><td align="left" valign="top">IP Addresses:<br />(One Per Line)</td>
                    <td><textarea name="ips[]" cols="60" rows="10">'; echo $iplist; echo '</textarea></td></tr>
                  </table><br />';
				}
				echo '				<p><input type="submit" name="save" value="Save Block IP List(s)" /></p>
				</form>';
			}
		}else{
			$result=mysql_query("SELECT * FROM `blockipinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."' ORDER BY `id` ASC");
			echo '			<form action="" method="post" enctype="multipart/form-data">
			<table width="100%" border="0" cellpadding="4" cellspacing="0">
			<tr><th colspan="4"><h2>Block IP Lists</h2></th></tr>
			<tr><th>&nbsp;</th><th>Name</th><th>IP Addresses</th><th>&nbsp;</th></tr>';
			$class='odd';
			while($row=mysql_fetch_array($result)){
				echo '				<tr class="'; echo $class; echo '">
				<td>'; echo (($row['memberid']==$_SESSION['id'])?'<input type="checkbox" name="select[]" value="'.$row['id'].'" />':'&nbsp;'); echo '</td>
				<td><strong>'; echo $row['name']; echo '</strong></td>
				<td>'; $rowcount=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM `blockips` WHERE `infoid`='".$row['id']."'")); 
						  echo number_format($rowcount[0]); echo '</td>
                <td><button onclick="location.href=\'?action=blockips&amp;view='; echo $row['id']; echo '\';return false;">View List</button></td>
				</tr>';
				if($class=='even'){$class='odd';}else{$class='even';}
			}
			echo '			<tr class="'; echo $class; echo '"><td colspan="4"><input type="submit" name="submit" value="Add New" /> With Selected: <select name="with"><option value="edit">Edit</option><option value="duplicate">Duplicate</option><option value="delete">Delete</option></select> <input type="submit" name="submit" value="Go" /></td></tr>
			</table>
			</form>';
		}
		break;
	case 'links':
		if($_POST['submit']=='Add New'){
			$name=$_POST['name'];
			$url=$_POST['url'];
			if($name!=""&&$url!=""){
				mysql_query("INSERT INTO `linkinfo`(`memberid`,`name`,`url`) VALUES('".$_SESSION['id']."','".$name."','".$url."')");
			}
			header("Location: ?action=links");
			exit;
		}elseif($_POST['submit']=='Go'){
			if($_POST['with']=='delete'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `linkinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("DELETE FROM `linkinfo` WHERE `id`='".$row['id']."'");
						mysql_query("DELETE FROM `links` WHERE `infoid`='".$row['id']."'");
					}
				}
				header("Location: ?action=links");
				exit;
			}
			if($_POST['with']=='duplicate'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `linkinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("INSERT INTO `linkinfo`(`memberid`,`name`,`url`) VALUES('".$row['memberid']."','".($row['name'].' Copy')."','".$row['url']."')");
					}
				}
				header("Location: ?action=links");
				exit;
			}
			if($_POST['with']=='edit'){
				if($_POST['save']=='Save Link(s)'){
					foreach($_POST['id'] as $var=>$val){
						mysql_query("UPDATE `linkinfo` SET `name`='".$_POST['name'][$var]."', `url`='".$_POST['url'][$var]."' WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."'");
					}
					header("Location: ?action=links");
					exit;
				}
				echo '                <form action="" method="post">
                <input type="hidden" name="submit" value="Go" />
                <input type="hidden" name="with" value="edit" />
                <p><input type="submit" name="save" value="Save Link(s)" /></p>';
                if(!is_array($_POST['select'])){
                    header("Location: ?action=links");
                    exit;
                }
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `linkinfo` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					$row=mysql_fetch_array($result);
					echo '                    <input type="hidden" name="id[]" value="'; echo $row['id']; echo '" />
                    <table width="100%" border="0" cellpadding="4" cellspacing="0">
                    <tr><th colspan="2"><h2>Edit Link '; echo $row['name']; echo '</h2></th></tr>
                    <tr class="odd"><td align="left" valign="top">Name:</td>
                    <td><input type="text" name="name[]" value="'; echo $row['name']; echo '" /></td></tr>
                    <tr class="even"><td align="left" valign="top">URL:</td>
                    <td><input type="text" name="url[]" value="'; echo $row['url']; echo '" /></td></tr>
                  </table><br />';
				}
				echo '				<p><input type="submit" name="save" value="Save Link(s)" /></p>
				</form>';
			}
		}else{
			$result=mysql_query("SELECT * FROM `linkinfo` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."' ORDER BY `id` ASC");
			echo '			<form action="" method="post" enctype="multipart/form-data">
			<table width="100%" border="0" cellpadding="4" cellspacing="0">
			<tr><th colspan="6"><h2>Affiliate Links</h2></th></tr>
			<tr><th colspan="6">Name: <input type="text" name="name" /> URL: <input type="text" name="url" value="http://" /> <input type="submit" name="submit" value="Add New" /></th></tr>
			<tr><th>&nbsp;</th><th>Name</th><th>URL</th><th>Hits</th><th>Blocks</th><th>Stuffs</th></tr>';
			$class='odd';
			while($row=mysql_fetch_array($result)){
				$hitcount=0;
				$blockcount=0;
				$stuffcount=0;
				$linkres=mysql_query("SELECT * FROM `links` WHERE `infoid`='".$row['id']."'");
				while($linkrow=mysql_fetch_array($linkres)){
					$hitcount+=$linkrow['hits'];
					$blockcount+=$linkrow['blocks'];
					$stuffcount+=$linkrow['stuffs'];
				}
				echo '				<tr class="'; echo $class; echo '">
				<td>'; echo (($row['memberid']==$_SESSION['id'])?'<input type="checkbox" name="select[]" value="'.$row['id'].'" />':'&nbsp;'); echo '</td>
				<td><strong>'; echo $row['name']; echo '</strong></td>
				<td>'; echo $row['url']; echo '</td>
                <td>'; echo $hitcount; echo '</td>
                <td>'; echo $blockcount; echo '</td>
                <td>'; echo $stuffcount; echo '</td>
				</tr>';
				if($class=='even'){$class='odd';}else{$class='even';}
			}
			echo '			<tr class="'; echo $class; echo '"><td colspan="6">With Selected: <select name="with"><option value="edit">Edit</option><option value="duplicate">Duplicate</option><option value="delete">Delete</option></select> <input type="submit" name="submit" value="Go" /></td></tr>
			</table>
			</form>';
		}
		break;
	case 'images':
		if($_POST['submit']=='Go'){
			if($_POST['with']=='delete'){
				foreach($_POST['select'] as $var=>$val){
					$result=mysql_query("SELECT * FROM `images` WHERE `id`='".$val."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
					if(mysql_num_rows($result)>0){
						$row=mysql_fetch_array($result);
						mysql_query("DELETE FROM `images` WHERE `id`='".$row['id']."'");
						unlink('./files/'.$row['tag'].'.'.$row['type']);
					}
				}
				header("Location: ?action=images");
				exit;
			}
		}
		if($_FILES){
			$image=$_FILES['image'];
			$tag=genkey('images','tag');
			$name=$image['name'];
			$type=strtolower(array_pop(explode('.',$name)));
			if($type=='jpg'||$type=='jpeg'||$type=='png'||$type=='gif'){
				if(move_uploaded_file($image['tmp_name'],'./files/'.$tag.'.'.$type)){
					mysql_query("INSERT INTO `images`(`memberid`,`tag`,`name`,`type`) VALUES('".$_SESSION['id']."','".$tag."','".$name."','".$type."')");
				}
			}
			header("Location: ?action=images");
			exit;
		}
		$result=mysql_query("SELECT * FROM `images` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."' ORDER BY `id` ASC");
		echo '        <form action="" method="post" enctype="multipart/form-data">
        <table width="100%" border="0" cellpadding="4" cellspacing="0">
        <tr><th colspan="4"><h2>Images</h2></th></tr>
        <tr><th colspan="4"> <input type="file" name="image" /> <input type="submit" name="submit" value="Upload Image" /> (JPG, GIF, PNG)</th></tr>
        <tr><th>&nbsp;</th><th>Preview</th><th>Name</th><th>&nbsp;</th></tr>';
		$class='odd';
		while($row=mysql_fetch_array($result)){
			echo '            <tr class="'; echo $class; echo '">
            <td>'; echo (($row['memberid']==$_SESSION['id'])?'<input type="checkbox" name="select[]" value="'.$row['id'].'" />':'&nbsp;'); echo '</td>
            <td><img src="preview.php?img='; echo $row['tag'].'.'.$row['type']; echo '" alt="'; echo $row['name']; echo '" /></td>
            <td><strong>'; echo $row['name']; echo '</strong><br />Handle: '; echo $row['tag']; echo '</td>
            <td><button onclick="window.open(\''; echo 'files/'.$row['tag'].'.'.$row['type']; echo '\');return false;">Preview</button></td>
            </tr>';
			if($class=='even'){$class='odd';}else{$class='even';}
		}
		echo '        <tr class="'; echo $class; echo '"><td colspan="4">With Selected: <select name="with"><option value="delete">Delete</option></select> <input type="submit" name="submit" value="Go" /></td></tr>
        </table>
        </form>';
		break;
	case 'logs':
		if($_REQUEST['log_type']!=''){
			$_SESSION['log_type']=$_REQUEST['log_type'];
		}
		if(!isset($_SESSION['log_type'])){
			$_SESSION['log_type']='today';
		}
		switch($_SESSION['log_type']){
			default:
				$_SESSION['log_start']=strtotime("today 12:00 am");
				$_SESSION['log_stop']=mktime();
				break;
			case 'yesterday':
				$_SESSION['log_start']=strtotime("yesterday 12:00 am");
				$_SESSION['log_stop']=strtotime("today 12:00 am");
				break;
			case 'last7days':
				$_SESSION['log_start']=strtotime("-1 week");
				$_SESSION['log_stop']=mktime();
				break;
			case 'thismonth':
				$_SESSION['log_start']=strtotime("month");
				$_SESSION['log_stop']=strtotime("next month");
				break;
			case 'lastmonth':
				$_SESSION['log_start']=strtotime("-2 months");
				$_SESSION['log_stop']=strtotime("month");
				break;
			case 'alltime':
				$_SESSION['log_start']=0;
				$_SESSION['log_stop']=mktime();
				break;
		}
			
		if($_REQUEST['ip']!=''){
			
			$result=mysql_query("SELECT * FROM `loginfo` WHERE `ip`='".$_REQUEST['ip']."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
			$row=mysql_fetch_array($result);
			echo '			<table width="100%" border="0" cellpadding="4" cellspacing="0">
	        <tr><th colspan="3"><h2>Traffic Log for '; echo $row['ip']; echo '</h2><p>';
            if(preg_match("/[a-zA-Z]/",$row['useragent'])||$row['useragent']==''){
                echo $row['useragent'];
            }else{
                $useragent=mysql_fetch_array(mysql_query("SELECT * FROM `useragents` WHERE `id`='".$row['useragent']."' LIMIT 1"));
                echo $useragent['useragent'];
            }
            echo '            </p></th></tr>
	        <tr><th colspan="3">
	        <form action="" method="POST">
	        <select name="log_type">
	        <option value="today"'; echo ($_SESSION['log_type']=='today')?' selected="selected"':'';echo '>Today</option>
	        <option value="yesterday"'; echo ($_SESSION['log_type']=='yesterday')?' selected="selected"':'';echo '>Yesterday</option>
	        <option value="last7days"'; echo ($_SESSION['log_type']=='last7days')?' selected="selected"':'';echo '>Last 7 Days</option>
	        <option value="thismonth"'; echo ($_SESSION['log_type']=='thismonth')?' selected="selected"':'';echo '>This Month</option>
	        <option value="lastmonth"'; echo ($_SESSION['log_type']=='lastmonth')?' selected="selected"':'';echo '>Last Month</option>
	        <option value="alltime"'; echo ($_SESSION['log_type']=='alltime')?' selected="selected"':'';echo '>All Time</option>
	        </select>
	        <button>Go</button>
	        </form>
	        </th></tr>
	        <tr><th>Time</th><th>Referer</th><th>Action</th></tr>';
	        $result=mysql_query("SELECT * FROM `logs` WHERE `infoid`='".$row['id']."' AND `timestamp`>'".$_SESSION['log_start']."' AND `timestamp`<'".$_SESSION['log_stop']."' ORDER BY `id` DESC");
			$class='odd';
			while($row=mysql_fetch_array($result)){
				echo '	            <tr class="'; echo $class; echo '">
	            <td>'; echo date("M/j/y g:i a",$row['timestamp']); echo '</td>
	            <td>'; echo $row['referer']; echo '</td>
	            <td>'; echo actionrep($row['action']); echo '</td>
	            </tr>';
				if($class=='even'){$class='odd';}else{$class='even';}
			}
			echo '	        </table>';
		}else{
			$extra='';
			if($_REQUEST['campaign']!=''&&$_REQUEST['campaign']!='all'){
				$extra.=" AND `campaignid`='".$_REQUEST['campaign']."'";
			}
			$result=mysql_query("SELECT * FROM `loginfo` WHERE `memberid`='".$_SESSION['id']."' AND `timestamp`>'".$_SESSION['log_start']."' AND `timestamp`<'".$_SESSION['log_stop']."'".$extra." ORDER BY `id` DESC");
			echo '			<table width="100%" border="0" cellpadding="4" cellspacing="0">
	        <tr><th colspan="6"><h2>Traffic Logs</h2></th></tr>
	        <tr><th colspan="6">
	        <form action="" method="POST">
	        <select name="log_type">
	        <option value="today"'; echo ($_SESSION['log_type']=='today')?' selected="selected"':'';echo '>Today</option>
	        <option value="yesterday"'; echo ($_SESSION['log_type']=='yesterday')?' selected="selected"':'';echo '>Yesterday</option>
	        <option value="last7days"'; echo ($_SESSION['log_type']=='last7days')?' selected="selected"':'';echo '>Last 7 Days</option>
	        <option value="thismonth"'; echo ($_SESSION['log_type']=='thismonth')?' selected="selected"':'';echo '>This Month</option>
	        <option value="lastmonth"'; echo ($_SESSION['log_type']=='lastmonth')?' selected="selected"':'';echo '>Last Month</option>
	        <option value="alltime"'; echo ($_SESSION['log_type']=='alltime')?' selected="selected"':'';echo '>All Time</option>
	        </select>
	        <button>Go</button>
	        </form>
	        <form action="" method="GET">
	     
	        <input type="hidden" name="action" value="logs" />
	        
	        <select name="campaign">
	        <option value="all">All Campaigns</option>';
			$r=mysql_query("SELECT * FROM `campaigns` WHERE `memberid`='1' OR `memberid`='".$_SESSION['id']."'");
			while($sub=mysql_fetch_array($r)){
				echo '<option value="'.$sub['id'].'"'.(($sub['id']==$_REQUEST['campaign'])?' selected="selected"':'').'>'.$sub['name'].'</option>';
			}
			echo '	        </select>
	        
	        <button onclick="return true;">Go</button>
	        </form>
	        </th></tr>
            <tr><th colspan="6">';
			$perpage=500;
			if(mysql_num_rows($result)>$perpage){
				$extravar=isset($_REQUEST['campaign'])?'&campaign='.$_REQUEST['campaign']:'';
				$page=(isset($_REQUEST['page']))?$_REQUEST['page']:1;
				$start=($page-1)*$perpage;
				$totalitems=mysql_num_rows($result);
				$pages=1+($totalitems/$perpage);
				echo '<p>Displaying '.number_format($start).' to '.number_format($start+$perpage).' of '.number_format($totalitems).'</p>';
				echo '<p>';
				if($page>1){
					echo '<button onclick="location.href=\'?action=logs'.$extravar.'&page=1\'">&laquo; first</button>';
					echo '<button onclick="location.href=\'?action=logs'.$extravar.'&page='.($page-1).'\'">&lsaquo; previous</button>';
					if($page>9){
						echo '&nbsp;&nbsp;...&nbsp;&nbsp;';
					}
				}
				$i=1;
				$n=1;
				while($i<$pages){
					if((abs($page-$i)<5&&$n<10)||($page<=9&&$n<10)||($page>$pages-10&&$i>$pages-10&&$n<10)){
						if($i==$page){
							echo '&nbsp;&nbsp;<strong>'.number_format($i).'</strong>&nbsp;&nbsp;';
						}else{
							echo '<button onclick="location.href=\'?action=logs'.$extravar.'&page='.($i).'\'">'.number_format($i).'</button>';
						}
						$n++;
					}
					$i++;
				}
				if($page<floor($pages)){
					if($page+5<floor($pages)){
						echo '&nbsp;&nbsp;...&nbsp;&nbsp;';
					}
					echo '<button onclick="location.href=\'?action=logs'.$extravar.'&page='.($page+1).'\'">next &rsaquo;</button>';
					echo '<button onclick="location.href=\'?action=logs'.$extravar.'&page='.floor($pages).'\'">last &raquo;</button>';
				}
				echo '&nbsp;&nbsp;Quick pick: <select onchange="location.href=\'?action=logs'.$extravar.'&page=\'+this.value">';
				$i=1;
				while($i<$pages){
					echo '<option value="'.$i.'"'.(($i==$page)?' selected="selected"':'').'>'.$i.'</option>';
					$i++;
				}
				echo '</select>';
				echo '</p>';
				$result=mysql_query("SELECT * FROM `loginfo` WHERE `memberid`='".$_SESSION['id']."' AND `timestamp`>'".$_SESSION['log_start']."' AND `timestamp`<'".$_SESSION['log_stop']."'".$extra." ORDER BY `id` DESC LIMIT $start,$perpage");
			}
			echo '            </th>
            </tr>
	        <tr><th>Campaign</th><th>IP Address</th><th>Whois/Map</th><th>Time</th><th>User Agent</th><th>Actions</th></tr>';
			$class='odd';
			while($row=mysql_fetch_array($result)){
				echo '	            <tr class="'; echo $class; echo '">
	            <td>';
	            $r=mysql_query("SELECT * FROM `campaigns` WHERE `id`='".$row['campaignid']."' AND `memberid`='".$_SESSION['id']."' LIMIT 1");
	            $sub=mysql_fetch_array($r);
	            echo '<a href="?action=logs&amp;campaign='.$sub['id'].'">'.$sub['name'].'</a>';
	            echo '</td>
	            <td><a href="?action=logs&amp;ip='; echo $row['ip']; echo '">'; echo $row['ip']; echo '</a></td>
                <td><a href="http://whois.sc/'; echo $row['ip']; echo '" target="_blank">Whois</a>/<a href="http://private.dnsstuff.com/tools/ipall.ch?ip='; echo $row['ip']; echo '" target="_blank">Map</a></td>
	            <td>'; echo date("M/j/y g:i a",$row['timestamp']); echo '</td>
	            <td>';
				if(preg_match("/[a-zA-Z]/",$row['useragent'])||$row['useragent']==''){
					echo $row['useragent'];
				}else{
					$useragent=mysql_fetch_array(mysql_query("SELECT * FROM `useragents` WHERE `id`='".$row['useragent']."' LIMIT 1"));
					echo $useragent['useragent'];
				}
				echo '</td>
	            <td>';
	            $r=mysql_query("SELECT COUNT(*) FROM `logs` WHERE `infoid`='".$row['id']."'");
	            $sub=mysql_fetch_array($r);
	            echo $sub[0];
	            echo '</td>
	            </tr>';
				if($class=='even'){$class='odd';}else{$class='even';}
			}
			echo '	        </table>';
		}
		break;
	case 'logout':
		$_SESSION['user']='';
		$_SESSION['pass']='';
		header("Location: ?action=main");
		break;
}
echo '  </div>
    
    <hr class="clear" />
    
</div>';
}else{
echo '<div id="wrapper">
	<div id="leftmenu">
    	<h2>Main Menu</h2>
        <ul>
        	<li><a href="?">Login</a></li>
        </ul>
    </div>
    <div id="content">
   	  <form action="" method="post">
		<table border="0" cellpadding="4" cellspacing="0">
        <tr><th colspan="2"><h2>Login</h2></th></tr>
        <tr class="odd"><td align="left">Username:</td>
        <td><input type="text" name="user" /></td></tr>
        <tr class="even"><td align="left">Password:</td>
        <td><input type="password" name="pass" /></td></tr>
        <tr class="odd"><td colspan="2"><input type="hidden" name="action" value="login" /><input type="submit" name="submit" value="Login" /></td></tr>
		</table>
      </form>
    </div>
    
    <hr class="clear" />
    
</div>';
}
echo '
</body>
</html>';
ob_end_flush();
?>