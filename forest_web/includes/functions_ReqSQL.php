<?php
function napthe_gamebank($cardseri,$cardcode,$type,$login)
{
	global $ContentUpdate,$timestamp;
	include("config/config_napthe.ini");
	include("config/config_AccAutoCard.php");
	include("nusoap.php");
	
	$client = new SoapClient("http://pay.gamebank.vn/service/cardServiceV2.php?wsdl",true);
	$result = $client->creditCard($cardseri, $cardcode, $type, $gamebank_account);

	switch($type){
		case '3': 
			$phantramchenh=$phantramchenh_vina;
			$TypeIns='VINA'; break;
		case '2': 
			$phantramchenh=$phantramchenh_mobi;
			$TypeIns='MOBI'; break;	
		case '1': 
			$phantramchenh=$phantramchenh_viettel;
			$TypeIns='Viettel'; break;		
		case '4': 
			$phantramchenh=$phantramchenh_gate;
			$TypeIns='Gate'; break;
		case '5': 
			$phantramchenh=$phantramchenh_vtc;
			$TypeIns='VTC'; break;
	}		
	
	
	if($result[0] >= 10000)
	{
		switch($result[0]){
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
		if(!isset($Content))
		{
			$money_add=$menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$money_add=$money_add+(($money_add*$khuyenmai_phantram)/100);
			}
					
			mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$result[0]','$TypeIns','$timestamp','$timestamp','1')") or die("SQL Error #1");
			mssql_query("UPDATE MEMB_INFO SET Bank_MoneyC1=Bank_MoneyC1+$money_add Where memb___id='$login'") or die("SQL Error #2");
			WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$result[0]</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($money_add)."</font> $name_money_cap1","NapThe");
			$Content= "AUTO|OK|$result[0]|$money_add";
		}
	}
	else
	{	
		$error_card  = '';
			switch($result[0]){
				case '-1': $error_card='Thẻ đã hết hạn sử dụng'; break;
				case '-2': $error_card='Thẻ đã bị khóa'; break;
				case '-3': $error_card='Thẻ không sử dụng được'; break;
				case '-4': $error_card='Thẻ chưa được kích hoạt'; break;
				case '-10': $error_card='Nhập sai định dạng thẻ'; break;
				case '-12': $error_card='Thẻ không tồn tại'; break;
				case '-51': $error_card='Seri thẻ không đúng'; break;
				case '-52': $error_card='Mã thẻ và seri không khớp'; break;
				case '-53': $error_card='Serial hoặc mã thẻ không đúng'; break;
				case '-99': $error_card='Nhập mã thẻ không đủ độ dài'; break;
				case '-1001': $error_card='Nhập sai quá 3 lần'; break;
				case '-1002': $error_card='Lỗi hệ thống'; break;
				case '-1003': $error_card='IP không được phép truy cập'; break;
				case '-1004': $error_card='Tên đăng nhập gamebank không đúng'; break;
				case '-1005': $error_card='Loại thẻ không đúng'; break;
				case '-1006': $error_card='Hệ thống đang được bảo trì'; break;
				default: $error_card=$result[0]; break;
			};
		
		WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$TypeIns</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($error_card)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$error_card".":".$type;
	}
	echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
}

function airpay_service($cardcode,$cardseri,$type,$login)
{
	include('config.php');
	global $ContentUpdate,$timestamp;
	include("config/config_napthe.ini");
	include("config/config_AccAutoCard.php");
	include("Airpay_API.php");
	// lay thong tin tu airpay - muc tich hop website trong quan ly tai khoan
	//Ket noi den Aripay
	$airpay_api = new Airpay_API();
	$airpay_api->setMerchantId($merchant_id);
	$airpay_api->setApiUser($api_user);
	$airpay_api->setApiPassword($api_password);
	$airpay_api->setPin($cardcode);
	$airpay_api->setSeri($cardseri);
	$airpay_api->setCardType($type);
	$airpay_api->setNote("Tài khoản : $login | Mu Thái Tử "); // ghi chu giao dich ben ban tu sinh
	$airpay_api->setReferId("79");
	$airpay_api->cardCharging();
	$code = $airpay_api->getCode();
	switch($type)
		{
				case '3': 
					$phantramchenh=$phantramchenh_vina;
					$TypeIns='VINA'; break;
				case '2': 
					$phantramchenh=$phantramchenh_mobi;
					$TypeIns='MOBI'; break;	
				case '1': 
					$phantramchenh=$phantramchenh_viettel;
					$TypeIns='Viettel'; break;
				case '4': 
					$phantramchenh=$phantramchenh_gate;
					$TypeIns='Gate'; break;
		}			
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
			//FW_Card
			$money_add=$menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$money_add=$money_add+(($money_add*$khuyenmai_phantram)/100);
			}
			mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$menhgiathearipay','$TypeIns','$timestamp','$timestamp','1')") or die("SQL Error #1");
			mssql_query("UPDATE MEMB_INFO SET $Colum_Bank_MoneyC1=$Colum_Bank_MoneyC1+$money_add Where memb___id='$login'") or die("SQL Error #2");
			WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$TypeIns</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$menhgiathearipay</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($money_add)."</font> $name_money_cap1","NapThe");
			$Content= "AUTO|OK|$menhgiathearipay|$money_add";
			//END
		}
	}else
	{
		$msg = $airpay_api->getMsg();
		WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$TypeIns</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($msg)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$msg";		
	}
	echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
}


