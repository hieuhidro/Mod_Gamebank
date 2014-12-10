<?php

/*=======================================================================*\
|| ##################################################################### ||
|| # vBCredits II Deluxe 2.0.0 - `credits_process.php`				   # ||
|| # ------------------------------------------------------------------# ||
|| # Author: Darkwaltz4 {blackwaltz4@msn.com}						   # ||
|| # Copyright � 2009 - 2010 John Jakubowski. All Rights Reserved.	   # ||
|| # This file may not be redistributed in whole or significant part.  # ||
|| # -----------------vBulletin IS NOT FREE SOFTWARE!------------------# ||
|| #			 Support: http://www.dragonbyte-tech.com/			   # ||
|| ##################################################################### ||
\*=======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
if (!is_object($vbulletin->db))
{
	exit;
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$done = 0;
global $usercache;
$db =& $vbulletin->db;
$transactions = $db->query_read("SELECT t.* FROM " . TABLE_PREFIX . "credits_transaction AS t LEFT JOIN " . TABLE_PREFIX . "credits_event AS e ON (t.eventid = e.eventid) WHERE t.status = 0 AND t.timestamp <= (" . TIMENOW . " - IF(ISNULL(e.eventid), 0, e.delay)) ORDER BY t.transactionid ASC LIMIT " . $vbulletin->options['credits_cron_queue']);

while ($trans = $db->fetch_array($transactions))
{	//process each by core
	if ($notmyself = ($vbulletin->userinfo['userid'] != $trans['userid']))
	{	//load fresh cache each time
		unset($usercache[$trans['userid']], $userinfo);
		$userinfo = fetch_userinfo($trans['userid']);
		cache_permissions($userinfo, false);
	}	//myself should be up to date
	else $userinfo =& $vbulletin->userinfo;

	VBCREDITS::process($trans, $userinfo);
	if ($notmyself) VBCREDITS::update($userinfo); //update myself on shutdown
	$done++;
}

VBCREDITS::shutdown();
$db->free_result($transactions);
log_cron_action(vb_number_format($done, 0), $nextitem, 1);