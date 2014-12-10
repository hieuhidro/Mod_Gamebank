<?php

/*=======================================================================*\
|| ##################################################################### ||
|| # vBCredits II Deluxe 2.0.0 - `credits_core.php`					   # ||
|| # ------------------------------------------------------------------# ||
|| # Author: Darkwaltz4 {blackwaltz4@msn.com}						   # ||
|| # Copyright � 2009 - 2010 John Jakubowski. All Rights Reserved.	   # ||
|| # This file may not be redistributed in whole or significant part.  # ||
|| # -----------------vBulletin IS NOT FREE SOFTWARE!------------------# ||
|| #			 Support: http://www.dragonbyte-tech.com/			   # ||
|| ##################################################################### ||
\*=======================================================================*/

//install script to clean out dead files from old version
//delete user deletes stuff, others?

global $vb4;
$datastore_fetch = array_merge((array) $datastore_fetch, array("'vbcredits'", "'max_allowed_packet'"));
$vb4 = (substr($vbulletin->options['templateversion'], 0, 1) == '4'); //compatibility flag

class VBCREDITS
{
	var $queue = array();
	var $insert = array();
	var $douser = false;

	function &init()
	{	//static wrapper
		static $instance;
		if (!$instance) $instance = new VBCREDITS();
		return $instance;
	}

	function user(&$fields, &$joins, $whitelist = array('user', 'userfield', 'usertextfield'), $weight = false)
	{	//load the user's credits
		static $query;
		global $vbulletin;
		$cid = md5(serialize($whitelist) . intval($weight));

		if (empty($query[$cid]) AND is_array($vbulletin->vbcredits['currency']))
		{	//only calculate if not cached; oldlastvisit used for timer actions
			$query[$cid] = array('', '');

			foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
			{	//determine whether to join table
				$table = ( ($dojoin = in_array($currency['table'], $whitelist)) ? $currency['table'] : 'vbcreditst_' . $currencyid );
				$query[$cid][0] .= ( $weight ? ' +' : ',' ) . ' (' . $table . '.' . $currency['column'] . ( $weight ? ' * ' . $currency['value'] . ')' : ') AS vbcredits_' . $currencyid . ', ' . $table . '.' . $currency['column'] . ' AS vbcreditsb_' . $currencyid );
				if (!$dojoin) $query[$cid][1] .= ' LEFT JOIN ' . ( $currency['useprefix'] ? TABLE_PREFIX : '' ) . $currency['table'] . ' AS ' . $table . ' ON (user.' . ( $currency['userid'] ? 'userid' : 'username' ) . ' = ' . $table . '.' . $currency['usercol'] . ') ';
			}
		}

		$fields .= $query[$cid][0];
		$joins .= $query[$cid][1];
	}

	function verify(&$cronimage, $cbf = false)
	{
		global $vbulletin, $vb4;

		if (empty($cbf))
		{
			$copyright = '<div><a href="http://www.dragonbyte-tech.com/product.php?credits">vBCredits II Deluxe</a> v2.0.0 Copyright &copy; 2010 <a href="http://www.dragonbyte-tech.com/">DragonByte Technologies</a></div>';
			if ($vb4) $cronimage .= $copyright; else $vbulletin->templatecache['footer'] = str_replace('$cronimage', addslashes('$cronimage' . $copyright), $vbulletin->templatecache['footer']);
		}
		if ($vbulletin->userinfo['userid'])
		{	//only users have transactions
			$trans = $vbulletin->db->query_first("SELECT t.* FROM " . TABLE_PREFIX . "credits_transaction AS t LEFT JOIN " . TABLE_PREFIX . "credits_event AS e ON (t.eventid = e.eventid) WHERE t.userid = " . $vbulletin->userinfo['userid'] . " AND t.status = 0 AND t.timestamp <= (" . TIMENOW . " - IF(ISNULL(e.eventid), 0, e.delay)) ORDER BY t.transactionid ASC LIMIT 1");
			VBCREDITS::process($trans, $vbulletin->userinfo);
		}
	}

	function process($trans, &$userinfo)
	{
		global $vbulletin, $vb4;
		$obj =& VBCREDITS::init();
		$db =& $vbulletin->db;
		$status = 1;

		if ($userinfo['userid'] AND $trans)
		{
			if ($trans['eventid'])
			{
				$event =& $vbulletin->vbcredits['event'][$trans['actionid']][$trans['currencyid']][$trans['eventid']];
				$perms = ( is_array($userinfo['creditspermissions']) ? $userinfo['creditspermissions'] : ( $userinfo['creditspermissions'] ? unserialize($userinfo['creditspermissions']) : array(array(), array()) ) );
				$currency =& $vbulletin->vbcredits['currency'][$trans['currencyid']];
				$useramt =& $userinfo['vbcredits_' . $trans['currencyid']];
				$status = 4; //unqualified

				$stats = $db->query_first("
					SELECT 
						SUM(IF(e.currencyid = " . $trans['currencyid'] . " AND t.status IN (1, 2)" . ( is_null($currency['maxtime']) ? '' : " AND t.timestamp >= " . (TIMENOW - $currency['maxtime']) ) . ", amount, 0)) AS earned,
						SUM(t.eventid = " . $trans['eventid'] . " AND t.status IN (1, 2)" . ( is_null($event['maxtime']) ? '' : " AND t.timestamp >= " . (TIMENOW - $event['maxtime']) ) . ") AS times,
						SUM(t.eventid = " . $trans['eventid'] . " AND t.status = 3 AND t.timestamp >= (SELECT timestamp FROM " . TABLE_PREFIX . "credits_transaction WHERE eventid = " . $trans['eventid'] . " AND userid = " . $userinfo['userid'] . " AND status IN (1, 2) AND negate = 0 ORDER BY transactionid DESC LIMIT 1)) AS skipped
					FROM " . TABLE_PREFIX . "credits_transaction AS t
					LEFT JOIN " . TABLE_PREFIX . "credits_event AS e ON (t.eventid = e.eventid)
					WHERE t.negate = 0 AND t.userid = " . $userinfo['userid']
				);

				if ((is_null($currency['earnmax']) OR ($useramt + $trans['amount']) <= $currency['earnmax']) AND (is_null($event['applymax']) OR $stats['times'] < $event['applymax']) AND !in_array($trans['currencyid'], $perms[0]) AND !($userinfo['permissions']['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_locked']))
				{	//qualified
					$status = ( ($stats['skipped'] < ($event['frequency'] - 1)) ? 3 : ( $event['moderate'] ? 2 : 1 ) );

					if ($status == 1)
					{	//i should get it now
						if ($vbulletin->userinfo['userid'] == $userinfo['userid']) $obj->douser = true;
						$useramt += $trans['amount'];//other things call update
					}
				}
			}	//this one needs to be resent as the owner
			else VBCREDITS::action($trans['actionid'], $trans['userid'], $trans['referenceid'], $trans['negate'], array('userinfo' => $userinfo) + array_intersect_key($trans, array('message' => true, 'multiplier' => true, 'timestamp' => true, 'currencyid' => true, 'forumid' => true, 'ownerid' => true)));
			if (!$trans['transactionid']) return $status; else $db->query_write("UPDATE " . TABLE_PREFIX . "credits_transaction SET status = $status WHERE transactionid = " . $trans['transactionid']);
		}
	}

