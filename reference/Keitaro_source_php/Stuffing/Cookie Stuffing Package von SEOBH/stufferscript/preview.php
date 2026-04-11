<?php
function getidealsize($sw,$sh){
	$ideal=132;
	if($sw>$ideal||$sh>$ideal){
		if($sw>$sh){
			$perc=$ideal/$sw;
		}else{
			$perc=$ideal/$sh;
		}
		$nw=round($sw*$perc);
		$nh=round($sh*$perc);
	}else{
		$nw=$sw;
		$nh=$sh;
	}
	return(array($nw,$nh));
}

$img='./files/'.$_REQUEST['img'];
if(file_exists($img)){
	header("Content-type: image/jpeg");
	list($sw,$sh)=getimagesize($img);
	list($nw,$nh)=getidealsize($sw,$sh);
	if($sw<=132&&$sh<=132){
		readfile($img);
	}else{
		$ic=file_get_contents($img);
		$src=imagecreatefromstring($ic);
		$im=imagecreatetruecolor($nw,$nh);
		imagecopyresampled($im,$src,0,0,0,0,$nw,$nh,$sw,$sh);
		imagejpeg($im,NULL,75);
		imagedestroy($im);
		imagedestroy($src);
	}
	exit;
}
?>