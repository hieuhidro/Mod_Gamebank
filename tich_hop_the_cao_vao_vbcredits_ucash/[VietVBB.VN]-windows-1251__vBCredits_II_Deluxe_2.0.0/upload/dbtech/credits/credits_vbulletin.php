<?php

/*=======================================================================*\
|| ##################################################################### ||
|| # vBCredits II Deluxe: vBulletin 1.3 - `credits_vbulletin.php`	   # ||
|| # ------------------------------------------------------------------# ||
|| # Author: Darkwaltz4 {blackwaltz4@msn.com}						   # ||
|| # Copyright � 2009 - 2010 John Jakubowski. All Rights Reserved.	   # ||
|| # This file may not be redistributed in whole or significant part.  # ||
|| # -----------------vBulletin IS NOT FREE SOFTWARE!------------------# ||
|| #			 Support: http://www.dragonbyte-tech.com/			   # ||
|| ##################################################################### ||
\*=======================================================================*/

require_once(DIR . '/includes/functions_misc.php');

class VBCREDITS_VBULLETIN
{
	function memberlist(&$fields, &$hooks)
	{
		global $vbulletin, $totalcols, $sqlsort, $sortfield, $sortarrow, $sorturl, $oppositesort, $perpage, $usergrouplink, $vb4, $show, $bgclass;

		if (is_array($vbulletin->vbcredits['display']) AND array_key_exists('memberlist', $vbulletin->vbcredits['display']))
		{
			$display =& $vbulletin->vbcredits['display']['memberlist'];
			$sfield = $vbulletin->input->clean_gpc('r', 'sortfield', TYPE_STR);
			$currencies = '';
			$combo = array();

			if ($vb4)
			{	//vb4 template registration
				$t = vB_Template::create('credits_display_memberlist');
				$t->register('vb4', $vb4);
				$t->register('sorturl', $sorturl);
				$t->register('oppositesort', $oppositesort);
				$t->register('perpage', $perpage);
				$t->register('usergrouplink', $usergrouplink);
			}
			if (is_array($vbulletin->vbcredits['currency']))
			{
				if (substr($sfield, 0, 9) == 'vbcredits' AND array_key_exists(substr($sfield, 10), $vbulletin->vbcredits['currency']))
				{	//handle sorting if calling existing currency
					$sortarrow[$sfield] = $sortarrow["$sortfield"];
					unset($sortarrow["$sortfield"]);
					$sortfield = $sqlsort = $sfield;
				}
				foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
				{
					$fieldname = 'vbcredits_' . $currencyid;
					if ($display['combine'] AND (empty($display['combined']) OR in_array($currencyid, $display['combined']))) $combo[] = ( in_array($currency['table'], array('user', 'userfield', 'usertextfield')) ? $currency['table'] : 'vbcreditst_' . $currencyid ) . '.' . $currency['column'];

					if ((empty($display['currencies']) OR in_array($currencyid, $display['currencies'])) AND ($currency['privacy'] == 2 OR ($currency['privacy'] == 1 AND ($user['userid'] == $vbulletin->userinfo['userid']))))
					{	//is it public or (private + (me or perms))
						$arrowsort = $sortarrow[$fieldname];
						$totalcols++;

						if ($vb4)
						{
							$t->register('currency', $currency);
							$t->register('fieldname', $fieldname);
							$t->register('arrowsort', $arrowsort);
							$currencies .= $t->render();
						}
						else eval('$currencies .= "' . fetch_template('credits_display_memberlist') . '";');
					}
				}
			}
			if (sizeof($combo))
			{
				$totalcols++;
				$fieldname = 'vbcredits_combo';
				$arrowsort = $sortarrow[$fieldname];
				$currency['title'] = $display['combine']; //fake it
				$fields .= ', (' . implode(' + ', $combo) . ') AS ' . $fieldname;

				if ($vb4)
				{
					$t->register('currency', $currency);
					$t->register('fieldname', $fieldname);
					$t->register('arrowsort', $arrowsort);
					$currencies .= $t->render();
				}
				else eval('$currencies .= "' . fetch_template('credits_display_memberlist') . '";');
			}

			$searchfor = '</tr>' . ( $vb4 ? '' : "\r\n" . '$memberlistbits' );
			$vbulletin->templatecache['memberlist'] = str_replace($searchfor, ( $vb4 ? $currencies : addslashes($currencies) ) . $searchfor, $vbulletin->templatecache['memberlist']);
		}
	}

