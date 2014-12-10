<?php

/*======================================*\
|| #################################### ||
|| # Post Thank You Hack version 7.80 # ||
|| #################################### ||
\*======================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
@set_time_limit(0);
ignore_user_abort(1);

// ##################### DEFINE IMPORTANT CONSTANTS #######################

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('maintenance');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
//require_once(DIR . '/includes/functions_post_thanks.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminthreads'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['maintenance']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'recounters';
}

/*$vbulletin->input->clean_array_gpc('r', array(
	'perpage' => TYPE_UINT,
	'startat' => TYPE_UINT
));*/

($hook = vBulletinHook::fetch_hook('gamebank_services_admin_start')) ? eval($hook) : false;

if ($_REQUEST['do'] == 'recounters')
{

	($hook = vBulletinHook::fetch_hook('gamebank_services_admin_recounters_start')) ? eval($hook) : false;
	

//echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
	print_form_header('gamebank_services_admin', 'gamebank_services_admin_process');
	print_table_header('Card History', 7, 0);
	/*print_cells_row(array(
		'Mã giao dịch', 
		'Mã thành viên', 
		'Tên thành viên', 
		'Seri', 
		'Loại thẻ', 
		'Ngày mua')); */
	$num_per_page=15;
	$rs= $db->query_read("
		SELECT trid
		FROM user_gbcard
	");
	$total= mysql_num_rows($rs);
	$totalpage=ceil($total/$num_per_page);
	$current_page=0;
	if($_GET['page']) $current_page=($_GET['page']-1)*$num_per_page;
	
	$thanks = $db->query_read("
		SELECT *
		FROM user_gbcard
		ORDER BY trid DESC
		LIMIT $current_page,$num_per_page
	");
	
		//echo "<table width='90%' border='0' align='center' cellspacing='0' cellpadding='4' id='cpform_table' class='tborder' style='border-collapse:separate'>";
		echo "<tr>";
		//echo "<td class='alt1'><b>M&#227; dao d&#7883;ch</b></td>";
		echo "<td class='alt1'><b>UID</b></td>";
		echo "<td class='alt1'><b>T&#234;n th&#224;nh vi&#234;n</b></td>";
		echo "<td class='alt1'><b>Seri</b></td>";
		echo "<td class='alt1'><b>Lo&#7841;i th&#7867;</b></td>";
		echo "<td class='alt1'><b>M&#7879;nh gi&#225;</b></td>";
		echo "<td class='alt1'><b>Ng&#224;y mua</b></td>";
		echo "<td class='alt1'><b>Tr&#7841;ng th&#225;i</b></td>";

		echo "</tr>";
		
	
		
		echo "<tr>";
		//echo "<td class='alt1'><b>M&#227; dao d&#7883;ch</b></td>";
		
		echo "<td class='alt1'><input type='text' size=2 name='uid' /></td>";
		echo "<td class='alt1'><input type='text' name='tentv' /></td>";
		echo "<td class='alt1'><input type='text' name='seri' /></td>";
		echo "<td class='alt1'><input type='text' size=8 name='loaithe' /></td>";
		echo "<td class='alt1'><input type='text' size=8 name='menhgia' /></td>";
		echo "<td class='alt1'><input type='text' name='ngaymua' /></td>";
		echo "<td class='alt1'><table><tr><td>
		<select id='lstTelco' name='lstTelco'>
			<option value='0'>All</option>
			<option value='-3'>Th&#7867; kh&#244;ng s&#7917; d&#7909;ng &#273;&#432;&#7907;c</option>
			<option value='-10'>Nh&#7853;p sai &#273;&#7883;nh d&#7841;ng th&#7867;</option>
			<option value='-1001'>Nh&#7853;p sai qu&#225; 3 l&#7847;n</option>
			<option value='-1002'>L&#7893;i h&#7879; th&#7889;ng</option>
			<option value='-1003'>IP kh&#244;ng &#273;&#432;&#7907;c ph&#233;p truy c&#7853;p</option>
			<option value='-1004'>T&#234;n &#273;&#259;ng nh&#7853;p gamebank kh&#244;ng &#273;&#250;ng</option>
			<option value='-1005'>Lo&#7841;i th&#7867; kh&#244;ng &#273;&#250;ng</option>
			<option value='-1006'>H&#7879; th&#7889;ng &#273;ang &#273;&#432;&#7907;c b&#7843;o tr&#236;</option>
		</select></td>
		<td><input type='submit' name='filter' value='Filter' /></td></tr></table></td>";

		echo "</tr>";
	date_default_timezone_set("Asia/Ho_Chi_Minh");	
	while ($thank = $db->fetch_array($thanks)){
	//var_dump($vbulletin);
		if($thank['status']==-3){
			$thank['status']="Th&#7867; kh&#244;ng s&#7917; d&#7909;ng &#273;&#432;&#7907;c";
		}elseif($thank['status']==-10){
			$thank['status']="Nh&#7853;p sai &#273;&#7883;nh d&#7841;ng th&#7867;";
		}elseif($thank['status']==-1001){
			$thank['status']="Nh&#7853;p sai qu&#225; ba l&#7847;n";
		}elseif($thank['status']==-1002){
			$thank['status']="L&#7893;i h&#7879; th&#7889;ng";
		}elseif($thank['status']==-1003){
			$thank['status']="IP kh&#244;ng &#273;&#432;&#7907;c ph&#233;p truy c&#7853;p";
		}elseif($thank['status']==-1004){
			$thank['status']="T&#234;n &#273;&#259;ng nh&#7853;p GameBank kh&#244;ng &#273;&#250;ng";
		}elseif($thank['status']==-1005){
			$thank['status']="Lo&#7841;i th&#7867; kh&#244;ng &#273;&#250;ng";
		}elseif($thank['status']==-1006){
			$thank['status']="H&#7879; th&#7889;ng &#273;ang &#273;&#432;&#7907;c b&#7843;o tr&#236;";
		}else{
       $thank['status']="Th&#224;nh c&#244;ng";
		}
		
		
		
		echo "<tr>";
		//echo "<td class='alt1'>".$thank['gb_tran_id']."</td>";
		echo "<td class='alt1'>".$thank['userid']."</td>";
		echo "<td class='alt1'>".$thank['username']."</td>";
		echo "<td class='alt1'>".$thank['seri']."</td>";
		echo "<td class='alt1'>".$thank['nametype']."</td>";
		echo "<td class='alt1'>".$thank['money']."</td>";
		echo "<td class='alt1'>".date("d/m/Y h:i:s",$thank['sale_date'])."</td>";
		echo "<td class='alt1'>".$thank['status']."</td>";
		echo "</tr>";
	}
	
	echo "<tr><td colspan='7' align=center>";
	for($i=1;$i<=$totalpage;$i++){
		if($i!=($current_page/$num_per_page)+1){
			echo "<a href='gamebank_services_admin.php?do=recounters&page=$i'>".$i."</a> ";
		}else{
			echo $i." ";
		}
	}
	echo "</td></tr>";
	
	echo "</table>";
	
	
	


	($hook = vBulletinHook::fetch_hook('post_thanks_admin_recounters_end')) ? eval($hook) : false;
}

if ($_REQUEST['do'] == 'post_thanks_user_amount')
{
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_user_amount_start')) ? eval($hook) : false;

	echo '<p>' . $vbphrase['post_thanks_user_amount'] . '</p>';

	$users = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "user
		WHERE userid >= " . $vbulletin->GPC['startat'] . " AND userid < $finishat
		ORDER BY userid
	");
	while ($user = $db->fetch_array($users))
	{
		$total = $db->query_first("
			SELECT COUNT(*) AS post_thanks_user_amount FROM " . TABLE_PREFIX . "post_thanks
			WHERE userid = $user[userid]
		");

        if (!($total[post_thanks_user_amount]))
        {
          $total[post_thanks_user_amount] = 0;
        }

		$db->query_write("
            UPDATE " . TABLE_PREFIX . "user
            SET post_thanks_user_amount = $total[post_thanks_user_amount]
            WHERE userid = $user[userid]
            ");

		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();
	}

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_user_amount_end')) ? eval($hook) : false;

	if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("post_thanks_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=post_thanks_user_amount&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href='post_thanks_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=post_thanks_user_amount&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "'>" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'post_thanks_admin.php');
		print_stop_message('updated_post_counts_successfully');
	}
}

if ($_REQUEST['do'] == 'post_thanks_thanked_posts')
{
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_thanked_posts_start')) ? eval($hook) : false;

	echo '<p>' . $vbphrase['post_thanks_thanked_posts'] . '</p>';

	$users = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "user
		WHERE userid >= " . $vbulletin->GPC['startat'] . " AND userid < $finishat
		ORDER BY userid
	");
	while ($user = $db->fetch_array($users))
	{
		$total = $db->query_first("
			SELECT COUNT(*) AS post_thanks_thanked_posts FROM " . TABLE_PREFIX . "post
			WHERE userid = $user[userid] AND post_thanks_amount > 0
		");

        if (!($total[post_thanks_thanked_posts]))
        {
          $total[post_thanks_thanked_posts] = 0;
        }

		$db->query_write("
            UPDATE " . TABLE_PREFIX . "user
            SET post_thanks_thanked_posts = $total[post_thanks_thanked_posts]
            WHERE userid = $user[userid]
            ");

		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();
	}

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_thanked_posts_end')) ? eval($hook) : false;

	if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("post_thanks_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=post_thanks_thanked_posts&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href='post_thanks_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=post_thanks_thanked_posts&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "'>" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'post_thanks_admin.php');
		print_stop_message('updated_post_counts_successfully');
	}
}

if ($_REQUEST['do'] == 'gamebank_services_admin_process')
{
	($hook = vBulletinHook::fetch_hook('post_thanks_admin_recounters_start')) ? eval($hook) : false;
	

//echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
	print_form_header('gamebank_services_admin', 'gamebank_services_admin_process');
	print_table_header('Card History', 7, 0);
	/*print_cells_row(array(
		'Mã giao dịch', 
		'Mã thành viên', 
		'Tên thành viên', 
		'Seri', 
		'Loại thẻ', 
		'Ngày mua')); */
			$vbulletin->session->db_fields = array_merge(
			$vbulletin->session->db_fields,
				array(
						'uid' => TYPE_STRING,
						'tentv' => TYPE_STRING,
						'seri' => TYPE_STRING,
						'loaithe' => TYPE_STRING,
						'menhgia' => TYPE_STRING,
						'ngaymua' => TYPE_STRING,
						'lstTelco' => TYPE_STRING
				)
			);
		
			if($_GET['con']==NULL){
			$where='';
			if($_POST['uid']){
				$where.=" userid='".$_POST['uid']."'";
				$vbulletin->session->set('uid', $_POST['uid']);
			}else{
				$where.=" 1=1";
				$vbulletin->session->set('uid','');
			}
			
			if($_POST['tentv']){
				$where.=" AND username='".$_POST['tentv']."'";
				$vbulletin->session->set('tentv', $_POST['tentv']);
			}else{
				$where.=" AND 1=1";
				$vbulletin->session->set('tentv','');
			}
			
			if($_POST['seri']){
				$where.=" AND seri='".$_POST['seri']."'";
				$vbulletin->session->set('seri', $_POST['seri']);
			}else{
				$where.=" AND 1=1";
				$vbulletin->session->set('seri','');
			}
			
			if($_POST['loaithe']){
				$where.=" AND nametype='".$_POST['loaithe']."'";
				$vbulletin->session->set('loaithe', $_POST['loaithe']);
				
			}else{
				$where.=" AND 1=1";
				$vbulletin->session->set('loaithe','');
			}
			
			if($_POST['menhgia']){
				$where.=" AND money='".$_POST['menhgia']."'";
				$vbulletin->session->set('menhgia', $_POST['menhgia']);
			}else{
				$where.=" AND 1=1";
				$vbulletin->session->set('menhgia','');
			}
			
			if($_POST['ngaymua']){
				$date=$_POST['ngaymua'];
				$date=explode("/",$date);
				$date=mktime(0,0,0,$date[1],$date[0],$date[2]);
				$date_in_day=$date+24*3600-1;
				$where.=" AND sale_date >=$date AND sale_date<=$date_in_day";
				$vbulletin->session->set('ngaymua', $_POST['ngaymua']);
			}else{
				$where.=" AND 1=1";
				$vbulletin->session->set('ngaymua','');
			}
			
			if($_POST['lstTelco']){
				$where.=" AND status='".$_POST['lstTelco']."'";
				$vbulletin->session->set('lstTelco', $_POST['lstTelco']);
			}else{
				$where.=" AND 1=1";
				$vbulletin->session->set('lstTelco','');
			}
			}else{
				
			}
	$num_per_page=15;
	$current_page=0;
	if($_GET['page']){ $current_page=($_GET['page']-1)*$num_per_page;$where=$_GET['con'];}
	
	$rs= $db->query_read("
		SELECT trid
		FROM user_gbcard
		WHERE $where
	");
	$total= mysql_num_rows($rs);
	$totalpage=ceil($total/$num_per_page);
	
	
	$thanks = $db->query_read("
		SELECT *
		FROM user_gbcard
		WHERE $where
		ORDER BY trid DESC
		LIMIT $current_page,$num_per_page
	");
	
		//echo "<table width='100%' border='0' align='center' cellspacing='0' cellpadding='4' id='cpform_table' class='tborder' style='border-collapse:separate'>";
		echo "<tr>";
		//echo "<td class='alt1'><b>M&#227; dao d&#7883;ch</b></td>";
		echo "<td class='alt1'><b>UID</b></td>";
		echo "<td class='alt1'><b>T&#234;n th&#224;nh vi&#234;n</b></td>";
		echo "<td class='alt1'><b>Seri</b></td>";
		echo "<td class='alt1'><b>Lo&#7841;i th&#7867;</b></td>";
		echo "<td class='alt1'><b>M&#7879;nh gi&#225;</b></td>";
		echo "<td class='alt1'><b>Ng&#224;y mua</b></td>";
		echo "<td class='alt1'><b>Tr&#7841;ng th&#225;i</b></td>";

		echo "</tr>";
		
		echo "<tr>";
		//echo "<td class='alt1'><b>M&#227; dao d&#7883;ch</b></td>";
		
		echo "<td class='alt1'><input type='text' size=2 name='uid' value='".$vbulletin->session->vars['uid']."' /></td>";
		echo "<td class='alt1'><input type='text' name='tentv' value='".$vbulletin->session->vars['tentv']."' /></td>";
		echo "<td class='alt1'><input type='text' name='seri' value='".$vbulletin->session->vars['seri']."' /></td>";
		echo "<td class='alt1'><input type='text' size=8 name='loaithe' value='".$vbulletin->session->vars['loaithe']."' /></td>";
		echo "<td class='alt1'><input type='text' size=8 name='menhgia' value='".$vbulletin->session->vars['menhgia']."' /></td>";
		echo "<td class='alt1'><input type='text' name='ngaymua' value='".$vbulletin->session->vars['ngaymua']."' /></td>";
		echo "<td class='alt1'><table><tr><td>
		<select id='lstTelco' name='lstTelco'>
			<option value='0'>All</option>
			<option value='-3'>Th&#7867; kh&#244;ng s&#7917; d&#7909;ng &#273;&#432;&#7907;c</option>
			<option value='-10'>Nh&#7853;p sai &#273;&#7883;nh d&#7841;ng th&#7867;</option>
			<option value='-1001'>Nh&#7853;p sai qu&#225; 3 l&#7847;n</option>
			<option value='-1002'>L&#7893;i h&#7879; th&#7889;ng</option>
			<option value='-1003'>IP kh&#244;ng &#273;&#432;&#7907;c ph&#233;p truy c&#7853;p</option>
			<option value='-1004'>T&#234;n &#273;&#259;ng nh&#7853;p gamebank kh&#244;ng &#273;&#250;ng</option>
			<option value='-1005'>Lo&#7841;i th&#7867; kh&#244;ng &#273;&#250;ng</option>
			<option value='-1006'>H&#7879; th&#7889;ng &#273;ang &#273;&#432;&#7907;c b&#7843;o tr&#236;</option>
		</select></td>
		<td><input type='submit' name='filter' value='Filter' /></td></tr></table></td>";

		echo "</tr>";
		
		
	while ($thank = $db->fetch_array($thanks)){
	//var_dump($vbulletin);
		if($thank['status']==-3){
			$thank['status']="Th&#7867; kh&#244;ng s&#7917; d&#7909;ng &#273;&#432;&#7907;c";
		}elseif($thank['status']==-10){
			$thank['status']="Nh&#7853;p sai &#273;&#7883;nh d&#7841;ng th&#7867;";
		}elseif($thank['status']==-1001){
			$thank['status']="Nh&#7853;p sai qu&#225; ba l&#7847;n";
		}elseif($thank['status']==-1002){
			$thank['status']="L&#7893;i h&#7879; th&#7889;ng";
		}elseif($thank['status']==-1003){
			$thank['status']="IP kh&#244;ng &#273;&#432;&#7907;c ph&#233;p truy c&#7853;p";
		}elseif($thank['status']==-1004){
			$thank['status']="T&#234;n &#273;&#259;ng nh&#7853;p GameBank kh&#244;ng &#273;&#250;ng";
		}elseif($thank['status']==-1005){
			$thank['status']="Lo&#7841;i th&#7867; kh&#244;ng &#273;&#250;ng";
		}elseif($thank['status']==-1006){
			$thank['status']="H&#7879; th&#7889;ng &#273;ang &#273;&#432;&#7907;c b&#7843;o tr&#236;";
		}else{
		  $thank['status']="Th&#224;nh c&#244;ng"; 
		}
		
		
		
		echo "<tr>";
		//echo "<td class='alt1'>".$thank['id']."</td>";
		echo "<td class='alt1'>".$thank['userid']."</td>";
		echo "<td class='alt1'>".$thank['username']."</td>";
		echo "<td class='alt1'>".$thank['seri']."</td>";
		echo "<td class='alt1'>".$thank['nametype']."</td>";
		echo "<td class='alt1'>".$thank['money']."</td>";
		echo "<td class='alt1'>".date("d/m/Y h:i:s",$thank['sale_date'])."</td>";
		echo "<td class='alt1'>".$thank['status']."</td>";
		echo "</tr>";
	}
		echo "<tr><td colspan='7' align=center>";
		for($i=1;$i<=$totalpage;$i++){
			if($i!=($current_page/$num_per_page)+1){
				echo "<a href=\"gamebank_services_admin.php?do=gamebank_services_admin_process&page=$i&con=$where\">".$i."</a> ";
			}else{
				echo $i." ";
			}
		}
		echo "</td></tr>";

	echo "</table>";
	$vbulletin->session->save();
	


	($hook = vBulletinHook::fetch_hook('post_thanks_admin_recounters_end')) ? eval($hook) : false;
}

if ($_REQUEST['do'] == 'post_thanks_post_amount')
{
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_post_amount_start')) ? eval($hook) : false;

	echo '<p>' . $vbphrase['post_thanks_post_amount'] . '</p>';

	$posts = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "post
		WHERE postid >= " . $vbulletin->GPC['startat'] . " AND postid < $finishat
		ORDER BY postid
	");
	while ($post = $db->fetch_array($posts))
	{
		$total = $db->query_first("
			SELECT COUNT(*) AS post_thanks_amount FROM " . TABLE_PREFIX . "post_thanks
			WHERE postid = $post[postid]
		");

        if (!($total[post_thanks_amount]))
        {
          $total[post_thanks_amount] = 0;
        }

		$db->query_write("
            UPDATE " . TABLE_PREFIX . "post
            SET post_thanks_amount = $total[post_thanks_amount]
            WHERE postid = $post[postid]
            ");

		echo construct_phrase($vbphrase['processing_x'], $post['postid']) . "<br />\n";
		vbflush();
	}

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_post_amount_end')) ? eval($hook) : false;

	if ($checkmore = $db->query_first("SELECT postid FROM " . TABLE_PREFIX . "post WHERE postid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("post_thanks_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=post_thanks_post_amount&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href='post_thanks_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=post_thanks_post_amount&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "'>" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'post_thanks_admin.php');
		print_stop_message('updated_post_counts_successfully');
	}
}

if ($_REQUEST['do'] == 'special_actions')
{
	($hook = vBulletinHook::fetch_hook('post_thanks_admin_special_actions_start')) ? eval($hook) : false;

	print_form_header('post_thanks_admin', 'delete_all_users_thanks');
	print_table_header($vbphrase['post_thanks_delete_all_users_thanks'], 2, 0);
	print_description_row($vbphrase['post_thanks_delete_all_users_thanks_help']);
	print_input_row($vbphrase['userid'], 'userid');
	print_submit_row($vbphrase['post_thanks_delete_all_users_thanks']);

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_special_actions_end')) ? eval($hook) : false;
}

if ($_REQUEST['do'] == 'delete_all_users_thanks')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT
	));

	$userid = $vbulletin->GPC['userid'];

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_delete_all_users_thanks_start')) ? eval($hook) : false;

	echo '<p>' . $vbphrase['post_thanks_delete_all_users_thanks'] . '</p>';

	$thanks = $db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "post_thanks
		WHERE userid = $userid
		ORDER BY postid
	");
	while ($thank = $db->fetch_array($thanks))
	{
		$postinfo = fetch_postinfo($thank['postid']);

		if ($postinfo === false)
		{
			$vbulletin->db->query_write("DELETE FROM ". TABLE_PREFIX ."post_thanks WHERE postid = '$thank[postid]' AND userid = '$userid'");
		}
		else
		{
			delete_thanks($postinfo, $userid);
		}

		echo construct_phrase($vbphrase['processing_x'], $thank['postid']) . "<br />\n";
		vbflush();
	}

	($hook = vBulletinHook::fetch_hook('post_thanks_admin_delete_all_users_thanks_end')) ? eval($hook) : false;

	define('CP_REDIRECT', 'post_thanks_admin.php?do=special_actions');
	print_stop_message('post_thanks_delete_all_users_thanks_successfully');
}

($hook = vBulletinHook::fetch_hook('post_thanks_admin_end')) ? eval($hook) : false;

print_cp_footer();
?>