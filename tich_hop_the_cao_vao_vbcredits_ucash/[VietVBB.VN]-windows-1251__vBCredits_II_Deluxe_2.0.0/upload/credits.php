<?php

/*=======================================================================*\
|| ##################################################################### ||
|| # vBCredits II Deluxe 2.0.0 - `credits.php`						   # ||
|| # ------------------------------------------------------------------# ||
|| # Author: Darkwaltz4 {blackwaltz4@msn.com}						   # ||
|| # Copyright � 2009 - 2010 John Jakubowski. All Rights Reserved.	   # ||
|| # This file may not be redistributed in whole or significant part.  # ||
|| # -----------------vBulletin IS NOT FREE SOFTWARE!------------------# ||
|| #			 Support: http://www.dragonbyte-tech.com/			   # ||
|| ##################################################################### ||
\*=======================================================================*/

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
if ($paymethod = preg_replace('/\W/i', '', $_GET['method']))
{	//special actions for payment processing
	define('THIS_SCRIPT', 'credits_gateway');
	define('CSRF_PROTECTION', false);
	define('SKIP_SESSIONCREATE', 1);
}
else
{	//regular page
	define('THIS_SCRIPT', 'credits');
	define('CSRF_PROTECTION', true);
}

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('credits');

// get special data templates from the datastore
$specialtemplates = array(
	'smiliecache',
	'bbcodecache'
);