	function navbartime()
	{
		global $vbulletin, $template_hook, $vb4;
		$display =& $vbulletin->vbcredits['display']['navbar'];
		if (!$vb4 AND $display['hookname'] == 'navbar_end') $display['hookname'] = 'navbar_buttons_right';
		VBCREDITS::display('navbar', $vbulletin->userinfo, $template_hook);

		if ($vbulletin->userinfo['userid'])
		{
			$birth = explode('-', $vbulletin->userinfo['birthday']);
			$prevyear = date('Y', $vbulletin->userinfo['lastactivity']);
			$payref = explode('-', $vbulletin->options['credits_action_paycheck_start']);
			$actref = explode('-', $vbulletin->options['credits_action_activity_start']);
			$which = $vbulletin->userinfo[$vbulletin->options['credits_action_activity_require']];
			$age = ( (substr($birth[2], 0, 1) != '0') ? intval($prevyear - $birth[2]) : 0 );

			while (true)
			{	//break if in the future
				$stamp = vbmktime(0, 0, 0, intval($birth[0]), intval($birth[1]), $prevyear);
				if ($stamp > TIMENOW) break;
				if ($stamp > $vbulletin->userinfo['lastactivity']) VBCREDITS::action('birthday', $vbulletin->userinfo['userid'], null, false, array('timestamp' => $stamp, 'multiplier' => $age));
				$age += ($age > 0);
				$prevyear++;
			}

			$refdate = vbmktime(0, 0, 0, intval($payref[1]), intval($payref[2]), intval($payref[0]));
			$interval = ($vbulletin->options['credits_action_paycheck_interval'] * 86400);
			$part = (abs($vbulletin->userinfo['lastactivity'] - $refdate) % $interval);

			for ($stamp = $vbulletin->userinfo['lastactivity'] + $interval - ( ($vbulletin->userinfo['lastactivity'] < $refdate) ? $interval - $part : $part ); $stamp <= TIMENOW; $stamp += $interval)
			{	//give all the missed paycheck awards
				VBCREDITS::action('paycheck', $vbulletin->userinfo['userid'], null, false, array('timestamp' => $stamp));
			}

			$refdate = vbmktime(0, 0, 0, intval($actref[1]), intval($actref[2]), intval($actref[0]));
			$interval = ($vbulletin->options['credits_action_activity_interval'] * 86400);
			$part = (abs($vbulletin->userinfo['lastactivity'] - $refdate) % $interval);

			for ($stamp = $vbulletin->userinfo['lastactivity'] + $interval - ( ($vbulletin->userinfo['lastactivity'] < $refdate) ? $interval - $part : $part ); $stamp <= TIMENOW; $stamp += $interval)
			{	//give an activity award
				VBCREDITS::action('activity', $vbulletin->userinfo['userid'], null, !($which > ($stamp - $interval) AND $which <= $stamp), array('timestamp' => $stamp));
			}
		}
	}

	function reputation()
	{
		global $vbulletin, $threadinfo, $score, $userid, $vbcredits, $postid;

		$insert = $vbulletin->db->query_first("
			SELECT reputationid
			FROM " . TABLE_PREFIX . "reputation
			WHERE postid = $postid AND
				whoadded = " . $vbulletin->userinfo['userid']
		);

		VBCREDITS::action('reputation', $userid, $insert['reputationid'], ($score < 0), array('forumid' => $threadinfo['forumid'], 'multiplier' => abs($score)));
		VBCREDITS::apply($vbcredits, $insert['reputationid']);
	}

	function profile()
	{
		global $vbulletin, $userinfo;
		VBCREDITS::action('profile', $vbulletin->userinfo['userid'], $userinfo['userid'], false, array('ownerid' => $userinfo['userid']));
		if ($vbulletin->userinfo['userid'] != $userinfo['userid'] AND ($vbulletin->userinfo['userid'] OR $vbulletin->options['credits_guest_views'])) VBCREDITS::action('visit', $userinfo['userid'], $vbulletin->userinfo['userid']);
	}

	function read()
	{
		global $vbulletin, $threadinfo;
		$extra = array('forumid' => $threadinfo['forumid'], 'ownerid' => $threadinfo['postuserid']);
		VBCREDITS::action('read', $vbulletin->userinfo['userid'], $threadinfo['threadid'], false, $extra);
		if ($vbulletin->userinfo['userid'] != $threadinfo['postuserid'] AND ($vbulletin->userinfo['userid'] OR $vbulletin->options['credits_guest_views'])) VBCREDITS::action('view', $threadinfo['postuserid'], $threadinfo['threadid'], false, $extra);
	}

	function infraction($inf, $delete = false)
	{
		global $vbulletin;

		if (($reverse = ($inf->fetch_field('action') != 0 OR $delete) OR !$inf->condition) AND ($points = $inf->fetch_field('points') OR $vbulletin->options['credits_action_infraction_warning']))
		{	//warnings dont involve points
			$extra = array('multiplier' => $points);
			VBCREDITS::action('infraction', $inf->fetch_field('userid'), $inf->infraction['infractionid'], $reverse, $extra);
			VBCREDITS::action('punish', $inf->fetch_field('whoadded'), $inf->infraction['infractionid'], $reverse, $extra);
		}
	}

	function download()
	{
		global $vbulletin, $attachmentinfo, $attach, $browsinginfo;

		if (array_pop(explode('_', strtolower(get_class($attach)))) == 'post')
		{	//only do this for forum attachments
			$extra = array('forumid' => $browsinginfo['foruminfo']['forumid'], 'multiplier' => $attachmentinfo['filesize'], 'ownerid' => $attachmentinfo['userid']);
			VBCREDITS::action('download', $vbulletin->userinfo['userid'], $attachmentinfo['attachmentid'], false, $extra);
			if ($vbulletin->userinfo['userid'] != $attachmentinfo['userid'] AND ($vbulletin->userinfo['userid'] OR $vbulletin->options['credits_guest_views'])) VBCREDITS::action('downloaded', $attachmentinfo['userid'], $attachmentinfo['attachmentid'], false, $extra);
		}
	}

	function userfields(&$user)
	{
		$user->vbcredits_old = $user->vbcredits_new = 0;

		foreach ($user->userfield AS $field => $value)
		{
			if ($value) $user->vbcredits_new++;
			if (array_key_exists($field, $user->existing) AND $user->existing[$field] != '') $user->vbcredits_old++;
		}

		$user->vbcredits = ( ($user->vbcredits_old != $user->vbcredits_new AND $user->vbcredits_new AND $userid = $user->fetch_field('userid')) ? VBCREDITS::action('describe', $userid, true, false, array('multiplier' => $user->vbcredits_new)) : false );
	}

