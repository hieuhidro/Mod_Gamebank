<?php
if(!$AccessModule)die();
$Card_Seri=$_GET['Card_Seri'];
$Card_Code=$_GET['Card_Code'];
$Card_Amount=$_GET['Card_Amount'];
$Card_Type=$_GET['Card_Type'];
kiemtra_so($Card_Amount,"Mệnh giá");
kiemtra_chu_so($Card_Seri,"Serial Thẻ");
kiemtra_chu_so($Card_Code,"Mã thẻ");

$sql_card_check_danap = mssql_query("Select * From FW_Card where (Card_Code='$Card_Code' or Card_Seri='$Card_Seri') AND Status='1'");
$card_check_danap = mssql_num_rows($sql_card_check_danap);

$sql_card_check_doikt = mssql_query("Select * From FW_Card where (Card_Code='$Card_Code' or Card_Seri='$Card_Seri') AND Status='0'");
$card_check_doikt = mssql_num_rows($sql_card_check_doikt);

if($card_check_danap>0){ $Content= "Thẻ đã nạp. Không thể nạp thêm lần nữa.";}
elseif($card_check_doikt>0){ $Content= "Thẻ đang đợi kiểm tra. Xin vui lòng chờ trong ít thời gian nữa.";}
else
{
	$Check_SQL=mssql_query("select Top 1 Resets from character where accountid='$username' order by resets desc");
	$CharResets=mssql_fetch_row($Check_SQL);
	
	switch($Card_Type)
	{
		case 'Gate':
			include('config/config_napthe_gate.ini');
			if($CharResets[0]<$CharReset)
			{
				$Content= "Tài khoản phải có ít nhất một nhân vật có Reset > $CharReset lần mới được nạp thẻ.";
			}
			else
			{
				if (strlen($Card_Seri) != 10 or strlen($Card_Code) != 10){$Content= "Thẻ không đúng. Xin vui lòng kiểm tra lại";}
				else{
					if($Use_Auto=='Gate'){napthetudong($Card_Seri,$Card_Code,'Gate',$username);}
					elseif($Use_Auto=='DTO'){dto_service($Card_Seri,$Card_Code,'GATE',$username);}
					elseif($Use_Auto=='NganLuong'){nganluong_service($Card_Seri,$Card_Code,'GATE',$username);}
					elseif($Use_Auto=='GameBank'){napthe_gamebank($Card_Seri,$Card_Code,4,$username);}
					elseif($Use_Auto=='KetNoiPay'){knp_napthe($Card_Code,$Card_Seri,$username,'GATE');}
				}
			}
			break;
		case 'VTC':
			include('config/config_napthe_vtc.ini');
			if($CharResets[0]<$CharReset)
			{
				$Content= "Tài khoản phải có ít nhất một nhân vật có Reset > $CharReset lần mới được nạp thẻ.";
			}
			else
			{
				if( !((strlen($Card_Code) == 12) && ( (substr($Card_Seri,0,3) == 'SA0' && strlen($Card_Seri) == 10) || (substr($Card_Seri,0,3) == 'PM0' && strlen($Card_Seri) == 12) )) ) {$Content= "Thẻ không đúng. Xin vui lòng kiểm tra lại";}
				else{
					if($Use_Auto=='VTC'){napthetudong($Card_Seri,$Card_Code,'VTC',$username);}
					elseif($Use_Auto=='DTO'){dto_service($Card_Seri,$Card_Code,'VC',$username);}
						elseif($Use_Auto=='NganLuong'){nganluong_service($Card_Seri,$Card_Code,'VCOIN',$username);}
					elseif($Use_Auto=='GameBank'){napthe_gamebank($Card_Seri,$Card_Code,5,$username);}
					elseif($Use_Auto=='KetNoiPay'){knp_napthe($Card_Code,$Card_Seri,$username,'VTC');}
				}
			}
			break;
		case 'MOBI':
			include('config/config_napthe_mobi.ini');
			if($CharResets[0]<$CharReset)
			{
				$Content= "Tài khoản phải có ít nhất một nhân vật có Reset > $CharReset lần mới được nạp thẻ.";
			}
			else
			{
				iF($Use_Auto=='Knetwork'){knw_mobi($Card_Code,$Card_Seri,$username,1);}
				elseif($Use_Auto=='DTO'){dto_service($Card_Seri,$Card_Code,'MBF',$username);}
					elseif($Use_Auto=='NganLuong'){nganluong_service($Card_Seri,$Card_Code,'VMS',$username);}
				elseif($Use_Auto=='BaoKim'){baokim_service($Card_Seri,$Card_Code,92,$username);}
				elseif($Use_Auto=='GameBank'){napthe_gamebank($Card_Seri,$Card_Code,2,$username);}
				elseif($Use_Auto=='KetNoiPay'){knp_napthe($Card_Code,$Card_Seri,$username,'VMS');}
			}
			break;
		case 'Viettel':
			include('config/config_napthe_viettel.ini');
			if($CharResets[0]<$CharReset)
			{
				$Content= "Tài khoản phải có ít nhất một nhân vật có Reset > $CharReset lần mới được nạp thẻ.";
			}
			else
			{
				iF($Use_Auto=='Knetwork'){knw_mobi($Card_Code,$Card_Seri,$username,3);}
				elseif($Use_Auto=='DTO'){dto_service($Card_Seri,$Card_Code,'VT',$username);}
					elseif($Use_Auto=='NganLuong'){nganluong_service($Card_Seri,$Card_Code,'VIETTEL',$username);}
				elseif($Use_Auto=='BaoKim'){baokim_service($Card_Seri,$Card_Code,107,$username);}
				elseif($Use_Auto=='GameBank'){napthe_gamebank($Card_Seri,$Card_Code,1,$username);}
				
				elseif($Use_Auto=='KetNoiPay'){knp_napthe($Card_Code,$Card_Seri,$username,'VTT');}
			}
			break;
		case 'VINA':
			include('config/config_napthe_vina.ini');
			if($CharResets[0]<$CharReset)
			{
				$Content= "Tài khoản phải có ít nhất một nhân vật có Reset > $CharReset lần mới được nạp thẻ.";
			}
			else
			{
				iF($Use_Auto=='Knetwork'){knw_mobi($Card_Code,$Card_Seri,$username,2);}
				elseif($Use_Auto=='DTO'){dto_service($Card_Seri,$Card_Code,'VNP',$username);}
				elseif($Use_Auto=='BaoKim'){baokim_service($Card_Seri,$Card_Code,93,$username);}
				elseif($Use_Auto=='NganLuong'){nganluong_service($Card_Seri,$Card_Code,'VNP',$username);}
				elseif($Use_Auto=='GameBank'){napthe_gamebank($Card_Seri,$Card_Code,3,$username);}
				elseif($Use_Auto=='KetNoiPay'){knp_napthe($Card_Code,$Card_Seri,$username,'VNP');}
			}
			break;
	}
}

if(!isset($Content)){
	mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$username','$Card_Code','$Card_Seri','$Card_Amount','$Card_Type','$timestamp',null,'0')") or die("SQL Error #1");
	$Content= "MANUAL";
}

echo "<Response>".$Content."</Response>".$ContentUpdate;
?>