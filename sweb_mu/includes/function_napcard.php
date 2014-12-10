<?php
function rand_string( $length ) {
	$chars = "abcdefghijklmnopqrstuvwxyz0123456789";	

	$size = strlen( $chars );
	for( $i = 0; $i < $length; $i++ ) {
		$str .= $chars[ rand( 0, $size - 1 ) ];
	}

	return $str;
}

function gamebank_service($cardserial, $cardcode, $cardtype,$login){
	global $timestamp,$year,$month,$day;
	include('config.php');
	include("config/config_event.php");
	include("config/config_napthe.php");
	include("sv_napthe/lib/nusoap.php");
	//include("config/config_AccAutoCard.php");
	
	$gamebank_account = "vinhdragon90"; //Thay đổi account ở đây nhap account tren gamebank cua ban	alo bạn con do ko
	
	$client = new nusoap_client("http://pay.gamebank.vn/service/cardServiceV2.php?wsdl",true);

	$result = $client->call("creditCard",array("seri"=>$cardserial,"code"=> $code,"cardtype"=> $cardcode, "gamebank_account"=>$gamebank_account));
	
	//print_r($result);
	if($result[0] >= 10000)
	{
		
		$menhgia = $result[0];
		switch($cardtype){
			case 1:
				$phantramchenh=$phantramchenh_viettel;
				$TypeIns='Viettel'; break;
			break;
			case 2:
				$phantramchenh=$phantramchenh_mobi;
				$TypeIns='MOBI'; break;
			break;
			case 3: 
				$phantramchenh=$phantramchenh_vina;
				$TypeIns='VINA'; break;
			break;
			case 4:
				$phantramchenh=$phantramchenh_gate;
				$TypeIns='Gate'; break;
			break;
			case 5:
				$phantramchenh=$phantramchenh_vtc;
				$TypeIns='VTC';
			break;
		};
		$gcoin_add=$menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$gcoinkm_add=(($gcoin_add*$khuyenmai_phantram)/100);
			}			
			$Sql_RegCard = "insert into CardSweb(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardserial','".$result[0]."','$TypeIns','$timestamp','$timestamp','1')";
			$db->Execute($Sql_RegCard) or die("SQL Error : $Sql_RegCard");
			$Sql_AddMembInfo  = "update memb_info set gcoin=gcoin+$gcoin_add where memb___id='$login'";
			$db->Execute($Sql_AddMembInfo) or die("Loi: $Sql_AddMembInfo");
			//Neu Co Khuyen Mai
			If($gcoinkm_add>0 || isset($gcoinkm_add)){
				$Sql_AddMembInfo  = "update memb_info set gcoin_km=gcoin_km+$gcoinkm_add where memb___id='$login'";
				$db->Execute($Sql_AddMembInfo) or die("loi: $Sql_AddMembInfo");
			}
			//Evet Top CardPay
			if( ($event_topcard_on == 1) && (strtotime($event_topcard_begin) < $timestamp) && (strtotime($event_topcard_end) + 24*60*60 > $timestamp) )
			{
				$datetime_now = "$year-$month-$day";
				//Kiem tra da co du lieu trong data Event_TOP_Point
				$data_check_sql = $db->Execute("SELECT * FROM Event_TOP_Card WHERE acc='$login' AND [time]='$datetime_now'");
				$data_check = $data_check_sql->numrows();
				//Du lieu da co
				if($data_check > 0) 
				{
					$update_data_query = "UPDATE Event_TOP_Card SET gcoin=gcoin+$gcoin_add WHERE acc='$login' AND [time]='$datetime_now'";
					$update_data_result = $db->Execute($update_data_query) OR DIE("Lỗi Query: $update_data_query");
				}
				//Du lieu chua co
				else {
					$insert_data_query = "INSERT INTO Event_TOP_Card (acc, gcoin, [time]) VALUES ('$login', $gcoin_add, '$datetime_now')";
					$insert_data_result = $db->Execute($insert_data_query) OR DIE("Lỗi Query: $insert_data_query");
				}
			}
			//Ghi vào Log
			$info_log_query = "SELECT gcoin, gcoin_km, vpoint FROM MEMB_INFO WHERE memb___id='$login'";
			$info_log_result = $db->Execute($info_log_query);
				check_queryerror($info_log_query, $info_log_result);
			$info_log = $info_log_result->fetchrow();
			$log_acc = "$login";
			$log_gcoin = $info_log[0];
			$log_gcoin_km = $info_log[1];
			$log_vpoint = $info_log[2];
			$log_price = "+ $gcoin_add Gcoin";
			$log_Des = "$login nạp thẻ $TypeIns với Serial: <b>$cardserial</b> Mệnh giá: <b>".$result[0]."</b> Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> Gcoin và <font color=#FF0000>".number_format($gcoinkm_add)."</font> gcoin KM";
			$log_time = $timestamp;
			$insert_log_query = "INSERT INTO Log_TienTe (acc, gcoin, gcoin_km, vpoint, price, Des, time) VALUES ('$log_acc', '$log_gcoin', '$log_gcoin_km', '$log_vpoint', '$log_price', '$log_Des', '$log_time')";
			$insert_log_result = $db->execute($insert_log_query);
				check_queryerror($insert_log_query, $insert_log_result);
				//End Ghi vào Log
			Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$cardtype</b> với Serial : <b>$cardserial</b>, Mệnh giá :<b>".$result[0]."</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> gcoin","NapThe");
			$Content= "AUTO|OK|".$result[0]."|$gcoin_add|$gcoinkm_add";		
	}else{
	
		//Lỗi nạp tiền, dựa vào bảng mã lỗi để show thông tin khách hàng lên
		$Notice = "";		
		switch($result[0])
		{
			case -3: $Notice =  "The khong su dung duoc" ;break;
			case -10: $Notice =  "Nhap sai dinh dang the";break;
			case -1001: $Notice =  "Nhap sai qua 3 lan ";break;
			case -1002; $Notice =  "Loi he thong ";break;
			case -1003: $Notice =  "IP khong duoc phep truy cap vui long quay lai sau 5 phut";break;
			case -1004: $Notice =  "Ten dang nhap gamebank khong dung"; break;
			case -1005: $Notice =  "Loai the khong dung";break;
			case -1006: $Notice =  "He thong dang bao tri";break;
			default: $Notice =  "Ket noi voi Gamebank that bai";
		}
		Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$cardtype</b> với Serial : <b>$TxtSeri</b>. Thông báo từ hệ thống : <i>($Notice)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$Notice";
	}
	echo $Content;exit();
}
function baokim_service($cardseri,$cardcode,$type,$login)
{
	global $timestamp,$year,$month,$day;
	include('config.php');
	include("config/config_event.php");
	include("config/config_napthe.php");
	include("config/config_AccAutoCard.php");
	include("BKTransactionAPI.php");
	
	$bk = new BKTransactionAPI("https://www.baokim.vn/the-cao/saleCard/wsdl");

	$transaction_id = time();
	/*
	 * API nap the cao dien thoai cho Merchant
	 * */
	$info_topup = new TopupToMerchantRequest();
	$info_topup->api_password = $bk_api_password;
	$info_topup->api_username = $bk_api_username;
	$info_topup->card_id = $type;
	$info_topup->merchant_id = $bk_merchant_id;
	$info_topup->pin_field = $cardcode;
	$info_topup->seri_field = $cardseri;
	$info_topup->transaction_id = $transaction_id;

	$data_sign_array = (array)$info_topup;
	ksort($data_sign_array);

	$data_sign = md5($bk_secure_pass . implode('', $data_sign_array));
	$info_topup->data_sign = $data_sign;
	$test = new TopupToMerchantResponse();

	$test = $bk->DoTopupToMerchant($info_topup);
	
	if($test->error_code==0)
	{
		switch($test->info_card)
		{
			case '10000.0':$menhgia=$menhgia10000;break;
			case '20000.0':$menhgia=$menhgia20000;break;
			case '30000.0':$menhgia=$menhgia30000;break;
			case '50000.0':$menhgia=$menhgia50000;break;
			case '100000.0':$menhgia=$menhgia100000;break;
			case '200000.0':$menhgia=$menhgia200000;break;
			case '300000.0':$menhgia=$menhgia300000;break;
			case '500000.0':$menhgia=$menhgia500000;break;
			case '1000000.0':$menhgia=$menhgia1000000;break;
			default: $Content= "Hệ thống nạp thẻ bị lỗi.";
		}
		iF(!isset($Content))
		{
			switch($type){
				case '93': 
					$phantramchenh=$phantramchenh_vina;
					$TypeIns='VINA'; break;
				case '92': 
					$phantramchenh=$phantramchenh_mobi;
					$TypeIns='MOBI'; break;	
				case '107': 
					$phantramchenh=$phantramchenh_viettel;
					$TypeIns='Viettel'; break;
				case '120': 
					$phantramchenh=$phantramchenh_gate;
					$TypeIns='Gate'; break;
			}
			$gcoin_add=$menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$gcoinkm_add=(($gcoin_add*$khuyenmai_phantram)/100);
			}
			$test->info_card=str_replace(".0","",$test->info_card);	
			$Sql_RegCard = "insert into CardSweb(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$test->info_card','$TypeIns','$timestamp','$timestamp','1')";
			$db->Execute($Sql_RegCard) or die("SQL Error : $Sql_RegCard");
			$Sql_AddMembInfo  = "update memb_info set gcoin=gcoin+$gcoin_add where memb___id='$login'";
			$db->Execute($Sql_AddMembInfo) or die("Loi: $Sql_AddMembInfo");
			//Neu Co Khuyen Mai
			If($gcoinkm_add>0 || isset($gcoinkm_add)){
				$Sql_AddMembInfo  = "update memb_info set gcoin_km=gcoin_km+$gcoinkm_add where memb___id='$login'";
				$db->Execute($Sql_AddMembInfo) or die("loi: $Sql_AddMembInfo");
			}
			//Evet Top CardPay
			if( ($event_topcard_on == 1) && (strtotime($event_topcard_begin) < $timestamp) && (strtotime($event_topcard_end) + 24*60*60 > $timestamp) )
			{
				$datetime_now = "$year-$month-$day";
				//Kiem tra da co du lieu trong data Event_TOP_Point
				$data_check_sql = $db->Execute("SELECT * FROM Event_TOP_Card WHERE acc='$login' AND [time]='$datetime_now'");
				$data_check = $data_check_sql->numrows();
				//Du lieu da co
				if($data_check > 0) 
				{
					$update_data_query = "UPDATE Event_TOP_Card SET gcoin=gcoin+$gcoin_add WHERE acc='$login' AND [time]='$datetime_now'";
					$update_data_result = $db->Execute($update_data_query) OR DIE("Lỗi Query: $update_data_query");
				}
				//Du lieu chua co
				else {
					$insert_data_query = "INSERT INTO Event_TOP_Card (acc, gcoin, [time]) VALUES ('$login', $gcoin_add, '$datetime_now')";
					$insert_data_result = $db->Execute($insert_data_query) OR DIE("Lỗi Query: $insert_data_query");
				}
			}
			//Ghi vào Log
			$info_log_query = "SELECT gcoin, gcoin_km, vpoint FROM MEMB_INFO WHERE memb___id='$login'";
			$info_log_result = $db->Execute($info_log_query);
				check_queryerror($info_log_query, $info_log_result);
			$info_log = $info_log_result->fetchrow();
			$log_acc = "$login";
			$log_gcoin = $info_log[0];
			$log_gcoin_km = $info_log[1];
			$log_vpoint = $info_log[2];
			$log_price = "+ $gcoin_add Gcoin";
			$log_Des = "$login nạp thẻ $TypeIns với Serial: <b>$cardseri</b> Mệnh giá: <b>$test->info_card</b> Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> Gcoin và <font color=#FF0000>".number_format($gcoinkm_add)."</font> gcoin KM";
			$log_time = $timestamp;
			$insert_log_query = "INSERT INTO Log_TienTe (acc, gcoin, gcoin_km, vpoint, price, Des, time) VALUES ('$log_acc', '$log_gcoin', '$log_gcoin_km', '$log_vpoint', '$log_price', '$log_Des', '$log_time')";
			$insert_log_result = $db->execute($insert_log_query);
				check_queryerror($insert_log_query, $insert_log_result);
			//End Ghi vào Log
			Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$test->info_card</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> gcoin","NapThe");
			$Content= "AUTO|OK|$test->info_card|$gcoin_add|$gcoinkm_add";
		}
	}
	else
	{
		Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$TypeIns</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($b->status_message)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$test->error_message";
	}
	echo $Content;exit();
}
//Ngan luong $cardseri,$cardcode
function nganluong_card($sopin,$soseri,$type_card,$login){
	global $timestamp,$year,$month,$day;
	include('config.php');
	include("config/config_AccAutoCard.php");
	include("config/config_event.php");
	include("config/config_napthe.php");
	include("BKTransactionAPI.php");
	include("MobiCard.php");
	$call = new MobiCard();
	$rs = new Result();
	$coin1 = rand(10,999);
	$coin2 = rand(0,999);
	$coin3 = rand(0,999);
	$coin4 = rand(0,999);
	$ref_code = $coin4 + $coin3 * 1000 + $coin2 * 1000000 + $coin1 * 100000000;				
	$rs = $call->CardPay($sopin,$soseri,$type_card,$ref_code,"STWEB","0919172669","votam_2x@yahoo.com");		  
	if($rs->error_code == '00') {					
		switch($rs->card_amount){
			case '10000':$menhgia=$menhgia10000;break;
			case '20000':$menhgia=$menhgia20000;break;
			case '30000':$menhgia=$menhgia30000;break;
			case '50000':$menhgia=$menhgia50000;break;
			case '100000':$menhgia=$menhgia100000;break;
			case '200000':$menhgia=$menhgia200000;break;
			case '300000':$menhgia=$menhgia300000;break;
			case '500000':$menhgia=$menhgia500000;break;
			case '1000000':$menhgia=$menhgia1000000;break;
			default: $Content= "Hệ thống nạp thẻ bị lỗi.";
		}
		iF(!isset($Content))
		{
			switch($type_card){
				case 'VNP': 
					$phantramchenh=$phantramchenh_vina;
					$TypeIns='VINA'; break;
				case 'VMS': 
					$phantramchenh=$phantramchenh_mobi;
					$TypeIns='MOBI'; break;	
				case 'VIETTEL': 
					$phantramchenh=$phantramchenh_viettel;
					$TypeIns='Viettel'; break;
				case 'GATE': 
					$phantramchenh=$phantramchenh_gate;
					$TypeIns='GATE'; break;
			}
			//FW_Card
			$gcoin_add=$menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$gcoinkm_add=(($gcoin_add*$khuyenmai_phantram)/100);
			}
			
			$Sql_RegCard = "insert into CardSweb(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$rs->card_amount','$TypeIns','$timestamp','$timestamp','1')";
			$db->Execute($Sql_RegCard) or die("SQL Error : $Sql_RegCard");
			$Sql_AddMembInfo  = "update memb_info set gcoin=gcoin+$gcoin_add where memb___id='$login'";
			$db->Execute($Sql_AddMembInfo) or die("Loi: $Sql_AddMembInfo");
			//Neu Co Khuyen Mai
			If($gcoinkm_add>0 || isset($gcoinkm_add)){
				$Sql_AddMembInfo  = "update memb_info set gcoin_km=gcoin_km+$gcoinkm_add where memb___id='$login'";
				$db->Execute($Sql_AddMembInfo) or die("loi: $Sql_AddMembInfo");
			}
			if( ($event_topcard_on == 1) && (strtotime($event_topcard_begin) < $timestamp) && (strtotime($event_topcard_end) + 24*60*60 > $timestamp) )
			{
				$datetime_now = "$year-$month-$day";
				//Kiem tra da co du lieu trong data Event_TOP_Point
				$data_check_sql = $db->Execute("SELECT * FROM Event_TOP_Card WHERE acc='$login' AND [time]='$datetime_now'");
				$data_check = $data_check_sql->numrows();
				//Du lieu da co
				if($data_check > 0) 
				{
					$update_data_query = "UPDATE Event_TOP_Card SET gcoin=gcoin+$gcoin_add WHERE acc='$login' AND [time]='$datetime_now'";
					$update_data_result = $db->Execute($update_data_query) OR DIE("Lỗi Query: $update_data_query");
				}
				//Du lieu chua co
				else {
					$insert_data_query = "INSERT INTO Event_TOP_Card (acc, gcoin, [time]) VALUES ('$login', $gcoin_add, '$datetime_now')";
					$insert_data_result = $db->Execute($insert_data_query) OR DIE("Lỗi Query: $insert_data_query");
				}
			}
			//Ghi vào Log
			$info_log_query = "SELECT gcoin, gcoin_km, vpoint FROM MEMB_INFO WHERE memb___id='$login'";
			$info_log_result = $db->Execute($info_log_query);
				check_queryerror($info_log_query, $info_log_result);
			$info_log = $info_log_result->fetchrow();
			$log_acc = "$login";
			$log_gcoin = $info_log[0];
			$log_gcoin_km = $info_log[1];
			$log_vpoint = $info_log[2];
			$log_price = "+ $gcoin_add Gcoin";
			$log_Des = "$login nạp thẻ $TypeIns với Serial: <b>$cardseri</b> Mệnh giá: <b>$rs->card_amount</b> Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> Gcoin và <font color=#FF0000>".number_format($gcoinkm_add)."</font> gcoin KM";
			$log_time = $timestamp;
			$insert_log_query = "INSERT INTO Log_TienTe (acc, gcoin, gcoin_km, vpoint, price, Des, time) VALUES ('$log_acc', $log_gcoin, $log_gcoin_km, $log_vpoint, '$log_price', '$log_Des', $log_time)";
			$insert_log_result = $db->execute($insert_log_query);
				check_queryerror($insert_log_query, $insert_log_result);
			//End Ghi vào Log
			Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$rs->card_amount</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font>Gcoin","NapThe");			
			$Content= "AUTO|OK|$rs->card_amount|$gcoin_add|$gcoinkm_add";
			//END
		}	
	}
	else{
		Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$TypeIns</b> với Serial : <b>$soseri</b>. Thông báo từ hệ thống : <i>($rs->error_message)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$rs->error_message";
	}
	echo $Content;exit();
	
}
//End NL
function knp_napthe($TxtMaThe,$TxtSeri,$login,$TxtType)
{
	global $timestamp,$year,$month,$day;
	include('config.php');
	include("config/config_event.php");
	include("config/config_napthe.php");
	include("config/config_AccAutoCard.php");
	include("class.gateWay.php");

	switch($TxtType){
		case 'VTT':
			$phantramchenh=$phantramchenh_viettel;
			$TxtUrl  = 'http://api.knp.vn:64990';
			$type='Viettel';
			break;
		case 'VMS':
			$phantramchenh=$phantramchenh_mobi;
			$TxtUrl  = 'http://api.knp.vn:64980';
			$type='MOBI';
			break;
		case 'VNP':
			$phantramchenh=$phantramchenh_vina;
			$TxtUrl  = 'http://api.knp.vn:64980';
			$type='VINA';
			break;
		case 'GATE':
			$phantramchenh=$phantramchenh_gate;
			$TxtUrl  = 'http://api.knp.vn:64986';
			$type='Gate';
			break;
		case 'VTC':
			$phantramchenh=$phantramchenh_vtc;
			$TxtUrl  = 'http://api.knp.vn:64987';
			$type='VTC';
			break;
	}

	# Gửi thẻ lên máy chủ FPAY
	$TransID = rand_string(6);
	$TxtKey   = md5(trim($Knp_PartnerID.$TxtType.$TransID.$TxtMaThe.$Knp_Signal));
	$gateWay  = new gateWay($Knp_PartnerID,$TxtType,$TxtMaThe,$TxtSeri,$TransID,$TxtKey,$TxtUrl);
	$response = $gateWay->ReturnResult();
	
	if(!strstr($response,'RESULT:10'))
	{
		if(strpos($response,'RESULT:03') !== false || strpos($response,'RESULT:05') !== false || strpos($response,'RESULT:07') !== false || strpos($response,'RESULT:06') !== false) // thẻ sai
		{
			$Notice = 'Mã thẻ cào hoặc seri không chính xác.';
		}
		elseif(strpos($response,'RESULT:08') !== false)
		{
			$Notice = 'Thẻ đã gửi sang hệ thống rồi. Không gửi thẻ này nữa.';
		}
		elseif(strpos($response,'RESULT:11') !== false)
		{
			$Notice = 'Thẻ đã gửi sang hệ thống nhưng bị trễ. Bạn hãy chờ đợi hệ thống đối soát rồi sẽ cộng tiền vào tài khoản của bạn...';
			mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status,KnpTransID) values('$login','$TxtMaThe','$TxtSeri','','$type','$timestamp',null,'0','$TransID')") or die("SQL Error #1");
		}
		elseif(strpos($response,'RESULT:99') !== false || strpos($response,'RESULT:00') !== false || strpos($response,'RESULT:01') !== false || strpos($response,'RESULT:04') !== false || strpos($response,'RESULT:09') !== false)
		{
			$Notice = 'Hệ thống nạp thẻ đang bảo trì. Mã bảo trì là '.$response;
		}
		else{
			$Notice = 'Có lỗi xảy ra trong quá trình nạp thẻ. Vui lòng quay lại sau. Mã báo lỗi là '.$response;
		}
		Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$TxtSeri</b>. Thông báo từ hệ thống : <i>($Notice)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$Notice";
	}
	else
	{
		$TxtMenhGia	   = intval(str_replace('RESULT:10@','',$response));
		$TienDuocHuong = $TxtMenhGia;

		switch($TienDuocHuong)
		{
			case '10000':$menhgia=$menhgia10000;break;
			case '20000':$menhgia=$menhgia20000;break;
			case '30000':$menhgia=$menhgia30000;break;
			case '50000':$menhgia=$menhgia50000;break;
			case '100000':$menhgia=$menhgia100000;break;
			case '200000':$menhgia=$menhgia200000;break;
			case '300000':$menhgia=$menhgia300000;break;
			case '500000':$menhgia=$menhgia500000;break;
			case '1000000':$menhgia=$menhgia1000000;break;
			default: $Content= "Hệ thống nạp thẻ bị lỗi.";
		}
		
		iF(!isset($Content))
		{
				$gcoin_add=$menhgia+(($menhgia*$phantramchenh)/100);
				if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
				{
					$gcoinkm_add=(($gcoin_add*$khuyenmai_phantram)/100);
				}
				
				$Sql_RegCard = "insert into CardSweb(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$TxtMaThe','$TxtSeri','$TienDuocHuong','$type','$timestamp','$timestamp','1')";
				$db->Execute($Sql_RegCard) or die("SQL Error : $Sql_RegCard");
				$Sql_AddMembInfo  = "update memb_info set gcoin=gcoin+$gcoin_add where memb___id='$login'";
				$db->Execute($Sql_AddMembInfo) or die("Loi: $Sql_AddMembInfo");
				//Neu Co Khuyen Mai
				If($gcoinkm_add>0 || isset($gcoinkm_add)){
					$Sql_AddMembInfo  = "update memb_info set gcoin_km=gcoin_km+$gcoinkm_add where memb___id='$login'";
					$db->Execute($Sql_AddMembInfo) or die("loi: $Sql_AddMembInfo");
				}
				if( ($event_topcard_on == 1) && (strtotime($event_topcard_begin) < $timestamp) && (strtotime($event_topcard_end) + 24*60*60 > $timestamp) )
				{
					$datetime_now = "$year-$month-$day";
					//Kiem tra da co du lieu trong data Event_TOP_Point
					$data_check_sql = $db->Execute("SELECT * FROM Event_TOP_Card WHERE acc='$login' AND [time]='$datetime_now'");
					$data_check = $data_check_sql->numrows();
					//Du lieu da co
					if($data_check > 0) 
					{
						$update_data_query = "UPDATE Event_TOP_Card SET gcoin=gcoin+$gcoin_add WHERE acc='$login' AND [time]='$datetime_now'";
						$update_data_result = $db->Execute($update_data_query) OR DIE("Lỗi Query: $update_data_query");
					}
					//Du lieu chua co
					else {
						$insert_data_query = "INSERT INTO Event_TOP_Card (acc, gcoin, [time]) VALUES ('$login', $gcoin_add, '$datetime_now')";
						$insert_data_result = $db->Execute($insert_data_query) OR DIE("Lỗi Query: $insert_data_query");
					}
				}
				//Ghi vào Log
				$info_log_query = "SELECT gcoin, gcoin_km, vpoint FROM MEMB_INFO WHERE memb___id='$login'";
				$info_log_result = $db->Execute($info_log_query);
					check_queryerror($info_log_query, $info_log_result);
				$info_log = $info_log_result->fetchrow();
				$log_acc = "$login";
				$log_gcoin = $info_log[0];
				$log_gcoin_km = $info_log[1];
				$log_vpoint = $info_log[2];
				$log_price = "+ $gcoin_add Gcoin";
				$log_Des = "$login nạp thẻ $TypeIns với Serial: <b>$cardseri</b> Mệnh giá: <b>$TienDuocHuong</b> Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> Gcoin và <font color=#FF0000>".number_format($gcoinkm_add)."</font> gcoin KM";
				$log_time = $timestamp;
				$insert_log_query = "INSERT INTO Log_TienTe (acc, gcoin, gcoin_km, vpoint, price, Des, time) VALUES ('$log_acc', $log_gcoin, $log_gcoin_km, $log_vpoint, '$log_price', '$log_Des', $log_time)";
				$insert_log_result = $db->execute($insert_log_query);
					check_queryerror($insert_log_query, $insert_log_result);
				//End Ghi vào Log
				Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$TxtSeri</b>, Mệnh giá :<b>$TienDuocHuong</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> Gcoin","NapThe");
				$Content= "AUTO|OK|$TienDuocHuong|$gcoin_add|$gcoinkm_add";
		}
	}
	echo $Content;exit();	
}
function Airpay_Sweb($seri,$pin,$card_type,$login)
{
	global $timestamp,$year,$month,$day;
	include('config.php');
	include("config/config_event.php");
	include("config/config_napthe.php");
	include("config/config_AccAutoCard.php");
	include("Airpay_API.php");
	// lay thong tin tu airpay - muc tich hop website trong quan ly tai khoan
	//Ket noi den Aripay
	$airpay_api = new Airpay_API();
	$airpay_api->setMerchantId($merchant_aripay);
	$airpay_api->setApiUser($api_useraripay);
	$airpay_api->setApiPassword($api_passaripay);
	$airpay_api->setPin($pin);
	$airpay_api->setSeri($seri);
	$airpay_api->setCardType($card_type);
	$airpay_api->setNote("Hệ thống nạp thẻ tự động từ Sweb"); // ghi chu giao dich ben ban tu sinh
	$airpay_api->cardCharging();
	$code = $airpay_api->getCode();
	//Lay Du Lieu ve
	if($code == 0) {
		$menhgiathearipay = $airpay_api->getInfoCard();
		switch($menhgiathearipay)
		{
			case '10000':$menhgia=$menhgia10000;break;
			case '20000':$menhgia=$menhgia20000;break;
			case '30000':$menhgia=$menhgia30000;break;
			case '50000':$menhgia=$menhgia50000;break;
			case '100000':$menhgia=$menhgia100000;break;
			case '200000':$menhgia=$menhgia200000;break;
			case '300000':$menhgia=$menhgia300000;break;
			case '500000':$menhgia=$menhgia500000;break;
			case '1000000':$menhgia=$menhgia1000000;break;
			default: $Content= "Hệ thống nạp thẻ bị lỗi.";
		}
		iF(!isset($Content))
		{
			switch($type){
				case '93': 
					$phantramchenh=$phantramchenh_vina;
					$TypeIns='VINA'; break;
				case '92': 
					$phantramchenh=$phantramchenh_mobi;
					$TypeIns='MOBI'; break;	
				case '107': 
					$phantramchenh=$phantramchenh_viettel;
					$TypeIns='Viettel'; break;
				case '120': 
					$phantramchenh=$phantramchenh_gate;
					$TypeIns='Gate'; break;
			}
			$gcoin_add=$menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$gcoinkm_add=(($gcoin_add*$khuyenmai_phantram)/100);
			}	
			$Sql_RegCard = "insert into CardSweb(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$menhgiathearipay','$TypeIns','$timestamp','$timestamp','1')";
			$db->Execute($Sql_RegCard) or die("SQL Error : $Sql_RegCard");
			$Sql_AddMembInfo  = "update memb_info set gcoin=gcoin+$gcoin_add where memb___id='$login'";
			$db->Execute($Sql_AddMembInfo) or die("Loi: $Sql_AddMembInfo");
			//Neu Co Khuyen Mai
			If($gcoinkm_add>0 || isset($gcoinkm_add)){
				$Sql_AddMembInfo  = "update memb_info set gcoin_km=gcoin_km+$gcoinkm_add where memb___id='$login'";
				$db->Execute($Sql_AddMembInfo) or die("loi: $Sql_AddMembInfo");
			}
			//Evet Top CardPay
			if( ($event_topcard_on == 1) && (strtotime($event_topcard_begin) < $timestamp) && (strtotime($event_topcard_end) + 24*60*60 > $timestamp) )
			{
				$datetime_now = "$year-$month-$day";
				//Kiem tra da co du lieu trong data Event_TOP_Point
				$data_check_sql = $db->Execute("SELECT * FROM Event_TOP_Card WHERE acc='$login' AND [time]='$datetime_now'");
				$data_check = $data_check_sql->numrows();
				//Du lieu da co
				if($data_check > 0) 
				{
					$update_data_query = "UPDATE Event_TOP_Card SET gcoin=gcoin+$gcoin_add WHERE acc='$login' AND [time]='$datetime_now'";
					$update_data_result = $db->Execute($update_data_query) OR DIE("Lỗi Query: $update_data_query");
				}
				//Du lieu chua co
				else {
					$insert_data_query = "INSERT INTO Event_TOP_Card (acc, gcoin, [time]) VALUES ('$login', $gcoin_add, '$datetime_now')";
					$insert_data_result = $db->Execute($insert_data_query) OR DIE("Lỗi Query: $insert_data_query");
				}
			}
			$tran_id = $airpay_api->getTransactionId();
			//Ghi vào Log
			$info_log_query = "SELECT gcoin, gcoin_km, vpoint FROM MEMB_INFO WHERE memb___id='$login'";
			$info_log_result = $db->Execute($info_log_query);
				check_queryerror($info_log_query, $info_log_result);
			$info_log = $info_log_result->fetchrow();
			$log_acc = "$login";
			$log_gcoin = $info_log[0];
			$log_gcoin_km = $info_log[1];
			$log_vpoint = $info_log[2];
			$log_price = "+ $gcoin_add Gcoin";
			$log_Des = "$login nạp thẻ $TypeIns với Serial: <b>$cardseri</b> Mệnh giá: <b>$test->info_card</b> Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> Gcoin và <font color=#FF0000>".number_format($gcoinkm_add)."</font> gcoin KM.mã giao dịch: $tran_id";
			$log_time = $timestamp;
			$insert_log_query = "INSERT INTO Log_TienTe (acc, gcoin, gcoin_km, vpoint, price, Des, time) VALUES ('$log_acc', $log_gcoin, $log_gcoin_km, $log_vpoint, '$log_price', '$log_Des', $log_time)";
			$insert_log_result = $db->execute($insert_log_query);
				check_queryerror($insert_log_query, $insert_log_result);
			//End Ghi vào Log
			Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$airpay_api->getInfoCard()</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($gcoin_add)."</font> gcoin. mã giao dịch: $tran_id","NapThe");
			$Content= "AUTO|OK|$airpay_api->getInfoCard()|$gcoin_add|$gcoinkm_add";
		}
	}else
	{
		$msg = $airpay_api->getMsg();
		Log_Sweb("Tài khoản <b>$login</b> nạp thẻ <b>$TypeIns</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($msg)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$msg";
	}
	echo $Content;exit();
}
function Log_Sweb($data,$file){
	include("config.php");
	$Date = date("h:iA, d/m/Y", $timestamp); 
	$file="Logs/$file.html";
	if(!is_file($file)){
		$fp = fopen($file, "a+");
		fputs ($fp, "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>\n");
		fclose($fp);
	}
	$fp = fopen($file, "a+");  
	fputs ($fp, "<font color=blue>(Lúc: $Date)</font> $data<br><br>\n");  
	fclose($fp);
}
?>