	function user($user, $change = false)
	{
		global $vbulletin;
		$userid = $user->fetch_field('userid');

		if ($user->vbcredits_old != $user->vbcredits_new)
		{	//give and take the fields if any were set and they changed
			if ($user->vbcredits_old) VBCREDITS::action('describe', $userid, null, true, array('multiplier' => $user->vbcredits_old));

			if ($user->vbcredits_new)
			{
				if ($user->condition) VBCREDITS::apply($user->vbcredits, null); else VBCREDITS::action('describe', $userid, null, false, array('multiplier' => $user->vbcredits_new));
			}
		}
		if ($change)
		{
			VBCREDITS::action('induction', $userid);

			if ($user->user['referrerid'] AND isset($user->user['usergroupid']) AND $user->user['usergroupid'] != $user->existing['usergroupid'] AND $user->user['usergroupid'] == $vbulletin->options['credits_action_referral_usergroup'])
			{	//primary usergroup changed and it matches the valid one and i have a refferer, give them the referral event
				$extra = array('timestamp' => $user->user['joindate']);
				VBCREDITS::action('referral', $user->user['referrerid'], $userid, false, $extra);
				VBCREDITS::action('reference', $userid, null, false, $extra);
			}
		}
	}

	function event($event)
	{
		if ($event->condition) VBCREDITS::action('calendar', $event->fetch_field('userid'), $event->existing['eventid'], true, array('multiplier' => $event->existing['event']));
		VBCREDITS::apply($event->vbcredits, $event->fetch_field('eventid'));
	}

	function visitor($visit, $delete = false)
	{
		if (($target = $visit->fetch_field('userid')) != ($userid = $visit->fetch_field('postuserid')))
		{	//only if not yourself
			if (!($delete XOR $this->condition)) VBCREDITS::action('wall', $target, $userid, $delete);
			if (!$delete) VBCREDITS::apply($visit->vbcredits, $target);
		}

		if ($visit->condition) VBCREDITS::action('visitor', $userid, $target, true, array('multiplier' => $visit->existing['pagetext'], 'ownerid' => $target));
	}

	function contacts()
	{
		global $vbulletin, $add, $remove;

		foreach (array('friend', 'approvals') AS $type)
		{
			if (array_key_exists($type, $add))
			{	//remove has empty keys, this has no keys
				foreach ($add[$type] AS $userid => $userinfo) VBCREDITS::action('friend', $vbulletin->userinfo['userid'], $userid);
			}
		}
		foreach (array('friend', 'approvals') AS $type)
		{
			foreach ($remove[$type] AS $userid)
			{
				VBCREDITS::action('friend', $userid, $vbulletin->userinfo['userid'], true);
				if ($type == 'friend') VBCREDITS::action('friend', $vbulletin->userinfo['userid'], $userid, true);
			}
		}
	}

	function friend($delete = false)
	{
		global $vbulletin, $userinfo, $vbcredits;
		$vbcredits = array();

		if (!$vbulletin->GPC['deny'] AND $vbulletin->GPC['userlist'] == 'friend')
		{
			$vbcredits[] = VBCREDITS::action('friend', $vbulletin->userinfo['userid'], true, $delete);
			if ($delete) $vbcredits[] = VBCREDITS::action('friend', $userinfo['userid'], true, true);
		}
	}

	function upload()
	{
		$action = '';
		$attaches = array();
		global $vbulletin, $attachlib, $vbcredits;
		$db =& $vbulletin->db;

		switch (array_pop(explode('_', strtolower(get_class($attachlib)))))
		{
			case 'post':
				$action = 'upload';
				$attachinfo = fetch_postinfo($vbulletin->GPC['values']['p']);
				$threadinfo = fetch_threadinfo($attachinfo['threadid']);
				$extra = array('forumid' => $threadinfo['forumid']);
			break;
			case 'album':
				$action = 'album';
				require_once(DIR . '/includes/functions_album.php');
				$attachinfo = fetch_albuminfo($vbulletin->GPC['values']['albumid']);
				$extra = array();
			break;
		}
		if ($action)
		{
			$post = ($action == 'upload');

			if (!$vbulletin->input->clean_gpc('p', 'upload', TYPE_STR))
			{
				$attachments = $attachlib->fetch_attachments();

				while ($attach = $db->fetch_array($attachments))
				{
					$attach['multiplier'] = ( $post ? $attach['filesize'] : $attach['width'] * $attach['height'] );
					$attaches[$attach['attachmentid']] = $attach;
				}
				foreach (array_keys($vbulletin->input->clean_gpc('p', 'delete', TYPE_ARRAY_STR)) AS $file)
				{
					if (is_numeric($file))
					{
						$extra['multiplier'] = $attaches[$file]['multiplier'];
						VBCREDITS::action($action, $attachinfo['userid'], $file, true, $extra);
					}
				}
			}
			else
			{
				if ($aid = $vbulletin->input->clean_gpc('p', 'filedata', TYPE_ARRAY_UINT) AND $aid[0])
				{
					$file = $db->query_first("SELECT *, filesize AS size FROM " . TABLE_PREFIX . "filedata WHERE filedataid = " . intval($aid[0]));
				}
				else if ($url = $vbulletin->input->clean_gpc('p', 'attachmenturl', TYPE_ARRAY_STR) AND $url[0])
				{
					$file = array();
					list($file['width'], $file['height']) = getimagesize($url[0]);
					$file['size'] = filesize($url[0]);
				}
				else
				{
					$file = $vbulletin->input->clean_gpc('f', 'attachment', TYPE_FILE);
					list($file['width'], $file['height']) = getimagesize($file['tmp_name'][0]);
					$file['size'] = $file['size'][0];
				}

				$extra['multiplier'] = ( $post ? $file['size'] : $file['width'] * $file['height'] );
				$vbcredits = VBCREDITS::action($action, $attachinfo['userid'], true, false, $extra);
			}
		}
	}

