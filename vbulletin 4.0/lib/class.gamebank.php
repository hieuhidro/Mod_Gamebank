<?php

/**
 * Create class object to update current user
 */ 
class GameBank {
	protected $userid;
	protected $userpayment;
	/*Construct create object
	 * Validate
	 * $_userid is an id of user in request $vbulletin->userinfo['userid'];
	 * $upayment is a new coins of user
	 */
	function __construct($_userid, $upayment) {
		$this -> userid = $_userid;
		$this -> userpayment = $upayment;
	}

	//Function update coins of current user
	public function UpdatePayment() {
		global $db,$gamebank_column;
		$sql = "update ".TABLE_PREFIX."user set $gamebank_column = " . $this->userpayment . " where userid = " . $this->userid;		
		return $result = $db -> query_first($sql);
	}

}
?>