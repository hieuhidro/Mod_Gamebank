<?php
/*
-- SwebMu: swebmu.net
-- Phien ban: v1.28.02.2014
-- Phat trien theo modules NWebMu
-- Duoc viet boi: votam_2x
*/
$username = $_POST['login'];
$Card_Seri=$_POST['Card_Seri'];
$Card_Code=$_POST['Card_Code'];
$Card_Amount=$_POST['Card_Amount'];
$Card_Type=$_POST['Card_Type'];
$sql_card_check_danap = $db->Execute("Select * From CardSweb where (Card_Code='$Card_Code' or Card_Seri='$Card_Seri') AND Status='1'");
$card_check_danap = $sql_card_check_danap->Numrows();
$sql_card_check_doikt = $db->Execute("Select * From CardSweb where (Card_Code='$Card_Code' or Card_Seri='$Card_Seri') AND Status='0'");
$card_check_doikt = $sql_card_check_doikt->Numrows();
if($card_check_danap>0){ $Content= "Thẻ đã nạp. Không thể nạp thêm lần nữa.";}
elseif($card_check_doikt>0){ $Content= "Thẻ đang đợi kiểm tra. Xin vui lòng chờ trong ít thời gian nữa.";}
else
{	
	/*
	<select id="lstTelco" name="lstTelco">
    	<option value="1">Viettel</option>
        <option value="2">MobiFone</option>
        <option value="3">Vinaphone</option>
        <option value="4">Gate</option>
        <option value="5">Vcoin</option>
    </select>
	*/
	$cardtype = -1;
	switch($Card_Type)
	{
		case 'Gate':
			$cardtype = 4;
			break;
		case 'VTC':
			$cardtype = 5;
			break;
		case 'MOBI':
			$cardtype = 2;
			break;
		case 'Viettel':
			$cardtype = 1;
			break;
		case 'VINA':
			$cardtype = 3;
		break;
	}
	gamebank_service($Card_Seri, $Card_Code, $cardtype,$username);	// 
}
if(!isset($Content)){
	$db->Execute("insert into CardSweb(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$username','$Card_Code','$Card_Seri','$Card_Amount','$Card_Type','$timestamp',null,'0')") or die("SQL Error #1");
	$Content= "MANUAL";
}
echo $Content;
?>