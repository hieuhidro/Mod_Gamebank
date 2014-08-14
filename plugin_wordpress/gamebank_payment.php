<?php
/**
 * Plugin Name: Gamebank payment
 * Plugin URI: http://gamebank.vn // Địa chỉ trang chủ của plugin
 * Description: Plugin gamebank payment 
 * Version: 1.0 // Đây là phiên bản đầu tiên của plugin
 * Author: Hoang hiếu (Gamebank) // Tên tác giả, người thực hiện plugin này
 * Author URI: https://www.linkedin.com/pub/hoang-hieu/76/54/a20 // Địa chỉ trang chủ của tác giả
 * License: GPLv2 //Thông tin license của plugin, nếu không quan tâm thì bạn cứ để GPLv2 vào đây
 */

 
 /*
  * 
		global $wpdb, current_user;
  * 
  * 
  */
 
 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 $cuf_version = '1.0';
 $cuf_script_printed = 0;
 $gamebank_payment = new GameBank_Payment();
 define( 'PAYMENT_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
 define( 'PAYMENT_PLUGIN_URL', plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) ) );
  
 class GameBank_Payment{
 	var $Username_gamebank = "thien321091";
	var $TiLe = array();
	
	function __construct(){
		
	}
	
	/*
	 * Required function **nusoap** 
	 */
	function includes(){		
		// Include core files
		require SC_PAYMENT_PLUGIN_PATH . '/lib/nusoap.php.php';	
		require SC_PAYMENT_PLUGIN_PATH . '/lib/class.gamebank.php';
		require SC_PAYMENT_PLUGIN_PATH . '/lib/class.payment_history.php';	
	}
	
	function process(){
		if(isset($_POST['payment'])){
			$str_CardCode = $_POST['txtCode'];
			$str_CardSerial = $_POST['txtSeri'];
		}
	}
	
	function active_plugin(){
		add_option('Activated_Plugin','GameBank_Payment');
	}
	function deactive_plugin(){
		delete_option('Activated_Plugin');
	}
	function load_plugin(){
		if ( is_admin() && get_option( 'Activated_Plugin' ) == 'Plugin-Slug' ) {
			//Do code logic here
		}
	}
	
	function out_put_form(){
		?>
		<link href="<?php echo PAYMENT_PLUGIN_PATH;?>/css/custom.style.css" rel="stylesheet">
		<link href="<?php echo PAYMENT_PLUGIN_PATH;?>/css/bootstrap.min.css" rel="stylesheet">
		<form name="payment_post" method="post" id="form-gamebank" action="/">
			<h2>Nạp thẻ điện thoại</h2>
			<div class="form-group">
				<label for="lstTelco">Chọn nhà mạng</label>
				<select id="lstTelco" name="lstTelco"  class="form-control">
					<option value="1">Viettel</option>
					<option value="2">MobiFone</option>
					<option value="3">Vinaphone</option>
					<option value="4">Gate</option>
					<option value="5">Vcoin</option>
				</select>
			</div>
		
			<div class="form-group">
				<label for="txtSeri">Số serial</label>
				<input type="txtSeri" class="form-control" id="txtSeri" name="txtSeri" placeholder="Số serial" required>
			</div>
			<div class="form-group">
				<label for="">Nhập mã số</label>
				<input type="txtCode" class="form-control" id="txtCode" name="txtCode" placeholder="Mã số" required>
			</div>
			<button type="submit" class="btn btn-primary" name="payment">
				Nạp thẻ
			</button>			
		</form>
		<?php
	}
 }

 /*
  * Thread here ... 
  */
 
?>