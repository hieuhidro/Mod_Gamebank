<style>
	#home{
		background: #428bca;		
	}
	#home > a{
		color:#ffffff !important;
	}
</style>
<div class="row">
	<div class="col-md-10 col-md-push-2">
		<iframe id='controller' name="controller" width="100%" height="580" frameborder="0" scrolling="yes" marginheight="0" marginwidth="0" ></iframe>
	</div>
	<div class="col-md-2 col-md-pull-10">
		<ul class="nav nav-pills nav-stacked">
			<li id="tab-1" onclick="checkactive(1);" >
				<a href="/profile.php?do=editusergroups" target="controller" >Join Group Permission</a>
			</li>
			<li id="tab-2" onclick="checkactive(2);" >
				<a href="/admincp/usergroup.php?do=modify" target="controller" >UserGroup Manager</a>
			</li>
			<li id="tab-3" onclick="checkactive(3);" >
				<a class="navlink" href="/admincp/usergroup.php?do=viewjoinrequests"  target="controller" >Join Requests</a>
			</li>
			<li id="tab-4" onclick="checkactive(4);" >
				<a href="/admincp/user.php?do=modify" target="controller" >Find User</a>
			</li>
		</ul>
	</div>
</div>