function baokim_service($cardseri,$cardcode,$type,$login)
{
	global $ContentUpdate,$timestamp;
	include("config/config_napthe.ini");
	include("config/config_AccAutoCard.php");
	include("BKTransactionAPI.php");
	
	$bk = new BKTransactionAPI("https://www.baokim.vn/services/transaction_api/init?wsdl");

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

			$money_add=$menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$money_add=$money_add+(($money_add*$khuyenmai_phantram)/100);
			}

			$test->info_card=str_replace(".0","",$test->info_card);	
			mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$test->info_card','$TypeIns','$timestamp','$timestamp','1')") or die("SQL Error #1");
			mssql_query("UPDATE MEMB_INFO SET $Colum_Bank_MoneyC1=$Colum_Bank_MoneyC1+$money_add Where memb___id='$login'") or die("SQL Error #2");
			WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$test->info_card</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($money_add)."</font> $name_money_cap1","NapThe");
			$Content= "AUTO|OK|$test->info_card|$money_add";
		}
	}
	else
	{
		WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$TypeIns</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($b->status_message)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$test->error_message";
	}
	
	echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
}
//Ngan luong $cardseri,$cardcode
function nganluong_service($cardseri,$cardcode,$type,$login)
{
	global $ContentUpdate,$timestamp;
	include("config.php");
	include("config/config_napthe.ini");
	include("config/config_AccAutoCard.php");
	include("MobiCard.php");
	
	$call = new MobiCard();
	$rs = new Result();
	$ref_code = $_POST['ref_code'];
		  
	$rs = $call->CardPay($cardcode,$cardseri,$type,$login,$login,"","",$nl_id,$nl_secure_pass,$nl_username);
	
	if($rs->error_code==0)
	{
		switch($rs->card_amount)
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
					$TypeIns='Gate'; break;	
				case 'VCOIN': 
					$phantramchenh=$phantramchenh_vtc;
					$TypeIns='VTC'; break;	
			}

			$money_add = $menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$money_add=$money_add+(($money_add*$khuyenmai_phantram)/100);
			}
			$time_now=$timestamp;
			//$test->info_card=str_replace(".0","",$test->info_card);	
			mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$rs->card_amount','$TypeIns','$time_now','$time_now','1')") or die("SQL Error #1");
			mssql_query("UPDATE MEMB_INFO SET Bank_MoneyC1=Bank_MoneyC1+$money_add Where memb___id='$login'") or die("SQL Error #2");
			WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$rs->card_amount</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($money_add)."</font> $name_money_cap1","NapThe");
			$Content= "AUTO|OK|$rs->card_amount|$money_add";
		}
	}
	else
	{
		WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$TypeIns</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($b->status_message)</i>","NapThe_Sai");
		$Content= "AUTO|ERROR|$rs->error_message";
	}
	
	echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
}
function napthetudong($cardseri,$cardcode,$type,$login)
{
	if($type=='VTC'){$cardseri=str_replace("PM","",$cardseri);}
	
	global $ContentUpdate,$timestamp;
	include("config/config_napthe.ini");
	include("config/config_AccAutoCard.php");
	include("config/config_APIAutoCard.php");
	
		//khai bao du lieu
		switch($type)
		{
			case 'VTC':
				$url=$url_vtc;
				$data=$data_vtc;
				$text_report_title=$text_report_title_vtc;
				break;
			case 'Gate':
				$url=$url_gate;
				$data=$data_gate;
				$text_report_title=$text_report_title_gate;
				break;
		}
		
		//get data
	    $ReadHtml = httppost($userAgent,$url,$data,$cookie);
		iF(empty($ReadHtml))
		{
			$Content="Không kết nối được đến hệ thống nạp thẻ";
		}
		else
		{
		    $report_title=GetTextByText($text_report_title,$ReadHtml);

		    //gate: đã nạp thành công với mệnh giá
		    //vtc: Nạp Vcoin thành công
		    if(strstr($report_title,"Vcoin thành công") && $type=='VTC')
		    {
				$amount=GetTextByText($text_report_amount_vtc,$ReadHtml);
				$phantramchenh=$phantramchenh_vtc;
			}
			elseif((strstr($report_title,"thành công với") && $type=='Gate'))
			{
				$amount=explode(" ",$report_title);
				$amount=str_replace(",","",$amount[7]);
				$phantramchenh=$phantramchenh_gate;
			}
				
			if(isset($amount))
			{
				if($amount=='100' || $amount=='10000'){$menhgia=$menhgia10000; $MenhGiaThuc=10000;}
				elseif($amount=='200' || $amount=='20000') {$menhgia=$menhgia20000; $MenhGiaThuc=20000;}
				elseif($amount=='300' || $amount=='30000') {$menhgia=$menhgia30000; $MenhGiaThuc=30000;}
				elseif($amount=='500' || $amount=='50000') {$menhgia=$menhgia50000; $MenhGiaThuc=50000;}
				elseif($amount=='1000' || $amount=='100000') {$menhgia=$menhgia100000; $MenhGiaThuc=100000;}
				elseif($amount=='2000' || $amount=='200000') {$menhgia=$menhgia200000; $MenhGiaThuc=200000;}
				elseif($amount=='3000' || $amount=='300000' ) {$menhgia=$menhgia300000; $MenhGiaThuc=300000;}
				elseif($amount=='5000' || $amount=='500000') {$menhgia=$menhgia500000; $MenhGiaThuc=500000;}
				elseif($amount=='10000' || $amount=='1000000') {$menhgia=$menhgia1000000; $MenhGiaThuc=1000000;}
				
				$money_add=$menhgia+(($menhgia*$phantramchenh)/100);
				if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
				{
					$money_add=$money_add+(($money_add*$khuyenmai_phantram)/100);
				}

				mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$MenhGiaThuc','$type','$timestamp','$timestamp','1')") or die("SQL Error #1");
				mssql_query("UPDATE MEMB_INFO SET $Colum_Bank_MoneyC1=$Colum_Bank_MoneyC1+$money_add Where memb___id='$login'") or die("SQL Error #2");
				WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$MenhGiaThuc</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($money_add)."</font> $name_money_cap1","NapThe");
				$Content= "AUTO|OK|$MenhGiaThuc|$money_add";
		    }
		    else
		    {
		    	if($type=='VTC'){
		    		$SystemReport=GetTextByText($text_report_error_vtc,$ReadHtml);
		    		$Content= "AUTO|ERROR|".$SystemReport;
		    	}
		    	else {
		    		$SystemReport=str_replace($taikhoan_gate,"",$report_title);
		    		$Content= "AUTO|ERROR|".$SystemReport;
		    	}
		    	WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($SystemReport)</i>","NapThe_Sai");
		    }
		}
	    echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
}

function knw_mobi($cardcode,$cardseri,$login,$operator_id)
{
	global $ContentUpdate,$timestamp;
	include("config/config_napthe.ini");
	include("config/config_AccAutoCard.php");
	include("config/config_APIAutoCard.php");
	
	switch($operator_id){
		case '1':
			$phantramchenh=$phantramchenh_mobi;
			$type='MOBI';
			break;
		case '2':
			$phantramchenh=$phantramchenh_vina;
			$type='VINA';
			break;
		case '3':
			$phantramchenh=$phantramchenh_viettel;
			$type='Viettel';
			break;
	}
		
	$Sign=md5("$CP_ID;$cardcode;$cardseri;$operator_id;$KEY");
	$string=file_get_contents("http://api2.kingnetwork.vn/kgpay/AdCardV2.aspx?Cp_id=$CP_ID&Pin=$cardcode&Seri=$cardseri&Operator_id=$operator_id&Sign=$Sign");
	
	if(empty($string))
	{
		$Content="Không kết nối được đến hệ thống nạp thẻ";
	}
	else
	{
		if(strstr($string,'|'))
		{
			$info=explode('|',$string);
			if(strstr($string,':')) {
				$DuLieu=explode(':',$info[2]);
				$Notice=$DuLieu[1];
			} else $Notice=$info[2];
			
			switch($info[0])
			{
				case '0':
					switch($info[1])
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
						$money_add=$menhgia+(($menhgia*$phantramchenh)/100);
						if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
						{
							$money_add=$money_add+(($money_add*$khuyenmai_phantram)/100);
						}
	
						mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$info[1]','$type','$timestamp','$timestamp','1')") or die("SQL Error #1");
						mssql_query("UPDATE MEMB_INFO SET $Colum_Bank_MoneyC1=$Colum_Bank_MoneyC1+$money_add Where memb___id='$login'") or die("SQL Error #2");
						WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$info[1]</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($money_add)."</font> $name_money_cap1","NapThe");
						$Content= "AUTO|OK|$info[1]|$money_add";
					}
					break;
				case '1':
					WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($Notice)</i>","NapThe_Sai");
					$Content= "AUTO|ERROR|$Notice";
					break;
			}
		}
		else
		{
			$Content='Hệ thống nạp thẻ bị lỗi';
		}
	}
	echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
}

