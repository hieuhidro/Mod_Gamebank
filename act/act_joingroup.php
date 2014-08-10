<?php
// HOOK profile_joingroup_unmoderated
//Template modifyusergroups_requesttojoin

/*
 * 
 * <vb:if condition="$vboptions[payment_enable] == 1">
     <div class="reason floatcontainer" >      
       	<p>Price perday: {vb:raw  group_price}</p>
        <ul class="commalist" id="leaderlist"> 
        	<li>Input number of day: 
        	<input type="text" name="join_day" value="0" id="join_day" required>
            </li>                                             
		</ul>   
	</div>
  </vb:if>
 */

global $vbulletin, $db, $userdata, $usergroup;
include ('/payment/lib/class.gamebank.php');

$gamebank_column = $vbulletin -> options['payment_column'];
if($vbulletin->options['payment_enable'] == 1){
	if(isset($_POST['join_day'])){
		$join_day = $_POST['join_day'];
		if($join_day != 0){
				
			$usergroup = $vbulletin->usergroupcache[$vbulletin->GPC['usergroupid']];
			
			$curgroup_price = $usergroup['group_price'];
			
			$calcprice = $vbulletin->userinfo[$gamebank_column] - $join_day * $curgroup_price;
			
			if($calcprice >= 0){
				$user_price = new GameBank($vbulletin->userinfo['userid'],$calcprice);
				$user_price->UpdatePayment();
			}else{
				eval(standard_error(fetch_error('Bạn không đủ tiền để tham gia nhóm')));
				return;
			}
		}else{
			eval(standard_error(fetch_error('Số ngày phải lớn hơn 0')));
			return;
		}
	}else{
		eval(standard_error(fetch_error('Số ngày phải lớn hơn 0')));
		return;
	}
}
?>