<?php
	
/**
 * Function genagate form setting 
 */
function gamebank_setting_form($form_state){
	$setting = new gamebank_setting();
	$form['#tree'] = TRUE;	// Prevent flattening the form values	
    $form['gamebank_account'] = array(
		'#title'  => t('ID đăng nhập gamebank'),
		'#required' => TRUE,
		'#type'  => 'textfield',
                '#default_value' => $setting->gamebank_account
    );
	$form['gamebank_column'] = array(
		'#title'  => t('Tên cột lưu GB coins'),
		'#required' => TRUE,
		'#type'  => 'textfield',
                '#default_value' => $setting->gamebank_column
    );
	$form['gamebank_channels'] = array(
		'#title'  => t('Kênh nạp thẻ'),
		'#required' => TRUE,
		'#type'  => 'textfield',
                '#default_value' => $setting->gamebank_channels
    );
	$form['gamebank_change'] = array(
		'#title'  => t('Tỉ lệ cho nạp thẻ'),
		'#required' => TRUE,
		'#type'  => 'textarea',
                '#default_value' => $setting->gamebank_change
    );
    $form['gamebank_percent'] = array(
		'#title'  => t('Khuyến mãi các loại thẻ nạp (%)'),
		'#required' => TRUE,
		'#type' => 'textarea',
                '#default_value' => $setting->gamebank_percent
    );
	return system_settings_form($form);
}



function gamebank_savesetting(){
	
};


?>