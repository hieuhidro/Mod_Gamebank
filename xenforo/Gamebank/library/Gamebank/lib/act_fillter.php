 <?php

function get_Request($key){
	if (isset($_REQUEST[$key])){
		return $_REQUEST[$key];
	}
	return "";
}
if ($userinfo['username']) {
	
	$username = "";
	$cardserial = "";
	$status = 0;
	$username = get_Request('username-filter');
	
	$cardserial = get_Request('cardserial-filter');
	
	$status = get_Request('status-filter');
				
	$username = ($username == "") ? ($userinfo['username']) : $username;
	global $cur_page;
	$cur_page = new paging($username, 5);	
	global $payment_history;
	$payment_history = new payment_history($username);
	
	if(!isset($_GET['page'])){
		$cur_page -> Current(1);		
	}
	$array_payment = $payment_history -> getItemFilter($cardserial, $status, ($cur_page -> Current() - 1) * $cur_page -> pageCount(), $cur_page -> pageCount());	
} else {
	header('location: /');
}
?>