	function prepost(&$post)
	{
		$post->vbcredits = array();
		global $vbulletin;

		if ($post->fetch_field('visible') == 1)
		{
			$forumid = $post->info['thread']['forumid'];
			$userid = $post->fetch_field('userid');
			$timespan = TIMENOW - $post->info['thread']['lastpost'];
			$post->vbcredits[] = VBCREDITS::action(( ($post->condition AND $post->fetch_field('postid') == $post->info['thread']['firstpostid']) ? 'thread' : 'post' ), $userid, true, false, array('forumid' => $forumid, 'multiplier' => $post->fetch_field('pagetext'), 'ownerid' => $post->info['thread']['postuserid']));
			if ($timespan >= $vbulletin->options['credits_action_revival_threshold']) $post->vbcredits[] = VBCREDITS::action('revival', $userid, true, false, array('forumid' => $forumid, 'multiplier' => floor($timespan / 86400)));
		}
	}

	function post($post)
	{
		$forumid = $post->info['thread']['forumid'];
		$userid = $post->fetch_field('userid');
		$postid = $post->fetch_field('postid');
		$fpuserid = $post->info['thread']['postuserid'];
		$threadid = $post->fetch_field('threadid');
		$action = ( ($post->condition AND $postid == $post->info['thread']['firstpostid']) ? 'thread' : 'post' );
		$refid = ( ($action == 'thread') ? $threadid : $postid );
		if ($post->condition AND $post->existing['visible'] == 1) VBCREDITS::action($action, $userid, $refid, true, array('forumid' => $forumid, 'multiplier' => $post->existing['pagetext'], 'ownerid' => $fpuserid));

		if ($post->fetch_field('visible') == 1)
		{
			VBCREDITS::apply($post->vbcredits[0], $refid);
			if (sizeof($post->vbcredits) > 1) VBCREDITS::apply($post->vbcredits[1], $threadid);
			if ($userid != $fpuserid AND (!$post->condition OR $post->existing['visible'] != 1)) VBCREDITS::action('reply', $fpuserid, $postid, false, array('forumid' => $forumid));

			if (!$post->condition AND $action != 'thread')
			{
				$extra = array('forumid' => $forumid, 'ownerid' => $fpuserid);
				if ($post->info['thread']['replycount']) VBCREDITS::action('last', $post->info['thread']['lastposterid'], $threadid, true, $extra);
				VBCREDITS::action('last', $userid, $threadid, false, $extra);
			}
		}
	}

	function delpost($post)
	{
		$forumid = $post->info['thread']['forumid'];
		$userid = $post->fetch_field('userid');
		$postid = $post->fetch_field('postid');
		$fpuserid = $post->info['thread']['postuserid'];
		//delete any reputation?
		if ($post->existing['visible'] == 1)
		{
			VBCREDITS::action('post', $userid, $postid, true, array('forumid' => $forumid, 'multiplier' => $post->existing['pagetext'], 'ownerid' => $fpuserid));
			if ($userid != $fpuserid) VBCREDITS::action('reply', $fpuserid, $postid, true, array('forumid' => $forumid));
		}
	}

	function poll($poll, $delete = false)
	{
		global $threadinfo;
		$pollid = $poll->fetch_field('pollid');
		if ($poll->condition) VBCREDITS::action('poll', $threadinfo['postuserid'], $pollid, true, array('forumid' => $threadinfo['forumid'], 'multiplier' => $poll->existing['numberoptions']));
		if (!$delete) VBCREDITS::apply($poll->vbcredits, $pollid);
	}

	function rating($rate, $delete = false)
	{
		$userid = $rate->fetch_field('userid');
		$owner = $rate->info['thread']['postuserid'];
		$threadid = $rate->fetch_field('threadid');
		$forumid = $rate->info['thread']['forumid'];
		$newvote = $rate->fetch_field('vote');
		$notmine = ($userid != $owner);

		if ($delete OR $rate->existing['vote'] != $newvote)
		{
			if ($rate->condition)
			{	//delete from self and owner
				if ($notmine) VBCREDITS::action('evaluate', $owner, $threadid, true, array('forumid' => $forumid, 'multiplier' => $rate->existing['vote']));
				VBCREDITS::action('rate', $userid, $threadid, true, array('forumid' => $forumid, 'multiplier' => $rate->existing['vote'], 'ownerid' => $owner));
			}
			if (!$delete AND $notmine)
			{	//give to self and owner
				VBCREDITS::action('evaluate', $owner, $threadid, false, array('forumid' => $forumid, 'multiplier' => $newvote));
				VBCREDITS::apply($rate->vbcredits, $threadid);
			}
		}
	}

	function prethread(&$thread)
	{
		if ($thread->condition)
		{
			$thread->existing = array_merge(fetch_threadinfo($thread->existing['threadid']), $thread->existing);
			if ($thread->existing['visible'] != 1 AND $thread->fetch_field('visible') == 1) $thread->vbcredits = VBCREDITS::action('thread', $thread->existing['postuserid'], true, false, array('forumid' => $thread->existing['forumid'], 'multiplier' => $thread->existing['description']));
		}
	}

