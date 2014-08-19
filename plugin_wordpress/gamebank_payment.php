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
  * Custom - option: username, url_kenhnap,  
  * global $wpdb, current_user;
  * is_admin() 
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
	/*
	 * Required CSS
	 */
	function enqueue_style(){
		wp_register_style('hocwp-foundation', PAYMENT_PLUGIN_PATH. '/css/custom.style.css', array(), get_theme_version() );
	}
	
	
	function process(){
		if(isset($_POST['payment'])){
			$str_CardCode = $_POST['txtCode'];
			$str_CardSerial = $_POST['txtSeri'];
			$str_CardType = $_POST['lstTelco'];
			
			$gamebank_account = "trieunguyen";
			// get_option('url_kenhnap');
			//
			//	URL cho kênh V5 ____	http://pay.gamebank.vn/service/csv5.php/?wsdl
			//	URL cho kênh V2 ____	http://pay.gamebank.vn/service/cardServiceV2.php/?wsdl
			//
			$client = new nusoap_client("http://pay.gamebank.vn/service/cardServiceV2.php/?wsdl",true);
		
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
					case -3: 
						echo "The khong su dung duoc" ;
					break;
					case -10:
						echo "Nhap sai dinh dang the";
					break;
					case -1001: 
						echo "Nhap sai qua 3 lan ";
					break;
					case -1002; 
						echo "Loi he thong ";
					break;
					case -1003: 
						echo "IP khong duoc phep truy cap vui long quay lai sau 5 phut";
					break;
					case -1004: 
						echo "Ten dang nhap gamebank khong dung";
					break;
					case -1005: 
						echo "Loai the khong dung";
					break;
					case -1006: 
						echo "He thong dang bao tri";
						break;
					default: 
						echo "Ket noi voi Gamebank that bai";
				}
			}
			
		}
	}
		
	
	function register_mysettings() {
		register_setting('gamebank-settings-group', 'gamebank_option_enable');
        register_setting('gamebank-settings-group', 'gamebank_option_username');
		register_setting('gamebank-settings-group', 'gamebank_option_column');
		register_setting('gamebank-settings-group', 'gamebank_option_change');
		register_setting('gamebank-settings-group', 'gamebank_option_percent');
	}
 	
	function mfpd_create_menu() {
	        add_menu_page('GameBank Plugin Settings', 'GameBank Settings', 'administrator', __FILE__, 'add_form_setting',plugins_url('/images/icon.png', __FILE__), 1);
	        add_action( 'admin_init', 'register_mysettings' );
	}
	function add_form_setting(){
		?>	
		<form action="options.php" method="POST">
			<?php settings_fields( 'gamebank-settings-group'); ?>
			<table>
				<tr valign="top">
					<th scope="row">Bật sử dụng Gamebank Plugin</th>
					<td>
						<input type="checkbox" <?php echo get_option('gamebank_option_enable') == 1 ? "checked" : "";?> name="gamebank_option_enable" />
						</td>
				</tr>
				<tr>
					<th scope="row">Username trên gamebank.vn</th>
					<td>
						<input type="checkbox" <?php echo get_option('gamebank_option_username') == "" ? "thien321091" : get_option('gamebank_option_username');?> name="gamebank_option_username" />
					</td>
				</tr>
				<tr>
					<th scope="row">Tên cột lưu coins trên Table User </th>
					<td>
						<input type="checkbox" <?php echo get_option('gamebank_option_column') == "" ? "payment" : get_option('gamebank_option_column');?> name="gamebank_option_column" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						Tỉ lệ chuyển đổi từ tiền qua coins 
						10000:10000;
						20000:20000;
						30000:30000;						
						50000:50000;
						100000:100000;
						200000:200000;
						300000:300000;
						500000;500000;
						1000000;1000000;
					</th>
					<td>
						<input type="checkbox" <?php echo get_option('gamebank_option_percent') == "" ? "" : get_option('gamebank_option_percent');?> name="gamebank_option_change" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						Tỉ lệ thưởng (khuyễn mãi) chuyển đổi của các loại thẻ (:%) 
						Viettel:0;
						Vinaphone:0;
						Mobifone:0;
						Gate:0;
						VTC:0;												
					</th>
					<td>
						<input type="checkbox" <?php echo get_option('gamebank_option_change') == 1 ? "" : get_option('gamebank_option_change');?> name="gamebank_option_change" />
					</td>
				</tr>
			</table>			
		</form>
		<?php
	}
	
	
	
	function add_form(){
		if(!function_exists('add_shortcode')) {
               treturn;
        }
        add_shortcode('form_gamebank' , array(&$this, 'out_put_form'));
	}
	function out_put_form($atts = array(), $content = null){
		return '
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
				<input type="input" class="form-control" id="txtSeri" name="txtSeri" placeholder="Số serial" required>
			</div>
			<div class="form-group">
				<label for="">Nhập mã số</label>
				<input type="input" class="form-control" id="txtCode" name="txtCode" placeholder="Mã số" required>
			</div>
			<button type="submit" class="btn btn-primary" name="payment">
				Nạp thẻ
			</button>			
		</form>';
	}
 }

 /*
  * Thread here ... 
  */
 
?>