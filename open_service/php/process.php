<?php

	include('lib/nusoap.php');
	$telco = $_POST['lstTelco'];
	$code = $_POST['txtCode'];
	$seri = $_POST['txtSeri'];
	$gamebank_account = "trieunguyen";
	

	$client = new nusoap_client("http://pay.gamebank.vn/service/cardServiceV2.php?wsdl",true);

	$result = $client->call("creditCard",array("seri"=>$seri,"code"=> $code,"cardtype"=> $telco, "gamebank_account"=>$gamebank_account));
	
	//print_r($result);
	if($result[0] >= 10000)
	{
		echo "Nap thanh cong ".$result[0];
		//Nap tien thanh cong, $result['resultCode'] là mệnh giá thẻ khách nạp	
	}
	else
	{
		//Lỗi nạp tiền, dựa vào bảng mã lỗi để show thông tin khách hàng lên	
		switch($result[0])
		{
			case -3: echo "The khong su dung duoc" ;break;
			case -10: echo "Nhap sai dinh dang the";break;
			case -1001: echo "Nhap sai qua 3 lan ";break;
			case -1002; echo "Loi he thong ";break;
			case -1003: echo "IP khong duoc phep truy cap vui long quay lai sau 5 phut";break;
			case -1004: echo "Ten dang nhap gamebank khong dung"; break;
			case -1005: echo "Loai the khong dung";break;
			case -1006: echo "He thong dang bao tri";break;
			default: echo "Ket noi voi Gamebank that bai";
		}
	}
	
?>