	function templates(&$cache)
	{
		global $vbulletin;
		$cache[] = 'credits_popup';
		$cache[] = 'credits_navtab';

		if (is_array($vbulletin->vbcredits['display']))
		{
			foreach ($vbulletin->vbcredits['display'] AS $display)
			{	//add any display templates
				if (empty($display['showpages']) OR in_array(THIS_SCRIPT, explode(',', $display['showpages'])))
				{
					if (!in_array($display['row_template'], $cache)) $cache[] = $display['row_template'];
					if ($display['main_template'] AND !in_array($display['main_template'], $cache)) $cache[] = $display['main_template'];
				}
			}
		}
	}

	function display($displayid, $user, &$hooks)
	{	//get the profile and process according to thingy
		global $vbulletin, $permissions, $vb4, $vbphrase;

		if (is_array($vbulletin->vbcredits['display']) AND array_key_exists($displayid, $vbulletin->vbcredits['display']))
		{
			$display =& $vbulletin->vbcredits['display'][$displayid];
			$showhide = ($user['permissions']['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_hidden']);
			$currencies = '';

			if ($user['userid'] AND (!($user['permissions']['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_locked']) OR $showhide))
			{	//member and (not locked or show hidden)
				if ($vb4)
				{	//vb4 template registration
					$t = vB_Template::create($display['row_template']);
					$t->register('display', $display);
					$t->register('user', $user);
					$t->register('vb4', $vb4);
				}

				$perms = ( is_array($user['creditspermissions']) ? $user['creditspermissions'] : ( $user['creditspermissions'] ? unserialize($user['creditspermissions']) : array(array(), array()) ) );
				$special = ($permissions['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_special']);
				$comboamt = 0;

				if (is_array($vbulletin->vbcredits['currency']))
				{
					foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
					{
						$useramt =& $user['vbcredits_' . $currencyid];
						if ($display['combine'] AND (empty($display['combined']) OR in_array($currencyid, $display['combined']))) $comboamt += $useramt * $currency['value'];

						if ((empty($display['currencies']) OR in_array($currencyid, $display['currencies'])) AND ($currency['privacy'] == 2 OR ($special OR ($user['userid'] == $vbulletin->userinfo['userid'] AND $currency['privacy'] == 1))) AND ($showhide OR (!in_array($currencyid, $perms[0]) OR !in_array($currencyid, $perms[1]))))
						{	//this currency is included and privacy settings fit and showhide or i can spend or earn this currency
							if ($useramt < 0 AND $currency['negative'] == 1) $useramt = 0;
							$useramt = fetch_word_wrapped_string(vb_number_format($useramt, $currency['decimals']));
							if ($useramt[0] == '-' AND !strlen(preg_replace('/[^1-9]/', '', $useramt))) $useramt = substr($useramt, 1);//-0

							if ($currency['decimals'])
							{	//might need to pad the decimals
								$useramt = explode($vbulletin->userinfo['lang_decimalsep'], $useramt);
								$useramt = $useramt[0] . $vbulletin->userinfo['lang_decimalsep'] . str_pad($useramt[1], $currency['decimals'], '0');
							}
							if ($vb4)
							{
								$t->register('currency', $currency);
								$t->register('useramt', $useramt);
								$currencies .= $t->render();
							}
							else eval('$currencies .= "' . fetch_template($display['row_template']) . '";');
						}
					}
				}
				if ($display['combine'])
				{	//add the combination row
					$currency['title'] = $display['combine']; //fake it
					$useramt = fetch_word_wrapped_string(vb_number_format($comboamt, $display['combodec']));

					if ($vb4)
					{
						$t->register('currency', $currency);
						$t->register('useramt', $useramt);
						$currencies .= $t->render();
					}
					else eval('$currencies .= "' . fetch_template($display['row_template']) . '";');
				}
			}
			if ($currencies)
			{	//if anything to show, then wrap and return
				if ($display['wrap_main'] AND $display['main_template'])
				{	//apply wrapper template
					if ($vb4)
					{
						$t = vB_Template::create($display['main_template']);
						$t->register('user', $user);
						$t->register('display', $display);
						$t->register('currencies', $currencies);
						$t->register('vb4', $vb4);
						$currencies = $t->render();
					}
					else eval('$currencies = "' . fetch_template($display['main_template']) . '";');
				}
				if ($display['hookname'] AND is_array($hooks))
				{	//if its a custom hook, replace the whole hook
					if ($display['customhook']) $hooks[$display['hookname']] = $currencies;
					else $hooks[$display['hookname']] .= $currencies;
				}
				else return $currencies; //attached to hook or just returned
			}
		}

		return '';
	}