function knp_napthe($TxtMaThe,$TxtSeri,$login,$TxtType)
{
	global $ContentUpdate,$timestamp;
	include("config/config_napthe.ini");
	include("config/config_AccAutoCard.php");
	include("class.gateWay.php");

	switch($TxtType){
		case 'VTT':
			$phantramchenh=$phantramchenh_viettel;
			$TxtUrl  = 'http://api2.cbviet.net:64990';
			$type='Viettel';
			break;
		case 'VMS':
			$phantramchenh=$phantramchenh_mobi;
			$TxtUrl  = 'http://api2.cbviet.net:64980';
			$type='MOBI';
			break;
		case 'VNP':
			$phantramchenh=$phantramchenh_vina;
			$TxtUrl  = 'http://api2.cbviet.net:64980';
			$type='VINA';
			break;
		case 'GATE':
			$phantramchenh=$phantramchenh_gate;
			$TxtUrl  = 'http://api2.cbviet.net:64986';
			$type='Gate';
			break;
		case 'VTC':
			$phantramchenh=$phantramchenh_vtc;
			$TxtUrl  = 'http://api2.cbviet.net:64987';
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

		WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$TxtSeri</b>. Thông báo từ hệ thống : <i>($Notice)</i>","NapThe_Sai");
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
			$money_add=$menhgia+(($menhgia*$phantramchenh)/100);
			if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
			{
				$money_add=$money_add+(($money_add*$khuyenmai_phantram)/100);
			}
			mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$TxtMaThe','$TxtSeri','$TienDuocHuong','$type','$timestamp','$timestamp','1')") or die("SQL Error #1");
			mssql_query("UPDATE MEMB_INFO SET $Colum_Bank_MoneyC1=$Colum_Bank_MoneyC1+$money_add Where memb___id='$login'") or die("SQL Error #2");
			WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$TxtSeri</b>, Mệnh giá :<b>$TienDuocHuong</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($money_add)."</font> $name_money_cap1","NapThe");
			$Content= "AUTO|OK|$TienDuocHuong|$money_add";
		}
	}
	
	echo "<Response>".$Content."</Response>".$ContentUpdate;exit();	
}
function dto_service($cardseri,$cardcode,$type,$login)
{
	global $ContentUpdate,$timestamp;
	include("config/config_napthe.ini");
	include("config/config_AccAutoCard.php");
	include("config/config_APIAutoCard.php");
	include("dto_service_charge.php");
	
	$request = new InfoRequest();
	$request->number_card=$cardcode;
	$request->type_card=$type;
	$request->username=$User_DTO;
	$request->seri_card=$cardseri;
	$request->time="yyyyMMddHHmmss";
	$stringToSign = "$request->number_card$request->seri_card$request->time$request->username"; 
	$hasher =hmac_sha1(trim($Secretkey_DTO),trim($stringToSign)); 
	//$signature = hex2b64($hasher);
	$request->sign=$signature;
	$a = new DTOService();
	$b= $a->chargeCard($request);
	
	switch($b->status)
	{
		case '1':
			switch($b->amount)
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
					case 'VNP': 
						$phantramchenh=$phantramchenh_vina;
						$TypeIns='VINA'; break;
					case 'MBF': 
						$phantramchenh=$phantramchenh_mobi;
						$TypeIns='MOBI'; break;
						
					case 'VT': 
						$phantramchenh=$phantramchenh_viettel;
						$TypeIns='Viettel'; break;
					case 'GATE': 
						$phantramchenh=$phantramchenh_gate;
						$TypeIns='Gate'; break;
					case 'VC': 
						$phantramchenh=$phantramchenh_vtc;
						$TypeIns='VTC'; break;
					}

				$money_add=$menhgia+(($menhgia*$phantramchenh)/100);
				if($timestamp>=strtotime($khuyenmai_from.' 00:00:00') && $timestamp<=strtotime($khuyenmai_to.' 23:59:00'))
				{
					$money_add=$money_add+(($money_add*$khuyenmai_phantram)/100);
				}
					
				mssql_query("insert into FW_Card(AccountID,Card_Code,Card_Seri,Card_Ammount,Card_Type,Time_Charge,Time_Process,Status) values('$login','$cardcode','$cardseri','$b->amount','$TypeIns','$timestamp','$timestamp','1')") or die("SQL Error #1");
				mssql_query("UPDATE MEMB_INFO SET $Colum_Bank_MoneyC1=$Colum_Bank_MoneyC1+$money_add Where memb___id='$login'") or die("SQL Error #2");
				WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>, Mệnh giá :<b>$b->amount</b>. Tài khoản được cộng thêm <font color=#FF0000>".number_format($money_add)."</font> $name_money_cap1","NapThe");
				$Content= "AUTO|OK|$b->amount|$money_add";
			}
			break;
		case '0':
			WriteLog("Tài khoản <b>$login</b> nạp thẻ <b>$type</b> với Serial : <b>$cardseri</b>. Thông báo từ hệ thống : <i>($b->status_message)</i>","NapThe_Sai");
			$Content= "AUTO|ERROR|$b->status_message";
			break;
	}
	
	echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
}

function LogAction($login,$IP,$Action)
{
	global $timestamp;
	/*
	1-Đăng nhập tài khoản
	2-Đổi câu hỏi và câu trả lời bí mật
	3-Đổi Email
	4-Đổi mật khẩu cấp 1
	5-Đổi mật khẩu cấp 2
	6-Đổi mật khẩu Game
	7-Đổi số điện thoại
	8-Cập nhật/tiếp tục bảo vệ IP
	9-Hủy bảo vệ IP
	*/
	
	//Xóa log đăng nhập nếu Count SQl trên 50k
	$CheckNumLog=mssql_query("select AccountID from FW_Log_Action where Action=1");
	if(mssql_num_rows($CheckNumLog)>5000){
		mssql_query("delete from FW_Log_Action where Action=1");
	}
	mssql_query("Insert Into FW_Log_Action(AccountID,IP,Action,Time) values ('$login','$IP','$Action','$timestamp')");
}

function check_otp_sms($login,$otp_code)
{
	include('config/config_sms.ini');
	global $timestamp;
	$sql_check_otp=mssql_query("select * from FW_OTP_SMS where AccountID='$login' AND OTP='$otp_code' AND Status='0'");
	$row=mssql_fetch_row($sql_check_otp);
	$Timewait_Auth=$Timewait_Auth*60;
	
	$CheckTime_Auth=$timestamp-$row[2];
	
	if(mssql_num_rows($sql_check_otp)>0)
	{
		if($CheckTime_Auth>$Timewait_Auth) return false;
		else {
			mssql_query("Update FW_OTP_SMS set Status='1' where AccountID='$login'");
			return true;
		}
	}
	else return false;
}

