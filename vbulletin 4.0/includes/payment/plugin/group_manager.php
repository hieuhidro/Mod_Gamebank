<?php
/*
 * Admin manager group
 * hook event group_dojoin
 * alter table 
 * $group
 * profile.php?do=joingroup
 * 
 * template modifyusergroups_nonmemberbit
 * 
 * HOOK profile_editusergroups_memberbit
 * 
 */
require_once ('./global.php');
require_once (DIR . '/includes/adminfunctions.php');
require_once (DIR . '/includes/class_bbcode.php');

if(session_start());

global $vbulletin, $group;

function getQueryString($param){
	if(!isset($_GET[$param])){
		return "";
	}
	return $_GET[$param];
}
// print_r($group);
// print_r($vbulletin->GPC);
 
// exit();
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="/payment/css/bootstrap.min.css">		
		<title>Group mannager</title>		
	</head>
	<body>
		<header></header>
		<nav class="nav navbar-default">
			<nav class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="#" >Group manager</a>
				</div>
				<!-- Collect the nav links, forms, and other content for toggling -->
				<div class="collapse navbar-collapse navbar-ex1-collapse">
					<ul class="nav navbar-nav">						
						<li id="home">
							<a href="?act=home">Trang chủ</a>
						</li>
						<li id="forum">
							<a href="?act=home">Forum</a>
						</li>
						<?php 
						if($vbulletin->userinfo['usertitle'] === "Administrator"){
						?>
							<li id="adminctr">							
								<a href="?act=admincontrol">Admin Control</a>
							</li>
							<li id="groupctr">
								<a href="/admincp/usergroup.php?do=modify" target="controller">Group permission</a>
							</li>
						<?php
						}
						?>
					</ul>					
				</div><!-- /.navbar-collapse -->
			</nav>
		</nav>
		<div class="container" style="margin-top: 10px;">
			<div class="row">
				<?php
					$query = getQueryString('act') == "" ? "home":getQueryString('act');
										 
					include ('payment/act/act_group_'.getQueryString('act').'.php');
				?>						
			</div>
		</div>
		<footer role="contentinfo">
			
		</footer>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		<script type="text/javascript">
			function checkactive(cur){
				for(var i = 1; i< 5; i++){
					if(i != cur){
						$("#tab-"+i).removeClass("active");
					}
				}
				$("#tab-"+cur).addClass("active");
			}
			
			function resize(){
				var heightnew = (window.innerHeight - 50);
				
	            $('#controller').attr({ 
	                height : heightnew
	            });
			}
			window.onresize = resize;
		</script>
	</body>	
</html>