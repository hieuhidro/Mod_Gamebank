<?php  /**
 *
 * Widget save image ...
 * Template hook save stick thread ....
 * Hook thread event ....
 * ($hook = vBulletinHook::fetch_hook('threadmanage_update')) ? eval($hook) : false;
 * <vb:if condition="$show['removeoption']">
 * echo $threadinfo['postusername'];
 * echo $threadinfo['sticky'];
 * class class_dm_threadpost.php;
 * class Posttings.php
 *
 * Edited thread (threadadmin_editthread) line
 <li>
 <label for="cb_payment_sticky"> Price per day:
 <input type="number" name="payment_sticky" id="cb_payment_sticky" tabindex="3" class="primary textbox" required/>
 </label>
 </li>
 <li>
 <label for="cb_payment_sticky"> End day from NOW:
 <input type="text" name="expires_sticky" id="cb_payment_expires_sticky" tabindex="3" class="primary textbox" placeholder="dd/mm/yyyy" required/>
 </label>
 </li>
 */

if (isset($_POST['payment_sticky']) && isset($_POST['expires_sticky'])) {

	global $threadinfo, $vbulletin, $db;

	/**
	 * Get setting of Payment MOD
	 */
	/** Column in user table */
	$gamebank_column = $vbulletin -> options['payment_column'];

	$date_now = new DateTime("now");

	//echo $threadinfo['payment_sticky'];
	//echo $threadinfo['expires_sticky'];

	//echo $_POST['payment_sticky']. "<br/>";

	$expires_sticky = new DateTime($_POST['expires_sticky']);

	$threadinfo['expires_sticky'] = strtotime($expires_sticky);

	$ddiff = $date_now -> diff($expires_sticky);

	/*
	 * Calculate Price of thread sticky with expires_sticky day
	 */
	$sumany = $ddiff -> Format("%a") * $_POST['payment_sticky'];

	$sql = "select payment from user where userid = " . $threadinfo['firstpostid'];
	$result = $db -> query_first($sql);
	if ($result) {
		$cur_payment = $result['payment'];
	}	
	if($cur_payment - $sumany >= 0){
		$sql = "update user set payment = (payment - $sumany) where userid = " . $threadinfo['firstpostid'];
	}else{
		echo "<script>alert('User posted this thread, doesn't have price');</script>";
		return;
	}
}
?>