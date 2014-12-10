<?php

/*=======================================================================*\
|| ##################################################################### ||
|| # vBCredits II Deluxe 2.0.0 - `credits_installer.php`			   # ||
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
if (empty($vbulletin)) exit;

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (!file_exists(DIR . '/dbtech/credits/credits_core.php') OR !file_exists(DIR . '/includes/xml/bitfield_credits.xml') OR !file_exists(DIR . '/includes/xml/cpnav_credits.xml') OR !file_exists(DIR . '/includes/xml/hooks_credits.xml'))
{
	print_dots_stop();
	print_cp_message('Please upload all of the files that came with vBCredits before installing or upgrading!');
}

require_once(DIR . '/dbtech/credits/credits_core.php');

define('CP_REDIRECT', ( $doinstall ? 'index.php?loc=' . urlencode('credits_admin.php?do=events') : 'plugin.php?do=product' ));
define('DISABLE_PRODUCT_REDIRECT', true);

if ($doinstall)
{
	$db->query_write("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "credits_action` (
	  `actionid` varchar(255) NOT NULL,
	  `title` varchar(255) NOT NULL,
	  `description` text NOT NULL,
	  `multiplier` varchar(255) NOT NULL,
	  `parent` varchar(255) NOT NULL,
	  `category` varchar(255) NOT NULL,
	  `global` tinyint(1) unsigned NOT NULL,
	  `revert` tinyint(1) unsigned NOT NULL,
	  `cancel` tinyint(1) unsigned NOT NULL,
	  `rebuild` tinyint(1) unsigned NOT NULL,
	  `currency` tinyint(1) unsigned NOT NULL,
	  `referformat` varchar(255) NOT NULL,
	  PRIMARY KEY  (`actionid`)
	) ENGINE=MyISAM");

	$db->query_write("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "credits_currency` (
	  `currencyid` int(10) unsigned NOT NULL auto_increment,
	  `title` varchar(255) NOT NULL,
	  `description` text NOT NULL,
	  `displayorder` int(10) unsigned NOT NULL default '0',
	  `table` varchar(255) NOT NULL,
	  `useprefix` tinyint(1) unsigned NOT NULL default '1',
	  `column` varchar(255) NOT NULL,
	  `userid` tinyint(1) unsigned NOT NULL default '1',
	  `usercol` varchar(255) NOT NULL,
	  `decimals` tinyint(2) unsigned NOT NULL default '0',
	  `negative` tinyint(1) unsigned NOT NULL default '0',
	  `privacy` tinyint(1) unsigned NOT NULL default '0',
	  `blacklist` tinyint(1) unsigned NOT NULL default '0',
	  `maxtime` int(10) unsigned default NULL,
	  `earnmax` double default NULL,
	  `value` double NOT NULL default '1',
	  `inbound` tinyint(1) unsigned NOT NULL default '1',
	  `outbound` tinyint(1) unsigned NOT NULL default '1',
	  PRIMARY KEY  (`currencyid`)
	) ENGINE=MyISAM");

	$db->query_write("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "credits_conversion` (
	  `conversionid` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `currencyid` int(10) unsigned NOT NULL DEFAULT '0',
	  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
	  `minimum` double NOT NULL DEFAULT '0',
	  `tiered` tinyint(1) unsigned NOT NULL DEFAULT '0',
	  `cost` text NOT NULL,
	  PRIMARY KEY (`conversionid`)
	) ENGINE=MyISAM");

	$db->query_write("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "credits_payment` (
	  `paymentid` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `hash` varchar(32) NOT NULL DEFAULT '',
	  `amount` double unsigned NOT NULL DEFAULT '0',
	  `currencyid` int(10) unsigned NOT NULL DEFAULT '0',
	  `price` double unsigned NOT NULL DEFAULT '0',
	  `currency` char(3) NOT NULL,
	  `fromuserid` int(10) unsigned NOT NULL DEFAULT '0',
	  `completed` smallint(6) NOT NULL DEFAULT '0',
	  `touserid` int(10) unsigned NOT NULL DEFAULT '0',
	  `note` text NOT NULL,
	  PRIMARY KEY (`paymentid`),
	  KEY `hash` (`hash`)
	) ENGINE=MyISAM");

	$db->query_write("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "credits_redemption` (
	  `redemptionid` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `title` varchar(255) NOT NULL,
	  `description` text NOT NULL,
	  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
	  `startdate` int(10) unsigned NOT NULL DEFAULT '0',
	  `enddate` int(10) unsigned NOT NULL DEFAULT '0',
	  `usergroups` text NOT NULL,
	  `currencyid` int(10) unsigned NOT NULL DEFAULT '0',
	  `amount` double NOT NULL DEFAULT '0',
	  `maxtimes` int(10) unsigned NOT NULL DEFAULT '0',
	  `maxusers` int(10) unsigned NOT NULL DEFAULT '0',
	  `codes` text NOT NULL,
	  `redirect` varchar(255) NOT NULL,
	  PRIMARY KEY (`redemptionid`)
	) ENGINE=MyISAM");

	$db->query_write("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "credits_display` (
	  `displayid` varchar(255) NOT NULL,
	  `title` varchar(255) NOT NULL,
	  `description` text NOT NULL,
	  `enabled` tinyint(1) unsigned NOT NULL default '1',
	  `currencies` text NOT NULL,
	  `combine` varchar(255) NOT NULL,
	  `combined` text NOT NULL,
	  `comdec` int(10) unsigned NOT NULL default '0',
	  `main_template` varchar(255) NOT NULL,
	  `row_template` varchar(255) NOT NULL,
	  `hookname` varchar(255) NOT NULL,
	  `customhook` varchar(255) NOT NULL,
	  `wrap_main` tinyint(1) unsigned NOT NULL default '1',
	  `showpages` varchar(255) NOT NULL,
	  PRIMARY KEY  (`displayid`)
	) ENGINE=MyISAM");

	$db->query_write("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "credits_event` (
	  `eventid` int(10) unsigned NOT NULL auto_increment,
	  `currencyid` int(10) unsigned NOT NULL,
	  `actionid` varchar(255) NOT NULL,
	  `usergroups` text NOT NULL,
	  `forums` text NOT NULL,
	  `enabled` tinyint(1) unsigned NOT NULL default '1',
	  `moderate` tinyint(1) unsigned NOT NULL default '0',
	  `main_add` double NOT NULL,
	  `main_sub` double NOT NULL,
	  `mult_add` double NOT NULL,
	  `mult_sub` double NOT NULL,
	  `delay` int(10) unsigned NOT NULL default '0',
	  `frequency` int(10) unsigned NOT NULL default '1',
	  `maxtime` int(10) unsigned default NULL,
	  `applymax` int(10) unsigned default NULL,
	  `upperrand` varchar(255) NOT NULL default '0',
	  `multmin` double default NULL,
	  `multmax` double default NULL,
	  `minaction` tinyint(1) unsigned NOT NULL default '0',
	  `owner` tinyint(1) unsigned default NULL,
	  `curtarget` tinyint(1) unsigned NOT NULL default '0',
	  `alert` tinyint(1) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`eventid`)
	) ENGINE=MyISAM");

	$db->query_write("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "credits_transaction` (
	  `transactionid` bigint(20) unsigned NOT NULL auto_increment,
	  `eventid` int(10) unsigned NOT NULL default '0',
	  `actionid` varchar(255) NOT NULL,
	  `userid` int(10) unsigned NOT NULL default '0',
	  `timestamp` int(10) unsigned NOT NULL default '0',
	  `amount` double NOT NULL default '0',
	  `status` tinyint(1) unsigned NOT NULL default '0',
	  `referenceid` varchar(255) default NULL,
	  `forumid` int(10) unsigned NOT NULL default '0',
	  `ownerid` int(10) unsigned NOT NULL default '0',
	  `multiplier` double NOT NULL default '0',
	  `currencyid` int(10) unsigned NOT NULL default '0',
	  `negate` tinyint(1) unsigned NOT NULL default '0',
	  `message` text NOT NULL,
	  PRIMARY KEY  (`transactionid`),
	  KEY `timestamp` (`timestamp`,`userid`,`status`),
	  KEY `userid` (`userid`),
	  KEY `userid_stats` (`userid`,`eventid`,`status`,`negate`,`timestamp`)
	) ENGINE=InnoDB");

	$insarray = array();
	$currency = $db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "credits_currency");

	if (empty($currency['total']))
	{
		$negs = array('normal' => 2, 'display' => 1, 'correct' => 0);
		$pubs = array(0, 1, 2, 'none' => 0, 'some' => 1, 'all' => 2);
		$decimals = intval($vbulletin->options['credits_decimals']);
		$negative = ( array_key_exists($vbulletin->options['credits_neghandle'], $negs) ? $negs[$vbulletin->options['credits_neghandle']] : 2 );
		$privacy = ( array_key_exists($vbulletin->options['credits_public'], $pubs) ? $pubs[$vbulletin->options['credits_public']] : 2 );

		$insarray[] = "('" . $db->escape_string( $vbphrase['credits'] ? $vbphrase['credits'] : 'Credits' ) . "', 'Classic vBCredits points field.', 10, 'user', 1, 'credits', 1, 'userid', $decimals, $negative, $privacy)";
		if (array_key_exists('credits_saved', $vbulletin->userinfo)) $insarray[] = "('" . $db->escape_string( $vbphrase['credits_savings'] ? $vbphrase['credits_savings'] : 'Savings' ) . "', 'Classic vBCredits points field.', 20, 'user', 1, 'credits_saved', 1, 'userid', $decimals, $negative, $privacy)";
		$db->query_write("INSERT INTO " . TABLE_PREFIX . "credits_currency (title, description, displayorder, `table`, useprefix, `column`, userid, usercol, decimals, negative, privacy) VALUES " . implode(', ', $insarray));

		tablesync(TABLE_PREFIX . 'user', array(
			'credits' => "DOUBLE NOT NULL DEFAULT '0'",
			'creditspermissions' => 'TEXT'
		));

		$has = array('get' => array_key_exists('credits_canget', $vbulletin->userinfo), 'earn' => array_key_exists('credits_canearn', $vbulletin->userinfo), 'spend' => array_key_exists('credits_canspend', $vbulletin->userinfo));
		if ($which = ( $has['get'] ? 'credits_canget' : ( $has['earn'] ? 'credits_canearn' : '' ) )) $db->query_write("UPDATE " . TABLE_PREFIX . "user SET creditspermissions = CASE " . ( $has['spend'] ? "WHEN $which = 0 AND credits_canspend = 0 THEN 'a:2:{i:0;a:1:{i:0;i:1;}i:1;a:1:{i:0;i:1;}}' WHEN credits_canspend = 0 THEN 'a:2:{i:0;a:0:{}i:1;a:1:{i:0;i:1;}}' " : '' ) . "WHEN $which = 0 THEN 'a:2:{i:0;a:1:{i:0;i:1;}i:1;a:0:{}}' ELSE 'a:2:{i:0;a:0:{}i:1;a:0:{}}' END");
		tablesync(TABLE_PREFIX . 'user', array('credits_canget', 'credits_canearn', 'credits_canspend'));
	}

	tablesync(TABLE_PREFIX . 'credits_action', array(
		'outbound' => "tinyint(1) unsigned NOT NULL default '1'",
		'inbound' => "tinyint(1) unsigned NOT NULL default '1'",
		'value' => "double NOT NULL default '1'"
	));

	tablesync(TABLE_PREFIX . 'credits_action', array(
		'rebuild' => 'tinyint(1) unsigned NOT NULL',
		'parent' => 'varchar(255) NOT NULL'
	));

	tablesync(TABLE_PREFIX . 'credits_event', array(
		'owner' => 'tinyint(1) unsigned default NULL',
		'alert' => "tinyint(1) unsigned NOT NULL default '0'",
		'curtarget' => "tinyint(1) unsigned NOT NULL default '0'"
	));

	tablesync(TABLE_PREFIX . 'usergroup', array(
		'credits_charge' => "INT( 10 ) UNSIGNED NOT NULL DEFAULT '1'",
		'creditspermissions' => "INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'"
	));

	tablesync(TABLE_PREFIX . 'credits_transaction', array('ownerid' => "int(10) unsigned NOT NULL default '0'"));
	tablesync(TABLE_PREFIX . 'administrator', array('creditspermissions' => "INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'"));
	$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "datastore` CHANGE `title` `title` CHAR( 50 ) NOT NULL");
	tablesync(TABLE_PREFIX . 'post', array('chargecontent' => 'TEXT NOT NULL'));

	if ($installed_version === null)
	{
		$usergroups = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "usergroup");

		while ($usergroup = $db->fetch_array($usergroups))
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "usergroup SET
					creditspermissions = " . ( (intval($usergroup['adminpermissions']) & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'] OR intval($usergroup['adminpermissions']) & $vbulletin->bf_ugp_adminpermissions['ismoderator']) ? 28 : 0 ) . "
				WHERE usergroupid = $usergroup[usergroupid]
			");
		}
	}
	//clean out pending transactions that will just waste time
	$db->query_write("DELETE FROM t USING `" . TABLE_PREFIX . "credits_transaction` AS t LEFT JOIN " . TABLE_PREFIX . "credits_event AS e ON (t.actionid = e.actionid) WHERE t.status = 0 AND t.eventid = 0 AND e.actionid IS NULL");

	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_display` (`displayid`, `title`, `description`, `main_template`, `row_template`, `hookname`, `customhook`, `wrap_main`, `showpages`, `currencies`, `combined`) VALUES ('credits', 'Currency', 'Corner of the transaction page, for the currently logged in user.', '', 'credits_display_credits_row', 'credits_display_currencies', '', 1, 'credits', 'a:0:{}', 'a:0:{}')");

	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_display` (`displayid`, `title`, `description`, `main_template`, `row_template`, `hookname`, `customhook`, `wrap_main`, `showpages`, `currencies`, `combined`) VALUES ('postbit', 'Postbit', 'Listed beneath avatar and post count on threads and other places.', '', 'credits_display_postbit_row', 'postbit_userinfo_right_after_posts', '', 1, '', 'a:0:{}', 'a:0:{}')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_display` (`displayid`, `title`, `description`, `main_template`, `row_template`, `hookname`, `customhook`, `wrap_main`, `showpages`, `currencies`, `combined`) VALUES ('memberlist', 'Member List', 'Sortable columns on the member list.', 'credits_display_memberlist', 'credits_display_memberlist_row', 'memberlist_resultsbit', 'memberlist_resultsbit', 0, 'memberlist', 'a:0:{}', 'a:0:{}')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_display` (`displayid`, `title`, `description`, `main_template`, `row_template`, `hookname`, `customhook`, `wrap_main`, `showpages`, `currencies`, `combined`) VALUES ('navbar', 'Navbar', 'Dropdown list in the header in the Forum tab, for the currently logged in user.', 'credits_display_navbar', 'credits_display_navbar_row', 'navbar_end', '', 1, '', 'a:0:{}', 'a:0:{}')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_display` (`displayid`, `title`, `description`, `main_template`, `row_template`, `hookname`, `customhook`, `wrap_main`, `showpages`, `currencies`, `combined`) VALUES ('profile', 'Profile', 'Section on the about me tab on every user''s profile.', 'credits_display_profile', 'credits_display_profile_row', 'profile_stats_pregeneral', '', 1, 'member', 'a:0:{}', 'a:0:{}')");

	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('donate', 'Donate', 'Transferring currency to another user.', '', '', 'accounts', 1, 0, 1, 0, 1, 'member.php?u=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('transfer', 'Transfer', 'Moving your own currency from one form to another.', '', '', 'accounts', 1, 0, 1, 0, 1, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('adjust', 'Adjust', 'Manipulating the currency of someone else.', '', '', 'accounts', 1, 1, 1, 0, 1, 'member.php?u=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('content', 'Content', 'Charging other users to view your marked content.', '', 'Post', 'discuss', 0, 0, 1, 0, 1, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('redeem', 'Redeem', 'Using a redemption code or visiting a special link.', '', 'Account', 'accounts', 1, 0, 0, 0, 1, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('interest', 'Interest', 'Growing the value of your currency over time.', 'Currency|Currency', '', 'accounts', 1, 0, 0, 0, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('purchase', 'Purchase', 'Buying currency for real money through any configured payment processor.', '', 'Account', 'accounts', 1, 1, 0, 0, 1, '')");

	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('referral', 'Referral', 'Someone else indicating you as their referrer.', '', '', 'network', 1, 0, 0, 1, 0, 'member.php?u=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('thread', 'Thread', 'Creating a forum topic.', 'Size', '', 'discuss', 0, 1, 1, 1, 0, 'showthread.php?t=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('induction', 'Welcome', 'Entering a usergroup. Events should be limited to once.', '', '', 'network', 1, 0, 0, 0, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('birthday', 'Birthday', 'Awarded on midnight according to profile. Events should be limited to annual. Multiplier is age.', 'Years|Year', '', 'time', 1, 0, 0, 1, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('paycheck', 'Paycheck', 'Occurs at regular intervals.', '', '', 'time', 1, 0, 0, 1, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('activity', 'Activity', 'Occurs with some participation at regular intervals. Inactivity triggers negation.', '', '', 'time', 1, 1, 0, 0, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('reply', 'Reply', 'Someone else posting in your thread.', '', '', 'discuss', 0, 1, 0, 1, 0, 'showpost.php?p=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('post', 'Post', 'Adding a post to a thread.', 'Size', 'Thread', 'discuss', 0, 1, 1, 1, 0, 'showpost.php?p=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('update', 'Respond', 'Posting in a group discussion.', 'Size', 'Group Discussion', 'group', 1, 1, 1, 1, 0, 'group.php?gmid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('poll', 'Poll', 'Creating a poll.', 'Options|Option', '', 'opinion', 0, 1, 1, 1, 0, 'poll.php?do=showresults&pollid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('vote', 'Vote', 'Choosing poll options. Multiplier is the number selected.', 'Options|Option', 'Poll', 'opinion', 0, 1, 1, 1, 0, 'poll.php?do=showresults&pollid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('rate', 'Rate', 'Rating a thread. Multiplier is rating.', 'Stars|Star', 'Thread', 'opinion', 0, 1, 1, 0, 0, 'showthread.php?t=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('infraction', 'Infraction', 'Getting an infraction.', 'Points|Point', '', 'behave', 1, 1, 0, 1, 0, 'infraction.php?do=view&infractionid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('reputation', 'Reputation', 'Getting reputation. Negative reputation triggers negation. Multiplier is the absolute value.', 'Points|Point', '', 'behave', 0, 1, 0, 1, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('approval', 'Give Reputation', 'Applying reputation to someone else.', 'Points|Point', '', 'behave', 0, 0, 1, 1, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('picture', 'Picture', 'Applying a profile image. Not the same as avatar.', '', '', 'share', 1, 1, 1, 0, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('view', 'Viewed', 'Someone else viewing your thread. Events should be limited.', '', '', 'discuss', 0, 0, 0, 0, 0, 'showthread.php?t=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('evaluate', 'Rated', 'Someone else rating your thread. Multiplier is rating.', 'Stars|Star', '', 'opinion', 0, 1, 0, 0, 0, 'showthread.php?t=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('visit', 'Profile', 'Someone else viewing your profile. Events should be limited.', '', '', 'network', 1, 0, 0, 0, 0, 'member.php?u=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('describe', 'Fields', 'Filling out a custom profile field. Multiplier is the number of custom fields. Events should specify a minimum.', 'Fields|Field', '', 'share', 1, 1, 1, 0, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('calendar', 'Calendar', 'Posting a calendar event.', 'Size', '', 'share', 1, 1, 1, 1, 0, 'calendar.php?do=getinfo&e=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('message', 'PM', 'Sending a private message.', 'Size', '', 'network', 1, 0, 1, 1, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('upload', 'Upload', 'Uploading a new attachment. Multiplier is filesize.', 'Bytes|Byte', '', 'share', 0, 1, 1, 1, 0, 'attachment.php?attachmentid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('reference', 'Refer', 'Indicating your referrer. Occurs with referral action.', '', '', 'network', 1, 0, 0, 0, 0, '')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('wall', 'Messaged', 'Someone else giving you a visitor message on your profile.', '', '', 'network', 1, 1, 0, 1, 0, 'member.php?u=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('revival', 'Revive', 'Posting in a dormant thread.', 'Days|Day', 'Thread', 'discuss', 0, 0, 1, 0, 0, 'showthread.php?t=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('punish', 'Give Infraction', 'Applying an infraction to someone else.', 'Points|Point', '', 'behave', 1, 1, 0, 1, 0, 'infraction.php?do=view&infractionid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('tag', 'Tag', 'Applying a descriptive label to a thread.', '', 'Thread', 'discuss', 0, 1, 1, 1, 0, 'tags.php?tag=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('download', 'Download', 'Downloading a forum attachment. Multiplier is filesize.', 'Bytes|Byte', 'Attachment', 'share', 0, 0, 1, 0, 0, 'attachment.php?attachmentid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('sticky', 'Sticky', 'When one of your threads becomes sticky.', '', '', 'discuss', 0, 1, 0, 0, 0, 'showthread.php?t=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('group', 'Group', 'Creating a social group.', '', '', 'group', 1, 1, 1, 1, 0, 'group.php?groupid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('member', 'Member', 'Gaining a new member to your social group.', '', '', 'group', 1, 1, 0, 1, 0, 'group.php?groupid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('join', 'Joined', 'Becoming a member to a social group started by someone else.', '', '', 'group', 1, 1, 1, 1, 0, 'group.php?groupid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('album', 'Album', 'Adding an album image. Multiplier is dimensions.', 'Pixels|Pixel', '', 'share', 1, 1, 1, 1, 0, 'attachment.php?attachmentid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('friend', 'Friend', 'Becoming a friend of someone. Both of you perform this action.', '', '', 'network', 1, 1, 1, 0, 0, 'member.php?u=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('visitor', 'Message', 'Posting a visitor message on a profile.', 'Size', 'Profile', 'network', 1, 1, 1, 1, 0, 'member.php?u=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('last', 'Latest', 'Having the latest post in a thread. Negates very often.', '', 'Thread', 'discuss', 0, 1, 0, 0, 0, 'showthread.php?t=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('discuss', 'Discuss', 'Starting a social group discussion.', 'Size', 'Social Group', 'group', 1, 1, 1, 1, 0, 'group.php?discussionid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('read', 'View', 'Viewing a thread. Charged events will lock out guests.', '', 'Thread', 'discuss', 0, 0, 1, 0, 0, 'showthread.php?t=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('popular', 'Responded', 'Someone else posting in your group discussion.', '', '', 'group', 1, 1, 0, 1, 0, 'group.php?gmid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('interesting', 'Discussed', 'Someone else starting a discussion in your social group.', '', '', 'group', 1, 1, 0, 1, 0, 'group.php?discussionid=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('profile', 'Visit', 'Viewing a profile. Earning events should be limited. Charged events will lock out guests.', '', 'Profile', 'network', 1, 0, 1, 0, 0, 'member.php?u=')");
	$db->query_write("REPLACE INTO `" . TABLE_PREFIX . "credits_action` (`actionid`, `title`, `description`, `multiplier`, `parent`, `category`, `global`, `revert`, `cancel`, `rebuild`, `currency`, `referformat`) VALUES ('downloaded', 'Downloaded', 'Someone else downloading your attachment. Multiplier is filesize.', 'Bytes|Byte', '', 'share', 0, 0, 0, 0, 0, 'attachment.php?attachmentid=')");

	$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_transaction WHERE actionid IN ('add', 'topic', 'respond')");
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_action WHERE actionid IN ('add', 'topic', 'respond')");
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_event WHERE actionid IN ('add', 'topic', 'respond')");

	$events = $db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "credits_event");
	$currency = $db->query_first("SELECT currencyid FROM " . TABLE_PREFIX . "credits_currency ORDER BY currencyid LIMIT 1");
	if (!$events['total'] AND file_exists($import = DIR . '/dbtech/credits/vbcredits-default-events.xml')) $count = vbcredits_import($import, $currency['currencyid'], false);

	if (array_key_exists('credits_pp', $vbulletin->products))
	{	//reverse and uninstall old addon
		$converses = array();

		$merge = array(
			'usd' => 0, 'gbp' => 0, 'eur' => 0, 'aud' => 0, 'cad' => 0,//used as a base for cost array
			'tax' => $vbulletin->options['credits_addtax'], 'shipping' => 0, 'ccbillsubid' => '', 'twocheckout_prodid' => ''
		);

		foreach (preg_split('/[\n\r]+/', $vbulletin->options['credits_convert']) AS $line)
		{	//break up into array
			$bits = preg_split('/\s+/', $line);
			if (sizeof($bits) == 3) $converses[doubleval($bits[0])][substr(strtolower($bits[2]), 0, 3)] = doubleval($bits[1]);
		}
		foreach ($converses AS $minimum => $prices)
		{	//build into conversions
			$db->query_write("INSERT INTO " . TABLE_PREFIX . "credits_conversion (currencyid, enabled, minimum, tiered, cost) VALUES (" . $currency['currencyid'] . ", 1, $minimum, 0, '" . $db->escape_string(serialize(array_merge($merge, $prices))) . "')");
		}

		tablesync(TABLE_PREFIX . 'subscription', array('creditbonus'));
		tablesync(TABLE_PREFIX . 'paymentinfo', array('credits'));
		@unlink(DIR . '/credits_payments.php');
		delete_product('credits_pp');
	}
	if (array_key_exists('credits_charge', $vbulletin->products))
	{	//reverse and uninstall old addon
		$hidden = $db->query_read("SELECT hideid, content, postid FROM " . TABLE_PREFIX . "credits_hidden ORDER BY hideid DESC");
		tablesync(TABLE_PREFIX . 'usergroup', array('credits_hide_discount', 'credits_hide_seeall', 'credits_hide_max'));

		while ($hide = $db->fetch_array($hidden))
		{	//replace with php because mysql doesnt do it case insensitive
			$post = $db->query_first("SELECT pagetext FROM " . TABLE_PREFIX . "post WHERE postid = " . $hide['postid']);
			$db->query_write("UPDATE " . TABLE_PREFIX . "post SET pagetext = '" . $db->escape_string(preg_replace('/]' . $hide['hideid'] . '\[\/charge]/i', ']' . $hide['content'] . '[/charge]', $post['pagetext'])) . "' WHERE postid = " . $hide['postid']);
		}

		$db->query_write("DROP TABLE `" . TABLE_PREFIX . "credits_hidden`");
		@unlink(DIR . '/includes/xml/bitfield_credits_charge.xml');
		delete_product('credits_charge');
	}
	if ($vbulletin->products['tms'])
	{	//remove legacy template edit
		require_once(DIR . '/includes/adminfunctions_templateedits.php');
		uninstall_templateedits('credits');
	}
	//old files from 1.4 and 2.x
	@unlink(DIR . '/clientscript/credits_ajax_postadd.js');
	@unlink(DIR . '/includes/functions_credits.php');
	@unlink(DIR . '/includes/functions_pixelfx.php');//yuck
	@unlink(DIR . '/includes/cron/credits_daily.php');
	@unlink(DIR . '/includes/cron/credits_process.php');
	@unlink(DIR . '/plugins/credits_installer.php');
	@unlink(DIR . '/plugins/credits_plugins.php');
	@unlink(DIR . '/plugins/credits_core.php');
	@unlink(DIR . '/plugins/credits_installer.php');
	@unlink(DIR . '/plugins/credits_vbulletin.php');
	@unlink(DIR . '/plugins/credits_vbulletin_installer.php');
	@unlink(DIR . '/dbtech/credits/credits_vbulletin_installer.php');
//convert old addon settings from 1.x?
	$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` IN ('credits_currencies', 'credits_actions', 'credits_displays', 'credits_events')");
	if (array_key_exists('credits_vbulletin', $vbulletin->products)) delete_product('credits_vbulletin');//merged
	build_forum_permissions();
	vbcredits_cache();
}
else
{	//most of this is legacy
	$currencies = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_currency");

	while ($currency = $db->fetch_array($currencies))
	{	//remove any added currency columns
		if (!$currency['blacklist']) tablesync(( $currency['useprefix'] ? TABLE_PREFIX : '' ) . $currency['table'], array($currency['column']));
	}

	$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "credits_action`");
	$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "credits_currency`");
	$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "credits_display`");
	$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "credits_event`");
	$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "credits_transaction`");
	$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "credits_transactions`");
	$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "credits_bank`");
	$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "credits_lottery`");

	tablesync(TABLE_PREFIX . 'post', array('chargecontent'));
	tablesync(TABLE_PREFIX . 'thread', array('awardedcredits'));
	tablesync(TABLE_PREFIX . 'administrator', array('creditspermissions'));
	$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` LIKE 'credits_%' OR `title` IN ('vbcredits', 'max_allowed_packet')");
	tablesync(TABLE_PREFIX . 'user', array('credits_numrefs', 'credits_canspend', 'credits_score', 'credits_interest', 'credits_statement', 'credits_alert', 'credits_canget', 'credits_canearn'));
	tablesync(TABLE_PREFIX . 'usergroup', array('credit_canadd', 'credit_cananon', 'credit_canview', 'credit_cansee', 'credit_unpost', 'credit_unthread', 'credit_repneg', 'credit_induction', 'credit_post', 'credit_thread', 'credit_referral', 'credit_active', 'credit_postsize', 'credit_refbonus', 'credit_birthday', 'credit_repgive', 'credit_threadrate', 'credit_ownthreadpost', 'credit_pollmake', 'credit_pollvote', 'creditpermissions', 'creditspermissions', 'credit_addevent', 'credit_addprofpic', 'credit_attachment', 'credit_sendpm', 'credit_ownthreadview', 'credit_ownprofileview', 'credit_ownthreadrate', 'credit_ownthreadreply', 'credit_infraction', 'credit_donmaxuser', 'credit_pastotpost', 'credit_pastotuser', 'credit_pasawuser', 'credit_pasawthread', 'credit_saveint', 'credit_maxsave', 'credit_loanint', 'credit_maxloan', 'credit_totloan', 'credit_maxtickets', 'credit_lotdiscount', 'credit_profile', 'credit_goodgroup', 'credit_badgroup', 'credit_midgroup', 'credit_paycheck', 'credit_reputation', 'credit_login', 'credit_perchar', 'credit_daily', 'credit_reppos', 'credits_charge'));
//kill my own files too?
	build_forum_permissions();
}

function tablesync($table, $columns = array())
{	//sync table with passed definition
	global $db;
	$cols = $query = array();
	$existing = $db->query_read("SHOW COLUMNS FROM $table");
	while ($exist = $db->fetch_array($existing)) $cols[] = $exist['Field'];

	foreach ($columns AS $col => $def)
	{	//assemble changes
		if ($drop = is_numeric($col)) $col = $def;
		if (in_array($col, $cols)) $query[] = ( $drop ? "DROP `$col`" : "CHANGE `$col` `$col` $def" );
		else if (!$drop) $query[] = "ADD `$col` $def";
	}
	//alter table if any change
	if ($query) $db->query_write("ALTER TABLE `$table` " . implode(', ', $query));
}
?>