// pre-cache templates used by all actions
$globaltemplates = array(
	//'credits',
	'credits_home',
	//'credits_transfer',
	'credits_richest',
	'credits_richest_user',
	'credits_transaction'
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions.php');
require_once(DIR . '/includes/class_bbcode.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if ($paymethod)
{	//payment handling cleaned and checked above
	if (file_exists(DIR . '/includes/paymentapi/class_' . $paymethod . '.php'))
	{
		require_once(DIR . '/includes/class_paid_subscription.php');
		$vbulletin->nozip = true;

		class vBCredits_Payment
		{	//override lookup query
			function query_first($query)
			{
				global $vbulletin, $db, $transid;
				static $found;
	
				if ($query[7] != '*')
				{	//replacement lookup
					return $found = $db->query_first("
						SELECT p.*, p.fromuserid AS userid, p.amount AS total, user.username, 0 AS subscriptionsubid
						FROM " . TABLE_PREFIX . "credits_payment AS p
						INNER JOIN " . TABLE_PREFIX . "user AS user ON (p.fromuserid = user.userid)
						WHERE hash = " . ($transid = array_pop(explode('WHERE hash = ', $query)))
					);
				}	//replacement subscription
				else return array('cost' => serialize(array(array('cost' => array($found['currency'] => $found['price'])))));
			}
			//replace this too...
			function escape_string($string) { global $db; return $db->escape_string($string); }
		}

		$api =& $vbulletin->vbcredits['processor'][$paymethod];
		require_once(DIR . '/includes/paymentapi/class_' . $api['classname'] . '.php');
		$api_class = 'vB_PaidSubscriptionMethod_' . $api['classname'];
		$api['settings'] = serialize($api['settings']);

		$vbcreg->GPC =& $vbulletin->GPC;
		$vbcreg->input = $vbulletin->input;
		$vbcreg->db = new vBCredits_Payment();
		$vbcreg->ipaddress = $vbulletin->ipaddress;
		$apiobj = new $api_class($vbcreg);//my own
		$subobj = new vB_PaidSubscription($vbulletin);//all its for
		$apiobj->settings = $subobj->construct_payment_settings($api['settings']);

		if ($apiobj->verify_payment())
		{	// its a valid payment now lets check transactionid
			$negate = intval($apiobj->type == 2);
			$tarcur =& $vbulletin->VBCREDITS['currency'][$apiobj->paymentinfo['currencyid']];
			$transaction = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "paymenttransaction WHERE transactionid = '" . $db->escape_string($apiobj->transaction_id) . "' AND paymentapiid = $api[paymentapiid]");
			$found = $db->query_first("SELECT transactionid FROM " . TABLE_PREFIX . "credits_transaction WHERE actionid = 'purchase' AND negate = $negate AND referenceid = " . $transid);//from above

			if (($negate OR (empty($transaction) AND $apiobj->type == 1)) AND $vbulletin->options['paymentemail'])
			{
				$processor = $api['title'];
				$transactionid = $apiobj->transaction_id;
				$username = unhtmlspecialchars($apiobj->paymentinfo['username']);
				$memberlink = 'member.php?u=' . ($userid = $apiobj->paymentinfo['userid']);
				$amount = vb_number_format($apiobj->paymentinfo['amount'], 2) . ' ' . strtoupper($apiobj->paymentinfo['currency']);
				$subscription = vb_number_format($apiobj->paymentinfo['total'], ( (strpos($apiobj->paymentinfo['total'], '.') !== false) ? strlen(array_pop(explode('.', $apiobj->paymentinfo['total']))) : 0 )) . ' ' . $tarcur['title'];
				eval(fetch_email_phrases(( $negate ? 'payment_reversed' : 'payment_received' ), 0));
	
				foreach (explode(' ', $vbulletin->options['paymentemail']) AS $toemail)
				{
					if ($toemail = trim($toemail)) vbmail($toemail, $subject, $message, true);
				}
			}
			if (empty($transaction))
			{	// transaction hasn't been processed before
				$trans = array(
					'transactionid' => $apiobj->transaction_id,
					'paymentinfoid' => $apiobj->paymentinfo['paymentid'],//eh
					'amount'        => $apiobj->paymentinfo['amount'],
					'currency'      => $apiobj->paymentinfo['currency'],
					'state'         => $apiobj->type,
					'dateline'      => TIMENOW,
					'paymentapiid'  => $api['paymentapiid'],
				);
				//insert a record of it
				if (!$apiobj->type) $trans['request'] = serialize(array('vb_error_code' => $apiobj->error_code, 'GET' => serialize($_GET), 'POST' => serialize($_POST)));
				$db->query_write(fetch_query_sql($trans, 'paymenttransaction'));
			}
			if (empty($found))
			{
				if ($myself = true AND !$vbulletin->userinfo['userid'])
				{	//load up myself
					$vbulletin->userinfo = fetch_userinfo($apiobj->paymentinfo['fromuserid']);
					cache_permissions($vbulletin->userinfo, false);
				}
				if ($vbulletin->userinfo['userid'] != $apiobj->paymentinfo['touserid'])
				{	//load up recipient
					$user = fetch_userinfo($apiobj->paymentinfo['touserid']);
					cache_permissions($user, false);
					$myself = false;
				}
	
				VBCREDITS::action('purchase', $apiobj->paymentinfo['touserid'], substr($transid, 1, -1), $negate, array('multiplier' => $apiobj->paymentinfo['total'], 'currencyid' => $apiobj->paymentinfo['currencyid'], 'userinfo' => ( $myself ? null : $user ), 'ownerid' => $apiobj->paymentinfo['touserid'], 'message' => $apiobj->paymentinfo['username'] . ( $apiobj->paymentinfo['note'] ? ': ' . $apiobj->paymentinfo['note'] : '' )));
	
				if (!$negate AND $apiobj->display_feedback)
				{	//happy redirect
					$vbulletin->url = $vbulletin->options['bburl'] . '/credits.php';
					eval(print_standard_redirect('payment_complete', true, true));
				}
			}
		}
		else
		{ //save error
			$db->query_write(fetch_query_sql(array('state' => 0, 'dateline' => TIMENOW, 'paymentapiid' => $api['paymentapiid'], 'request' => serialize(array('vb_error_code' => $apiobj->error_code, 'GET' => serialize($_GET), 'POST' => serialize($_POST)))), 'paymenttransaction'));

			if ($apiobj->display_feedback AND !empty($apiobj->error))
			{
				define('VB_ERROR_LITE', true);
				standard_error($apiobj->error);
			}
		}
	}

	exec_header_redirect($vbulletin->options['forumhome'] . '.php');
}

if (!class_exists('VBCREDITS') OR !$vbulletin->userinfo['userid']) print_no_permission();
$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());//logged in / enabled

