<?php
if ($vbulletin -> userinfo['userid']) {
	$username = "";
	$cardserial = "";
	$status = 0;
	if (isset($_GET['username-filter'])) {
		$username = $_GET['username-filter'];
	}
	if (isset($_GET['cardserial-filter'])) {
		$cardserial = $_GET['cardserial-filter'];
	}
	if (isset($_GET['status-filter'])) {
		$status = $_GET['status-filter'];
	}

	$cur_page = new paging($username, 5);

	$username = ($username == "") ? ($vbulletin -> userinfo['username']) : $username;
	$payment_history = new payment_history($username);
	if(!isset($_GET['page'])){
		$cur_page -> Current(1);		
	}
	$array_payment = $payment_history -> getItemFilter($cardserial, $status, ($cur_page -> Current() - 1) * $cur_page -> pageCount(), $cur_page -> pageCount());

} else {
	header("location: forum.php");
}
?>