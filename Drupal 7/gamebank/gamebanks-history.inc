<?php

function get_Request($key) {
	if (isset($_REQUEST[$key])) {
		return $_REQUEST[$key];
	}
	return "";
}
$cur_page = null;
function gamebank_process_history($form_state){
	global $user;
	if ($user -> uid != "") {	
		$username = "";
		$cardserial = "";
		$status = 0;
		$username = get_Request('username-filter');
	
		$cardserial = get_Request('cardserial-filter');
	
		$status = get_Request('status-filter');
	
		$username = ($username == "") ? ($user -> name) : $username;
		global $cur_page;
		$cur_page = new paging($username, 20);
		$payment_history = new payment_history($username);		
		if (!isset($_GET['page'])) {
			$cur_page -> Current(1);
		}
		$array_payment = $payment_history -> getItemFilter($cardserial, $status, ($cur_page -> Current() - 1) * $cur_page -> pageCount(), $cur_page -> pageCount());		
	$form_string = '
			<style type="text/css">
				textarea, input[type="text"], input[type="password"], 
				input[type="datetime"], input[type="datetime-local"], 
				input[type="date"], input[type="month"], input[type="time"], 
				input[type="week"], input[type="number"], input[type="email"], 
				input[type="url"], input[type="search"], input[type="tel"], 
				input[type="color"], .uneditable-input {
					height: auto;
				}
			</style>			
			<div class="container">';
			
			if(user_access("authenticated user")){
				$form_string .= '<!-- Default panel contents -->
					<div class="form-group">
						<label class="col-sm-2 control-label">Lọc theo user name</label>
						<div class="col-sm-5">
							<input type="text" id="username-filter" value="'.@$username.'" name="username-filter" class="form-control" placeholder="Nhập user name"/>
						</div>
					</div>';
			}
			$form_string .= '<div class="form-group">
						<input type="hidden" name="username-filter" />
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Lọc theo số seriak</label>
						<div class="col-sm-5">
							<input type="text" name="cardserial-filter" value="'.$cardserial.'" id="cardserial-filter" class="form-control" placeholder="Nhập mã số thẻ"/>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label" >Lọc theo trạng thái</label>
						<div class="col-sm-5">
							<select name="status-filter" id="status-filter" class="form-control" value="'.@$status.'" >
								<option value="0">Chọn trạng thái cần lọc</option>
								<option value="-3" >Thẻ không sử dụng được</option>
								<option value="-10"   >Nhập sai định dạng thẻ</option>
								<option value="-1001" >Nhập sai quá 3 lần</option>
								<option value="-1002" >Lỗi hệ thống</option>
								<option value="-1003" >IP vui lòng quay lại sau 5 phút</option>
								<option value="-1004" >Tên đăng nhập không đúng</option>
								<option value="-1005" >Loại thẻ không đúng</option>
								<option value="-1006" >Hệ thống đang bảo trì</option>
								<option value="-1007" >Kết nối thất bại</option>
								<option value="10000" >Nạp thẻ thành công</option>
							</select>
						</div>
						<div class="col-sm-5">
							<button type="submit" class="btn btn-info col-sm-3" name="btn-filter">
								Lọc
							</button>
						</div>
					</div>
				<div class="panel panel-default">
					<div class="panel-heading">
						Your payment history
					</div>
					<!-- Table -->
					<table class="table">
						<thead>
							<tr>
								<th>STT</th>
								<th>User Name</th>
								<th>Date Time</th>
								<th>Card Serial</th>
								<th>Card Number</th>
								<th>Coins</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>';
					
					$form_string.= $payment_history -> printHtmlItems($array_payment);
					
					$form_string.= '</tbody>
					</table>
					<!-- /. table -->
				</div>
				<!-- /. Default panel contents -->
	
				<!-- Paging contents -->'.$cur_page -> Compile_ToString().'<!-- /. Paging contents -->
			</div>
			<!-- /. container -->';
			
			$form['html'] = array(
			 '#type' => 'markup',
			 '#markup' => $form_string,
			);
			return $form;
	}
}
?>