	function thread($thread)
	{
		$threadid = $thread->fetch_field('threadid');
		VBCREDITS::apply($thread->vbcredits, $threadid);
		if (($sticky = $thread->fetch_field('sticky')) != $thread->existing['sticky']) VBCREDITS::action('sticky', $thread->fetch_field('postuserid'), $threadid, !$sticky, array('forumid' => $thread->fetch_field('forumid')));
	}

	function delthread($thread)
	{
		//thread
		//tagging
		//sticky
		$thread->existing = array_merge(fetch_threadinfo($thread->existing['threadid']), $thread->existing);
		if ($thread->existing['visible'] == 1) VBCREDITS::action('thread', $thread->existing['postuserid'], $thread->existing['threadid'], true, array('forumid' => $thread->existing['forumid'], 'multiplier' => $thread->existing['description']));

		//if not keeping attachments, revert that?
		//if perm, delete the posts, ratings, 
	}

	function premember(&$member)
	{
		$userid = $member->fetch_field('userid');
		if (empty($member->info['group'])) $member->info['group'] = fetch_socialgroupinfo($member->fetch_field('groupid'));
		if ($member->info['group']['creatoruserid'] != $userid AND $member->fetch_field('type') == 'member' AND (!$member->condition OR $member->existing['type'] != 'member')) $member->vbcredits = VBCREDITS::action('join', $userid, true);
	}

	function member($member, $delete = false)
	{
		$userid = $member->fetch_field('userid');
		$groupid = $member->fetch_field('groupid');
		$ownerid = $member->info['group']['creatoruserid'];

		if ($ownerid != $userid)
		{
			if ($delete OR ($member->fetch_field('type') != 'member' AND $member->existing['type'] == 'member'))
			{	// moderated
				VBCREDITS::action('join', $userid, $groupid, true);
				VBCREDITS::action('member', $ownerid, $groupid, true);
			}
			else if (!is_null($member->vbcredits))
			{	// accepted
				VBCREDITS::apply($member->vbcredits, $groupid);
				VBCREDITS::action('member', $ownerid, $groupid);
			}
		}
	}
	
	function prediscuss(&$discuss)
	{
		$discuss->info['message'] = fetch_groupmessageinfo($discuss->fetch_field('firstpostid'));
		if (empty($discuss->info['group'])) $discuss->info['group'] = fetch_socialgroupinfo($discuss->fetch_field('groupid'));
		if (!$discuss->condition) $discuss->vbcredits = VBCREDITS::action('discuss', $discuss->info['message']['postuserid'], true, false, array('ownerid' => $discuss->info['group']['creatoruserid'], 'multiplier' => $discuss->info['message']['pagetext']));
	}
	
	function discuss($discuss, $delete = false)
	{
		$discussid = $discuss->fetch_field('discussionid');
		$ownerid = $discuss->info['group']['creatoruserid'];
		$userid = $discuss->info['message']['postuserid'];
		$notowner = ($ownerid != $userid);

		if ($delete)
		{
			VBCREDITS::action('discuss', $userid, $discussid, true, array('ownerid' => $discuss->info['group']['creatoruserid'], 'multiplier' => $discuss->info['message']['pagetext']));
			if ($notowner) VBCREDITS::action('interesting', $ownerid, $discussid, true);
		}
		if (!$discuss->condition)
		{
			VBCREDITS::apply($discuss->vbcredits, $discussid);
			if ($notowner) VBCREDITS::action('interesting', $ownerid, $discussid);
		}
	}
	
	function prereply(&$reply)
	{
		if ($discussid = $reply->fetch_field('discussionid'))
		{
			$reply->info['discussion'] = fetch_socialdiscussioninfo($discussid);
			$isdiscuss = ($reply->fetch_field('gmid') == $reply->info['discussion']['firstpostid']);
			if ($reply->fetch_field('state') == 'visible') $reply->vbcredits = VBCREDITS::action(( $isdiscuss ? 'discuss' : 'update' ), $this->fetch_field('postuserid'), true, false, array('ownerid' => ( $isdiscuss ? $reply->info['group']['creatoruserid'] : $reply->info['discussion']['postuserid'] ), 'multiplier' => $reply->fetch_field('pagetext')));
		}
	}
	
	function reply($reply, $delete = false)
	{
		$gmid = $reply->fetch_field('gmid');
		$userid = $reply->fetch_field('postuserid');

		if ($gmid == $reply->info['discussion']['firstpostid'])
		{	//this is the first post of the discussion
			$groupid = $reply->info['discussion']['groupid'];
			$reply->info['group'] = fetch_socialgroupinfo($groupid);
			$ownerid = $reply->info['group']['creatoruserid'];
			$gmid = $reply->info['discussion']['discussionid'];
			$action = array('discuss', 'interesting');
		}
		else
		{	//just a regular post
			$ownerid = $reply->info['discussion']['postuserid'];			
			$action = array('update', 'popular');
		}

		$notowner = ($ownerid != $userid);

		if ($reply->condition)
		{
			VBCREDITS::action($action[0], $userid, $gmid, true, array('ownerid' => $ownerid, 'multiplier' => $reply->existing['pagetext']));
			if ($notowner) VBCREDITS::action($action[1], $ownerid, $gmid, true);
		}
		if (!$delete)
		{
			VBCREDITS::apply($reply->vbcredits, $gmid);
			if ($notowner) VBCREDITS::action($action[1], $ownerid, $gmid);
		}
	}