function RestartResetDay()
{	
	$check_resetday=mssql_query("select TimeCheck from FW_CheckTime where DATEPART(Day,convert(varchar, getdate(), 25))<>DATEPART(Day,TimeCheck)");
	
	if(mssql_num_rows($check_resetday)>0){
		//Reset all
		mssql_query("UPDATE character SET Event_MoneyvsUT_UTType_Day=0 where Event_MoneyvsUT_UTType_Day>0");
		mssql_query("UPDATE character SET Event_MoneyvsUT_MoneyType_Day=0 where Event_MoneyvsUT_MoneyType_Day>0");
		mssql_query("UPDATE character SET Event_MoneyvsUT_ItemType_Day=0 where Event_MoneyvsUT_ItemType_Day>0");
		mssql_query("UPDATE Character SET Day_Reset=0 where Day_Reset>0");
		mssql_query("UPDATE Character SET DanhVong_Day=0 where DanhVong_Day>0");
		
		//Update time
		mssql_query("Update FW_CheckTime set TimeCheck=convert(varchar, getdate(), 25)");
	}
}

function serial_gen()
{
	$serial_query = mssql_query("EXEC WZ_GetItemSerial");	
	$serial= mssql_fetch_row($serial_query);
	
	return dechex($serial[0]);
}

function jewel_check($character)
{
	global $Length_Loop;
	include('config/config_item_to_money2.ini');
	include('config/config_event_item_point.ini');
	include('config/config_event_item_to_PointUT.ini');
	include('config/config_ItemGame2PointEvent.ini');
	
	$codehex1_itemUTevent=hexdec(substr($eventUT_item_code, 0, 4));
	$codehex2_itemUTevent=hexdec(substr($eventUT_item_code, 18, 1));
		
	$codehex1_itemtrade1=hexdec(substr($item_code, 0, 4));
	$codehex2_itemtrade1=hexdec(substr($item_code, 18, 1));

	$codehex1_itemtrade2=hexdec(substr($item_code2, 0, 4));
	$codehex2_itemtrade2=hexdec(substr($item_code2, 18, 1));
	
	$codehex1_eventpoint1=hexdec(substr($event1_item_point_itemdrop1_code, 0, 4));
	$codehex2_eventpoint1=hexdec(substr($event1_item_point_itemdrop1_code, 18, 1));
	
	$codehex1_eventpoint2=hexdec(substr($event1_item_point_itemdrop2_code, 0, 4));
	$codehex2_eventpoint2=hexdec(substr($event1_item_point_itemdrop2_code, 18, 1));

	$codehex1_eventpoint3=hexdec(substr($event1_item_point_itemshop_code, 0, 4));
	$codehex2_eventpoint3=hexdec(substr($event1_item_point_itemshop_code, 18, 1));

	$codehex1_eventpoint4=hexdec(substr($ItemGame2PointEvent_Item_Code, 0, 4));
	$codehex2_eventpoint4=hexdec(substr($ItemGame2PointEvent_Item_Code, 18, 1));
				
	$inventory = inventory($character);

	$chaos = 0;
	$cre = 0;
	$blue = 0;
	$xu=0;
	$gold=0;
	$goldxu=0;
	$itemtrade1=0;
	$itemtrade2=0;
	$itempoint1=0;
	$itempoint2=0;
	$itempoint3=0;
	$itempoint4=0;
	$itemUT=0;
	
	for($x=0; $x<$Length_Loop; ++$x)
	{
		$item = substr($inventory,$x*32,32);
		$code = hexdec(substr($item, 0, 4));
		$code2 = hexdec(substr($item, 18, 1));
		$code3 = hexdec(substr($item, 3, 1));
		
		if($code == 3840  AND $code2 == 12)
		++$chaos;
		if($code == 5632 AND $code2 == 14)
		++$cre;
		if($code == 3584 AND $code2 == 13)
		++$blue;
		if($code == 3072 AND $code2 == 14)
		++$xu;
		if($code == 3088 AND $code2 == 14)
		++$gold;
		if($code == 3840 AND $code2 == 14)
		++$goldxu;
		if($code == $codehex1_itemtrade1 AND $code2 == $codehex2_itemtrade1)
		++$itemtrade1;
		if($code == $codehex1_itemtrade2 AND $code2 == $codehex2_itemtrade2)
		++$itemtrade2;
		if($code == $codehex1_eventpoint1 AND $code2 == $codehex2_eventpoint1)
		++$itempoint1;
		if($code == $codehex1_eventpoint2 AND $code2 == $codehex2_eventpoint2)
		++$itempoint2;
		if($code == $codehex1_eventpoint3 AND $code2 == $codehex2_eventpoint3)
		++$itempoint3;
		if($code == $codehex1_eventpoint4 AND $code2 == $codehex2_eventpoint4)
		++$itempoint4;
		if($code == $codehex1_itemUTevent AND $code2 == $codehex2_itemUTevent)
		++$itemUT;
	}

	$j = array( 
			'Chaos' => $chaos,
			'Cre' => $cre,
			'Blue' => $blue,
			'Xu' => $xu,
			'Gold' => $gold,
			'GoldXu' => $goldxu,
			'ItemTrade1' => $itemtrade1,
			'ItemTrade2' => $itemtrade2,
			'ItemEventPoint1' => $itempoint1,
			'ItemEventPoint2' => $itempoint2,
			'ItemPointShop' => $itempoint3,
			'ItemEventInGame' => $itempoint4,
			'ItemEventToPointUT' => $itemUT
	);
	return $j;
}

function clear_jewel($codehex1,$codehex2,$Number,$Name)
{	
	global $Length_Loop;
	$inventory = inventory($Name);
	$inventory_dupe = $inventory;
	
	//$SQLGetAcc = mssql_query("select AccountID from character where Name = '$Name'");
	//$GetAcc = mssql_fetch_row($SQLGetAcc);
	//$Acc = $GetAcc[1];
	
	//Blocked
	//mssql_query("update memb_info set bloc_code='1',PendingBlock='1' where memb___id='$Acc'");
	mssql_query("update character set ctlcode = 1 where name = '$Name'");
					
	$codehex1=hexdec($codehex1);
	$codehex2=hexdec($codehex2);
	$CountNum=0;
	$isDupe=false;
	$Log_Content = '';
	for($x=0; $x<$Length_Loop; ++$x)
	{
		$item = substr($inventory,$x*32,32);
		$code = hexdec(substr($item, 0, 4));
		$code2 = hexdec(substr($item, 18, 1));
		$Serial =substr($item,6,8);
		
		if($code == $codehex1 AND $code2 == $codehex2)
		{		
			$Item_Count=0;
			$Serial =substr($item,6,8);
			if($Serial!='00000000' AND $Serial!='FFFFFFFF'){
				$NumItem=mssql_num_rows(mssql_query("select [Name] from [Character] where (charindex (0x$Serial, Inventory) %16=4)")); 
				$Item_Count+=$NumItem;
				$NumItem=mssql_num_rows(mssql_query("select [AccountId] from [warehouse] where (charindex (0x$Serial, Items) %16=4)"));
				$Item_Count+=$NumItem;
				$NumItem=mssql_num_rows(mssql_query("select [AccountId],[Number] from [ExtWarehouse] where (charindex (0x$Serial, Items) %16=4)"));
				$Item_Count+=$NumItem;
			}
			
			if($Item_Count>1){
				$isDupe=true;
				$inventory_dupe=str_replace($item,"FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF",$inventory_dupe);
				$item_info = check_code($item,1);
				$Log_Content.="(".$Item_Count." ".str_replace('<br>','',$item_info[name]).". Serial: $Serial)<br>";
			}
			else{
				if($CountNum == $Number) break;
				else {
					$CountNum++;
					$inventory=str_replace($item,"FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF",$inventory);
				}
			}
		}
	}

	//Unblock
	//mssql_query("update memb_info set bloc_code='0',PendingBlock='0' where PendingBlock='1' And memb___id='$Acc'");
	mssql_query("update character set ctlcode = 0 where name = '$Name'");
				
	if($isDupe){
		//mssql_query("Update character set inventory=0x$inventory_dupe where Name='$Name'");
		//echo "<Response>Giao dịch thất bại. Hệ thống tự động xóa Item Dupe trong lần giao dịch này.</Response>".$ContentUpdate;
		echo "<Response>Có thể nhân vật này đang chứa Item dupe. Hoặc item bạn đang muốn đổi hoặc gửi vừa được giao dịch xong. Vui lòng thử lại vào lúc khác...</Response>".$ContentUpdate;
		
		$Log_Content.="---------------------------------------------------------------------------------------<br><br>";
		//$Log_Content = "Xóa Item Dupe nhân vật $Name. Danh sách item Dupe:<br>".$Log_Content;
		$Log_Content = "Nhân vật $Name nghi vấn có chứa Item Dupe.<br>";
		WriteLog($Log_Content,"DupeItem");
		exit();
	}else{
		mssql_query("Update character set inventory=0x$inventory where Name='$Name'");
	}
}

