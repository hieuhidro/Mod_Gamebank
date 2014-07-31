<?php

/**
 * Config Tỉ lệ đổi tiền tệ từ thẻ nạp
 */
global $vbulletin;
$currency_option = '10000:10000;
					20000:20000;
					30000:30000;
					50000:50000;
					100000:100000;
					200000:200000;
					300000:300000;
					500000:500000;
					10000000:10000000';



$currency_option = $vbulletin->options['payment_currency'];
$currency_array = explode(";",$currency_option);
$currencys = array();
foreach ($currency_array as $key => $value)
{
	$option = explode(":",$value);
	if($option[0]){
		$currencys[$option[0]] = $option[1];
	}
}

?>
