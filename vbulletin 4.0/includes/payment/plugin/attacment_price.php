<?php
/**
 * HOOK EVENT, UPDATE USER COINS WHEN USER DOWNLOAD FILE
 */
global $vbulletin, $gamebank_column, $attachmentinfo;

if ($attachmentinfo) {
	if ($vbulletin -> options['payment_downloadprice'] == "") {
		global $db;
		/**
		 * Get column name from vbulletin option Your Payment (string)
		 */
		$gamebank_column = $vbulletin -> options['payment_column'];
		/**
		 * Get user detail from $vbulletin (userid, username, coins);
		 */
		$user_detail = array('userid' => $vbulletin -> userinfo['userid'], 'username' => $vbulletin -> userinfo['username'], 'coins' => $vbulletin -> userinfo[$gamebank_column]);

		include '/payment/lib/class.gamebank.php';
		$coins = $user_detail['coins'] - $vbulletin -> options['payment_downloadprice'];
		$user_coins = new GameBank($user_detail['userid'], $coins);
		if ($coins < 0) {
			exit('<script>alert("You aren\'t have coins to view this thread");</script>');
		} else {
			$vbulletin -> userinfo['username'] = str_replace($user_detail['coins'], $coins, $vbulletin -> userinfo['username']);
			$user_coins -> UpdatePayment();
		}
	}
};
?>