function item_character($ItemCode,$number,$Name)
{
	global $ContentUpdate;

	$inventory = inventory($Name);
		
	for($j=0;$j<$number;$j++) 
	{
		$serial=strtoupper(serial_gen());
		$len_serial = strlen($serial);
		$pos_replace_start = 6+(8-$len_serial);
		$ItemCode = substr_replace($ItemCode, $serial, $pos_replace_start, -18);
		$slot = Slot_AddItemCharacter($ItemCode,$Name,$inventory);
		if(is_numeric($slot)){
			$inventory = substr_replace($inventory,$ItemCode,384+($slot*32),32);
		}
		else{
			$Content= "Thùng đồ nhân vật $Name không đủ chỗ trống.";
			echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
		}
	}
	mssql_query("Update character set inventory=0x$inventory where Name='$Name'") or die("Lỗi SQL");
}

function item_warehouse($ItemCode,$number,$Accountid)
{
	global $ContentUpdate;
	
	$inventory = warehouse($Accountid);

	for($j=0;$j<$number;$j++) 
	{
		$serial=strtoupper(serial_gen());
		$len_serial = strlen($serial);
		$pos_replace_start = 6+(8-$len_serial);
		$ItemCode = substr_replace($ItemCode, $serial, $pos_replace_start, -18);
		$slot = Slot_AddItemWarehouse($ItemCode,$Accountid,$inventory);

		if(is_numeric($slot)){
			$inventory = substr_replace($inventory,$ItemCode,$slot*32,32);	
		}
		else{
			$Content= "Thùng đồ chung của bạn không đủ chỗ trống.";
			echo "<Response>".$Content."</Response>".$ContentUpdate;exit();
		}
	}
	
	mssql_query("Update warehouse set Items=0x$inventory where Accountid='$Accountid'") or die("Lỗi SQL");
}

