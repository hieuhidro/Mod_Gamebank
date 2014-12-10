<?php
/**
 * Plugin Name: Gamebank payment
 * Plugin URI: http://gamebank.vn 
 * Description: Plugin gamebank payment 
 * Version: 1.0 // Đây là phiên bản đầu tiên của plugin
 * Author: Hoang hiếu (Gamebank) 
 * Author URI: https://www.linkedin.com/pub/hoang-hieu/76/54/a20 
 * License: GPLv2 
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
 define( 'PAYMENT_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
 define( 'PAYMENT_PLUGIN_URL', plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) ) );
 
if(!function_exists('installing_value')){
	/**
	 * function installing_value
	 * @access public 
	 * @param $string_array string (option of change and percent)
	 * @return  array (array of change or percent)
	 */	 
	function installing_value($string_array = ''){		
		$currency_array = explode(";",$string_array);
		$currencys = array();
		foreach ($currency_array as $key => $value)
		{
			$option = explode(":",$value);
			if($option[0]){
				$currencys[$option[0]] = $option[1];
			}
		}
		return $currencys;
	}
}


if(!class_exists('GameBank_Payment')) {       
 	class GameBank_Payment{
	 	protected $enable = 0;
	 	protected $Username_gamebank = "thien321091";
		protected $Nusoap_clien = null;
		protected $changes = array(	10000=>10000,    
									20000=>20000,    
									30000=>30000,    
									50000=>50000,    
									100000=>100000,  
									200000=>200000,  
									300000=>300000,  
									500000=>500000,  
									1000000=>1000000);
		protected $changes_percent = array('Viettel' => 0,
									'Vinaphone' => 0,
									'Mobifone'=> 0,
									'Gate'=>0,
									'VTC'=>0);
		protected $channels = '';	
		protected $column = '';
		
		function __construct(){
			$this->required_Setting();
			$this->includes();
		}
		/**
		 * Required setting
		 * @access public
		 */ 
		public function required_Setting(){
			$this->changes_percent = installing_value(get_option('gamebank_option_percent'));
			$this->changes	= installing_value(get_option('gamebank_option_change'));
			$this->Username_gamebank = get_option('gamebank_option_username');
			$this->column = get_option('gamebank_option_column');
			$this->enable = get_option('gamebank_option_enable');
		}
	 
		
		/*
		 * Required function **nusoap** 
		 * @access public 
		 */
		public function includes(){	
			// Include core files
			require PAYMENT_PLUGIN_PATH . '/lib/nusoap.php';
			require PAYMENT_PLUGIN_PATH . '/lib/class.gamebank.php';
			require PAYMENT_PLUGIN_PATH . '/lib/class.payment_history.php';			
			$this->Nusoap_clien = new nusoap_client("http://pay.gamebank.vn/service/cardServiceV2.php?wsdl",true);
		}
		
		/**
		 * function check enable
		 * @return true or false
		 */
		 public function IsEnable(){
		 	return $this->enable == 1;
		 }
		
		/**
		 * gamebank_Create_Menu
		 * Create menu form setting in administrator
		 * @access public 
		 * @param none
		 * @return void;
		 */	  
		public function gamebank_Create_Menu() {		 	
		        $hook = add_menu_page('GameBank Plugin Settings', 'GameBank Settings', 'administrator', __FILE__,array($this,'add_Form_Setting'),plugins_url('/images/logo.png', __FILE__), 1);
		        add_action('admin_init',array($this,'register_My_Settings'));
				//add_action('load-'.$hook, array($this,'update_options'));       
		}	
		public function register_My_Settings() {			
			register_setting('gamebank-settings-group', 'gamebank_option_enable');
		    register_setting('gamebank-settings-group', 'gamebank_option_username');
			register_setting('gamebank-settings-group', 'gamebank_option_column');
			register_setting('gamebank-settings-group', 'gamebank_option_channels');
			register_setting('gamebank-settings-group', 'gamebank_option_change');
			register_setting('gamebank-settings-group', 'gamebank_option_percent');			
		}
		
		public function add_Form_Setting(){	
		?>		
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('/css/custom.style.css',__FILE__);?>"/>
			<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('/css/bootstrap.min.css',__FILE__);?>" />
			<script>
				document.getElementById("dolly").style.display = "none";				
			</script>
			<div class="wrap">
			<h2>Trang cài đặt cho GameBank Payment</h2>			
			<form action="options.php" method="POST" role="form">
				<?php 
					settings_fields('gamebank-settings-group'); 
					do_settings_sections('gamebank-settings-group');
				?>
				<div class="checkbox">
					<div class="col-sm-offset-4 col-sm-8">
						<label>
						<input type="checkbox" value="1" id="gamebank_option_enable" name="gamebank_option_enable" <?php echo checked( 1, get_option( 'gamebank_option_enable' ), false ); ?>>
						Bật sử dụng Gamebank Plugin
						</label>
					</div>
				</div>
				<div class="form-group">
					<label for="gamebank_option_username" class="col-sm-4 control-label">Username trên gamebank.vn</label>
					<div class="col-sm-8">
						<input type="text" class="form-control" value="<?php echo esc_attr(get_option('gamebank_option_username')); ?>" name="gamebank_option_username" id="gamebank_option_username" >
					</div>
				</div>
				<div class="form-group">
					<label  for="gamebank_option_column" class="col-sm-4 control-label">Tên cột lưu coins trên Table User </label>
					<div class="col-sm-8">
						<input type="text" class="form-control" value="<?php echo esc_attr(get_option('gamebank_option_column')); ?> " name="gamebank_option_column" id="gamebank_option_column" >
					</div>
				</div>
				<div class="form-group">
					<label  for="gamebank_option_column" class="col-sm-4 control-label">Kênh nạp</label>
					<div class="col-sm-8">
						<input type="text" class="form-control" value="<?php echo esc_attr(get_option('gamebank_option_channels')); ?> " name="gamebank_option_channels" id="gamebank_option_channels" >
					</div>
				</div>
				<div class="form-group">
					<label  for="gamebank_option_change" class="col-sm-4 control-label">Tỉ lệ chuyển đổi từ tiền qua coins</label>
					<div class="col-sm-8">
						<textarea name="gamebank_option_change" id="gamebank_option_change" class="form-control" style="height:auto;"><?php echo esc_attr(get_option('gamebank_option_change')); ?></textarea>
						<p> 10000:10000;    </p>
						<p> 20000:20000;    </p>
						<p> 30000:30000;    </p>
						<p> 50000:50000;    </p>
						<p> 100000:100000;  </p>
						<p> 200000:200000;  </p>
						<p> 300000:300000;  </p>
						<p> 500000:500000;  </p>
						<p> 1000000:1000000;</p>
					</div>
				</div>
				<div class="form-group">
					<label for="gamebank_option_percent" class="col-sm-4 control-label"> Tỉ lệ thưởng (khuyễn mãi) chuyển đổi của các loại thẻ (:%)</label>
					<div class="col-sm-8">
					<textarea  name="gamebank_option_percent" id="gamebank_option_percent" class="form-control"><?php echo esc_attr(get_option('gamebank_option_percent')); ?></textarea>
					<p> Viettel:0;    </p>
					<p> Vinaphone:0;  </p>
					<p> Mobifone:0;   </p>
					<p> Gate:0;       </p>
					<p> VTC:0;		  </p>
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-offset-4 col-sm-8">
						<?php submit_button(); ?>		 
					</div>
				</div>
			</form>
			<div class="clear"></div>
			</div>
		<?php 
		}
		function Process(){
			if(isset($_POST['payment'])){
				global $current_user,$wpdb;
							
				get_currentuserinfo();
				
				$str_CardCode = $_POST['txtCode'];
				$str_CardSerial = $_POST['txtSeri'];
				$str_CardType = $_POST['lstTelco'];
				
				$gamebank_account = $this->Username_gamebank;				
				$table_name = $wpdb->prefix . 'payment_history';
				$sql = "select * from $table_name where coins > 0 and cardserial = '$str_CardSerial' or cardnumber = '$str_CardCode'";
				$result = $wpdb -> get_results($sql);
				if(@@mysql_num_rows($result) <= 0){
				$result = $this->Nusoap_clien->call("creditCard",array("seri"=>$str_CardSerial,"code"=> $str_CardCode,"cardtype"=> $str_CardType, "gamebank_account"=>$gamebank_account, "option" => "nap bang wordpress","website"=> $_SERVER['SERVER_NAME']));
				
				//print_r($result);
				$status = 10000;
				if($result[0] >= 10000)
				{		
					echo "Nap thanh cong ".$result[0];
					$table_name = $wpdb->prefix.'users';
					
					$wpdb->update(
						$table_name,
						array(
							$this->column => $this->column + $result[0]  
						),
						array(
							'user_login' => $current_user->user_login
						)
					);
					//Nap tien thanh cong, $result[0] là mệnh giá thẻ khách nạp		
				}
				else
				{
					//Lỗi nạp tiền, dựa vào bảng mã lỗi để show thông tin khách hàng lên
					$status = $result[0];	
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
				
				$payment = new payment($str_CardSerial,$str_CardCode,$status,$result[0]);
				$payment->insertItemp($current_user->user_login);
				}else{
					echo "Thẻ đã được nạp trước đó vui lòng kiểm tra lại";
				}
			}
		}
			
		/**
		 * short_enable_form
		 * @access public 
		 * @return null
		 * @example [form_gamebank], [gamebank_history]
		 */
		public function short_enable_form(){
			if(!function_exists('add_shortcode')) {
				return;
			}
			add_shortcode('form_gamebank' , array(&$this, 'out_put_form'));
			add_shortcode('gamebank_history',array(&$this, 'history_form'));
		}
		
		
		public function history_form(){
			include PAYMENT_PLUGIN_PATH.'/checkout.php';
		}
		public function out_put_form(){			
			return '
				<link rel="stylesheet" type="text/css" href="'.plugins_url('/css/custom.style.css',__FILE__).'"/>
				<link rel="stylesheet" type="text/css" href="'.plugins_url('/css/bootstrap.min.css',__FILE__).'" />
				<div class="container-payment">	
				<form name="payment_post" method="post" id="form-gamebank" action="#">
					<h2>Nạp thẻ điện thoại</h2>
						<div class="form-group">
							<label for="lstTelco">Chọn nhà mạng</label>
							<select id="lstTelco" name="lstTelco"  class="form-control">
								<option value="1">Viettel</option>
								<option value="2">MobiFone</option>
								<option value="3">Vinaphone</option>
								<option value="4">Gate</option>								
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
					<button type="submit" class="btn btn-primary" name="payment">Nạp thẻ</button>
				</form>
			</div>';
		}
		
		/**
		 * on_Download 
		 * On download file event calculate user coins
		 */
		public function on_Download(){
				
		}
		
		/**
		 * on_changegroup
		 * On download file event calculate user coins
		 * 
		 */
		public function on_ChangeGroup(){
			
		}
		
		/**
		 * on_ViewPage or Post
		 */
		public function on_TagetPost(){
			
		}
	}
}
	/*
	* Required CSS
	*/
	global $gamebank;
	$gamebank = new GameBank_Payment();
	
	if(!function_exists('enqueue_style')){
		function enqueue_style(){
			wp_enqueue_style('form-setting',plugins_url('/css/custom.style.css',__FILE__));
			wp_enqueue_style('form-setting-2',plugins_url('/css/bootstrap.min.css',__FILE__));
		}
	}
	if(!function_exists('create_mainMenu')){
		function create_mainMenu(){
			global $gamebank;
			if ( is_admin() ){ // admin actions
				$gamebank->gamebank_Create_Menu();				
			} else {
  				// non-admin enqueues, actions, and filters
			};			
		}
	}
	if(!function_exists('function_request')){
		function function_request() {
			global $gamebank;
			if(isset($_POST['payment'])){
				$gamebank->Process();
			}
		}
	}
	if(!function_exists('Gamebank_load')){
		function Gamebank_load() {
	        global $gamebank,$mfpd;
			if($gamebank->IsEnable()){
				$mfpd = $gamebank;
				$mfpd->short_enable_form();
			}		
		}
	}
	
	if(!function_exists('install_plugin')){
		function install_plugin(){
			global $wpdb;			
			$table_name = $wpdb->prefix . 'payment_history';
			
			$sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
			        `historyid` int(11) NOT NULL AUTO_INCREMENT,
					`username` text COLLATE utf8_unicode_ci NOT NULL,
					`datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`cardserial` text COLLATE utf8_unicode_ci NOT NULL,
					`cardnumber` text COLLATE utf8_unicode_ci NOT NULL,
					`coins` int(11) NOT NULL DEFAULT '0',
					`status` int(11) NOT NULL DEFAULT '-1007',
					PRIMARY KEY (`historyid`)
			       ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Save history user exchange' AUTO_INCREMENT=3 ;";
			$wpdb->query($sql);
			$table_name = $wpdb->prefix.'users';
			try{
			$sql_clm = "ALTER TABLE  `".$table_name."` ADD `payment` INT NOT NULL DEFAULT '0'";
            $wpdb->query($sql_clm);
			}catch(exception $ex){
				return;
			}
		}
	}

	/*
	* Add action plugin
	*/
	register_activation_hook(__FILE__, 'install_plugin');
	add_action('wp_enqueue_scripts', 'enqueue_style');
	add_action('admin_menu', 'create_mainMenu');
	add_action('plugins_loaded','Gamebank_load');
	add_action('the_post', 'function_request');
?>