	function tag()
	{
		global $content, $vbulletin, $errors, $threadinfo;

		if ($taglist = $content->filter_tag_list_content_limits($vbulletin->GPC['taglist'], $content->fetch_tag_limits(), $errors) AND is_array($taglist))
		{
			foreach ($taglist AS $tag) VBCREDITS::action('tag', $vbulletin->userinfo['userid'], $tag, false, array('ownerid' => $threadinfo['postuserid'], 'forumid' => $threadinfo['forumid']));
		}
	}

	function deltag()
	{
		global $vbulletin, $delete, $contentid, $contenttypeid, $threadinfo;
		$db =& $vbulletin->db;

		if ($delete)
		{
			$deltags = $db->query_read("SELECT c.*, t.tagtext FROM " . TABLE_PREFIX . "tagcontent AS c LEFT JOIN " . TABLE_PREFIX . "tag AS t ON (t.tagid = c.tagid) WHERE c.contentid = $contentid AND c.contenttypeid = " . intval($contenttypeid) . " AND c.tagid IN (" . implode(',', $delete) . ")");
			while ($deltag = $db->fetch_array($deltags)) VBCREDITS::action('tag', $deltag['userid'], $deltag['tagtext'], true, array('ownerid' => $threadinfo['postuserid'], 'forumid' => $threadinfo['forumid']));
			$db->free_result($deltags);
		}
	}