function Slot_AddItemCharacter($ItemCode,$Name,$inventory)
{
	$Slot_Finish = array(7,15,23,31,39,47,55,63);
	$inventory_part1 = substr($inventory,0,384);
	$inventory_part2 = substr($inventory,384,2048);
	$inventory_part3 = substr($inventory,2048,strlen($inventory));
	
	//Write item to array
	for($x = 0;$x < strlen($inventory_part2)/32; $x++){
		$item_check = substr($inventory_part2,$x*32,32);
		$slot[$x] = $item_check;
	}
	
	//Flag slot set
	for($i = 0; $i < count($slot); $i++){
		if($slot[$i] != 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' && $slot[$i] != 'slotUsed')
		{
			$item_info = check_code($slot[$i],1);
			for($z = $i; $z < $item_info[x]+$i; $z++) $slot[$z] = 'slotUsed';
			
			if($item_info[y] > 1){
				for($v = 0; $v < $item_info[y]; $v++){
					for($d = 0; $d < $item_info[x]; $d++){
						$slot[$i+($v*8)+$d] = 'slotUsed';
					}
				}
			}
		}
	}

	//check slot available
	$FlagX = false;
	$FlagY = false;
	$item_info = check_code($ItemCode,1);
	$Num_Row = 0;
	for($i = 0; $i < count($slot); $i++)
	{
		if($i > 0 && $i % 8 == 0) $Num_Row++;
		if($slot[$i] != 'slotUsed')
		{
			if($item_info[x] == 1 && $item_info[y] == 1) {
				$FlagX = true;
				$FlagY = true;
				break;
			}
			else
			{
				if($item_info[x] == 1) $FlagX = true;
				else if($item_info[x] > 1)
				{
					if(($i + $item_info[x]) > $Slot_Finish[$Num_Row]){
						$FlagX = false;
					}
					else{
						for($z = $i; $z < $item_info[x]+$i; $z++){
							if($slot[$z] == 'slotUsed') $FlagX = false;
							else $FlagX = true;
						}
					}
				}
				
				if($item_info[y] == 1) $FlagY = true;
				elseif($item_info[y] > 1){
					for($v = 0; $v < $item_info[y]; $v++){
						for($d = 0; $d < $item_info[x]; $d++){
							if($slot[$i+($v*8)+$d] == 'slotUsed' || strlen($slot[$i+($v*8)+$d]) == 0) $FlagY = false;
							else $FlagY = true;
						}
					}
				}
				if($FlagX && $FlagY) break;
			}
		}
	}
	if($FlagX && $FlagY){
		return $i;
	}
	else return 'NoSlot';
}

function Slot_AddItemWarehouse($ItemCode,$Accountid,$inventory)
{
	
	//Write item to array
	for($x = 0;$x < strlen($inventory)/32; $x++){
		$item_check = substr($inventory,$x*32,32);
		$slot[$x] = $item_check;
	}

	//Flag slot set
	for($i = 0; $i < count($slot); $i++){
		if($slot[$i] != 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF' && $slot[$i] != 'slotUsed')
		{
			$item_info = check_code($slot[$i],1);
			for($z = $i; $z < $item_info[x]+$i; $z++) $slot[$z] = 'slotUsed';
			
			if($item_info[y] > 1){
				for($v = 0; $v < $item_info[y]; $v++){
					for($d = 0; $d < $item_info[x]; $d++){
						$slot[$i+($v*8)+$d] = 'slotUsed';
					}
				}
			}
		}
	}

	//check slot available
	$FlagX = false;
	$FlagY = false;
	$item_info = check_code($ItemCode,1);
	for($i = 0; $i < count($slot); $i++)
	{
		if($slot[$i] != 'slotUsed')
		{
			if($item_info[x] == 1 && $item_info[y] == 1) {
				$FlagX = true;
				$FlagY = true;
				break;
			}
			else
			{
				if($item_info[x] == 1) $FlagX = true;
				else if($item_info[x] > 1)
				{
					for($z = $i; $z < $item_info[x]+$i; $z++){
						if($slot[$z] == 'slotUsed' || strlen($slot[$z]) == 0) $FlagX = false;
						else $FlagX = true;
					}
				}
				
				if($item_info[y] == 1) $FlagY = true;
				elseif($item_info[y] > 1){
					for($v = 0; $v < $item_info[y]; $v++){
						for($d = 0; $d < $item_info[x]; $d++){
							if($slot[$i+($v*8)+$d] == 'slotUsed' || strlen($slot[$i+($v*8)+$d]) == 0) $FlagY = false;
							else $FlagY = true;
						}
					}
				}
				if($FlagX && $FlagY) break;
			}
		}
	}
	if($FlagX && $FlagY){
		return $i;
	}
	else return 'NoSlot';
}

function CheckResetInTime($Name)
{
	global $timestamp,$event_toprs_on;
	include('config/config_event_top_reset.ini');
	
	$TimeBegin=strtotime($event_toprs_begin);
	$TimeEnd=strtotime($event_toprs_end.' 23:59:00');
	
	if($event_toprs_on){
		if($TimeBegin<$timestamp && $TimeEnd>$timestamp){
			//Kiem tra da co du lieu trong data Event_TOP_RS
			$data_check_sql = mssql_query("SELECT * FROM TopResetInTime WHERE name='$Name' And Code='$event_toprs_code'");
			$data_check = mssql_num_rows($data_check_sql);
			//Du lieu da co
			if($data_check > 0) 
			{
				$update_data_query = "UPDATE TopResetInTime SET resets=resets+1 WHERE name='$Name' And Code='$event_toprs_code'";
				$update_data_result = mssql_query($update_data_query) OR DIE("Loi Query: $update_data_query");
			}
			//Du lieu chua co
			else {
				$insert_data_query = "INSERT INTO TopResetInTime (name,resets,Code) VALUES ('$Name','1','$event_toprs_code')";
				$insert_data_result = mssql_query($insert_data_query) OR DIE("Loi Query: $insert_data_query");
			}	
		}
	}
}

function CheckEventDanhVong($Name){

	global $timestamp,$IsAdd_DanhVong,$event_danhvong_on;
	
	include('config/config_diemdanhvong.ini');
	
	if($event_danhvong_on && strtotime($event_danhvong_begin) < $timestamp && (strtotime($event_danhvong_begin) + ($event_danhvong_week_end*7)*86400) > $timestamp){
		if($IsAdd_DanhVong){
			$event_code=date('WY');
			//Kiem tra da co du lieu trong data Event_TOP_RS
			$data_check_sql = mssql_query("SELECT * FROM TopDanhVongInTime WHERE name='$Name' And Code='$event_code'");
			$data_check = mssql_num_rows($data_check_sql);
			//Du lieu da co
			if($data_check > 0) 
			{
				$update_data_query = "UPDATE TopDanhVongInTime SET DanhVong=DanhVong+1 WHERE name='$Name' And Code='$event_code'";
				$update_data_result = mssql_query($update_data_query) OR DIE("Loi Query: $update_data_query");
			}
			//Du lieu chua co
			else {
				$insert_data_query = "INSERT INTO TopDanhVongInTime (name,DanhVong,Code) VALUES ('$Name','1','$event_code')";
				$insert_data_result = mssql_query($insert_data_query) OR DIE("Loi Query: $insert_data_query");
			}
		}	
	}
		
}

function CheckEventManhTuong($Name,$number){

	global $timestamp,$Event_TopMT_on;
	
	include('config/config_ItemGame2PointEvent.ini');
	
	if($Event_TopMT_on){
		if(strtotime($ItemGame2PointEvent_Begin) < $timestamp && (strtotime($ItemGame2PointEvent_Begin) + ($ItemGame2PointEvent_Week_End*7)*86400) > $timestamp){
			$event_code=date('WY');
			//Kiem tra da co du lieu trong data Event_TOP_RS
			$data_check_sql = mssql_query("SELECT * FROM TopManhTuongInTime WHERE name='$Name' And Week='$event_code'");
			$data_check = mssql_num_rows($data_check_sql);
			//Du lieu da co
			if($data_check > 0) 
			{
				$update_data_query = "UPDATE TopManhTuongInTime SET Points=Points+$number WHERE name='$Name' And Week='$event_code'";
				$update_data_result = mssql_query($update_data_query) OR DIE("Loi Query: $update_data_query");
			}
			//Du lieu chua co
			else {
				$insert_data_query = "INSERT INTO TopManhTuongInTime VALUES ('$Name','$number','$event_code')";
				$insert_data_result = mssql_query($insert_data_query) OR DIE("Loi Query: $insert_data_query");
			}
		}	
	}
}

function CheckEventTopResetMini($Name){

	global $timestamp,$Event_TopRsMini_on,$EventTopRsMini_Start;
	
	include('config/config_event_topreset_mini.ini');
	
	if($Event_TopRsMini_on && $EventTopRsMini_Start){
		if(strtotime($TopResetMini_Begin) < $timestamp && (strtotime($TopResetMini_Begin) + ($TopResetMini_Week_End*7)*86400) > $timestamp){
			$event_code=date('WY');
			//Kiem tra da co du lieu trong data Event_TOP_RS
			$data_check_sql = mssql_query("SELECT * FROM TopResetMiniInTime WHERE name='$Name' And WeekYear='$event_code'");
			$data_check = mssql_num_rows($data_check_sql);
			//Du lieu da co
			if($data_check > 0) 
			{
				$update_data_query = "UPDATE TopResetMiniInTime SET Resets=Resets+1 WHERE name='$Name' And WeekYear='$event_code'";
				$update_data_result = mssql_query($update_data_query) OR DIE("Loi Query: $update_data_query");
			}
			//Du lieu chua co
			else {
				$insert_data_query = "INSERT INTO TopResetMiniInTime VALUES ('$Name','1','$event_code')";
				$insert_data_result = mssql_query($insert_data_query) OR DIE("Loi Query: $insert_data_query");
			}
		}	
	}
}

function CheckEventPointInTime($Name,$number)
{
	global $timestamp,$event_toppoint_on;
	include('config/config_event_top_item_point.ini');
	
	$TimeBegin=strtotime($event_toppoint_begin);
	$TimeEnd=strtotime($event_toppoint_end.' 23:59:00');
	
	if($event_toppoint_on){
		if($TimeBegin<$timestamp && $TimeEnd>$timestamp){
			//Kiem tra da co du lieu trong data Event_TOP_RS
			$data_check_sql = mssql_query("SELECT * FROM TopPointInTime WHERE name='$Name' And Code='$event_itempoint_code'");
			$data_check = mssql_num_rows($data_check_sql);
			//Du lieu da co
			if($data_check > 0) 
			{
				$update_data_query = "UPDATE TopPointInTime SET Points=Points+$number WHERE name='$Name' And Code='$event_itempoint_code'";
				$update_data_result = mssql_query($update_data_query) OR DIE("Loi Query: $update_data_query");
			}
			//Du lieu chua co
			else {
				$insert_data_query = "INSERT INTO TopPointInTime (name,Points,Code) VALUES ('$Name','$number','$event_itempoint_code')";
				$insert_data_result = mssql_query($insert_data_query) OR DIE("Loi Query: $insert_data_query");
			}	
		}
	}
}

function CheckResetMonth($Name)
{
	global $timestamp;
	$monthyear=date("m/Y",$timestamp);
	
	//Kiem tra da co du lieu trong data top rs thang
	$data_check_sql = mssql_query("SELECT * FROM TopResetInMonth WHERE name='$Name' And MonthYear='$monthyear'");
	$data_check = mssql_num_rows($data_check_sql);
	//Du lieu da co
	if($data_check > 0) 
	{
		$update_data_query = "UPDATE TopResetInMonth SET resets=resets+1 WHERE name='$Name' And MonthYear='$monthyear'";
		$update_data_result = mssql_query($update_data_query) OR DIE("Loi Query: $update_data_query");
	}
	//Du lieu chua co
	else {
		$insert_data_query = "INSERT INTO TopResetInMonth (name,resets,MonthYear) VALUES ('$Name','1','$monthyear')";
		$insert_data_result = mssql_query($insert_data_query) OR DIE("Loi Query: $insert_data_query");
	}	
}

function CheckResetWeek($Name)
{
	global $timestamp;
	$weekyear=date("W/Y",$timestamp);
	
	//Kiem tra da co du lieu trong data top rs thang
	$data_check_sql = mssql_query("SELECT * FROM TopResetInWeek WHERE name='$Name' And WeekYear='$weekyear'");
	$data_check = mssql_num_rows($data_check_sql);
	//Du lieu da co
	if($data_check > 0) 
	{
		$update_data_query = "UPDATE TopResetInWeek SET resets=resets+1 WHERE name='$Name' And WeekYear='$weekyear'";
		$update_data_result = mssql_query($update_data_query) OR DIE("Loi Query: $update_data_query");
	}
	//Du lieu chua co
	else {
		$insert_data_query = "INSERT INTO TopResetInWeek (name,resets,WeekYear) VALUES ('$Name','1','$weekyear')";
		$insert_data_result = mssql_query($insert_data_query) OR DIE("Loi Query: $insert_data_query");
	}	
}

function warehouse($acc)
{
	$warehouse_result_sql = mssql_query("SELECT CAST(CAST(Items AS varbinary(3840)) AS image) AS col FROM Warehouse where Accountid='$acc'");
	$warehouse_result = mssql_fetch_row($warehouse_result_sql);
	$warehouse = $warehouse_result[0];
	$warehouse = bin2hex($warehouse);
	$warehouse = strtoupper($warehouse);
	return $warehouse;
}


function inventory($Name)
{
	global $Char_Inventory_Length;
	$inventory_result_sql = mssql_query("SELECT CAST(CAST(Inventory AS varbinary($Char_Inventory_Length)) AS image) AS col FROM Character where Name='$Name'");
	$inventory_result = mssql_fetch_row($inventory_result_sql);
	$inventory = $inventory_result[0];
	$inventory = bin2hex($inventory);
	$inventory = strtoupper($inventory);
	return $inventory;
}

function deleteItem($IsDel,$Type,$Code,$where)
{	
	$SerialItem=substr($Code,6,8);
	
	if(isset($IsDel)){
		if($Type == 'inv'){
			
			$inventory = inventory($where);

		    $item_total = floor(strlen($inventory)/32);
			for($i=0; $i<$item_total; $i++) {
			  	$item = substr($inventory,$i*32, 32);

			  	if(strstr($Code,$item)) {
			     	$inventory=str_replace($item,"FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF",$inventory);
			 	}
			}
			mssql_query("update character set Inventory=0x$inventory where name='$where'");
		}        
		elseif($Type=='ware'){
			$inventory = warehouse($where);
			
		    $item_total = floor(strlen($inventory)/32);
			for($i=0; $i<$item_total; $i++) {
			  	$item = substr($inventory,$i*32, 32);
				
			  	if(strstr($Code,$item)) {
			     	$inventory=str_replace($item,"FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF",$inventory);
			 	}
			}	        
			mssql_query("update warehouse set Items=0x$inventory where accountid='$where'");	
		}
	}
}
	
//Funtion quét toàn bộ item dupe trên tài khoản
function Check_Item_DupeAll($Account){
	global $ContentUpdate;

	$GetChar=mssql_query("select Name from character where accountid='$Account'");
	for($i=0;$i<mssql_num_rows($GetChar);$i++){
		$row=mssql_fetch_row($GetChar);

		$inventory = inventory($row[0]);

		//Scan iventory
		for($x=0; $x<floor(strlen($inventory)/32); ++$x)
		{
			$items = substr($inventory,$x*32,32);
			$Serial =substr($items,6,8);
			
			if($items != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF" && strlen($items)==32 && $Serial!="00000000" && $Serial!="FFFFFFFF")
			{
				$Serial_arr[]=$Serial;
				$Item_Code[$Serial]=$items;
			}
		}
	}
	
	//Scan warehouse
	$warehouse=warehouse($Account);
	
	for($x=0; $x<floor(strlen($warehouse)/32); ++$x)
	{
		$items = substr($warehouse,$x*32,32);
		$Serial =substr($items,6,8);
			
		if($items != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF" && strlen($items)==32 && $Serial!="00000000" && $Serial!="FFFFFFFF")
		{
			$Serial_arr[]=$Serial;
			$Item_Code[$Serial]=$items;
		}
	}
	
	//Kiem tra item dupe
	$ListSeri=arrayDuplicate($Serial_arr);
	$GetChar=mssql_query("select Name from character where accountid='$Account'");
	if(count($ListSeri[DupeSeri])>0){
		
		$Log_Content="Xóa Item Dupe tài khoản <b>$Account</b>. Danh sách item Dupe:<br>";
		
		for($i=0;$i<count($ListSeri[DupeSeri]);$i++){
			$SerialItem=$ListSeri[DupeSeri][$i];
			$Item_Code_Temp=$Item_Code[$SerialItem];
			$item_info=check_code($Item_Code_Temp,1);
			
			for($k=0;$k<mssql_num_rows($GetChar);$k++){
				$row2=mssql_fetch_row($GetChar);
				deleteItem('on','inv',$Item_Code_Temp,$row2[0]);
			}
			deleteItem('on','ware',$Item_Code_Temp,$Account);
			
			$Log_Content.="(".$ListSeri[CountDupe][$SerialItem]." ".str_replace('<br>','',$item_info[name]).". Serial: $SerialItem)<br>";
		}
		$Log_Content.="---------------------------------------------------------------------------------------<br><br>";
		
		
		WriteLog($Log_Content,"DupeItem");
		
		echo "<Response>Tài khoản $Account đang sở hữu Item Dupe. Toàn bộ Item Dupe đã bị xóa.</Response>".$ContentUpdate;
		exit();
	}
}

//Funtion quét toàn bộ item dupe trên nhân vật
function Check_Item_DupeChar($Name){
	global $ContentUpdate;
	
	$inventory = inventory($Name);

	//Scan iventory
	for($x=0; $x<floor(strlen($inventory)/32); ++$x)
	{
		$items = substr($inventory,$x*32,32);
		$Serial =substr($items,6,8);
			
		if($items != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF" && strlen($items)==32 && $Serial!="00000000" && $Serial!="FFFFFFFF")
		{
			$Serial_arr[]=$Serial;
			$Item_Code[$Serial]=$items;
		}
	}
	
	//Kiem tra item dupe
	$ListSeri=arrayDuplicate($Serial_arr);

	if(count($ListSeri[DupeSeri])>0){
		$Log_Content="Xóa Item Dupe nhân vật <b>$Name</b>. Danh sách item Dupe:<br>";
					
		for($u=0;$u<count($ListSeri[DupeSeri]);$u++)
		{
			$SerialItem=$ListSeri[DupeSeri][$u];
			$Item_Code_Temp=$Item_Code[$SerialItem];
			$item_info=check_code($Item_Code_Temp,1);
			
			$Log_Content.="(".$ListSeri[CountDupe][$SerialItem]." ".str_replace('<br>','',$item_info[name]).". Serial: $SerialItem)<br>";
			
		    $item_total = floor(strlen($inventory)/32);
			for($i=0; $i<$item_total; $i++) {
			  	$item = substr($inventory,$i*32, 32);
				$SerialCur=substr($item,6,8);
				
			  	if(strstr($SerialItem,$SerialCur)) {
			     	$inventory=str_replace($item,"FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF",$inventory);
			 	}
			}
		}			        
	  	mssql_query("update character set Inventory=0x$inventory where name='$Name'");

		
		$Log_Content.="---------------------------------------------------------------------------------------<br><br>";
		WriteLog($Log_Content,"DupeItem");
				
		echo "<Response>Nhân vật $Name đang sở hữu Item Dupe. Toàn bộ Item Dupe đã bị xóa.</Response>".$ContentUpdate;exit();
	}	
}


function Check_Item_Dupe($Serial,$Name){
	global $ContentUpdate,$timestamp;
	
	if($Serial!='00000000' && $Serial!='FFFFFFFF')
	{
		$GetAcc=mssql_fetch_row(mssql_query("select accountid from character where name='$Name'"));
		
		$inventory = inventory($Name);	
		$warehouse = warehouse($GetAcc[0]);
		
		$Item_Count=0;
		$IsDupe=false;
		//Kiem tra Dupe
		if ($NumItem=mssql_num_rows(mssql_query("select [Name] from [Character] where (charindex (0x$Serial, Inventory) %16=4)"))>0) 
		{$Item_Count+=$NumItem;}
		if($NumItem=mssql_num_rows(mssql_query("select [AccountId] from [warehouse] where (charindex (0x$Serial, Items) %16=4)"))>0) 
		{$Item_Count+=$NumItem;}
		if($NumItem=mssql_num_rows(mssql_query("select [AccountId],[Number] from [ExtWarehouse] where (charindex (0x$Serial, Items) %16=4)"))>0) 
		{$Item_Count+=$NumItem;}
		
		if($Item_Count==1){
			$Item_Count=0;
			for($x=0; $x<strlen($inventory)/32; ++$x){
				$checkdupe = substr($inventory,$x*32,32);
				if(strstr($checkdupe,$Serial)){
					$Item_Count++;
				}
			}
			for($x=0; $x<strlen($warehouse)/32; ++$x){
				$checkdupe = substr($warehouse,$x*32,32);
				if(strstr($checkdupe,$Serial)){
					$Item_Count++;
				}
			}
			
			iF($Item_Count>1) $IsDupe=true;
		}
		elseif($Item_Count>1) $IsDupe=true;
		
		if($IsDupe){
			$Block_End_Time=$timestamp+(30*24*60*60);
			mssql_query("update memb_info set bloc_code=1,Block_End_Time='$Block_End_Time' where memb___id='$GetAcc[0]'");
			WriteLog("Tài khoản <b>$GetAcc[0]</b> đang chứa Item Dupe với Serial là <b>$Serial</b>","DupeItem");
			echo "<Response>Tài khoản $GetAcc[0] đang sở hữu Item Dupe. Tài khoản bị tạm khóa 30 ngày để xử lý.</Response>".$ContentUpdate;exit();
		}
	}
}

function check_dongdau_char($Name,$num_dong){
	
	$inventory = inventory($Name);
	$inventory_part1=substr($inventory,0,384);
	$inventory_part2=substr($inventory,384,strlen($inventory));

	$o_hex = $num_dong*8;
	$items_check=0;
	for($x=0; $x<$o_hex; ++$x)
	{
		$itemssss = substr($inventory_part2,$x*32,32);
		if($itemssss != "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")
		++$items_check;
	}
	
	return $items_check;
}

function check_inject() 
{ 
	$badchars = array("INSERT","DROP","UNION","SELECT","UPDATE","DELETE","DISTINCT","HAVING","TRUNCATE","REPLACE","WHERE","HANDLER","PROCEDURE","LIMIT","ORDER BY","GROUP BY","ASC","DESC");

	if (isset($_POST)){
		foreach($_POST as $value) 
    	{ 
    		if (eregi("[^a-zA-Z0-9_/.@$,:]", $value)) die("Bad char");
			$value = clean_variable($value);
			$str = str_replace($badchars, "", strtoupper($value), $count);
			if($count > 0){ 
				echo "SQL Injection Detected (Bad Char in $value)";exit();       
			} 
      		else { 
        		$check = preg_split("//", $value, -1, PREG_SPLIT_OFFSET_CAPTURE); 
        		foreach($check as $char) { 
          		if(in_array($char, $badchars)) { 
					echo "SQL Injection Detected (Bad Char in $value)";exit();       
				}  
        	} 
      	} 
    } 
 }
if (isset($_GET)){
	foreach($_GET as $value) 
    {     	
    	if (eregi("[^a-zA-Z0-9_/.@$,:]", $value)) die("Bad char");
		$value = clean_variable($value);
		$str = str_replace($badchars, "", strtoupper($value), $count);
		if($count > 0){ 
			echo "SQL Injection Detected (Bad Char in $value)";exit();       
		} 
      	else { 
        	$check = preg_split("//", $value, -1, PREG_SPLIT_OFFSET_CAPTURE); 
        	foreach($check as $char) { 
          		if(in_array($char, $badchars)) { 
					echo "SQL Injection Detected (Bad Char in $value)";exit();       
				} 
           	} 
      	} 
    } 
 }
if (isset($_REQUEST)){
	foreach($_REQUEST as $value) {
		if (eregi("[^a-zA-Z0-9_/.@$,:]", $value)) die("Bad char");
		$value = clean_variable($value);
		$str = str_replace($badchars, "", strtoupper($value), $count);
		if($count > 0){ 
			echo "SQL Injection Detected (Bad Char in $value)";exit();       
		} 
      	else { 
        	$check = preg_split("//", $value, -1, PREG_SPLIT_OFFSET_CAPTURE); 
        	foreach($check as $char) { 
          		if(in_array($char, $badchars)){ 
				echo "SQL Injection Detected (Bad Char in $value)";exit();      
				}  
        	} 
      	} 
   	 } 
  }
} 
?>