<?php
//######################## REQUIRE BACK-END  ################# Delete when completed
require_once ('/global.php');
require_once (DIR . '/includes/adminfunctions.php');
require_once (DIR . '/includes/class_bbcode.php');
global $vbulletin, $vb;
//#############################################################

//INSERT INTO `payment_history`(`userid`, `serial`, `cardnumber`, `cardvalue`, `status`) VALUES (1,'123123','123123','sfsdfsdf',1223)
//ALTER TABLE  `payment_history` CHANGE  `datetime`  `datetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP

/**--
 *-- Database: `user database`
 *--
 *-- --------------------------------------------------------
 *--
 *-- Table structure for table `payment_history`
 *-- Query insert table...
 */
$sql = "CREATE TABLE IF NOT EXISTS 'payment_history' (
       'historyid' int(11) NOT NULL AUTO_INCREMENT,
       'userid' int(11) NOT NULL,
       'datetime' timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       'serial' text COLLATE utf8_unicode_ci NOT NULL,
       'cardnumber' text COLLATE utf8_unicode_ci NOT NULL,
       'cardvalue' text COLLATE utf8_unicode_ci NOT NULL,
       'status' int(11) NOT NULL DEFAULT '10000',
       PRIMARY KEY ('historyid'),
       KEY 'userid' ('userid')
       ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Save history user exchange' AUTO_INCREMENT=3 ;";

$db -> query_first($sql);

/**
 * Query insert column into table user of database
 */
$sql_clm = "ALTER TABLE  'user' ADD 'payment' INT NOT NULL DEFAULT '0'";
$db -> query_first($sql_clm);
?>