	function rebuild($actionid, $user)
	{
		global $vbulletin, $vb4;
		$userinfo = ( ($vbulletin->userinfo['userid'] == $user['userid']) ? null : $user );
		$db =& $vbulletin->db;

		switch ($actionid)
		{
			case 'album':
				$actions = $db->query_read("SELECT a.*" . ( $vb4 ? ", f.width, f.height" : '' ) . " FROM " . TABLE_PREFIX . "attachment AS a" . ( $vb4 ? " LEFT JOIN " . TABLE_PREFIX . "filedata AS f ON (f.filedataid = a.filedataid)" : '' ) . " WHERE " . ( $vb4 ? "a.state = 'visible' AND a.contenttypeid = 8" : "a.visible = 1" ) . " AND a.userid = " . $user['userid'] . " AND a.dateline >= " . $vbulletin->GPC['start_date'] . " AND a.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('album', $user['userid'], $action['attachmentid'], false, array('multiplier' => $action['width'] * $action['height'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'approval':
				$actions = $db->query_read("SELECT r.*, t.forumid FROM " . TABLE_PREFIX . "reputation AS r LEFT JOIN " . TABLE_PREFIX . "post AS p ON (p.postid = r.postid) LEFT JOIN " . TABLE_PREFIX . "thread AS t ON (t.threadid = p.threadid) WHERE r.whoadded = " . $user['userid'] . " AND r.dateline >= " . $vbulletin->GPC['start_date'] . " AND r.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('approval', $user['userid'], $action['reputationid'], false, array('forumid' => $action['forumid'], 'multiplier' => ( $vbulletin->options['credits_action_approval_absolute'] ? abs($action['reputation']) : $action['reputation'] ), 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'birthday':
				$birth = explode('-', $user['birthday']);
				$start = max($vbulletin->GPC['start_date'], $user['joindate']);
				$prevyear = date('Y', $start);
				$age = ( (substr($birth[2], 0, 1) != '0') ? intval($prevyear - $birth[2]) : 0 );

				while (true)
				{	//break if in the future
					$stamp = vbmktime(0, 0, 0, intval($birth[0]), intval($birth[1]), $prevyear);
					if ($stamp > $vbulletin->GPC['end_date']) break;
					if ($stamp > $start) VBCREDITS::action('birthday', $user['userid'], null, false, array('userinfo' => $userinfo, 'timestamp' => $stamp, 'multiplier' => $age));
					$age += ($age > 0);
					$prevyear++;
				}

				break;
			case 'calendar':
				$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "event WHERE visible = 1 AND userid = " . $user['userid'] . " AND dateline >= " . $vbulletin->GPC['start_date'] . " AND dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('calendar', $user['userid'], $action['eventid'], false, array('multiplier' => $action['event'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'discuss':
				$actions = $db->query_read("SELECT m.*, g.creatoruserid FROM " . TABLE_PREFIX . "discussion AS d LEFT JOIN " . TABLE_PREFIX . "groupmessage AS m ON (m.gmid = d.firstpostid) LEFT JOIN " . TABLE_PREFIX . "socialgroup AS g ON (g.groupid = d.groupid) WHERE m.postuserid = " . $user['userid'] . " AND m.dateline >= " . $vbulletin->GPC['start_date'] . " AND m.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('discuss', $user['userid'], $action['discussionid'], false, array('ownerid' => $action['creatoruserid'], 'multiplier' => $action['pagetext'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'group':
				$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "socialgroup WHERE creatoruserid = " . $user['userid'] . " AND dateline >= " . $vbulletin->GPC['start_date'] . " AND dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('group', $user['userid'], $action['groupid'], false, array('userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'infraction':
				$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "infraction WHERE action = 0 AND userid = " . $user['userid'] . " AND dateline >= " . $vbulletin->GPC['start_date'] . " AND dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('infraction', $user['userid'], $action['infractionid'], false, array('multiplier' => $action['points'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'interesting':
				$actions = $db->query_read("SELECT m.* FROM " . TABLE_PREFIX . "discussion AS d LEFT JOIN " . TABLE_PREFIX . "groupmessage AS m ON (m.gmid = d.firstpostid) LEFT JOIN " . TABLE_PREFIX . "socialgroup AS g ON (g.groupid = d.groupid) WHERE m.postuserid != g.creatoruserid AND g.creatoruserid = " . $user['userid'] . " AND m.dateline >= " . $vbulletin->GPC['start_date'] . " AND m.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('interesting', $user['userid'], $action['discussionid'], false, array('userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'join':
				$actions = $db->query_read("SELECT m.* FROM " . TABLE_PREFIX . "socialgroupmember AS m LEFT JOIN " . TABLE_PREFIX . "socialgroup AS g ON (g.groupid = m.groupid) WHERE m.type = 'member' AND g.creatoruserid != m.userid AND m.userid = " . $user['userid'] . " AND m.dateline >= " . $vbulletin->GPC['start_date'] . " AND m.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('join', $user['userid'], $action['groupid'], false, array('userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'member':
				$actions = $db->query_read("SELECT m.* FROM " . TABLE_PREFIX . "socialgroupmember AS m LEFT JOIN " . TABLE_PREFIX . "socialgroup AS g ON (g.groupid = m.groupid) WHERE m.type = 'member' AND g.creatoruserid != m.userid AND g.creatoruserid = " . $user['userid'] . " AND m.dateline >= " . $vbulletin->GPC['start_date'] . " AND m.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('member', $user['userid'], $action['groupid'], false, array('userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'message':
				$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "pmtext WHERE fromuserid = " . $user['userid'] . " AND dateline >= " . $vbulletin->GPC['start_date'] . " AND dateline <= " . $vbulletin->GPC['end_date']);

				while ($action = $db->fetch_array($actions))
				{
					$size = $action['message'];
					$recips = unserialize($action['touserarray']);
					for ($x = ( $vbulletin->options['credits_action_message_multiple'] ? sizeof($recips['cc']) + sizeof($recips['bcc']) : 1 ); $x > 0; $x--) VBCREDITS::action('message', $user['userid'], null, false, array('multiplier' => $size, 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				}

				$db->free_result($actions);
				break;
			case 'paycheck':
				$payref = explode('-', $vbulletin->options['credits_action_paycheck_start']);
				$refdate = vbmktime(0, 0, 0, intval($payref[1]), intval($payref[2]), intval($payref[0]));
				$interval = ($vbulletin->options['credits_action_paycheck_interval'] * 86400);
				$start = max($vbulletin->GPC['start_date'], $user['joindate']);
				$part = (abs($start - $refdate) % $interval);

				for ($stamp = $start + $interval - ( ($start < $refdate) ? $interval - $part : $part ); $stamp <= $vbulletin->GPC['end_date']; $stamp += $interval)
				{	//give all the missed paycheck awards
					VBCREDITS::action('paycheck', $user['userid'], null, false, array('userinfo' => $userinfo, 'timestamp' => $stamp));
				}

				break;
			case 'poll':
				$actions = $db->query_read("SELECT p.*, t.forumid FROM " . TABLE_PREFIX . "poll AS p LEFT JOIN " . TABLE_PREFIX . "thread AS t ON (t.pollid = p.pollid) WHERE t.visible = 1 AND t.postuserid = " . $user['userid'] . " AND t.dateline >= " . $vbulletin->GPC['start_date'] . " AND t.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('poll', $user['userid'], $action['pollid'], false, array('forumid' => $action['forumid'], 'multiplier' => $action['numberoptions'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'popular':
				$actions = $db->query_read("SELECT g.* FROM " . TABLE_PREFIX . "groupmessage AS g LEFT JOIN " . TABLE_PREFIX . "discussion AS d ON (d.discussionid = g.discussionid) LEFT JOIN " . TABLE_PREFIX . "groupmessage AS f ON (d.firstpostid = f.gmid) WHERE g.postuserid != f.postuserid AND f.postuserid = " . $user['userid'] . " AND g.dateline >= " . $vbulletin->GPC['start_date'] . " AND g.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('popular', $user['userid'], $action['gmid'], false, array('userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'post':
				$actions = $db->query_read("SELECT p.*, t.forumid, t.postuserid FROM " . TABLE_PREFIX . "post AS p LEFT JOIN " . TABLE_PREFIX . "thread AS t ON (t.threadid = p.threadid) WHERE p.visible = 1 AND p.postid != t.firstpostid AND p.userid = " . $user['userid'] . " AND p.dateline >= " . $vbulletin->GPC['start_date'] . " AND p.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('post', $user['userid'], $action['postid'], false, array('forumid' => $action['forumid'], 'multiplier' => $action['pagetext'], 'ownerid' => $action['postuserid'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'punish':
				$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "infraction WHERE action = 0 AND whoadded = " . $user['userid'] . " AND dateline >= " . $vbulletin->GPC['start_date'] . " AND dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('punish', $user['userid'], $action['infractionid'], false, array('multiplier' => $action['points'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'referral':
				$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "user WHERE usergroupid = " . $vbulletin->options['credits_action_referral_usergroup'] . " AND referrerid = " . $user['userid'] . " AND joindate >= " . $vbulletin->GPC['start_date'] . " AND joindate <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('referral', $user['userid'], $action['userid'], false, array('userinfo' => $userinfo, 'timestamp' => $action['joindate']));
				$db->free_result($actions);
				break;
			case 'reply':
				$actions = $db->query_read("SELECT p.*, t.forumid FROM " . TABLE_PREFIX . "post AS p LEFT JOIN " . TABLE_PREFIX . "thread AS t ON (t.threadid = p.threadid) WHERE p.visible = 1 AND t.postuserid = " . $user['userid'] . " AND p.userid != " . $user['userid'] . " AND p.dateline >= " . $vbulletin->GPC['start_date'] . " AND p.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('reply', $user['userid'], $action['postid'], false, array('forumid' => $action['forumid'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'reputation':
				$actions = $db->query_read("SELECT r.*, t.forumid FROM " . TABLE_PREFIX . "reputation AS r LEFT JOIN " . TABLE_PREFIX . "post AS p ON (p.postid = r.postid) LEFT JOIN " . TABLE_PREFIX . "thread AS t ON (t.threadid = p.threadid) WHERE r.userid = " . $user['userid'] . " AND r.dateline >= " . $vbulletin->GPC['start_date'] . " AND r.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('reputation', $user['userid'], $action['reputationid'], ($action['reputation'] < 0), array('forumid' => $action['forumid'], 'multiplier' => abs($action['reputation']), 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'tag':
				$diff = ( $vb4 ? 'content' : 'thread' );
				$actions = $db->query_read("SELECT r.*, c.userid, c.dateline, t.tagtext FROM " . TABLE_PREFIX . "tag$diff AS c LEFT JOIN " . TABLE_PREFIX . "tag AS t ON (t.tagid = c.tagid) LEFT JOIN " . TABLE_PREFIX . "thread AS r ON (r.threadid = c." . $diff . "id) WHERE" . ( $vb4 ? " c.contenttypeid = 2 AND" : '' ) . " c.userid = " . $user['userid'] . " AND c.dateline >= " . $vbulletin->GPC['start_date'] . " AND c.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('tag', $user['userid'], $action['tagtext'], false, array('ownerid' => $action['postuserid'], 'forumid' => $action['forumid'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline'])); 
				$db->free_result($actions);
				break;
			case 'thread':
				$actions = $db->query_read("SELECT t.*, p.pagetext FROM " . TABLE_PREFIX . "thread AS t LEFT JOIN " . TABLE_PREFIX . "post AS p ON (p.postid = t.firstpostid) WHERE t.visible = 1 AND t.postuserid = " . $user['userid'] . " AND t.dateline >= " . $vbulletin->GPC['start_date'] . " AND t.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('thread', $user['userid'], $action['threadid'], false, array('forumid' => $action['forumid'], 'multiplier' => $action['pagetext'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'update':
				$actions = $db->query_read("SELECT g.*, f.postuserid AS discussuserid FROM " . TABLE_PREFIX . "groupmessage AS g LEFT JOIN " . TABLE_PREFIX . "discussion AS d ON (d.discussionid = g.discussionid) LEFT JOIN " . TABLE_PREFIX . "groupmessage AS f ON (d.firstpostid = f.gmid) WHERE g.gmid != f.gmid AND g.postuserid = " . $user['userid'] . " AND g.dateline >= " . $vbulletin->GPC['start_date'] . " AND g.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('update', $user['userid'], $action['gmid'], false, array('ownerid' => $action['discussuserid'], 'multiplier' => $action['pagetext'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'upload':
				$actions = $db->query_read("SELECT a.*," . ( $vb4 ? " f.filesize," : '' ) . " t.forumid FROM " . TABLE_PREFIX . "attachment AS a LEFT JOIN " . TABLE_PREFIX . "post AS p ON (p.postid = a." . ( $vb4 ? 'content' : 'post' ) . "id) LEFT JOIN " . TABLE_PREFIX . "thread AS t ON (t.threadid = p.threadid)" . ( $vb4 ? " LEFT JOIN " . TABLE_PREFIX . "filedata AS f ON (f.filedataid = a.filedataid)" : '' ) . " WHERE " . ( $vb4 ? "a.state = 'visible' AND a.contenttypeid = 1" : "a.visible = 1" ) . " AND a.userid = " . $user['userid'] . " AND a.dateline >= " . $vbulletin->GPC['start_date'] . " AND a.dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('upload', $user['userid'], $action['attachmentid'], false, array('forumid' => $action['forumid'], 'multiplier' => $action['filesize'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'visitor':
				$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "visitormessage WHERE state = 'visible' AND postuserid = " . $user['userid'] . " AND dateline >= " . $vbulletin->GPC['start_date'] . " AND dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('visitor', $user['userid'], $action['userid'], false, array('multiplier' => $action['pagetext'], 'ownerid' => $action['userid'], 'userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
			case 'vote':
				$actions = $db->query_read("SELECT v.*, t.forumid, t.postuserid, COUNT(*) AS votes FROM " . TABLE_PREFIX . "pollvote AS v LEFT JOIN " . TABLE_PREFIX . "thread AS t ON (t.pollid = v.pollid) WHERE v.userid = " . $user['userid'] . " AND v.votedate >= " . $vbulletin->GPC['start_date'] . " AND v.votedate <= " . $vbulletin->GPC['end_date'] . " GROUP BY v.pollid");
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('vote', $user['userid'], $action['pollid'], false, array('forumid' => $action['forumid'], 'multiplier' => $action['votes'], 'ownerid' => $action['postuserid'], 'userinfo' => $userinfo, 'timestamp' => $action['votedate']));
				$db->free_result($actions);
				break;
			case 'wall':
				$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "visitormessage WHERE state = 'visible' AND postuserid != " . $user['userid'] . " AND userid = " . $user['userid'] . " AND dateline >= " . $vbulletin->GPC['start_date'] . " AND dateline <= " . $vbulletin->GPC['end_date']);
				while ($action = $db->fetch_array($actions)) VBCREDITS::action('wall', $user['userid'], $action['postuserid'], false, array('userinfo' => $userinfo, 'timestamp' => $action['dateline']));
				$db->free_result($actions);
				break;
		}
	}
}
?>