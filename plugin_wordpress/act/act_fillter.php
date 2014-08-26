 <?php
global $current_user;
get_currentuserinfo();
function get_Request($key){
	if (isset($_REQUEST[$key])){
		return $_REQUEST[$key];
	}
	return "";
}
if ($current_user->user_login != "") {
	
	$username = "";
	$cardserial = "";
	$status = 0;
	$username = get_Request('username-filter');
	
	$cardserial = get_Request('cardserial-filter');
	
	$status = get_Request('status-filter');
				
	$username = ($username == "") ? ($current_user->user_login) : $username;
	$cur_page = new paging($username, 5);	
	global $payment_history;
	$payment_history = new payment_history($username);
	
	if(!isset($_GET['page'])){
		$cur_page -> Current(1);		
	}
	$array_payment = $payment_history -> getItemFilter($cardserial, $status, ($cur_page -> Current() - 1) * $cur_page -> pageCount(), $cur_page -> pageCount());	
} else {
}
?>