<?php

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
	function __construct($pagecount = null) {
		$this -> total = paging::getTotal();
		$this -> current = $this -> getCurrentPage();
		$this -> pagecount = ($pagecount == null) ? paging::getPageCount() : $pagecount;	
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
	 * @return int
	 */
	public static function getTotal() {
		if (isset($_GET['total'])) {
			return $_GET['total'];
		} else {
			global $vbulletin, $db;
			if ($vbulletin -> userinfo['userid']) {
				
				$sql = "select count(userid) as total from payment_history where userid = " . $vbulletin -> userinfo['userid'];
				$result = $db -> query_first($sql);
				if (@@mysql_num_rows($result) > 0) {
					$row = @@mysql_fetch_array($result, MYSQL_ASSOC);
					return $row['total'];
				}
				return 0;
			}
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
		return ($total / $pagecount) + 1;
	}

	/**
	 * Compile_Url()
	 *
	 * @access public
	 * @return string as URL
	 */
	public function Compile_Url($current = 1) {
		if ($this -> current > 1 && $this -> total >= 0) {
			return "checkout.php?page=" . ($current + 1) . "&total=" . $this -> total . "&pagecount=" . $this -> pagecount;
		}
		return "checkout.php";
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
			return '<li class="disabled"><a href="#">&raquo;</a></li>';
		}
	}

	/**
	 * Compile_ToString()
	 *
	 * @access public
	 * @return string as HTML.
	 *
	 */
	function Compile_ToString() {
		$str_content = "<ul class='pagination pagination-sm'>";
		$str_content .= $this -> forward($this -> current);
		$numpage = paging::calulatePage($this -> total, $this -> pagecount);
		for ($i = 0; $i < $numpage; $i++) {
			$str_content .= '<li class="active"><a href="' . $this -> Compile_Url($i + 1) . '">' . ($i + 1) . '<span class="sr-only">(current)</span></a></li>';
		}
		$str_content .= $this -> nextpage($this -> current);
		$str_content .= '</ul>';
		return $str_content;
	}

}
?>