	function regular()
	{
		require_once(DIR . '/includes/adminfunctions.php');
		require_once(DIR . '/includes/functions_misc.php');
		global $vb4, $vbulletin, $permissions, $footer, $vbphrase;

		if ($vbulletin->userinfo['userid'] AND !empty($vbulletin->vbcredits['currency']))
		{
			$options = $doacts = $tocurs = $fromcurs = $valopts = $realcurs = $avapros = array();
			$intref = explode('-', $vbulletin->options['credits_action_interest_start']);
			$refdate = vbmktime(0, 0, 0, intval($intref[1]), intval($intref[2]), intval($intref[0]));
			$interval = ($vbulletin->options['credits_action_interest_interval'] * 86400);
			$part = (abs($vbulletin->userinfo['lastactivity'] - $refdate) % $interval);
//hook?
			foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
			{
				$actions = array();

				for ($stamp = $vbulletin->userinfo['lastactivity'] + $interval - ( ($vbulletin->userinfo['lastactivity'] < $refdate) ? $interval - $part : $part ); $stamp <= TIMENOW; $stamp += $interval)
				{	//give all the missed interest
					VBCREDITS::action('interest', $vbulletin->userinfo['userid'], null, false, array('timestamp' => $stamp, 'currencyid' => $currencyid, 'multiplier' => $vbulletin->userinfo['vbcredits_' . $currencyid]));
				}

				if ($currency['inbound'] AND (sizeof($vbulletin->vbcredits['event']['donate'][$currencyid]) OR sizeof($vbulletin->vbcredits['event']['transfer'][$currencyid])))
				{
					$curs = array();

					foreach ($vbulletin->vbcredits['currency'] AS $curid => $cury)
					{
						if (($curid == $currencyid OR $cury['outbound']) AND (sizeof($vbulletin->vbcredits['event']['donate'][$curid]) OR sizeof($vbulletin->vbcredits['event']['transfer'][$curid])))
						{
							$fromcurs[$curid] = $cury['title'];
							$curs[] = $curid;
						}
					}
					if (sizeof($curs))
					{
						$doacts['donate'] = $vbulletin->userinfo['username'];
						$actions['donate'] = '"donate":[' . implode(',', $curs) . ']';
					}
				}

				if (sizeof($vbulletin->vbcredits['event']['adjust'][$currencyid]) AND ($permissions['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_adjust']))
				{
					$doacts['adjust'] = $vbulletin->vbcredits['action']['adjust']['title'];
					$actions['adjust'] = '"adjust":[]';
				}

				if (sizeof($vbulletin->vbcredits['processor']) AND sizeof($vbulletin->vbcredits['event']['purchase'][$currencyid]) AND sizeof($converses = $vbulletin->vbcredits['conversion'][$currencyid]))
				{
					$curs = array();

					foreach ($converses AS $converse)
					{
						foreach (array('usd', 'gbp', 'eur', 'aud', 'cad') AS $cur)
						{
							$pros = array();
							if (!$converse['cost'][$cur]) continue;

							foreach ($vbulletin->vbcredits['processor'] AS $processid => $processor)
							{	//arg my brain
								if ($cur == 'usd' AND $processid == 'ccbill') $good = $converse['cost']['ccbillsubid'];
								else if ($cur == 'usd' AND $processid == '2checkout') $good = $converse['cost']['twocheckout_prodid'];
								else $good = in_array($cur, $processor['currency']);

								if ($good)
								{
									$avapros[$processid] = $processor['title'];
									$pros[] = $processid;
								}
							}
							if ($pros)
							{
								$realcurs[$cur] = strtoupper($cur);
								$curs[$cur] = '"' . $cur . '":["' . implode('","', $pros) . '"]';
							}
						}
					}
					if ($curs)
					{
						$doacts['purchase'] = $vbulletin->vbcredits['action']['purchase']['title'];
						$actions['purchase'] = '"purchase":{' . implode(',', $curs) . '}';
					}
				}

				if (sizeof($vbulletin->vbcredits['event']['redeem'][$currencyid]) AND sizeof($redeems = $vbulletin->vbcredits['redemption'][$currencyid]))
				{	//any redemptions for this currency
					foreach ($redeems AS $redeem)
					{
						if (empty($redeem['usergroups']) OR is_member_of($vbulletin->userinfo, $redeem['usergroups']))
						{	//anything i could possible use
							$doacts['redeem'] = $vbulletin->vbcredits['action']['redeem']['title'];
							$actions['redeem'] = '"redeem":[]';
							break;
						}
					}
				}
//hook?
				if (sizeof($actions))
				{
					$tocurs[$currencyid] = $currency['title'];
					$options[$currencyid] = '"' . $currencyid . '":{' . implode(',', $actions) . '}';
				}
			}
			if (sizeof($options))
			{
				if ($vbulletin->options['credits_action_transfer_increments'])
				{	//dont make blank options
					foreach (explode(',', $vbulletin->options['credits_action_transfer_increments']) AS $val)
					{	//create nice array of numeric values that keep decimals
						$valopts[$val = doubleval($val)] = vb_number_format($val, ( (strpos($val, '.') !== false) ? strlen(array_pop(explode('.', $val))) : 0 ));
					}
				}

				$options = '{' . implode(',', $options) . '}';
				$doacts = construct_select_options($doacts);
				$tocurs = construct_select_options($tocurs);
				$fromcurs = construct_select_options($fromcurs);
				$realcurs = construct_select_options($realcurs);
				$avapros = construct_select_options($avapros);
				$valopts = construct_select_options($valopts);
//hook? template hook?
				if ($vb4)
				{
					$t = vB_Template::create('credits_popup');
					$t->register('options', $options);
					$t->register('doacts', $doacts);
					$t->register('tocurs', $tocurs);
					$t->register('fromcurs', $fromcurs);
					$t->register('valopts', $valopts);
					$t->register('realcurs', $realcurs);
					$t->register('avapros', $avapros);
					$t->register('vb4', $vb4);
					$footer .= $t->render();
				}
				else eval('$footer .= "' . fetch_template('credits_popup') . '";');
			}
		}
	}

