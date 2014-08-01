<?php
/**
 *
 * HOOK EVENT, UPDATE USER COINS WHEN USER VISIT FORUM
 *
 */
session_start();
global $vbulletin, $forumid, $gamebank_column, $user_detail;
/**
 * Get option from forum
 */
if ($forumid != -1) {
	if ($vbulletin -> userinfo['userid']) {
		if (!isset($_SESSION['forumid'])) {
			$_SESSION['forumid'] = $forumid;
		}
		//echo $_SESSION['forumid'];
		if ($_SESSION['forumid'] != $forumid) {
			$value_option = $vbulletin -> options['payment_price'];
			if ($value_option != "") {
				$array_frm = explode(';', $value_option);
				$value_item = array();
				$checked = false;
				foreach ($array_frm as $key => $value) {
					$value_item = explode(":", $value);
					if($value_item[0]){					
						if ($value_item[0] == $forumid) {
							$checked = true;
							break;
						}
					}
				}
				if ($checked == true) {
					global $db;
					include '/payment/lib/class.gamebank.php';
					$coins = $user_detail['coins'] - $value_item[1] + 0;
					$user_coins = new GameBank($user_detail['userid'], $coins);
					if ($coins < 0) {
						exit('<script>alert("You aren\'t have coins to view this thread"); window.top.location.replace(\'forum.php\');</script>');
					} else {
						$vbulletin -> userinfo['username'] = str_replace($user_detail['coins'], $coins, $vbulletin -> userinfo['username']);
						$user_coins -> UpdatePayment();
					}
				}
			}
		}
		$_SESSION['forumid'] = $forumid;
	}
}
?>