if ($_POST['do'] == 'charge')
{
	if ($post = $db->query_first("SELECT p.*, t.forumid FROM " . TABLE_PREFIX . "post AS p LEFT JOIN " . TABLE_PREFIX . "thread AS t ON (t.threadid = p.threadid) WHERE p.postid = " . intval($_POST['postid'])))
	{	//get information from post
		$bbcode_parser->parse($post['pagetext']); //build charge hashes and update chargecontent on post
		if (!$post['chargecontent']) $db->query_write("UPDATE " . TABLE_PREFIX . "post SET chargecontent = '" . $db->escape_string(serialize($bbcode_parser->chargelist)) . "' WHERE postid = " . $post['postid']);

		if ($content = $bbcode_parser->chargelist[$_POST['hash']])
		{	//content found for it
			if ($vbulletin->userinfo['userid'] != $post['userid'] AND !$found = $db->query_first("SELECT transactionid FROM " . TABLE_PREFIX . "credits_transaction WHERE actionid = 'content' AND userid = " . $vbulletin->userinfo['userid'] . " AND status = 1 AND referenceid = '" . $db->escape_string($_POST['hash']) . "'"))
			{	//needs to buy the content
				$extra = array('currencyid' => $vbulletin->options['credits_action_content_currency'], 'multiplier' => -1 * $content[1], 'forumid' => $post['forumid'], 'ownerid' => $post['userid'], 'message' => '[post=' . $post['postid'] . ']' . $vbphrase['post'] . '[/post]');
				$fromself = VBCREDITS::action('content', $vbulletin->userinfo['userid'], true, false, $extra);

				$extra['multiplier'] = -1 * $multiplier; //updated from first call
				$user = fetch_userinfo($post['userid']);
				cache_permissions($user, false);
				$extra['userinfo'] = $user;

				$fromuser = VBCREDITS::action('content', $post['userid'], true, false, $extra);
				if (is_numeric($fromself)) VBCREDITS::apply($fromself, $_POST['hash']); else eval(standard_error(fetch_error('credits_charge_error')));
				if (is_numeric($fromuser)) VBCREDITS::apply($fromuser, $_POST['hash']);
			}
			/*if ($vbulletin->GPC['ajax'])*/
			VBCREDITS::shutdown();
			exit($content[0]);
		}
	}
	//got here couldnt load content
	eval(standard_error(fetch_error('credits_charge_error')));
}
else if ($_GET['code'])
{	//direct code in url
	foreach ($vbulletin->vbcredits['redemption'] AS $currencyid => $redeems)
	{
		foreach ($redeems AS $redeem)
		{
			if ($redeem['startdate'] < TIMENOW AND (!$redeem['enddate'] OR $redeem['enddate'] > TIMENOW) AND in_array($_GET['code'], $redeem['codes']) AND (empty($redeem['usergroups']) OR is_member_of($vbulletin->userinfo, $redeem['usergroups'])))
			{	//phew, we can use this one
				$stats = $db->query_first("SELECT COUNT(DISTINCT userid) AS users, COUNT(*) AS total FROM " . TABLE_PREFIX . "credits_transaction WHERE actionid = 'redeem' AND referenceid IN ('" . implode("', '", $redeem['codes']) . "')");
				$found = $db->query_first("SELECT transactionid FROM " . TABLE_PREFIX . "credits_transaction WHERE actionid = 'redeem' AND referenceid = '" . $db->escape_string($_GET['code']) . "'");
				if (!$redirect) $redirect = $redeem['redirect'];

				if ((sizeof($redeem['codes']) == 1 OR !$found) AND (!$redeem['maxtimes'] OR $stats['total'] < $redeem['maxtimes']) AND (!$redeem['maxusers'] OR $stats['users'] < $redeem['maxusers']))
				{	//code still very much valid
					VBCREDITS::action('redeem', $vbulletin->userinfo['userid'], $_GET['code'], false, array('currencyid' => $currencyid, 'multiplier' => $redeem['amount'], 'ownerid' => $vbulletin->userinfo['userid'], 'message' => $_GET['code']));
				}
			}
		}
	}
	//redirect regardless of success - expire some?
	exec_header_redirect( $redirect ? $redirect : $vbulletin->options['bburl'] );
}
else if ($_POST['do'] == 'popup')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'target_user'		=> TYPE_NOHTML,
		'target_currency'	=> TYPE_UINT,
		'actionid'			=> TYPE_NOHTML,
		'donate_currency'	=> TYPE_UINT,
		'adjust_method'		=> TYPE_INT,//1 or -1
		'purchase_money'	=> TYPE_NOHTML,
		'purchase_service'	=> TYPE_NOHTML,
		'value'				=> TYPE_STR,//code or num
		'note'				=> TYPE_STR,
		'info'				=> TYPE_BOOL
	));

	if (!$user = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username LIKE '" . $db->escape_string_like($vbulletin->GPC['target_user']) . "'")) eval(standard_error(fetch_error('invalid_user_specified')));
	$tarcur =& $vbulletin->vbcredits['currency'][$vbulletin->GPC['target_currency']];
	$usernote = ( $vbulletin->GPC['note'] ? ': ' . $vbulletin->GPC['note'] : '' );
	$user = fetch_userinfo($user['userid']);
	cache_permissions($user, false);
	$good = false;

	switch ($vbulletin->GPC['actionid'])
	{
		case 'donate':
			$vbulletin->GPC['value'] = floatval($vbulletin->GPC['value']);
			if ($vbulletin->GPC['value'] <= 0) eval(standard_error(fetch_error('credits_transfer_value')));
			$soucur =& $vbulletin->vbcredits['currency'][$vbulletin->GPC['donate_currency']];
			$action = ( ($transfer = ($user['userid'] == $vbulletin->userinfo['userid'])) ? 'transfer' : 'donate' );
			if ($soucur['currencyid'] != $tarcur['currencyid'] AND (!$soucur['outbound'] OR !$tarcur['inbound'])) eval(standard_error(fetch_error('credits_transfer_perms')));
			if ($transfer AND $soucur['currencyid'] == $tarcur['currencyid']) eval(standard_error(fetch_error('credits_transfer_self')));
			$fromself = VBCREDITS::action($action, $vbulletin->userinfo['userid'], true, false, array('currencyid' => $soucur['currencyid'], 'multiplier' => -1 * $vbulletin->GPC['value'], 'message' => $user['username'] . $usernote));

			if (is_numeric($fromself))
			{	//only give if sent worked
				$fromuser = VBCREDITS::action($action, $user['userid'], true, false, array('currencyid' => $tarcur['currencyid'], 'userinfo' => ( $transfer ? null : $user ), 'multiplier' => -1 * $multiplier * $soucur['value'] / $tarcur['value'], 'message' => $vbulletin->userinfo['username'] . $usernote));

				if (is_numeric($fromself))
				{	//only take if give worked
					VBCREDITS::apply($fromself, ( $transfer ? null : $user['userid']));
					VBCREDITS::apply($fromuser, ( $transfer ? null : $vbulletin->userinfo['userid'] ));
					$good = true;
				}
			}

			echo ( $good ? $vbphrase['credits_transfer_success'] : $vbphrase['credits_transfer_failure'] );
		break;
		case 'adjust':
			if ($permissions['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_adjust'])
			{
				$vbulletin->GPC['value'] = floatval($vbulletin->GPC['value']);
				if ($vbulletin->GPC['value'] <= 0) eval(standard_error(fetch_error('credits_transfer_value')));
				VBCREDITS::action('adjust', $user['userid'], $vbulletin->userinfo['userid'], $negate = ($vbulletin->GPC['adjust_method'] < 0), array('currencyid' => $tarcur['currencyid'], 'userinfo' => ( ($user['userid'] != $vbulletin->userinfo['userid']) ? $user : null ), 'multiplier' => ( $negate ? -1 : 1 ) * $vbulletin->GPC['value'], 'ownerid' => $vbulletin->userinfo['userid'], 'message' => $vbulletin->userinfo['username'] . $usernote));
				echo $vbphrase['credits_adjust_success'];
			}
		break;
		case 'purchase':
			if (is_array($converses = $vbulletin->vbcredits['conversion'][$tarcur['currencyid']]))
			{
				$value = floatval($vbulletin->GPC['value']);
				$total = $prevval = 0;
				$prev = array();

				foreach ($converses AS $converse)
				{
					if ($converse['minimum'] > $value) break;
					$total += $prevval * ($converse['minimum'] - $prev['minimum']);
					$prevval = $converse['cost'][$vbulletin->GPC['purchase_money']];
					$prev = $converse;
				}

				$total = round(( $prev['tiered'] ? $total + $prevval * ($value - $prev['minimum']) : $value * $prevval ), 2);

				if (!$vbulletin->GPC['info'])
				{	//insert and return info
					$hash = md5($vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt'] . $total . uniqid(microtime(),1));
					$db->query_write("INSERT INTO " . TABLE_PREFIX . "credits_payment (hash, completed, amount, currencyid, price, currency, fromuserid, touserid, note) VALUES ('" . $db->escape_string($hash) . "', 0, $value, " . $tarcur['currencyid'] . ", $total, '" . $db->escape_string($vbulletin->GPC['purchase_money']) . "', " . $vbulletin->userinfo['userid'] . ", " . $user['userid'] . ", '" . $db->escape_string($vbulletin->GPC['note']) . "')");
					require_once(DIR . '/includes/class_paid_subscription.php');
					//crazy faking
					$subinfo = $prev['cost'];
					$vbphrase['x_subscription'] = '%1$s';
					$subobj = new vB_PaidSubscription($vbulletin);
					$timeinfo['cost'][$vbulletin->GPC['purchase_money']] = $total;
					$method = $vbulletin->vbcredits['processor'][$vbulletin->GPC['purchase_service']];
					$subinfo['title'] = vb_number_format($value, ( (strpos($value, '.') !== false) ? strlen(array_pop(explode('.', $value))) : 0 )) . ' ' . $tarcur['title'];
					$subinfo['options'] = $prev['cost']['tax'] + $prev['cost']['shipping'];
					$method['settings'] = serialize($method['settings']);
					//generate fake form to submit
					$form = $subobj->construct_payment($hash, $method, $timeinfo, $vbulletin->GPC['purchase_money'], $subinfo, $vbulletin->userinfo);
					exit( ($form AND $total) ? str_replace(array('payment_gateway', $vbulletin->options['bburl'] . '/' . $vbulletin->options['forumhome'] . '.php', $vbulletin->options['bburl']), array('credits', REFERRER_PASSTHRU, REFERRER_PASSTHRU), implode('|', $form)) : $vbphrase['credits_payment_failure'] );
				}
				else exit( $total ? vb_number_format($total, 2) : '' );
			}
		break;
		case 'redeem':
			if (is_array($redeems = $vbulletin->vbcredits['redemption'][$tarcur['currencyid']]))
			{
				foreach ($redeems AS $redeem)
				{
					if ($redeem['startdate'] < TIMENOW AND (!$redeem['enddate'] OR $redeem['enddate'] > TIMENOW) AND in_array($vbulletin->GPC['value'], $redeem['codes']) AND (empty($redeem['usergroups']) OR is_member_of($vbulletin->userinfo, $redeem['usergroups'])))
					{	//phew, we can use this one
						if ($vbulletin->GPC['info']) exit($redeem['title'] . '|' . vb_number_format($redeem['amount'], $tarcur['decimals']) . ' ' . $tarcur['title']);
						$stats = $db->query_first("SELECT COUNT(DISTINCT userid) AS users, COUNT(*) AS total FROM " . TABLE_PREFIX . "credits_transaction WHERE actionid = 'redeem' AND referenceid IN ('" . implode("', '", $redeem['codes']) . "')");
						$found = $db->query_first("SELECT transactionid FROM " . TABLE_PREFIX . "credits_transaction WHERE actionid = 'redeem' AND referenceid = '" . $db->escape_string($vbulletin->GPC['value']) . "'");

						if ((sizeof($redeem['codes']) == 1 OR !$found) AND (!$redeem['maxtimes'] OR $stats['total'] < $redeem['maxtimes']) AND (!$redeem['maxusers'] OR $stats['users'] < $redeem['maxusers']))
						{	//code still very much valid
							$attempt = VBCREDITS::action('redeem', $user['userid'], true, false, array('currencyid' => $redeem['currencyid'], 'multiplier' => $redeem['amount'], 'userinfo' => ( ($user['userid'] != $vbulletin->userinfo['userid']) ? $user : null ), 'ownerid' => $vbulletin->userinfo['userid'], 'message' => $vbulletin->userinfo['username'] . ' (' . $vbulletin->GPC['value'] . ')' . $usernote));

							if (is_numeric($attempt))
							{
								VBCREDITS::apply($attempt, $vbulletin->GPC['value']);
								$good = true;
							}
						}
					}
				}
				//expire some?
				if (!$vbulletin->GPC['info']) echo ( $good ? $vbphrase['credits_redeemed_success'] : $vbphrase['credits_redeemed_failure'] );
			}
		//default case with hook?
	}

	VBCREDITS::shutdown();
	exit;
}
else
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'		=> TYPE_UINT,
		'pagenumber'     => TYPE_UINT,
		'currencyid'     => TYPE_UINT,
		'actionid'     => TYPE_NOHTML
	));
	
	$curs = $acts = array();
	VBCREDITS::display('credits', $vbulletin->userinfo, $template_hook);
	$navbits = array('credits.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['currency']);
	$special = ($permissions['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_special']);
	if ($vbulletin->GPC['userid'] != $vbulletin->userinfo['userid'] AND !($permissions['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_viewlog'])) $vbulletin->GPC['userid'] = $vbulletin->userinfo['userid'];

	if ($vbulletin->GPC['userid'])
	{	//look up specific user
		if ($notmyself = ($vbulletin->userinfo['userid'] != $vbulletin->GPC['userid']))
		{
			$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
			cache_permissions($userinfo, false);
		}	//myself should be up to date
		else $userinfo =& $vbulletin->userinfo;
	}
	if (is_array($vbulletin->vbcredits['currency']) AND $vbulletin->options['credits_richest'])
	{
		foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
		{
			if ($currency['privacy'] == 2 OR $special)
			{	//public currencies or im special
				$hook_query_fields = $hook_query_joins = $topusers = '';
				VBCREDITS::user($hook_query_fields, $hook_query_joins);
				$users = $db->query_read("SELECT userfield.*, usertextfield.*, user.*, IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid" .
				$hook_query_fields . " FROM " . TABLE_PREFIX . "user AS user LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON (user.userid = userfield.userid)
				LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid) " . $hook_query_joins . "
				ORDER BY vbcredits_$currencyid DESC LIMIT " . $vbulletin->options['credits_richest']);
	
				while ($user = $db->fetch_array($users))
				{
					$useramt =& $user['vbcredits_' . $currencyid];
					if ($useramt < 0 AND $currency['negative'] == 1) $useramt = 0;
					$useramt = fetch_word_wrapped_string(vb_number_format($useramt, $currency['decimals']));
					$user['musername'] = fetch_musername($user);
	
					if ($vb4)
					{
						$t = vB_Template::create('credits_richest_user');
						$t->register('currencyid', $currencyid);
						$t->register('useramt', $useramt);
						$t->register('user', $user);
						$t->register('vb4', $vb4);
						$topusers .= $t->render();
					}
					else eval('$topusers .= "' . fetch_template('credits_richest_user') . '";');
				}

				$db->free_result($users);

				if ($vb4)
				{
					$t = vB_Template::create('credits_richest');
					$t->register('currency', $currency);
					$t->register('topusers', $topusers);
					$t->register('vb4', $vb4);
					$richest .= $t->render();
				}
				else eval('$richest .= "' . fetch_template('credits_richest') . '";');
			}

			$curs[$currencyid] = $currency['title'];//for the dropdown
		}
	}

	foreach ($vbulletin->vbcredits['action'] AS $actionid => $action) $acts[$vbphrase['credits_category_' . $action['category']]][$actionid] = $action['title'];
	$allcurrencies = construct_select_options($curs, $vbulletin->GPC['currencyid']);
	$allactions = construct_select_options($acts, $vbulletin->GPC['actionid']);

	$condition = 't.eventid != 0 AND t.status = 1' . ( $userinfo['userid'] ? ' AND t.userid = ' . $userinfo['userid'] : '' );
	if ($vbulletin->GPC['currencyid']) $condition .= ' AND t.currencyid = ' . $vbulletin->GPC['currencyid'];
	if ($vbulletin->GPC['actionid']) $condition .= " AND t.actionid = '" . $db->escape_string($vbulletin->GPC['actionid']) . "'";

	$counttrans = $db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "credits_transaction AS t WHERE $condition");
	if ($vbulletin->GPC['pagenumber'] < 1) $vbulletin->GPC['pagenumber'] = 1;

	$perpage = $vbulletin->options['credits_transactions'];
	$total_pages = max(ceil($counttrans['total'] / $perpage), 1);
	$pagenumber = ($vbulletin->GPC['pagenumber'] > $total_pages ? $total_pages : $vbulletin->GPC['pagenumber']);
	$start = ($pagenumber - 1) * $perpage;

	$sizetext = 'credits_size_' . ( $vbulletin->options['credits_size_words'] ? 'word' : 'char' );
	$sizetext = array($vbphrase[$sizetext . 's'], $vbphrase[$sizetext]);//size mult labels
	$pagenav = construct_page_nav($pagenumber, $perpage, $counttrans['total'], 'credits.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $vbulletin->GPC['userid'] . '&currencyid=' . $vbulletin->GPC['currencyid'] . '&actionid=' . $vbulletin->GPC['actionid']);
	$trans = $db->query_read("SELECT t.*, u.username, a.multiplier AS hasmult FROM " . TABLE_PREFIX . "credits_transaction AS t LEFT JOIN " . TABLE_PREFIX . "credits_action AS a ON (a.actionid = t.actionid) LEFT JOIN " . TABLE_PREFIX . "user AS u ON (u.userid = t.userid) WHERE $condition ORDER BY t.timestamp DESC LIMIT $start, $perpage");

	while ($tran = $db->fetch_array($trans))
	{
		$which = 'earned';
		$action =& $vbulletin->vbcredits['action'][$tran['actionid']];
		$negate = ( $tran['negate'] ? array('<i>', '</i>', '<s>', '</s>') : array('', '', '', '') );
		$multlabel = ( ($action['multiplier'] == 'Size') ? $sizetext : ( $action['currency'] ? $vbphrase['currency'] : explode('|', $action['multiplier'], 2) ) );
		$mult = ( $tran['hasmult'] ? array('<span title="' . $multlabel[0] . ': ' . $tran['multiplier'] . '">', '</span>') : array('', '') );
		$currency =& $vbulletin->vbcredits['currency'][$tran['currencyid']];
		$f = ( $userinfo['userid'] ? 5 : 0 );//for vb3 widths
	
		if ($tran['amount'] < 0)
		{
			$which = 'spent';
			$tran['amount'] *= -1;
		}
	
		$transdate = vbdate($vbulletin->options['dateformat'], $tran['timestamp'], true) . ', ' . vbdate($vbulletin->options['timeformat'], $tran['timestamp']);
		$tranaction = $negate[2] . $action['title'] . $negate[3];
		$trannote = $bbcode_parser->parse($tran['message']);
		$tranamount = $negate[0] . $mult[0] . construct_phrase($vbphrase['credits_transaction_' . $which], vb_number_format($tran['amount'], max($currency['decimals'], $vbulletin->options['credits_transaction_decimals'])), $currency['title']) . $mult[1] . $negate[1];
		$tranlink = ( $tran['referenceid'] ? $action['referformat'] . $tran['referenceid'] : '' );

		if ($vb4)
		{
			$t = vB_Template::create('credits_transaction');
			$t->register('userinfo', $userinfo);
			$t->register('transdate', $transdate);
			$t->register('tranaction', $tranaction);
			$t->register('tranamount', $tranamount);
			$t->register('tranlink', $tranlink);
			$t->register('trannote', $trannote);
			$t->register('tran', $tran);
			$t->register('vb4', $vb4);
			$transactions .= $t->render();
		}
		else eval('$transactions .= "' . fetch_template('credits_transaction') . '";');
	}
	//	if (!$numtrans)
	//	{
	//		print_description_row('No Transactions Found', false, 7, '', 'center');
	//	}
	
	if ($vb4)
	{
		$navbar = render_navbar_template(construct_navbits($navbits));	
		$t = vB_Template::create('credits_home');
		$t->register_page_templates();
		$t->register('richest', 			$richest);
		$t->register('transactions',		$transactions);
		$t->register('allcurrencies',		$allcurrencies);
		$t->register('allactions',		$allactions);
		$t->register('pagenav',		$pagenav);
		$t->register('navbar', 				$navbar);
		$t->register('pagetitle', 			$pagetitle);
		$t->register('pagedescription', 	$pagedescription);
		$t->register('template_hook', 		$template_hook);
		$t->register('includecss', 			$includecss);
		$t->register('vb4', 			$vb4);
		$t->register('userinfo', $userinfo);
		print_output($t->render());
	}
	else
	{
		// Create navbits
		$navbits = construct_navbits($navbits);	
		eval('$navbar = "' . fetch_template('navbar') . '";');
		eval('print_output("' . fetch_template('credits_home') . '");');
	}
}
?>