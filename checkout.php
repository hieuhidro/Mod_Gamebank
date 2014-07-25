<?php
	include_once('../global.php');
	include_once(DIR.'/includes/adminfunctions.php');
	include_once(DIR.'/includes/class_bbcode.php');
	require_once('lib/class.paging.php');
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Nạp thẻ điện thoại vào tài khoản gamebank</title>
		<!-- Custom styles for this template -->
		<link href="css/custom.style.css" rel="stylesheet">
		<link href="css/bootstrap.min.css" rel="stylesheet">
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
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>1</td>
							<td>Mark</td>
							<td>Otto</td>
							<td>@mdo</td>
							<td>@mdo</td>
							<td>@mdo</td>
						</tr>						
					</tbody>
				</table>
				<!-- /. table -->
			</div>
			<!-- /. Default panel contents -->
			
			<!-- Paging contents -->
			<?php 
				$cur_page = new paging(20);
				$cur_page->Compile_ToString();
			?>	
			<!-- /. Paging contents -->
		</div>
		<!-- /. container -->
		<footer>
			
		</footer>
	</body>
</html>