	function shutdown()
	{
		global $vbulletin;
		$obj =& VBCREDITS::init();
		if (sizeof($obj->insert)) VBCREDITS::commit();
		if ($obj->douser) VBCREDITS::update($vbulletin->userinfo);
		$obj->douser = false;
	}

	function update(&$userinfo)
	{	//string together all the currencies to update unbuffered
		if ($userinfo['userid'])
		{
			global $vbulletin;
			$db =& $vbulletin->db;
			$query = array(array(), array(), array());

			foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
			{
				$table = 'vbcreditst_' . $currencyid;
				$query[0][] = ( $currency['useprefix'] ? TABLE_PREFIX : '' ) . $currency['table'] . ' AS ' . $table;
				$query[1][] = $table . '.' . $currency['column'] . ' = ' . $table . '.' . $currency['column'] . ' + ' . ($userinfo['vbcredits_' . $currencyid] - $userinfo['vbcreditsb_' . $currencyid]);
				$query[2][] = $table . '.' . $currency['usercol'] . ' = ' . ( $currency['userid'] ? $userinfo['userid'] : "'" . $db->escape_string(htmlspecialchars_uni($userinfo['username'])) . "'" );
				$userinfo['vbcreditsb_' . $currencyid] = $userinfo['vbcredits_' . $currencyid];//reset backup
			}

			$db->query_write("UPDATE " . implode(', ', $query[0]) . " SET " . implode(', ', $query[1]) . " WHERE " . implode(' AND ', $query[2]), false);
		}
	}

	function action($actionid, $userid, $refid = null, $negate = false, $extra = array())
	{	//if you have a refid send it, if you will have one send true, if you wont have one leave it as null
		global $vbulletin, $vbphrase, $multiplier;
		static $sizetext = array();
		$obj =& VBCREDITS::init();
		$queue = array();

		if ($vbulletin->options['credits_enabled'] AND $userid AND is_array($vbulletin->vbcredits['action']) AND array_key_exists($actionid, $vbulletin->vbcredits['action']) AND is_array($vbulletin->vbcredits['event']) AND array_key_exists($actionid, $vbulletin->vbcredits['event']))
		{	//events are enabled and exist plus skip unused actions
			if (!$sizetext)
			{	//size mult labels
				global $vbphrase;
				$sizetext = 'credits_size_' . ( $vbulletin->options['credits_size_words'] ? 'word' : 'char' );
				$sizetext = array($vbphrase[$sizetext . 's'], $vbphrase[$sizetext]);//size mult labels
			}

			$action =& $vbulletin->vbcredits['action'][$actionid];
			$usesize = ($action['multiplier'] == 'Size');
			$multlabel = ( $usesize ? $sizetext : explode('|', $action['multiplier'], 2) );
			$message = $vbulletin->db->escape_string( array_key_exists('message', $extra) ? $extra['message'] : '' );
			$timestamp = ( empty($extra['timestamp']) ? TIMENOW : intval($extra['timestamp']) );
			$multiplier = ( $usesize ? vbcredits_size($extra['multiplier']) : ( is_numeric($extra['multiplier']) ? $extra['multiplier'] : 0 ) );

			if ($notmyself = ($vbulletin->userinfo['userid'] != $userid) AND is_array($extra['userinfo'])) $userinfo = $extra['userinfo'];
			else $userinfo =& $vbulletin->userinfo;

			$currencyid = intval($extra['currencyid']);
			$ownerid = intval($extra['ownerid']);
			$forumid = intval($extra['forumid']);
			$sfx = ( $negate ? '_sub' : '_add' );
			unset($extra);//save memory

			if ($userid == $userinfo['userid'])
			{	//narrow down to specific currency if selected
				$currencies = ( ($currencyid AND is_array($vbulletin->vbcredits['currency']) AND array_key_exists($currencyid, $vbulletin->vbcredits['currency'])) ? array($currencyid => $vbulletin->vbcredits['currency'][$currencyid]) : $vbulletin->vbcredits['currency'] );
				$perms = ( is_array($userinfo['creditspermissions']) ? $userinfo['creditspermissions'] : ( $userinfo['creditspermissions'] ? unserialize($userinfo['creditspermissions']) : array(array(), array()) ) );

				if (is_array($currencies))
				{
					foreach ($currencies AS $currencyid => $currency)
					{	//returns the best eventid for the criteria
						$events = array();

						foreach ((array) $vbulletin->vbcredits['event'][$actionid][$currencyid] AS $eventid => $event)
						{
							if ((empty($event['forums']) OR in_array($forumid, $event['forums'])) AND (empty($event['usergroups']) OR is_member_of($userinfo, $event['usergroups'])) AND (!$action['parent'] OR is_null($event['owner']) OR $event['owner'] == intval($userid == $ownerid)))
							{	//event applies to my group and forum
								$events[$eventid] = $event['main' . $sfx];
							}
						}
						if ($vbulletin->options['credits_best_event'] AND sizeof($events))
						{	//pick best one
							arsort($events);
							$events = array(key($events) => array_shift($events));
						}
						foreach ($events AS $eventid => $amount)
						{	//picked best one
							$event =& $vbulletin->vbcredits['event'][$actionid][$currencyid][$eventid];
							$now = ($timestamp == TIMENOW);
							$status = 0; //pending

							if (!$negate AND !empty($event['upperrand']))
							{	//vary the amount by the upper bound
								$dec = explode('.', $event['upperrand']);
								$dec = pow(10, strlen($dec[1]));
								$amount += vbrand(0, $dec * $event['upperrand']) / $dec;
							}
							if ($action['multiplier'] OR $action['currency'])
							{	//this action has multipliers
								$bounds = array((is_null($event['multmin']) OR abs($multiplier) >= $event['multmin']), (is_null($event['multmax']) OR abs($multiplier) <= $event['multmax']));

								if ($bounds[0])
								{	//within the bounds - check if multiplier was negative
									$sender = (($multiplier < 0) XOR $negate);
									$multiplier = ( $bounds[1] ? $multiplier : ( $sender ? -1 : 1 ) * $event['multmax'] );

									if ($action['currency'])
									{	//only apply adjustments if applicable
										$doadjust = (($event['curtarget'] XOR $sender) OR $event['curtarget'] == 2);
										$amount = ( $doadjust ? $amount : 0 ) + $multiplier * (1 + $doadjust * ( $sender ? -1 : 1 ) * $event['mult' . $sfx]);
									}	//otherwise now
									else $amount += $multiplier * $event['mult' . $sfx];
								}
								else if ($event['minaction'] == 1)
								{	//skip the event
									continue;
								}
								else if (!$negate AND $event['minaction'] == 2)
								{	//stop the action
									if ($now AND $action['cancel'])
									{	//action is cancelable, show error
										eval(standard_error(fetch_error("credits_cancel_mult_$actionid", vb_number_format($event['multmin'], ( (strpos($event['multmin'], '.') !== false) ? strlen(array_pop(explode('.', $event['multmin']))) : 0 )), ( $action['currency'] ? $currency['title'] : $multlabel[intval($event['multmin'] == 1)] ))));
									}
									else return false;
								}
							}
							if ($amount < 0)
							{	//paying credits, apply now
								$status = 1;
								$posamount = -1 * $amount;

								if ($now AND !$negate AND $action['cancel'] AND ($posamount > $userinfo['vbcredits_' . $currencyid] OR in_array($currencyid, $perms[1]) OR ($userinfo['permissions']['creditspermissions'] & $vbulletin->bf_ugp_creditspermissions['credits_locked'])))
								{	//not enough credits or cant spend this and action is cancelable - show error
									eval(standard_error(fetch_error("credits_cancel_price_$actionid", fetch_word_wrapped_string(vb_number_format($posamount, $currency['decimals'])), $currency['title'])));
								}
								else $userinfo['vbcredits_' . $currencyid] -= $posamount;
							}
							if ($amount != 0)
							{	//store events that affect credits
								$queue[] = "($eventid, '$actionid', $userid, $timestamp, $amount, $status, REFERENCEID, $forumid, $ownerid, $multiplier, $currencyid, " . intval($negate) . ", '$message')";
							}
						}
					}
				}
			}
			else
			{	//unknown - user will have to load it later
				$queue[] = "(0, '$actionid', $userid, $timestamp, 0, 0, REFERENCEID, $forumid, $ownerid, $multiplier, $currencyid, " . intval($negate) . ", '$message')";
			}
			if (sizeof($queue))
			{	//dont bother saving guest stuff
				$queueid = sizeof($obj->queue);
				$obj->queue[] = $queue;
	
				foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
				{	//currency doesnt like negative
					$useramt =& $userinfo['vbcredits_' . $currencyid];	
					if ($useramt < 0 AND $currency['negative'] == 0) $useramt = 0;
				}

				if ($notmyself) VBCREDITS::update($userinfo);
				else $obj->douser = true;

				if ($refid !== true)
				{	//if we have a refid use it
					VBCREDITS::apply($queueid, $refid);
					return;
				}	//or queue for later
				else return $queueid; 
			}
		}
		
		return false;
	}

