<?php
//######################### Require class ###########################
require_once 'class.paging.php';
//###################################################################

/**
 * Class payment
 *
 * __contruct($cardserial,$cardnumber,$status,$value,$paymentid,$datetime)
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
	function __construct($cardserial, $cardnumber, $status, $value, $paymentid = null, $datetime = null) {
		$this -> cardserial = $cardserial;
		$this -> cardnumber = $cardnumber;
		$this -> coins = $value;
		$this -> status = $status;

		//Default = null
		$this -> datetime = $datetime;
		$this -> paymentid = $paymentid;
	}

	/**
	 * 
	 * paymentId()
	 * @return $paymentid
	 */
	 public function paymentId()
	 {
	 	return $this->paymentid;
	 }
	/**
	 * getStatus()
	 * @access public
	 * @return String of status id
	 */
	public function getStatus() {

		if ($this -> status >= 10000) {
			return "Nap the thanh cong";
		} else {
			$str_result ="";
			switch($this->status) {
				case -3 :
					$str_result = "The khong su dung duoc";
					break;
				case -10 :
					$str_result = "Nhap sai dinh dang the";
					break;
				case -1001 :
					$str_result = "Nhap sai qua 3 lan ";
					break;
				case -1002 :
					$str_result = "Loi he thong ";
					break;
				case -1003 :
					$str_result = "IP khong duoc phep truy cap vui long quay lai sau 5 phut";
					break;
				case -1004 :
					$str_result = "Ten dang nhap gamebank khong dung";
					break;
				case -1005 :
					$str_result = "Loai the khong dung";
					break;
				case -1006 :
					$str_result = "He thong dang bao tri";
					break;
				default :
					$str_result = "Ket noi voi Gamebank that bai";
			}
			return $str_result;
		}
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
				$this->paymentid = $db -> insert_id();				
				return $this->paymentid;
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
		$str_html .= "<td>$this->datetime</td>";
		$str_html .= "<td>$this->cardserial</td>";
		$str_html .= "<td>$this->cardnumber</td>";
		$str_html .= "<td>$this->coins</td>";
		$str_html .= "<td>".$this->getStatus()."</td>";
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
	protected $username;

	/**
	 * __contruct($userid = 1,$username)
	 * @access public
	 * @param $username string user name of current user (important)
	 * @return void
	 */
	function __construct($username) {
		$this -> username = $username;
	}

	/**
	 *
	 * getAllItem()
	 * @access public
	 * @return array of payment
	 */
	public function getAllItem() {
		global $db;
		$sql = "select * from payment_history where username = $this->username";
		$result = $db -> query_read($sql);
		$array = array();
		if ($result) {
			while ($row = @@mysql_fetch_array($result, MYSQL_ASSOC)) {
				$paymentid = $row['historyid'];
				$datetime = $row['datetime'];
				$cardserial = $row['cardserial'];
				$cardnumber = $row['cardnumber'];
				$coins = $row['coins'];
				$status = $row['status'];
				$array[] = new payment($cardserial, $cardnumber, $status, $paymentid, $datetime);
			}
		}
		return $array;
	}

	/**
	 * getItemLimit()
	 * @access public
	 * @param $from (int), default = 0;
	 * @param $limit (int), cout of select, default = 20;
	 * @return array of payment
	 */
	public function getItemLimit($from = 0, $limit = 20) {
		global $db;
		$sql = "select * from payment_history where username = '$this->username' limit $from, $limit";
		$result = $db -> query_read($sql);
		$array = array();
		if ($result) {
			while ($row = @@mysql_fetch_array($result, MYSQL_ASSOC)) {
				$paymentid = $row['historyid'];
				$datetime = $row['datetime'];
				$cardserial = $row['cardserial'];
				$cardnumber = $row['cardnumber'];
				$coins = $row['coins'];
				$status = $row['status'];
				$array[] = new payment($cardserial, $cardnumber, $status, $coins, $paymentid, $datetime);
			}
		}
		return $array;
	}
	/**
	 * getItemFilter($cardserial,$status)
	 * @access public
	 * @param $username string username default = "";
	 * @param $cardserial string card serial default = "";
	 * @param $status int status number default = "";
	 * @param $from (int), default = 0;
	 * @param $limit (int), cout of select, default = 20;
	 * @return array payment of user
	 */
	public function getItemFilter($cardserial = "",$status = 0,$from = 0,$limit = 20){
		global $vbulletin, $db;		
		if($status != 0){			
			$sql = "select historyid, datetime,cardserial,cardnumber,coins,status from payment_history where username = '$this->username' and status = '$status' limit $from, $limit";
		}else{
			$sql = "select historyid, datetime,cardserial,cardnumber,coins,status from payment_history where username = '$this->username' and cardserial like '%$cardserial%' limit $from, $limit";
		}
		
		$result = $db -> query_read($sql);
		$array = array();
		if ($result) {
			while ($row = @@mysql_fetch_array($result, MYSQL_ASSOC)) {
				$paymentid = $row['historyid'];
				$datetime = $row['datetime'];
				$cardserial = $row['cardserial'];
				$cardnumber = $row['cardnumber'];
				$coins = $row['coins'];
				$status = $row['status'];
				$array[] = new payment($cardserial, $cardnumber, $status, $coins, $paymentid, $datetime);
			}
		}
		if($from == 0 && !isset($_GET['page']) && $cardserial != "" && $status != 0 && $this->username != $vbulletin->userinfo['username']){			
			$sql = str_replace("limit $from,$limit", "",$sql);
			echo $sql;
			$result = $db -> query_read($sql);
			if($result){
				global $cur_page;
				$cur_page->Total(@@mysql_num_rows($result));
			}
		}
		return $array;
	}
	
	/**
	 * stringHtmlItems($cur_page)
	 * @access public
	 * @param $array_payment object of payment default = null;
	 */ 
	 public function printHtmlItems($array_payment = null){
	 	$curpage = paging::getCurrentPage() -1 ;
		$pagecout = paging::getPageCount();
		foreach ($array_payment as $key => $value) {
				echo "<tr class='item_payment'>
						<td>".($curpage *$pagecout + $key+1)."</td>";
				echo "<td>".$this->username."</td>";
				echo $value -> toItemHTML();
				echo '</tr>';
		}
	}
}
	?>