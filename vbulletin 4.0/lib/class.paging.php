<?php
if(session_start());
//######################## REQUIRE BACK-END  ################# Delete when completed
//include_once('../global.php');
//include_once('../includes/adminfunctions.php');
//include_once('../includes/class_bbcode.php');
//#############################################################
/**
 * public class paging, paging for payment history of user
 *
 * __contructer($pagecount);
 *
 * $total is a cout of row
 */

class paging {

	/**
	 * Cout row (number)
	 */
	protected $total;
	protected $pagecount;
	protected $current;
	/**
	 * __construct
	 *
	 * @access public
	 * @param  integer $pagecount (default: 20)
	 * @return void
	 */
	function __construct($username, $pagecount = 20) {
		$this -> total = paging::getTotal($username);
		$this -> current = $this -> getCurrentPage();
		$this -> pagecount = ($pagecount == null) ? paging::getPageCount() : $pagecount;	
	}
	/**
	 * getCurrentURL()
	 * @access public static
	 * 
	 * @return current URL;
	 */
	 public static function getCurrentURL(){
	 	if((!isset($_SESSION['request_url']) && !isset($_GET['page']))|| (isset($_SESSION['request_url']) && !isset($_GET['page']))){
	 		$_SESSION['request_url'] = htmlentities($_SERVER['REQUEST_URI']);	
	 	}	
	 	return $_SESSION['request_url'];
	 	
	 }
	/**
	 * 
	 */
	public function Current($value = null){
		if($value != null){
			$this->current = $value;
		}
		return $this->current;
	}
	/**
	 * 
	 */
	public function Total($value = null){
		if($value != null){
			$this->total = $value;
		}
		return $this->total;
	}
	/**
	 * pageCount()
	 * @return int this->pagecount;
	 */
	 public function pageCount(){
	 	return $this->pagecount;
	 }
	/**
	 * getPageCount()
	 *
	 * @access public static
	 * @return int default return 20
	 */
	public static function getPageCount() {
		if (isset($_GET['pagecount'])) {
			return $_GET['pagecount'];
		}
		return 20;
	}

	/**
	 * getCurrentPage()
	 *
	 * @access public static
	 * @return int
	 */
	public static function getCurrentPage() {
		if (isset($_GET['page'])) {
			return $_GET['page'];
		} else {
			return 1;
		}
	}
	
	
	
	/**
	 * getTotal()
	 *
	 * @access public
	 * @param $username string user name
	 * @param $sql string user name default = ""
	 * @return int
	 * 
	 */
	public static function getTotal($username) {
		if (isset($_GET['total'])) {
			return $_GET['total'];
		} else {
				global $vbulletin, $db;
				$sql = "select count(username) as total from payment_history where username = '" . $username."'";
				$result = $db -> query_first($sql);
				if ($result) {
					return $result['total'];
				}
				return 0;
		}
	}

	/**
	 * calulatePage($total);
	 *
	 * @access public
	 * @param $total int.
	 * @param $countrow int
	 * @return int page count
	 */
	public static function calulatePage($total, $pagecount) {
		return (int)($total / $pagecount) + 1;
	}

	/**
	 * Compile_Url()
	 *
	 * @access public
	 * @return string as URL
	 */
	public function Compile_Url($current = 1) {
		
		if ($this -> total >= 0) {
			if(isset($_GET['username-filter'])){
				return paging::getCurrentURL()."&page=" . ($current) . "&total=" . $this -> total . "&pagecount=" . $this -> pagecount;
			}
		}
		return paging::getCurrentURL()."?page=" . ($current) . "&total=" . $this -> total . "&pagecount=" . $this -> pagecount;
	}

	/**
	 * forward();
	 *
	 * @access public
	 * @param $current int default = 0
	 * @return string
	 */
	public function forward($current = 1) {
		if ($current == 1) {
			return '<li class="disabled"><a>&laquo;</a></li>';
		} else {
			return '<li><a href="' . $this -> Compile_Url($current - 1) . '">&laquo;</a></li>';
		}
	}

	/**
	 * nextpage();
	 *
	 * @access public
	 * @param $current int default = 0
	 * @return string tag HTML
	 */
	public function nextpage($current = 1) {
		if ($current < paging::calulatePage($this -> total, $this -> pagecount)) {
			return '<li ><a href="' . $this -> Compile_Url($current + 1) . '">&raquo;</a></li>';
		} else {
			return '<li class="disabled"><a>&raquo;</a></li>';
		}
	}
	
	/**
	 * compile_path($i,$active) 
	 * @access public
	 * @param $i int Stt
	 * @param $active boolean default false
	 * @return string tag <li> element 
	 */
	public function compile_path($i,$active = false){
		if($active){
			return '<li class="active"><a href="' . $this -> Compile_Url($i) . '">' . ($i) . '<span class="sr-only">(current)</span></a></li>';
		}
		return '<li><a href="' . $this -> Compile_Url($i) . '">' . ($i) . '<span class="sr-only">(current)</span></a></li>';
	}
	/**
	 * Compile_ToString()
	 *
	 * @access public
	 * @return string as HTML.
	 *
	 */
	public function Compile_ToString() {
		$str_content = "<ul class='pagination pagination-sm'>";
		$str_content .= $this -> forward($this -> current);		
		$numpage = paging::calulatePage($this -> total, $this -> pagecount);
		$checked = 1;
		for ($i = 1; $i <= $numpage; $i++) {
			if($i == $this->getCurrentPage()){
				if(($i > 3 && $i < $numpage - 2) || ($i <= $numpage - 2 && $i > 3)){
					$str_content .= $this->compile_path($i-1);
				}
				$str_content .= $this->compile_path($i,true);
				$i += 1;
				if($i <= $numpage){
					$str_content .= $this->compile_path($i);
				}				
				$checked = 1;
				
			}else{
				if($i >= $numpage - 2 || $i <= 2){					
					$str_content .= '<li><a href="' . $this -> Compile_Url($i) . '">' . $i . '<span class="sr-only">(current)</span></a></li>';	
				}else{
					$checked = ($checked == 1) ? 2 : 3;						
					if($checked == 2){
						$str_content .= '<li><a>...</a></li>';
					}
				}
			}
		}
		$str_content .= $this -> nextpage($this -> current);
		$str_content .= '</ul>';
		return $str_content;
	}

}
?>