	function apply($queueid, $refid)
	{	//we got back the refid, queue the inserts
		if (is_numeric($queueid))
		{	//only if queueid is valid
			static $sqlbase;
			global $vbulletin;
			$obj =& VBCREDITS::init();
			$inssize = sizeof($obj->insert);
			if (empty($sqlbase)) $sqlbase = 166 + strlen(TABLE_PREFIX);
			$sqlsize = $sqlbase + array_sum(array_map('strlen', $obj->insert)) + ( $inssize ? 2 * ($inssize - 1) : 0 );
			if (is_null($refid)) $refid = 'NULL'; else if (!is_numeric($refid)) $refid = '\'' . $vbulletin->db->escape_string($refid) . '\'';

			foreach ($obj->queue[$queueid] AS $count => $set)
			{	//clear out of queue, but leave the empty array
				$set = str_replace('REFERENCEID', $refid, $set);
				$setsize = strlen($set) + ( sizeof($obj->insert) ? 2 : 0 );

				if ($vbulletin->max_allowed_packet AND ($sqlsize + $setsize) > $vbulletin->max_allowed_packet)
				{	//insert is too much, send it now and reset
					VBCREDITS::commit();
					$sqlsize = $sqlbase;
				}

				$sqlsize += $setsize;
				$obj->insert[] = $set;
				unset($obj->queue[$queueid][$count]);
			}
		}
	}

