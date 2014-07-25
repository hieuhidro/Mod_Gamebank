<?php
/**
 * Class payment
 *
 * __contruct($paymentid,$datetime,$cardserial,$cardnumber,$status)
 *
 * @param $paymentid int value id of item payment default = null
 * @param $datetime string value date time of item payment default = null
 * @param $cardserial string value card serial of item payment
 * @param $cardnumber string value card number of item payment
 * @param $status string value status of item payment
 */
class payment {

	/**
	 * Protected
	 */
	protected $paymentid;
	protected $datetime;
	protected $cardserial;
	protected $cardnumber;
	protected $coins;
	protected $status;

	/** __contruct($cardserial,$cardnumber,$status,$value,$paymentid,$datetime)
	 *
	 * @access public
	 * @param $paymentid int value id of item payment default = null
	 * @param $datetime string value date time of item payment default = null
	 * @param $cardserial string value card serial of item payment
	 * @param $cardnumber string value card number of item payment
	 * @param $value string value card value of item card
	 * @param $status string value status of item payment
	 * @return void
	 */
	function __contruct($cardserial, $cardnumber, $status, $value, $paymentid = null, $datetime = null) {
		$this -> cardserial = $cardserial;
		$this -> cardnumber = $cardnumber;
		$this -> status = $status;
		$this -> coins = $value;

		//Default = null
		$this -> datetime = $datetime;
		$this -> paymentid = $paymentid;
	}

	/**
	 * insertItemp($userid = null)
	 * @access public
	 * @param $userid int. id of user will be add defalut = null (Not insert)
	 * @return int id of row inserted (insert false return -1)
	 */
	public function insertItemp($userid = null) {
		if ($userid != null) {
			$sql = "INSERT INTO 'payment_history' ('userid', 'datetime', 'serial', 'cardnumber', 'cardvalue', 'status')	VALUES	(" . $userid . ", '" . $this -> datetime . "', '" . $this -> cardserial . "', '" . $this -> cardnumber . "', '" . $this -> coins . "', " . $this -> status . ")";
			$result = $db -> query_first($sql);
			if ($result) {
				$result = $db -> insert_id();
				return $result;
			}
		}
		return -1;
	}

	/**
	 * toItemHTML()
	 *
	 * @access public
	 * @return string itemp element HTML
	 * 			<td>Date Time</td>
	 *			<td>Card Serial</td>
	 *			<td>Card Number</td>
	 *			<td>Coins</td>
	 * 			<td>Status</td>
	 */
	public function toItemHTML() {
		$str_html  = "<td>$this->datetime</td>";
		$str_html .= "<td>$this->cardserial</td>";
		$str_html .= "<td>$this->cardnumber</td>";
		$str_html .= "<td>$this->coins</td>";
		$str_html .= "<td>$this->status</td>";
		return $str_html;
	}

}

/**
 *
 * Class Payment_history.
 *
 * Get, insert current user payment history
 * __contruct($userid)
 * @param $userid int id of user will be get detail
 * @param $username string string user name of current user
 * @param $info array of object payment
 */
class payment_history {

	protected $userid;
	protected $username;
	protected $info = array();
	/**
	 * __contruct($userid = 1)
	 * @access
	 * @param $userid int id of user will be get detail  (important)
	 * @param $username string user name of current user (important)
	 * @return void
	 */
	function _contruct($userid,$username) {

	}

}
?>