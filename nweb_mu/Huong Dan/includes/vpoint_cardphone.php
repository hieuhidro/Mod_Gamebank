<?php 
/**
 * @author		NetBanBe
 * @copyright	2005 - 2012
 * @website		http://netbanbe.net
 * @Email		nwebmu@gmail.com
 * @HotLine		094 92 92 290
 * @Version		v5.12.0722
 * @Release		22/07/2012
 
 * WebSite hoan toan duoc thiet ke boi NetBanBe.
 * Vi vay, hay ton trong ban quyen tri tue cua NetBanBe
 * Hay ton trong cong suc, tri oc NetBanBe da bo ra de thiet ke nen NWebMU
 * Hay su dung ban quyen duoc cung cap boi NetBanBe de gop 1 phan nho chi phi phat trien NWebMU
 * Khong nen su dung NWebMU ban crack hoac tu nguoi khac dua cho. Nhung hanh dong nhu vay se lam kim ham su phat trien cua NWebMU do khong co kinh phi phat trien cung nhu san pham tri tue bi danh cap.
 * Cac ban hay su dung NWebMU duoc cung cap boi NetBanBe de NetBanBe co dieu kien phat trien them nhieu tinh nang hay hon, tot hon.
 * Cam on nhieu!
 */
 
include_once("security.php");
include_once("config.php");
include_once("config/config_napthe.php");
include_once("function.php");

function _cardinfo($msg, $gcoin = 0, $gcoinkm = 0) {
    echo "<reponse style='color:red;'><msg>$msg</msg><gcoin>$gcoin</gcoin><gcoinkm>$gcoinkm</gcoinkm></reponse>";
}

function _gcoin($login) {
    global $db;
    $gcoin_query = "SELECT gcoin, gcoin_km FROM MEMB_INFO WHERE memb___id='$login'";
    $gcoin_result = $db->Execute($gcoin_query) OR DIE("Query Error : $gcoin_query");
    $gcoin_fetch = $gcoin_result->FetchRow();
    
    $gcoin = array(
        'gcoin' =>  $gcoin_fetch[0],
        'gcoinkm'   =>  $gcoin_fetch[1]
    );
    
    return $gcoin;
}

$login = $_POST['login'];
$cardtype = $_POST['cardtype'];
$menhgia = $_POST['menhgia'];
$card_num = $_POST['card_num'];
$card_serial = $_POST['card_serial'];
$passtransfer = $_POST["passtransfer"];

$card_num = strtoupper($card_num);
$card_serial = strtoupper($card_serial);
	