	function commit()
	{	//apply unbuffered queries and reset array
		global $vbulletin, $usercache, $vbphrase;
		$obj =& VBCREDITS::init();

		if (sizeof($obj->insert))
		{
			static $sender = null, $fixself = false, $keys = array('eventid', 'actionid', 'userid', 'timestamp', 'amount', 'status', 'referenceid', 'forumid', 'ownerid', 'multiplier', 'currencyid', 'negate', 'message');

			foreach ($obj->insert AS $i => $insert)
			{
				for ($pos = $x = 0; $x < 6 AND $pos = strpos($insert, ',', $pos + 1); $x++);//status
				$trans = array_combine($keys, explode(',', preg_replace('/[^\w\-\.,]/', '', $insert), 13));

				if ($trans['eventid'])
				{	//only process events we know about
					$action =& $vbulletin->vbcredits['action'][$trans['actionid']];
					$currency =& $vbulletin->vbcredits['currency'][$trans['currencyid']];
					$event =& $vbulletin->vbcredits['event'][$trans['actionid']][$trans['currencyid']][$trans['eventid']];

					if ($vbulletin->options['credits_dangerous'] OR $event['alert'])
					{
						if ($notmyself = ($vbulletin->userinfo['userid'] != $trans['userid']))
						{	//load fresh cache each time
							unset($usercache[$trans['userid']], $userinfo);
							$userinfo = fetch_userinfo($trans['userid']);
							cache_permissions($userinfo, false);
						}	//myself should be up to date
						else
						{
							$userinfo =& $vbulletin->userinfo;

							if ($trans['amount'] < 0 AND ($userinfo['vbcredits_' . $trans['currencyid']] == $userinfo['vbcreditsb_' . $trans['currencyid']] OR $fixself))
							{	//fix lost references somewhere
								$useramt =& $userinfo['vbcredits_' . $trans['currencyid']];
								$useramt += $trans['amount'];
								if ($useramt < 0 AND $currency['negative'] == 0) $useramt = 0;
								$fixself = true;
							}
						}
						if ($event['alert'] AND $vbulletin->options['credits_alert_sender'])
						{
							if (is_null($sender))
							{	//needed for pms - static
								$sender = fetch_userinfo($vbulletin->options['credits_alert_sender']);
								cache_permissions($sender, false);
							}

							$deduct = ($trans['amount'] < 0);
							$tranamount = construct_phrase($vbphrase['credits_transaction_' . ( $deduct ? 'spent' : 'earned' )], vb_number_format(( $deduct ? -1 : 1 ) * $trans['amount'], $currency['decimals']), $currency['title']);
							$alertmessage = construct_phrase($vbphrase['credits_alert_message'], $userinfo['username'], $tranamount, $action['title'], ( $trans['message'] ? "\n\n" . '"' . stripslashes(substr(array_pop(explode(', ', $insert, 13)), 1, -2)) . '"' : '' ), $vbulletin->options['bburl'], $userinfo['userid']);
							$alerttitle = construct_phrase($vbphrase['credits_alert_subject'], $tranamount, $action['title']);

							if ($vbulletin->options['credits_alert_method'] == 'mail') vbmail($userinfo['email'], $alerttitle, $alertmessage, true, $sender['email'], '', $sender['username']); else
							{	//send a pm alert
								require_once(DIR . '/includes/functions_newpost.php');
								$pmdm =& datamanager_init('PM', $vbulletin, ERRTYPE_SILENT);
								$pmdm->overridequota = true;
								$pmdm->set('fromuserid', $sender['userid']);
								$pmdm->set('fromusername', $sender['username']);
								$pmdm->set_info('receipt', false);
								$pmdm->set_info('savecopy', false);
								$pmdm->set('title', $alerttitle);
								$pmdm->set('message', convert_url_to_bbcode($alertmessage));
								$pmdm->set_recipients($userinfo['username'] . ';', $sender['permissions']);
								$pmdm->set('dateline', TIMENOW);
								$pmdm->set('allowsmilie', true);
								$pmdm->save();
								unset($pmdm);
							}
						}
						if ($vbulletin->options['credits_dangerous'] AND !$event['delay'] AND !$trans['status'] AND $status = VBCREDITS::process($trans, $userinfo))
						{	//realtime processing!
							$obj->insert[$i][$pos - 1] = $status;//process sets douser
							if ($status == 1 AND $notmyself) VBCREDITS::update($userinfo);
						}
					}
				}
			}

			$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "credits_transaction (" . implode(', ', $keys) . ") VALUES " . implode(', ', $obj->insert), false);
			$obj->insert = array();
		}
	}

	function charged_content($post, &$return_value)
	{	//cache charged content
		global $vbulletin, $userinfo;
		require_once(DIR . '/includes/class_bbcode.php');
		if (THIS_SCRIPT == 'editpost') $post->set_info('user', ( $userinfo ? $userinfo : $vbulletin->userinfo ));
		$bbcode_parser = new vB_BbCodeParser($post->registry, fetch_tag_list());
		$bbcode_parser->parse($post->post['pagetext']);

		if ($charge = sizeof($bbcode_parser->chargelist))
		{	//only bother checking if any charges first
			if ($return_value = ($charge <= $post->info['user']['permissions']['credits_charge'])) $post->set('chargecontent', serialize($bbcode_parser->chargelist)); else $post->error('credits_charge_toomuch');
		}
	}

	function cache_charge()
	{	//build charge info for later
		global $post, $vbulletin, $chargeown, $chargelook;

		if ($post['chargecontent'])
		{	//charge content saved
			$charge = array_keys(unserialize($post['chargecontent']));
			if ($vbulletin->userinfo['userid'] == $post['userid']) $chargeown = ( $chargeown ? array_merge($chargeown, $charge) : $charge );
			else $chargelook = ( $chargelook ? array_merge($chargelook, $charge) : $charge );
		}
	}

	function charge_output()
	{	//build charge content for javascript
		global $vbulletin, $db, $vbphrase, $template_hook, $chargelook, $chargeown;

		if ($chargelook)
		{	//look up the purchased content
			$charge = $db->query_read("SELECT referenceid FROM " . TABLE_PREFIX . "credits_transaction WHERE actionid = 'content' AND userid = " . $vbulletin->userinfo['userid'] . " AND status = 1 AND referenceid IN ('" . implode("', '", $chargelook) . "')");
			while ($trans = $db->fetch_array($charge)) $chargeown[] = $trans['referenceid'];
		}
		
		if ($chargeown) $template_hook['showthread_below_posts'] .= '<script type="text/javascript">var chargeowns = [\'' . implode("', '", $chargeown) . '\'], chargeown = \'' . $vbphrase['credits_charge_owncontent'] . '\';</script>';
	}

	function charge_strip(&$local)
	{	//remove charge tags from previews
		if (!$ispost = empty($local['preview']) OR !empty($local['posttext']))
		{	//dont bother unless its there
			if (!$ispost)
			{	//stupid php53 broke func_get_arg
				$history = debug_backtrace();
				$pagetext = $history[2]['args'][0]['preview'];
				unset($history);//heavy
			}	//get search retry text
			else $pagetext = $local['posttext'];
			if (strpos(strtolower($pagetext), 'charge') !== false)
			{	//dont waste time replacing what isnt there
				global $vbulletin;
				$pagetext = VBCREDITS::charge_strip_raw(strip_quotes($pagetext));
				$pagetext = htmlspecialchars_uni(fetch_censored_text(trim(fetch_trimmed_title(strip_bbcode($pagetext, false, true, true, true), ( $ispost ? 200 : $vbulletin->options['threadpreview'] )))));
				$local[( $ispost ? 'pagetext' : 'preview' )] = ( $ispost ? nl2br($pagetext) : $pagetext );
			}
		}
	}

	function charge_strip_raw($text)
	{	//helper function used everywhere
		global $vbulletin, $vbphrase;
		return ( ($charge = $vbulletin->options['credits_action_content_bbcode']) ? preg_replace('/\[' . $charge . '.*\[\/' . $charge . ']/i', $vbphrase['credits_charge_stripped'], $text) : $text );
	}
}

