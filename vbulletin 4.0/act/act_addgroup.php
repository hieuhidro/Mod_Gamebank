<?php

global $vbulletin,$db,$newugid;


if($newugid != null && $vbulletin->userinfo['userid']){
	
	$userid = $vbulletin->userinfo['userid'];
	
	$sql = "INSERT INTO `usergroupleader`(`userid`, `usergroupid`) VALUES ($userid,$newugid)";
	
	$db->query_first($sql);
}
?>