if ($passtransfer == $transfercode) {

$string_login = $_POST['string_login'];
checklogin($login,$string_login);

$card_num = str_replace(" ", "", $card_num);            
    $card_num_md5 = md5($card_num);
    $card_num_encode = $card_num;
$card_serial = str_replace(" ", "", $card_serial);      $card_serial_encode = $card_serial;


$num_length = strlen($card_num);
$serial_length = strlen($card_serial);

$time_check = date("Y-m-d",$timestamp);

if ($cardtype == 'VinaPhone') { 
	kiemtra_kituso($card_num);
	if(!($num_length == 14 || $num_length == 12)) {
		$msg = "Thẻ sai. Mã thẻ phải có 12 hoặc 14 số."; 
        _cardinfo($msg);
        exit(); 
    }
}

elseif ($cardtype == 'Viettel') { 
	kiemtra_kituso($card_num);
	kiemtra_kituso($card_serial);
	if($num_length != 13) {
		$msg = "Thẻ sai. Mã thẻ phải có 13 số."; 
        _cardinfo($msg);
        exit(); 
    }
    if($serial_length != 11) {
		$msg = "Serial sai. Serial phải có 11 số."; 
        _cardinfo($msg);
        exit(); 
    }
}

elseif ($cardtype == 'MobiPhone') { 
	kiemtra_kituso($card_num);
	kiemtra_kituso($card_serial);
	if( !($num_length == 14 || $num_length == 12) ) {
        $msg = "Thẻ sai. Mã thẻ phải có 12 hoặc 14 số."; 
        _cardinfo($msg);
        exit(); 
	}
}

elseif ($cardtype == 'VinaGame') { 
	if($num_length != 9 or $serial_length != 12) {
		$msg = "Thẻ sai. Xin vui lòng kiểm tra kĩ lại thông tin thẻ."; 
        _cardinfo($msg);
        exit(); 
    }
	elseif ( strtoupper(substr($card_serial,0,2)) != 'HA' && strtoupper(substr($card_serial,0,2)) != 'SA') {
		$msg = "Thẻ sai. Xin vui lòng kiểm tra kĩ lại thông tin thẻ."; 
        _cardinfo($msg);
        exit(); 
    }
}

elseif ($cardtype == 'VTC') { 
	if( ($num_length == 12) && ( (substr($card_serial,0,3) == 'SA0' && $serial_length == 10) || (substr($card_serial,0,3) == 'PM0' && $serial_length == 12) ) ) { 
	   
	}
	else { 
	   $msg = "Thẻ sai. Xin vui lòng kiểm tra kĩ lại thông tin thẻ."; 
       _cardinfo($msg);
       exit(); 
    }
}

elseif ($cardtype == 'GATE') { 
	if( $num_length == 10 && $serial_length == 10 ) { 
	   
	}
	else	{ 
	   $msg = "Thẻ sai. Xin vui lòng kiểm tra kĩ lại thông tin thẻ."; 
       _cardinfo($msg);
       exit(); 
    }
}
$card_num = nbb_encode($card_num);

$sql_char_check = $db->SelectLimit("Select Name,Resets,Relifes From Character where AccountID='$login' ORDER BY Relifes DESC, Resets DESC", 1, 0);
$char_check = $sql_char_check->fetchrow();

$slg_card_check = $db->Execute("Select * From CardPhone where acc='$login' and ngay='$time_check' AND (status=0 OR status IS NULL OR status=3)");
$slg_card_check = $slg_card_check->numrows();

if($cardtype == 'GATE' || $cardtype == 'VTC') {
    $sql_card_wait = $db->Execute("Select * From CardPhone where (status=0 OR status IS NULL) AND card_serial='$card_serial'");
    $card_wait = $sql_card_wait->numrows();
    
    $sql_card_right = $db->Execute("Select * From CardPhone where status=2 AND card_serial='$card_serial'");
    $card_right = $sql_card_right->numrows();
} else {
    $sql_card_wait = $db->Execute("Select * From CardPhone where (status=0 OR status IS NULL) AND card_num_md5='$card_num_md5'");
    $card_wait = $sql_card_wait->NumRows();
    
    $sql_card_right = $db->Execute("Select * From CardPhone where status=2 AND card_num_md5='$card_num_md5'");
    $card_right = $sql_card_right->NumRows();
}

$card_check_query = "Select * From CardPhone where card_num_md5='$card_num_md5' AND card_serial='$card_serial'";
$card_check_result = $db->execute($card_check_query);
    check_queryerror($card_check_query, $card_check_result);

$card_check = $card_check_result->NumRows();

$name = $char_check[0];
if ($char_check[1] <= $card_reset){ 
   $msg = "Nhân vật Reset ít hơn $card_reset lan."; 
   _cardinfo($msg);
   exit();
}

if ($card_check > 2){ 
   $msg = "Thẻ trùng với thẻ đã nạp."; 
   _cardinfo($msg);
   exit();
}
else if($card_wait > 0)
{
    $msg = "Thẻ đã nạp từ trước. Đang chờ duyệt. Nếu nạp nhầm mã thẻ, đợi duyệt sai sẽ được phép nạp lại."; 
    _cardinfo($msg);
    exit();
}
else if($card_right > 0)
{
    $msg = "Thẻ đã nạp từ trước và được duyệt Đúng. Đề nghị không nạp lại."; 
    _cardinfo($msg);
    exit();
}
	
if($char_check[2] == 0 && $char_check[1] < $reset_4) {
	if($char_check[1] < $reset_1 && $slg_card_check >= $slg_card_1) { 
		$msg = "Bạn đã nạp $slg_card_check thẻ trong hôm nay .<br>Nhân vật cấp cao nhất trong tài khoản của bạn: $name (ReLife: 0 - Reset: $char_check[1]) . Với cấp độ đó bạn chỉ được nạp tối đa $slg_card_1 thẻ/ngày."; 
        _cardinfo($msg);
        exit();
	}
	elseif($char_check[1] >= $reset_1 && $char_check[1] < $reset_2 && $slg_card_check >= $slg_card_2) { 
		$msg = "Bạn đã nạp $slg_card_check thẻ trong hôm nay .<br>Nhân vật cấp cao nhất trong tài khoản của bạn: $name (ReLife: 0 - Reset: $char_check[1]) . Với cấp độ đó bạn chỉ được nạp tối đa $slg_card_2 thẻ/ngày."; 
        _cardinfo($msg);
        exit();
	}
	elseif($char_check[1] >= $reset_2 && $char_check[1] < $reset_3 && $slg_card_check >= $slg_card_3) { 
		$msg = "Bạn đã nạp $slg_card_check thẻ trong hôm nay .<br>Nhân vật cấp cao nhất trong tài khoản của bạn: $name (ReLife: 0 - Reset: $char_check[1]) . Với cấp độ đó bạn chỉ được nạp tối đa $slg_card_3 thẻ/ngày."; 
        _cardinfo($msg);
        exit();
	}
	elseif($char_check[1] >= $reset_3 && $char_check[1] < $reset_4 && $slg_card_check >= $slg_card_4) { 
		$msg = "Bạn đã nạp $slg_card_check thẻ trong hôm nay .<br>Nhân vật cấp cao nhất trong tài khoản của bạn: $name (ReLife: 0 - Reset: $char_check[1]) . Với cấp độ đó bạn chỉ được nạp tối đa $slg_card_4 thẻ/ngày."; 
        _cardinfo($msg);
        exit();
	}
} else { 
    if($slg_card_check >= $slg_card_max) {
        $msg = "Bạn chỉ được nạp tối đa $slg_card_max thẻ/ngày."; 
        _cardinfo($msg);
        exit();
    }
}

$msquery = "INSERT INTO CardPhone (acc, name, card_type, menhgia, card_num, card_num_md5, card_serial, ngay,status, timenap) VALUES ('$login', '$name', '$cardtype', '$menhgia', '$card_num', '$card_num_md5', '$card_serial', '".date("Y-m-d",$timestamp)."',2, '$timestamp')";
$msresults = $db->Execute($msquery);

    include_once('config_autonap.php');
    include_once('autonap_func.php');
    $stt_query = "SELECT stt FROM CardPhone WHERE card_type='$cardtype' AND card_num='$card_num' AND card_serial='$card_serial' AND timenap='$timestamp'";
    $stt_result = $db->execute($stt_query) OR DIE("Query Error : $stt_query");
    $stt_fetch = $stt_result->fetchrow();
    $stt = $stt_fetch[0];
    
	$gamebank_account = "thien321091";//Edit your account 
    
	
	
    //if($auto_gate === true) {
	
		include ('autonap_gamebank.php');	
		include ('autonap_duyet.php');
	
    //    if($gate_doitac == 'GATE') {
    //        include_once('autonap_gate.php');
    //        $msg = $notice_nap;
    //        $gcoin = _gcoin($login);
    //        _cardinfo($msg, $gcoin[0], $gcoin[1]);
    //    }
    //    else if($gate_doitac == 'KETNOIPAY') {
    //        include_once('autonap_ketnoipay.php');
    //        $msg = $notice_nap;
    //        $gcoin = _gcoin($login);
    //        _cardinfo($msg, $gcoin[0], $gcoin[1]);
    //    }
    //} 
    //// Nap the VTC
    //else if($cardtype == 'VTC' && $auto_vtc === true) {
    //    if($vtc_doitac == 'VTC') {
    //        include_once('autonap_vtc.php');
    //        $msg = $notice_nap;
    //        $gcoin = _gcoin($login);
    //        _cardinfo($msg, $gcoin[0], $gcoin[1]);
    //    }
    //    else if($vtc_doitac == 'KETNOIPAY') {
    //        include_once('autonap_ketnoipay.php');
    //        $msg = $notice_nap;
    //        $gcoin = _gcoin($login);
    //        _cardinfo($msg, $gcoin[0], $gcoin[1]);
    //    }
    //} 
    //// Nap the Dien thoai
    //else if( $telcard_use === true && ($cardtype == 'MobiPhone' || $cardtype == 'VinaPhone' || $cardtype == 'Viettel') ) {
    //    if($telcard_doitac == 'BAOKIM') {
    //        include_once('autonap_baokim.php');
    //        $msg = $notice_nap;
    //        $gcoin = _gcoin($login);
    //        _cardinfo($msg, $gcoin[0], $gcoin[1]);
    //    }
    //    else if($telcard_doitac == 'KETNOIPAY') {
    //        include_once('autonap_ketnoipay.php');
    //        $msg = $notice_nap;
    //        $gcoin = _gcoin($login);
    //        _cardinfo($msg, $gcoin[0], $gcoin[1]);
    //    }
    //    else if($telcard_doitac == 'TEKNET') {
    //        switch ($cardtype) { 
    //        	case 'MobiPhone': 
    //                $operator_id = 1;
    //        	   break;
    //        
    //        	case 'VinaPhone':
    //                $operator_id = 2;
    //        	   break;
    //            case 'Viettel' :
    //                $operator_id = 3;
    //                break;
    //         }
    //        
    //        $acc = $login;
    //        $pin = $card_num_encode;
    //        $seri = $card_serial_encode;
    //
    //        if( $operator_id == 1 || $operator_id == 2 || $operator_id == 3 ) {
    //            include_once('teknet_sendcard.php');
    //            $msg = $notice_nap;
    //            $gcoin = _gcoin($login);
    //            _cardinfo($msg, $gcoin[0], $gcoin[1]);
    //        }
    //    }
    //}else {
    //    $msg = "Đăng kí mua V.Point bằng thẻ <strong>$cardtype</strong> cho tài khoản <strong>$login</strong> thành công. Hãy theo dõi trong phần danh sách thẻ đã nạp.";
    //    _cardinfo($msg);
    //}
}
$db->Close();
?>