function credits_charge(&$parser, $value, $option)
{	//handle charge bbcode
	global $vbulletin, $vbphrase;
	$parser->chargelist[$md5 = md5($value)] = array($value, $optamt = doubleval($option));
	$currency =& $vbulletin->vbcredits['currency'][$vbulletin->options['credits_action_content_currency']];
	return '<input type="button" class="button credits_charge" rel="' . $md5 . '" value="' . construct_phrase($vbphrase['credits_charge_viewcontent'], vb_number_format($optamt, $currency['decimals']), $currency['title']) . '" />';
}

function vbcredits_size($text)//, $keepsmile = false
{	//fetches the size of the text according to settings
	global $vbulletin;
	$db =& $vbulletin->db;

	if ($vbulletin->options['credits_exclude_blocks'])
	{
		foreach (array('quote', 'php', 'html', 'code') AS $tag)
		{
			$start_pos = $end_pos = array();
			$lowertext = strtolower($text);
			$taglen = strlen($tag);
			$curpos = 0;

			do
			{
				$pos = strpos($lowertext, '[' . $tag, $curpos);

				if ($pos !== false AND ($lowertext[$pos + $taglen + 1] == '=' OR $lowertext[$pos + $taglen + 1] == ']'))
				{
					$start_pos["$pos"] = 'start';
				}

				$curpos = $pos + $taglen + 1;
			}
			while ($pos !== false);

			if (sizeof($start_pos) == 0) continue;
			$curpos = 0;

			do
			{
				$pos = strpos($lowertext, '[/' . $tag . ']', $curpos);

				if ($pos !== false)
				{
					$end_pos["$pos"] = 'end';
					$curpos = $pos + $taglen + 3;
				}
			}
			while ($pos !== false);

			if (sizeof($end_pos) == 0) continue;
			$pos_list = $start_pos + $end_pos;
			ksort($pos_list);

			do
			{
				$stack = array();
				$newtext = '';
				$substr_pos = 0;

				foreach ($pos_list AS $pos => $type)
				{
					$stacksize = sizeof($stack);

					if ($type == 'start')
					{
						if ($stacksize == 0) $newtext .= substr($text, $substr_pos, $pos - $substr_pos);
						array_push($stack, $pos);
					}
					else
					{
						// pop off the latest opened tag
						if ($stacksize)
						{
							array_pop($stack);
							$substr_pos = $pos + $taglen + 3;
						}
					}
				}

				$newtext .= substr($text, $substr_pos);

				if ($stack)
				{
					foreach ($stack AS $pos) unset($pos_list["$pos"]);
				}
			}
			while ($stack);

			$text = $newtext;
		}
	}
	if ($vbulletin->smiliecache === null)
	{
		DEVDEBUG('querying for smilies');
		$vbulletin->smiliecache = array();

		$smilies = $db->query_read("
			SELECT *, LENGTH(smilietext) AS smilielen
			FROM " . TABLE_PREFIX . "smilie
			ORDER BY smilielen DESC
		");

		while ($smilie = $db->fetch_array($smilies)) $vbulletin->smiliecache["$smilie[smilieid]"] = $smilie;
	}

	foreach ($vbulletin->smiliecache AS $smilie) $text = str_replace(trim($smilie['smilietext']), '', $text);//!$keepsmile
	$text = strip_bbcode(preg_replace(array('#\[(email|url)=("??)(.+)\\2\]\\3\[/\\1\]#siU', '#\[(thread|post)=("??)(.+)\\2\]\\3\[/\\1\]#siU'), '', $text), false, false, false);
	return ( $vbulletin->options['credits_size_words'] ? count(preg_split('/\s+/', $text)) : strlen(preg_replace('/\s/', '', $text)) );
}

if (defined('VB_AREA') AND VB_AREA == 'AdminCP')
{
	function vbcredits_cache()
	{
		$cache = array();
		global $vbulletin;
		$db =& $vbulletin->db;
		$vars = $db->query_write("SHOW VARIABLES LIKE 'max_allowed_packet'");
		$var = $db->fetch_row($vars);
		build_datastore('max_allowed_packet', $var[1], false);
		$currencies = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_currency ORDER BY displayorder ASC");
		$db->free_result($vars);

		while ($currency = $db->fetch_array($currencies))
		{
			$cache['currency'][$currency['currencyid']] = $currency;
		}

		$db->free_result($currencies);
		$displays = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_display WHERE enabled = 1");

		while ($display = $db->fetch_array($displays))
		{
			$display['currencies'] = ( ($display['currencies'] AND $display['currencies'] != 'a:1:{i:0;i:0;}') ? unserialize($display['currencies']) : array() );
			$display['combined'] = ( ($display['combined'] AND $display['combined'] != 'a:1:{i:0;i:0;}') ? unserialize($display['combined']) : array() );
			$cache['display'][$display['displayid']] = $display;
		}

		$db->free_result($displays);
		$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_action ORDER BY category ASC, title ASC");

		while ($action = $db->fetch_array($actions))
		{
			$cache['action'][$action['actionid']] = $action;
		}

		$db->free_result($actions);
		$events = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_event WHERE enabled = 1");

		while ($event = $db->fetch_array($events))
		{
			$event['usergroups'] = ( ($event['usergroups'] AND $event['usergroups'] != 'a:1:{i:0;i:0;}') ? unserialize($event['usergroups']) : array() );
			$event['forums'] = ( ($event['forums'] AND $event['forums'] != 'a:1:{i:0;i:0;}') ? unserialize($event['forums']) : array() );
			$cache['event'][$event['actionid']][$event['currencyid']][$event['eventid']] = $event;
		}

		$db->free_result($events);
		$redemptions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_redemption WHERE enabled = 1");

		while ($redeem = $db->fetch_array($redemptions))
		{
			$redeem['usergroups'] = ( ($redeem['usergroups'] AND $redeem['usergroups'] != 'a:1:{i:0;i:0;}') ? unserialize($redeem['usergroups']) : array() );
			$redeem['codes'] = unserialize($redeem['codes']);
			$cache['redemption'][$redeem['currencyid']][$redeem['redemptionid']] = $redeem;
		}

		$db->free_result($redemptions);
		$processors = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "paymentapi WHERE active = 1");

		while ($process = $db->fetch_array($processors))
		{
			$process['settings'] = unserialize($process['settings']);
			$process['currency'] = explode(',', $process['currency']);
			$cache['processor'][$process['classname']] = $process;
		}

		$db->free_result($processors);
		$conversions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_conversion WHERE enabled = 1 ORDER BY minimum ASC");

		while ($converse = $db->fetch_array($conversions))
		{
			$converse['cost'] = unserialize($converse['cost']);
			$cache['conversion'][$converse['currencyid']][$converse['conversionid']] = $converse;
		}

		$db->free_result($conversions);
		build_datastore('vbcredits', serialize($cache), true);
	}
	function vbcredits_import($filepath, $currencyid, $verify = true)
	{
		global $vbulletin, $db;
		require_once(DIR . '/includes/class_xml.php');

		$count = 0;
		$xmlobj = new vB_XML_Parser(file_read($filepath));
		if ($xmlobj->error_no == 1) print_stop_message('no_xml_and_no_path');
		if (!$imp = $xmlobj->parse()) print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
		if (!is_array($imp['events']['event'])) print_stop_message('no_events_uploaded');

		foreach ($imp['events']['event'] AS $event)
		{	//read each row
			if ($verify AND !array_key_exists($event['actionid'], $vbulletin->vbcredits['action'])) continue;
			$event['currencyid'] = intval($currencyid);
			unset($event['value']);
			$nulls = array();
			$count++;
	
			$event['upperrand'] = preg_replace('/[^\d\.]/i', '', $event['upperrand']);
			if (substr($event['upperrand'], -1) == '.') $event['upperrand'] = substr($event['upperrand'], 0, -1);
			if ($event['upperrand'] == '') $event['upperrand'] = 0;
	
			foreach (array('enabled', 'moderate', 'alert') AS $field)
			{	//set bool vals
				$event[$field] = ( $event[$field] ? 1 : 0 );
			}
			foreach (array('main_add' => true, 'main_sub' => true, 'mult_add' => true, 'mult_sub' => true, 'curtarget' => false, 'delay' => false, 'frequency' => false, 'minaction' => false) AS $field => $float)
			{	//set numeric vals
				$which = ( $float ? 'doubleval' : 'intval' );
				$event[$field] = $which($event[$field]);
			}
			foreach (array('usergroups', 'forums') AS $field)
			{	//serialize the arrays
				$event[$field] = serialize(array_map('intval', explode(',', $event[$field])));
			}
			foreach (array('owner', 'maxtime', 'applymax', 'multmin', 'multmax') AS $field)
			{	//if theyre blank they should be null
				if (!is_numeric($event[$field])) $nulls[] = $field . ' = null';
			}
	
			$maxval = ( $action['cancel'] ? 2 : 1 );
			if ($event['frequency'] < 1) $event['frequency'] = 1;
			if ($event['minaction'] > $maxval) $event['minaction'] = $maxval;
			$db->query_write(fetch_query_sql($event, 'credits_event'));
			if (sizeof($nulls) AND $event['eventid'] = $db->insert_id()) $db->query_write("UPDATE " . TABLE_PREFIX . "credits_event SET " . implode(', ', $nulls) . " WHERE eventid = " . $event['eventid']);
		}

		return $count;
	}
}
if (!function_exists('array_intersect_key'))
{	//filter an array for a set of keys you want
	function array_intersect_key($master, $extract)
	{	//only for two arrays!
		foreach (array_keys($master) AS $key)
		{
			if (!array_key_exists($key, $extract)) unset($master[$key]);
		}

		return $master;
	}
}
if (!function_exists('array_combine'))
{	//merge an array of keys and vals
	function array_combine($keys, $vals)
	{
		$combined = array();
		$vals = array_values($vals);
		foreach (array_values($keys) AS $i => $key) $combined[$key] = $vals[$i];
		return $combined;
	}
}
?>