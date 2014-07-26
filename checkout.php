<?php
require_once ('./global.php');
require_once (DIR . '/includes/adminfunctions.php');
require_once (DIR . '/includes/class_bbcode.php');
require_once ('payment/lib/class.payment_history.php');
if(session_start());
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Nạp thẻ điện thoại vào tài khoản gamebank</title>
		<!-- Custom styles for this template -->
		<link href="payment/css/custom.style.css" rel="stylesheet">
		<link href="payment/css/bootstrap.min.css" rel="stylesheet">
	</head>
	<body>
		<header></header>
		<!-- Start navbar -->
		<nav class="navbar navbar-default" role="navigation">
			<div class="container-fluid">
				<!-- Brand and toggle get grouped for better mobile display -->
				<div class="navbar-header">
					<a class="navbar-brand" href="#">Payment History</a>
				</div>
				<!-- Collect the nav links, forms, and other content for toggling -->
				<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
					<ul class="nav navbar-nav">
						<li class="active">
							<a href="../forum.php">Forum</a>
						</li>
					</ul>
				</div><!-- /.navbar-collapse -->
			</div><!-- /.container-fluid -->
		</nav><!-- /. start navbar -->
		<!-- Start container -->
		<div class="container">
			<!-- Default panel contents -->
			<form method="GET" id="form-history" class="form-horizontal" role="form" action="checkout.php">
				<h2>Lọc lược sử</h2>
				<?php				
				include 'payment/act/act_fillter.php';			
				if($vbulletin->userinfo['usertitle'] == "Administrator")
				{
				?>
				<div class="form-group">
					<label class="col-sm-2 control-label">Lọc theo user name</label>
					<div class='col-sm-5'>
						<input type="text" id="username-filter" <?php echo ($username!="")? "value=$username": "";?> name="username-filter" class="form-control" placeholder="Nhập user name"/>						
					</div>					
				</div>
				<?php 
				}
				?>
				<div class="form-group">
					<label class="col-sm-2 control-label">Lọc theo số seriak</label>
					<div class='col-sm-5'>
						<input type="text" id="cardserial-filter" <?php echo ($cardserial != "")? "value=$cardserial;": "";?> name="cardserial-filter" class="form-control" placeholder="Nhập mã số thẻ"/>
					</div>					
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label" >Lọc theo trạng thái</label>
					<div class='col-sm-5'>
						<select name="status-filter" id="status-filter" class="form-control">
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
						<button type="submit" class="btn btn-info col-sm-3" name="btn-filter">Lọc</button>
					</div>					
				</div>
			</form>
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
					<tbody>
						<?php 						
							global $payment_history;
							$payment_history -> printHtmlItems($array_payment);
						?>
					</tbody>
				</table>
				<!-- /. table -->
			</div>
			<!-- /. Default panel contents -->

			<!-- Paging contents -->
			<?php
				echo $cur_page -> Compile_ToString();
			?>
			<!-- /. Paging contents -->
		</div>
		<!-- /. container -->
		<footer>

		</footer>
	</body>
</html>
