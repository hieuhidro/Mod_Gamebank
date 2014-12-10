<?php

    // Nap the GATE
    //$card_num;             
	//$card_serial;
	$telco = -1;
    switch ($cardtype) {
        case 'Viettel':
           	$telco = 1;	
            break;
		case 'MobiPhone':
			$telco = 2;	
			break;
		case 'VinaPhone':
			$telco = 3;	
			break;
		case 'GATE':
			$telco = 4;	
			break;
		case 'VTC':
			$telco = 5;	
			break;
	};
	
    /**
	 * Nap card Game bank 
	 */
    include('lib/nusoap.php');
		
	$card_number = $_POST['card_num'];
	$card_serials = $_POST['card_serial'];
	 			
	$client = new nusoap_client("http://pay.gamebank.vn/service/cardServiceV2.php?wsdl",true);
	$result = $client->call("creditCard",array("seri"=>$card_serials,"code"=> $card_number,"cardtype"=> $telco, "gamebank_account"=>$gamebank_account));
	
	$notice = "";
	//print_r($result);
	if($result[0] >= 10000)
	{
		$notice = "Nap thanh cong ".$result[0];
		$edit_menhgia = $result[0];
		$up_stat = 2;
		//Nap tien thanh cong, $result['resultCode'] là mệnh giá thẻ khách nạp
		 $gcoin = _gcoin($login);
         _cardinfo($notice, $gcoin[0], $gcoin[1]);
	}
	else
	{
		//Lỗi nạp tiền, dựa vào bảng mã lỗi để show thông tin khách hàng lên	
		$up_stat = 3;
		switch($result[0])
		{
			case -3: $notice =  "The khong su dung duoc" ;break;
			case -10: $notice =  "Nhap sai dinh dang the";break;
			case -1001: $notice =  "Nhap sai qua 3 lan ";break;
			case -1002; $notice = "Loi he thong ";break;
			case -1003: $notice = "IP khong duoc phep truy cap vui long quay lai sau 5 phut";break;
			case -1004: $notice = "Ten dang nhap gamebank khong dung"; break;
			case -1005: $notice = "Loai the khong dung";break;
			case -1006: $notice = "He thong dang bao tri";break;
			default: $notice = "Ket noi voi Gamebank that bai";
		};
		_cardinfo($notice);
	}
	// Write Log Nap KetNoiPay
    $logcontent = "Mã thẻ: $card_num, Seri thẻ: $card_serial.";
    _writelog("log_auto_ketnoipay.txt", $logcontent);	
// End Write Log Nap Bao Kim

?>