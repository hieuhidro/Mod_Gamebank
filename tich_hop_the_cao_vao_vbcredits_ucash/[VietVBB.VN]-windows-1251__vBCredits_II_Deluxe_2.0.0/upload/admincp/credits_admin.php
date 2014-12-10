<?php

/*=======================================================================*\
|| ##################################################################### ||
|| # vBCredits II Deluxe 2.0.0 - `credits_admin.php`				   # ||
|| # ------------------------------------------------------------------# ||
|| # Author: Darkwaltz4 {blackwaltz4@msn.com}						   # ||
|| # Copyright � 2009 - 2010 John Jakubowski. All Rights Reserved.	   # ||
|| # This file may not be redistributed in whole or significant part.  # ||
|| # -----------------vBulletin IS NOT FREE SOFTWARE!------------------# ||
|| #			 Support: http://www.dragonbyte-tech.com/			   # ||
|| ##################################################################### ||
\*=======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// TODO: break these groups down into being called only when needed :: see the stats check following this array assignment
$phrasegroups = array('credits_admin', 'cprofilefield', 'cpuser', 'user');
$specialtemplates = array('vbcredits');

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/adminfunctions_profilefield.php');
require_once(DIR . '/includes/adminfunctions_user.php');
require_once(DIR . '/includes/adminfunctions_stats.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!class_exists('VBCREDITS') OR !can_administer('credits')) print_cp_no_permission();

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

switch ($_REQUEST['do'])
{
	case 'delete_event': case 'save_event': case 'edit_event': case 'import_events': case 'events':
		$title = 'credits_menu_event'; break;
	case 'save_action': case 'edit_action': case 'actions':
		$title = 'credits_menu_action'; break;
	case 'save_display': case 'edit_display': case 'update_displays': case 'displays':
		$title = 'credits_menu_display'; break;
	case 'delete_currency': case 'transfer_currency': case 'save_currency': case 'edit_currency': case 'update_currencies': case 'currencies':
		$title = 'credits_menu_currency'; break;
	case 'delete_conversion': case 'save_conversion': case 'edit_conversion': case 'update_conversions': case 'conversions':
		$title = 'credits_menu_conversion'; break;
	case 'delete_redemption': case 'save_redemption': case 'edit_redemption': case 'update_redemptions': case 'redemptions':
		$title = 'credits_menu_redemption'; break;
	case 'delete_transaction': case 'process_transactions': case 'transactions':
		$title = 'credits_menu_transaction'; break;
	case 'findusers': case 'users':
		$title = 'credits_menu_user'; break;
	default: if ($_REQUEST['do'] != 'process_events')
	{	//export needs to be clean
		$_REQUEST['do'] = 'currencies';
		$title = 'credits_menu_currency';
	}
}
if (in_array($_REQUEST['do'], array('process_transactions', 'transactions', 'findusers')))
{	//will be processing many records
	@set_time_limit(0);
	ignore_user_abort(1);
	$vbulletin->nozip = true;
	//default stamp for start dates
	$earliest = $db->query_first("SELECT joindate FROM " . TABLE_PREFIX . "user ORDER BY joindate ASC LIMIT 1");
	$obj =& VBCREDITS::init();
	$obj->douser = true;
}
if ($title)
{ 	//only show title and js when its not user stuff
	print_cp_header($vbphrase[$title]);
?>
<style type="text/css">.hidden { display: none; }</style>
<script type="text/javascript" src="../clientscript/jquery/jquery-1.4.4.min.js?v=<?php echo $vbulletin->options['simpleversion']; ?>"></script>
<script type="text/javascript">
var vbcredits_advset = [], vbcredits_transfer = [], vbcredits_depend = null;

jQuery(function($)
{
	var recolor = function()
	{
		$('table').each(function()
		{
			var alt = 2;

			$('tr[valign=top]:visible', $(this)).each(function()
			{
				$('td[class^=alt]', $(this)).each(function()
				{
					$(this).removeClass().addClass('alt' + alt);
				});

				alt = ( (alt == 1) ? 2 : 1 );
			});
		})
	};
	$('select[multiple]').each(function()
	{	//multi selects that allow for any with nothing
		var select = $(this), opts = $('option', select);

		if (select.attr('name') != 'actions[]')
		{	//skip the recalculation actions
			select.change(function()
			{	//the chose everything and probably mean any
				if ($('option:selected', select).length == opts.length && confirm('<?php echo $vbphrase['credits_admin_selectall']; ?>')) opts.removeAttr('selected');
			});
		}
	});
	$('[name^=all_]:checkbox').click(function()
	{	//checkboxes with name=all_*
		var self = $(this); //select all the boxes that start with all's name
		var boxes = $('[name^=' + self.attr('name').substr(4) + ']:checkbox');
		if (self.is(':checked')) boxes.attr('checked', 'checked');
		else boxes.removeAttr('checked');
	});
	$('input:submit[rel]').click(function()
	{	//change the do based on the button
		var self = $(this);
		$('input[name=do]', self.parents('form')).val(self.attr('rel'));
	});
	$('label input:text').click(function(e)
	{	//fix textboxes in labels
		e.preventDefault();
		$(this).siblings('input:radio').attr('checked', 'checked');
	});
	if (vbcredits_advset.length)
	{
		var advset = [];
		var hidden = true;
		var button = $('#advanced_toggle');

		$.each(vbcredits_advset, function()
		{
			advset[advset.length] = $('[name^=' + this + ']').closest('tr[valign=top]').addClass('hidden');
		});

		recolor();

		button.click(function(e)
		{
			e.preventDefault();

			$.each(advset, function()
			{
				this.removeClass('hidden');
			});

			recolor();
			hidden = false;
			button.closest('table').hide();
		});
	}
	if (vbcredits_transfer.length)
	{
		var sel = $('select[name=currency]');

		sel.change(function()
		{
			$.each(vbcredits_transfer[sel.val()], function(field, value)
			{
				if (value == '0' || value == '1') $('input[name=' + field + '][value=' + value + ']').attr('checked', 'checked');
				else $(':input[name=' + field + ']').val(value);
			});
		}).change();
	}
	if (vbcredits_depend)
	{
		var depset = [];

		$.each(vbcredits_depend.depends, function()
		{
			depset[depset.length] = $('[name=' + this + ']').closest('tr');
		});

		$('[name=' + vbcredits_depend.setting + ']').closest('tr').click(function()
		{
			var show = ($('[name=' + vbcredits_depend.setting + ']:checked').val() == vbcredits_depend.value);
			$('span.earn').toggle(show);
			$('span.spend').toggle(!show);

			$.each(depset, function()
			{
				this.css('display', ( show ? '' : 'none' ));
			});

			recolor();
		}).click();
	}

	var redeemcodes = $('#redeemcodes');

	if (redeemcodes.length)
	{	//generate numbers
		var codebox = $('textarea[name=codes]'), rtot = $('input[name=redeemtot]', redeemcodes), rsiz = $('input[name=redeemsiz]', redeemcodes);
		var rucl = $('input[name=redeemucl]', redeemcodes), rlcl = $('input[name=redeemlcl]', redeemcodes);
		var rnum = $('input[name=redeemnum]', redeemcodes), rsym = $('input[name=redeemsym]', redeemcodes);

		$('input.button', redeemcodes).click(function(e)
		{
			e.preventDefault();
			var pool = ( rucl ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '' ) + ( rlcl.is(':checked') ? 'abcdefghijklmnopqrstuvwxyz' : '' ) + ( rnum.is(':checked') ? '1234567890' : '' ) + ( rsym.is(':checked') ? '~!@#$%^&*()_+-=[]{}|;:,./<>?' : '' );
			var i, code, regex, codes = codebox.val(), todo = parseInt(rtot.val()), size = parseInt(rsiz.val()), plen = pool.length, fix = new RegExp("[.*+?|()\\[\\]{}\\\\]", 'g');

			while (todo > 0 && size > 0)
			{
				code = '';//reset string
				for (i = 0; i < size; i++) code += pool.charAt(Math.floor(Math.random() * plen));
				regex = new RegExp('^' + code.replace(fix, "\\$&") + '$', 'm');

				if (!regex.test(codes))
				{	//new, add it
					codes += ( (codes == '') ? '' : '\n' ) + code;
					todo--;
				}
			}

			codebox.val(codes);
		});
	}
});
</script>
<?php
}

$sizetext = 'credits_size_' . ( $vbulletin->options['credits_size_words'] ? 'word' : 'char' );
$sizetext = array($vbphrase[$sizetext . 's'], $vbphrase[$sizetext]);//size mult labels

$vbulletin->input->clean_array_gpc('r', array(
	'perpage' => TYPE_UINT,
	'startat' => TYPE_UINT
));

// ##################### Start Currency Delete ###################################

if ($_REQUEST['do'] == 'delete_currency')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'currencyid'	=> TYPE_UINT,
		'confirm'		=> TYPE_BOOL
	));

	$currency =& $vbulletin->vbcredits['currency'][$vbulletin->GPC['currencyid']];

	if ($vbulletin->GPC['confirm'])
	{	//drop column if not blacklisted
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_transaction WHERE currencyid = " . $vbulletin->GPC['currencyid']);
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_event WHERE currencyid = " . $vbulletin->GPC['currencyid']);
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_conversion WHERE currencyid = " . $vbulletin->GPC['currencyid']);
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_redemption WHERE currencyid = " . $vbulletin->GPC['currencyid']);
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_currency WHERE currencyid = " . $vbulletin->GPC['currencyid']);
		if (!$currency['blacklist']) $db->query_write("ALTER TABLE `" . ( $currency['useprefix'] ? TABLE_PREFIX : '' ) . $currency['table'] . "` DROP `" . $currency['column'] . "`");
		vbcredits_cache();

		define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=currencies');
		print_stop_message('credits_currency_deleted');
	}
	else print_delete_confirmation('credits_currency', $vbulletin->GPC['currencyid'], 'credits_admin', 'delete_currency', 'currency', array('confirm' => 1), ( $currency['blacklist'] ? 'As an imported currency, the existing database fields and data will be left intact.' : 'As a custom currency, the database fields and data will be permanantly deleted.' ));
}

// ##################### Start Currency Transfer ###################################

if ($_REQUEST['do'] == 'transfer_currency')
{
	$vbulletin->input->clean_array_gpc('r', $set = array(
		'currencyid'		=> TYPE_UINT,
		'convert_table'		=> TYPE_NOHTML,
		'convert_useprefix'	=> TYPE_BOOL,
		'convert_column'	=> TYPE_NOHTML,
		'convert_userid'	=> TYPE_BOOL,
		'convert_usercol'	=> TYPE_NOHTML
	));

	$transfer = array_intersect_key($vbulletin->GPC, $set);

	foreach (array('convert_table', 'convert_column', 'convert_usercol') AS $field)
	{	//clean these fields as database types
		$transfer[$field] = preg_replace('/\W/i', '', $transfer[$field]);
	}

	$tablename = ( $transfer['convert_useprefix'] ? TABLE_PREFIX : '' ) . $transfer['convert_table'];

	if (!$currency =& $vbulletin->vbcredits['currency'][$transfer['currencyid']])
	{
		print_stop_message('credits_missing_field');
	}
	if (!$found = $db->query_first("SHOW TABLES LIKE '$tablename'"))
	{	//does table exist?
		print_stop_message('credits_missing_database');
	}
	if (!$found = $db->query_first("SHOW COLUMNS FROM $tablename LIKE '" . $transfer['convert_usercol'] . "'") OR !$found = $db->query_first("SHOW COLUMNS FROM $tablename LIKE '" . $transfer['convert_column'] . "'"))
	{	//does usercol and column exist?
		print_stop_message('credits_missing_database');
	}

 	$curwh = ( $currency['userid'] ? 'userid' : 'username' );
	$which = ( $transfer['convert_userid'] ? 'userid' : 'username' );
	$db->query_write("UPDATE " . ( $currency['useprefix'] ? TABLE_PREFIX : '' ) . $currency['table'] . " AS c, $tablename AS t, " . TABLE_PREFIX . "user AS u SET c." . $currency['column'] . " = c." . $currency['column'] . " + t." . $transfer['convert_column'] . " WHERE u.$curwh = c.$curwh AND u.$which = t.$which");

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=currencies');
	print_stop_message('credits_currency_transferred');
}

// ##################### Start Currency Save ###################################

if ($_REQUEST['do'] == 'save_currency')
{
	$vbulletin->input->clean_array_gpc('r', $set = array(
		'currencyid'	=> TYPE_UINT,
		'title'			=> TYPE_STR,
		'description'	=> TYPE_NOHTML,
		'displayorder'	=> TYPE_UINT,
		'table'			=> TYPE_NOHTML,
		'useprefix'		=> TYPE_BOOL,
		'column'		=> TYPE_NOHTML,
		'userid'		=> TYPE_BOOL,
		'usercol'		=> TYPE_NOHTML,
		'decimals'		=> TYPE_UINT,
		'negative'		=> TYPE_UINT,
		'privacy'		=> TYPE_UINT,
		'maxtime'		=> TYPE_STR,
		'earnmax'		=> TYPE_STR,
		'value'			=> TYPE_NUM,
		'inbound'		=> TYPE_BOOL,
		'outbound'		=> TYPE_BOOL
	));

	$currency = array_intersect_key($vbulletin->GPC, $set);
	$nulls = array();

	foreach (array('negative', 'privacy') AS $field)
	{	//nonexistant value
		if ($currency[$field] > 2) $currency[$field] = 2;
	}
	foreach (array('maxtime', 'earnmax') AS $field)
	{	//if theyre blank they should be null
		if (!is_numeric($currency[$field])) $nulls[] = $field . ' = null';
	}
	foreach (array('table', 'column', 'usercol') AS $field)
	{	//clean these fields as database types
		$currency[$field] = preg_replace('/\W/i', '', $currency[$field]);
	}
	foreach (array('title', 'table', 'column', 'usercol') AS $field)
	{	//missing fields
		if (empty($currency[$field])) print_stop_message('credits_missing_field');
	}

	$tablename = ( $currency['useprefix'] ? TABLE_PREFIX : '' ) . $currency['table'];
	if ($currency['currencyid']) $oldcur =& $vbulletin->vbcredits['currency'][$currency['currencyid']];

	if (!$found = $db->query_first("SHOW TABLES LIKE '$tablename'"))
	{	//does table exist?
		print_stop_message('credits_missing_database');
	}
	if (!$found = $db->query_first("SHOW COLUMNS FROM $tablename LIKE '" . $currency['usercol'] . "'"))
	{	//does usercol exist?
		print_stop_message('credits_missing_database');
	}
	if (!$currency['currencyid'] OR $colchange = ($oldcur['column'] != $currency['column']))
	{	//deal with desired table column
		$found = $db->query_first("SHOW COLUMNS FROM $tablename LIKE '" . $currency['column'] . "'");

		if (!$currency['blacklist'] = intval(!empty($found)))
		{	//create or switch custom columns
			if ($colchange AND !$oldcur['blacklist']) $db->query_write("ALTER TABLE `$tablename` CHANGE `" . $oldcur['column'] . "` `" . $currency['column'] . "` DOUBLE NOT NULL DEFAULT '0'");
			else $db->query_write("ALTER TABLE `$tablename` ADD `" . $currency['column'] . "` DOUBLE NOT NULL DEFAULT '0'");
		}	//switch to existing column - drop custom
		else if ($colchange AND !$oldcur['blacklist']) $db->query_write("ALTER TABLE `$tablename` DROP `" . $oldcur['column'] . "`");
	}
	//update and fix the nulls
	$db->query_write(fetch_query_sql($currency, 'credits_currency', ( $currency['currencyid'] ? "WHERE currencyid = " . $currency['currencyid'] : '' )));
	if (sizeof($nulls) AND ($currency['currencyid'] OR $currency['currencyid'] = $db->insert_id())) $db->query_write("UPDATE " . TABLE_PREFIX . "credits_currency SET " . implode(', ', $nulls) . " WHERE currencyid = " . $currency['currencyid']);
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=currencies');
	print_stop_message('credits_currency_saved');
}

// ##################### Start Conversion Delete ###################################

if ($_REQUEST['do'] == 'delete_conversion')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'conversionid'	=> TYPE_UINT,
		'confirm'		=> TYPE_BOOL
	));

	if ($vbulletin->GPC['confirm'])
	{	//keep transactions
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_conversion WHERE conversionid = " . $vbulletin->GPC['conversionid']);
		vbcredits_cache();

		define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=conversions');
		print_stop_message('credits_conversion_deleted');
	}
	else print_delete_confirmation('credits_conversion', $vbulletin->GPC['conversionid'], 'credits_admin', 'delete_conversion', 'conversion', array('confirm' => 1), '', 'currencyid');
}

// ##################### Start Conversion Save ###################################

if ($_REQUEST['do'] == 'save_conversion')
{
	$vbulletin->input->clean_array_gpc('r', $set = array(
		'conversionid'			=> TYPE_UINT,
		'enabled'				=> TYPE_BOOL,
		'currencyid'			=> TYPE_UINT,
		'minimum'				=> TYPE_UNUM,
		'tiered'				=> TYPE_BOOL,
		'usd'					=> TYPE_UNUM,
		'gbp'					=> TYPE_UNUM,
		'eur'					=> TYPE_UNUM,
		'aud'					=> TYPE_UNUM,
		'cad'					=> TYPE_UNUM,
		'tax'					=> TYPE_BOOL,
		'shipping'				=> TYPE_UINT,
		'ccbillsubid'			=> TYPE_NOHTML,
		'twocheckout_prodid'	=> TYPE_NOHTML
	));

	$total = 0;
	$cost = array();
	$converse = array_intersect_key($vbulletin->GPC, $set);

	foreach (array('usd', 'gbp', 'eur', 'aud', 'cad') AS $cur)
	{	//move currencies amounts to cost
		$total += $cost[$cur] = $converse[$cur];
		unset($converse[$cur]);
	}
	if (!$currency =& $vbulletin->vbcredits['currency'][$converse['currencyid']] OR !$total)
	{
		print_stop_message('credits_missing_field');
	}
	foreach (array('tax', 'shipping', 'ccbillsubid', 'twocheckout_prodid') AS $field)
	{	//move values to cost
		$cost[$field] = $converse[$field];
		unset($converse[$field]);
	}

	$converse['cost'] = serialize($cost);
	$db->query_write(fetch_query_sql($converse, 'credits_conversion', ( $converse['conversionid'] ? "WHERE conversionid = " . $converse['conversionid'] : '' )));
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=conversions');
	print_stop_message('credits_conversion_saved');
}

// ##################### Start Redemption Delete ###################################

if ($_REQUEST['do'] == 'delete_redemption')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'redemptionid'	=> TYPE_UINT,
		'confirm'		=> TYPE_BOOL
	));

	if ($vbulletin->GPC['confirm'])
	{	//keep transactions
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_redemption WHERE redemptionid = " . $vbulletin->GPC['redemptionid']);
		vbcredits_cache();

		define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=redemptions');
		print_stop_message('credits_redemption_deleted');
	}
	else print_delete_confirmation('credits_redemption', $vbulletin->GPC['redemptionid'], 'credits_admin', 'delete_redemption', 'redemption', array('confirm' => 1));
}

// ##################### Start Redemption Save ###################################

if ($_REQUEST['do'] == 'save_redemption')
{
	$vbulletin->input->clean_array_gpc('r', $set = array(
		'redemptionid'	=> TYPE_UINT,
		'title'			=> TYPE_NOHTML,
		'description'	=> TYPE_NOHTML,
		'enabled'		=> TYPE_BOOL,
		'startdate'		=> TYPE_UNIXTIME,
		'enddate'		=> TYPE_UNIXTIME,
		'usergroups'	=> TYPE_ARRAY_UINT,
		'currencyid'	=> TYPE_UINT,
		'amount'		=> TYPE_NUM,
		'maxtimes'		=> TYPE_UINT,
		'maxusers'		=> TYPE_UINT,
		'codes'			=> TYPE_NOHTML,
		'redirect'		=> TYPE_NOHTML
	));

	$redeem = array_intersect_key($vbulletin->GPC, $set);

	if (!$currency =& $vbulletin->vbcredits['currency'][$redeem['currencyid']])
	{
		print_stop_message('credits_missing_field');
	}
	foreach (array('title', 'amount', 'codes') AS $field)
	{	//missing fields
		if (empty($redeem[$field])) print_stop_message('credits_missing_field');
	}

	$redeem['usergroups'] = serialize($redeem['usergroups']);
	$redeem['codes'] = serialize(array_diff(array_map('trim', explode("\n", $redeem['codes'])), array('')));
	$db->query_write(fetch_query_sql($redeem, 'credits_redemption', ( $redeem['redemptionid'] ? "WHERE redemptionid = " . $redeem['redemptionid'] : '' )));
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=redemptions');
	print_stop_message('credits_redemption_saved');
}

// ##################### Start Display Save ###################################

if ($_REQUEST['do'] == 'save_display')
{
	$vbulletin->input->clean_array_gpc('r', $set = array(
		'displayid'		=> TYPE_STR,
		'title'			=> TYPE_NOHTML,
		'description'	=> TYPE_NOHTML,
		'enabled'		=> TYPE_BOOL,
		'currencies'	=> TYPE_ARRAY_UINT,
		'combine'		=> TYPE_NOHTML,
		'combined'		=> TYPE_ARRAY_UINT,
		'comdec'		=> TYPE_UINT,
		'main_template'	=> TYPE_NOHTML,
		'wrap_main'		=> TYPE_BOOL,
		'row_template'	=> TYPE_NOHTML,
		'hookname'		=> TYPE_NOHTML,
		'customhook'	=> TYPE_NOHTML,
		'showpages'		=> TYPE_NOHTML
	));

	$display = array_intersect_key($vbulletin->GPC, $set);
	$display['showpages'] = preg_replace('/[^\w,]/i', '', $display['showpages']);

	foreach (array('displayid', 'main_template', 'row_template', 'hookname', 'customhook') AS $field)
	{	//clean these fields as database types
		$display[$field] = preg_replace('/\W/i', '', $display[$field]);
	}
	foreach (array('currencies', 'combined') AS $field)
	{	//serialize the arrays
		$display[$field] = serialize($display[$field]);
	}
	foreach (array('displayid', 'title', 'row_template') AS $field)
	{	//missing fields
		if (empty($display[$field])) print_stop_message('credits_missing_field');
	}
//check if templates exist?
	$found = $db->query_first("SELECT displayid FROM " . TABLE_PREFIX . "credits_display WHERE displayid = '" . $db->escape_string($display['displayid']) . "'");
	$db->query_write(fetch_query_sql($display, 'credits_display', ( $found['displayid'] ? "WHERE displayid = '" . $db->escape_string($display['displayid']) . "'" : '' )));
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=displays');
	print_stop_message('credits_display_saved');
}

// ##################### Start Action Save ###################################

if ($_REQUEST['do'] == 'save_action')
{
	$vbulletin->input->clean_array_gpc('r', $set = array(
		'actionid'		=> TYPE_STR,
		'title'			=> TYPE_NOHTML,
		'description'	=> TYPE_NOHTML,
		'multiplier'	=> TYPE_UINT,
		'mult_single'	=> TYPE_NOHTML,
		'mult_plural'	=> TYPE_NOHTML,
		'category'		=> TYPE_NOHTML,
		'parent'		=> TYPE_NOHTML,
		'global'		=> TYPE_BOOL,
		'revert'		=> TYPE_BOOL,
		'cancel'		=> TYPE_BOOL,
		'rebuild'		=> TYPE_BOOL,
		'referformat'	=> TYPE_STR
	));

	$action = array_intersect_key($vbulletin->GPC, $set);
	$action['actionid'] = preg_replace('/\W/i', '', $action['actionid']);

	foreach (array('actionid', 'title') AS $field)
	{	//missing fields
		if (empty($action[$field])) print_stop_message('credits_missing_field');
	}

	if (!$action['multiplier']) $action['multiplier'] = '';
	else if ($action['multiplier'] == 2) $action['multiplier'] = 'Size';
	else $action['multiplier'] = ( (!$action['currency'] = intval($action['multiplier'] == 3)) ? $action['mult_plural'] . '|' . $action['mult_single'] : '' );

	if ($action['mult_single'] AND !$action['mult_plural'])
	{	//needs either just plural or both
		print_stop_message('credits_multplier_match');
	}

	unset($action['mult_single'], $action['mult_plural']);
	$found = $db->query_first("SELECT actionid FROM " . TABLE_PREFIX . "credits_action WHERE actionid = '" . $db->escape_string($action['actionid']) . "'");
	$db->query_write(fetch_query_sql($action, 'credits_action', ( $found['actionid'] ? "WHERE actionid = '" . $db->escape_string($action['actionid']) . "'" : '' )));
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=actions');
	print_stop_message('credits_action_saved');
}

// ##################### Start Event Delete ###################################

if ($_REQUEST['do'] == 'delete_event')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'eventids'		=> TYPE_ARRAY_UINT,
		'eventid'		=> TYPE_UINT,
		'confirm'		=> TYPE_BOOL
	));

	if ($vbulletin->GPC['confirm'])
	{
		$where = ( $vbulletin->GPC['eventid'] ? '= ' . $vbulletin->GPC['eventid'] : 'IN(' . implode(', ', $vbulletin->GPC['eventids']) . ')' );
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_transaction WHERE eventid $where");
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_event WHERE eventid $where");
		vbcredits_cache();

		define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=events');
		print_stop_message('credits_event_deleted');
	}
	else print_delete_confirmation('credits_event', $vbulletin->GPC['eventid'], 'credits_admin', 'delete_event', 'event', array('confirm' => 1), '', 'actionid');
}

// ##################### Start Event Save ###################################

if ($_REQUEST['do'] == 'save_event')
{
	$vbulletin->input->clean_array_gpc('r', $set = array(
		'eventid'		=> TYPE_UINT,
		'currencyid'	=> TYPE_UINT,
		'actionid'		=> TYPE_STR,
		'usergroups'	=> TYPE_ARRAY_UINT,
		'forums'		=> TYPE_ARRAY_UINT,
		'owner'			=> TYPE_STR,
		'enabled'		=> TYPE_BOOL,
		'moderate'		=> TYPE_BOOL,
		'main_add'		=> TYPE_NUM,
		'negative'		=> TYPE_BOOL,
		'upperrand'		=> TYPE_STR,
		'main_sub'		=> TYPE_NUM,
		'mult_add'		=> TYPE_NUM,
		'mult_sub'		=> TYPE_NUM,
		'curtarget'		=> TYPE_UINT,
		'delay'			=> TYPE_UINT,
		'frequency'		=> TYPE_UINT,
		'maxtime'		=> TYPE_STR,
		'applymax'		=> TYPE_STR,
		'multmin'		=> TYPE_STR,
		'multmax'		=> TYPE_STR,
		'minaction'		=> TYPE_UINT,
		'alert'			=> TYPE_BOOL
	));

	$event = array_intersect_key($vbulletin->GPC, $set);
	$action =& $vbulletin->vbcredits['action'][$event['actionid']];
	$currency =& $vbulletin->vbcredits['currency'][$event['currencyid']];
	$nulls = array();

	$event['upperrand'] = preg_replace('/[^\d\.]/i', '', $event['upperrand']);
	if (substr($event['upperrand'], -1) == '.') $event['upperrand'] = substr($event['upperrand'], 0, -1);

	foreach (array('main_sub', 'mult_sub') AS $field)
	{	//these ones are normally negative
		$event[$field] *= -1;
	}
	if ($event['negative'])
	{
		foreach (array('main_add', 'mult_add', 'main_sub', 'mult_sub') AS $field)
		{	//charging, so flip everything
			$event[$field] *= -1;
		}
	}
	foreach (array('usergroups', 'forums') AS $field)
	{	//serialize the arrays
		$event[$field] = serialize($event[$field]);
	}
	foreach (array('owner', 'maxtime', 'applymax', 'multmin', 'multmax') AS $field)
	{	//if theyre blank they should be null
		if (!is_numeric($event[$field])) $nulls[] = $field . ' = null';
	}
	if (empty($action) OR empty($currency))
	{
		print_stop_message('credits_missing_field');
	}

	unset($event['negative']); //not a field
	$maxval = ( $action['cancel'] ? 2 : 1 );
	if ($event['frequency'] < 1) $event['frequency'] = 1;
	if ($event['minaction'] > $maxval) $event['minaction'] = $maxval;
	$db->query_write(fetch_query_sql($event, 'credits_event', ( $event['eventid'] ? "WHERE eventid = " . $event['eventid'] : '' )));
	if (sizeof($nulls) AND ($event['eventid'] OR $event['eventid'] = $db->insert_id())) $db->query_write("UPDATE " . TABLE_PREFIX . "credits_event SET " . implode(', ', $nulls) . " WHERE eventid = " . $event['eventid']);
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=events');
	print_stop_message('credits_event_saved');
}

// ##################### Start Currency Edit ###################################

if ($_REQUEST['do'] == 'edit_currency')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'currencyid'	=> TYPE_UINT,
		'docopy'		=> TYPE_BOOL
	));

	print_form_header('credits_admin', 'save_currency');
	print_column_style_code(array('width: 70%', 'width: 30%'));
	$currency = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "credits_currency WHERE currencyid = " . $vbulletin->GPC['currencyid']);
	if ($vbulletin->GPC['docopy']) unset($currency['currencyid']);
	$installed = fetch_product_list(true);
	$transfer = array();

	if (empty($currency))
	{
		$currency = array(
			'table' => 'user',
			'useprefix' => 1,
			'userid' => 1,
			'usercol' => 'userid',
			'decimals' => 0,
			'negative' => 2,
			'privacy' => 2,
			'value' => 1,
			'inbound' => 1,
			'outbound' => 1
		);
	}

	$convert = array(
		'' => array(
			'addon' => '',
			'table' => 'user',
			'useprefix' => 1,
			'userid' => 1,
			'usercol' => 'userid',
			'column' => ''
		)
	);

	$currencies = array( //include credits/credits_saved?
		array('title' => $vbulletin->options['dbtech_vbshop_pointsname'] . ' - Hand', 'addon' => '[DBTech] vBShop', 'productid' => 'dbtech_vbshop', 'minversion' => '1.0.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'dbtech_vbshop_points'),
		array('title' => $vbulletin->options['dbtech_vbshop_pointsname'] . ' - Bank', 'addon' => '[DBTech] vBShop', 'productid' => 'dbtech_vbshop', 'minversion' => '1.1.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'dbtech_vbshop_bank'),
		array('title' => $vbulletin->options['dbt_tt_currency'], 'addon' => '[DBTech] Triple Triad', 'productid' => '_dbtech_triple_triad', 'minversion' => '1.0.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'dbt_tt_money'),
		array('title' => $vbphrase['xperience_points'], 'addon' => 'vBExperience', 'productid' => 'vbexperience3', 'minversion' => '3.7.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'xperience'),
		array('title' => $vbphrase['xperience_points'], 'addon' => 'vBExperience', 'productid' => 'xperience38', 'minversion' => '3.8.0.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'xperience'),
		array('title' => 'Gold', 'addon' => 'vBArmy', 'productid' => 'armysystem', 'minversion' => '1.0', 'maxversion' => '', 'table' => 'as_user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'gold'),
		array('title' => $vbphrase['conquest_gold'], 'addon' => 'Realm Conquest System', 'productid' => 'conquest', 'minversion' => '1.0', 'maxversion' => '', 'table' => 'conquest_players', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'playerID', 'column' => 'pGold'),
		array('title' => $vbphrase['dbtech_vbactivity_points'], 'addon' => '[DBTech] vBActivity', 'productid' => 'dbtech_vbactivity', 'minversion' => '1.0.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'dbtech_vbactivity_points'),
		array('title' => $vbulletin->options['kbankn'], 'addon' => 'kBank', 'productid' => 'kbank', 'minversion' => '1.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'money'),
		array('title' => $vbphrase['ucs_points'], 'addon' => 'uCash', 'productid' => 'ucs', 'minversion' => '2.00 Beta 1', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'ucash'),
		array('title' => $vbphrase['ucash_points'], 'addon' => 'uCash', 'productid' => 'ucash', 'minversion' => '3.0.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'ucash'),
		array('title' => $vbphrase['vbbux_points'], 'addon' => 'vbBux', 'productid' => 'vbbuxplaza', 'minversion' => '1.5.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'vbbux'),
		array('title' => $vbphrase['vbbux_bank'], 'addon' => 'vbPlaza', 'productid' => 'vbbuxplaza', 'minversion' => '1.5.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'vbbank'),
		array('title' => $vbphrase['vbbux_points'], 'addon' => 'vbBux', 'productid' => 'vbbux_vbplaza', 'minversion' => '2.0.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'vbbux'),
		array('title' => $vbphrase['vbbux_bank'], 'addon' => 'vbPlaza', 'productid' => 'vbbux_vbplaza', 'minversion' => '2.0.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'vbbank'),
		array('title' => $inferno->lang['money'], 'addon' => 'RPG Inferno', 'productid' => '_rpg_inferno', 'minversion' => '3.0.0 Gold', 'maxversion' => '', 'table' => 'inferno_user', 'useprefix' => 0, 'userid' => 1, 'usercol' => 'user_id', 'column' => 'money'),
		array('title' => $vbphrase['gold_gold'], 'addon' => 'Gold!', 'productid' => 'gold', 'minversion' => '1.0.3', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'gold'),
		array('title' => $vbulletin->options['icashn'], 'addon' => 'ICash', 'productid' => 'Icash', 'minversion' => '1.0.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'money'),
		array('title' => $vbulletin->options['icashn'], 'addon' => 'ICash', 'productid' => 'icash', 'minversion' => '2.0.3', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'money'),
		array('title' => $vbulletin->options['ibank_moneyname'], 'addon' => 'IBank', 'productid' => 'Ibank', 'minversion' => '1.1.0', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'bankmoney'),
		array('title' => $vbphrase['vbpoints_points'], 'addon' => 'vBPoints', 'productid' => 'vbpoints', 'minversion' => '1.0.0 B 4', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'vbpoints'),
		array('title' => $vbulletin->options['nex_points_currency_names'] . ' - Hand', 'addon' => 'Nexia\'s POINTS system', 'productid' => 'nex_points', 'minversion' => '1.0.1', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'nex_points_hand'),
		array('title' => $vbulletin->options['nex_points_currency_names'] . ' - Bank', 'addon' => 'Nexia\'s POINTS system', 'productid' => 'nex_points', 'minversion' => '1.0.1', 'maxversion' => '', 'table' => 'user', 'useprefix' => 1, 'userid' => 1, 'usercol' => 'userid', 'column' => 'nex_points_bank')
	);

	($hook = vBulletinHook::fetch_hook('credits_currency_wizard')) ? eval($hook) : false;
	require_once(DIR . '/includes/adminfunctions_template.php');

	foreach ($currencies AS $check)
	{	//check each one if installed and valid
		if (!array_key_exists($check['productid'], $installed)) continue;
		$sys_version = fetch_version_array($installed[$check['productid']]['version']);

		if ($check['minversion'])
		{
			$dep_version = fetch_version_array($check['minversion']);

			for ($i = 0; $i <= 5; $i++)
			{	//installed version too old
				if ($sys_version["$i"] < $dep_version["$i"]) continue 2;
				else if ($sys_version["$i"] > $dep_version["$i"]) break;
			}
		}
		if ($check['maxversion'])
		{
			$dep_version = fetch_version_array($check['maxversion']);
			$all_equal = true;

			for ($i = 0; $i <= 5; $i++)
			{	//installed version is too new
				if ($sys_version["$i"] > $dep_version["$i"]) continue 2;
				else if ($sys_version["$i"] < $dep_version["$i"]) { $all_equal = false; break; }
				else if ($sys_version["$i"] != $dep_version["$i"]) $all_equal = false;
			}
			//installed is same as max, which is first invalid
			if ($all_equal) continue;
		}
		foreach ($vbulletin->vbcredits['currency'] AS $vbccur)
		{	//is it already loaded into vbcredits?
			if ($vbccur['table'] == $check['table'] AND $vbccur['useprefix'] == $check['useprefix'] AND $vbccur['column'] == $check['column']) continue 2;
		}
		//if we got this far, add it to convert array
		$convert[$check['title'] . ( $check['addon'] ? ' (' . $check['addon'] . ')' : '' )] = array_intersect_key($check, array('table' => true, 'useprefix' => true, 'userid' => true, 'usercol' => true, 'column' => true));
	}

	print_table_header( $currency['currencyid'] ? construct_phrase($vbphrase['x_y_id_z'], $vbphrase['currency'], $currency['title'], $currency['currencyid']) : $vbphrase['credits_currency_new'] );
	if (sizeof($convert) > 1 AND !$currency['currencyid']) print_select_row($vbphrase['credits_currency_import'], 'currency', array_keys($convert));

	construct_hidden_code('currencyid', $currency['currencyid']);
	print_input_row($vbphrase['title'], 'title', $currency['title']);
	print_textarea_row($vbphrase['description'], 'description', $currency['description']);

	print_input_row($vbphrase['display_order'], 'displayorder', $currency['displayorder']);

	print_input_row($vbphrase['credits_currency_table'], 'table', $currency['table']);
	print_yes_no_row($vbphrase['credits_currency_prefix'], 'useprefix', $currency['useprefix']);
	print_input_row($vbphrase['credits_currency_column'], 'column', $currency['column']);

	print_yes_no_row($vbphrase['credits_currency_userid'], 'userid', $currency['userid']);
	print_input_row($vbphrase['credits_currency_usercol'], 'usercol', $currency['usercol']);

	print_input_row($vbphrase['credits_currency_decimals'], 'decimals', $currency['decimals']);

	$negatives = array($vbphrase['credits_currency_negreset'], $vbphrase['credits_currency_negshow'], $vbphrase['credits_currency_negallow']);
	print_radio_row($vbphrase['credits_currency_negative'], 'negative', $negatives, $currency['negative']);

	$privacies = array($vbphrase['credits_currency_privspecial'], $vbphrase['credits_currency_privself'], $vbphrase['credits_currency_privall']);
	print_radio_row($vbphrase['credits_currency_privacy'], 'privacy', $privacies, $currency['privacy']);

	print_input_row($vbphrase['credits_currency_earnmax'], 'earnmax', $currency['earnmax']);
	print_input_row($vbphrase['credits_currency_maxtime'], 'maxtime', $currency['maxtime']);

	print_input_row($vbphrase['credits_currency_value'], 'value', $currency['value']);
	print_yes_no_row($vbphrase['credits_currency_inbound'], 'inbound', $currency['inbound']);
	print_yes_no_row($vbphrase['credits_currency_outbound'], 'outbound', $currency['outbound']);

	print_submit_row();

	$advanced = array('convert_table', 'convert_useprefix', 'convert_userid', 'convert_usercol', 'convert_column', 'table', 'useprefix', 'userid', 'usercol', 'earnmax', 'maxtime', 'value', 'inbound', 'outbound');
	print_advanced_toggle();

	if ($currency['currencyid'])
	{
		$convert['']['table'] = $convert['']['usercol'] = '';//remove these from the blank

		foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $concur)
		{	//add the other currencies to the list for transfer
			if ($currencyid != $currency['currencyid'])
			{	//dont add the current one
				$convert[$concur['title']] = array(
					'table' => $concur['table'],
					'useprefix' => $concur['useprefix'],
					'userid' => $concur['userid'],
					'usercol' => $concur['usercol'],
					'column' => $concur['column']
				);
			}
		}

		print_form_header('credits_admin', 'transfer_currency');
		print_column_style_code(array('width: 70%', 'width: 30%'));
		print_table_header($vbphrase['credits_currency_transfer']);
		construct_hidden_code('currencyid', $currency['currencyid']);
		print_select_row($vbphrase['credits_currency_transcur'], 'currency', array_keys($convert));
		print_input_row($vbphrase['credits_currency_table'], 'convert_table');
		print_yes_no_row($vbphrase['credits_currency_prefix'], 'convert_useprefix', 1);
		print_input_row($vbphrase['credits_currency_column'], 'convert_column');
		print_yes_no_row($vbphrase['credits_currency_userid'], 'convert_userid', 1);
		print_input_row($vbphrase['credits_currency_usercol'], 'convert_usercol');
		print_submit_row($vbphrase['credits_transfer']);
	}
	foreach ($convert AS $ctitle => $info)
	{	//prepare json output for js to process
		$jqobj = array();
		$info['title'] = $info['description'] = '';

		if (preg_match('/^(.*) \((.*)\)$/U', $ctitle, $match))
		{	//get title and description
			$info['title'] = $match[1];
			$info['description'] = 'From ' . $match[2];
		}
		else if ($ctitle) $info['title'] = $ctitle;

		foreach ($info AS $field => $value) $jqobj[] = ( $currency['currencyid'] ? 'convert_' : '' ) . $field . ': \'' . addslashes($value) . '\'';
		$transfer[] = '{ ' . implode(', ', $jqobj) . ' }';		
	}
}

// ##################### Start Conversion Edit ###################################

if ($_REQUEST['do'] == 'edit_conversion')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'conversionid'	=> TYPE_UINT,
		'docopy'		=> TYPE_BOOL
	));

	print_form_header('credits_admin', 'save_conversion');
	print_column_style_code(array('width: 70%', 'width: 30%'));
	$converse = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "credits_conversion WHERE conversionid = " . $vbulletin->GPC['conversionid']);
	if ($converse['cost']) $converse['cost'] = unserialize($converse['cost']);
	if ($vbulletin->GPC['docopy']) unset($converse['conversionid']);

	if (empty($converse))
	{	//some defaults
		$converse = array(
			'enabled' => 1
		);
	}

	print_table_header( $converse['conversionid'] ? construct_phrase($vbphrase['x_y_id_z'], 'Conversion', '', $converse['conversionid']) : 'Create New Conversion' );
	construct_hidden_code('conversionid', $converse['conversionid']);
	print_yes_no_row('Active', 'enabled', $converse['enabled']);

	$currencies = array();
	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency) $currencies[$currencyid] = $currency['title'];
	print_select_row('Currency<dfn>Make sure you created a Purchase event with this currency.</dfn>', 'currencyid', $currencies, $converse['currencyid']);
	print_input_row('Minimum<dfn>The desired amount of currency to trigger this conversion. The lowest overall minimum must be reached to accept a purchase.</dfn>', 'minimum', $converse['minimum']);
	print_yes_no_row('Tiered<dfn>When enabled, each applicable conversion will affect the prices within their bracket, otherwise the entire amount will use these prices.</dfn>', 'tiered', $converse['tiered']);

	print_input_row('U.S. Dollars<dfn>The price per 1 currency in USD, or blank to disallow.</dfn>', 'usd', ( $converse['cost']['usd'] ? $converse['cost']['usd'] : '' ));
	print_input_row('Pounds Sterling<dfn>The price per 1 currency in GBP, or blank to disallow.</dfn>', 'gbp', ( $converse['cost']['gbp'] ? $converse['cost']['gbp'] : '' ));
	print_input_row('Euros<dfn>The price per 1 currency in EUR, or blank to disallow.</dfn>', 'eur', ( $converse['cost']['eur'] ? $converse['cost']['eur'] : '' ));
	print_input_row('Australian Dollars<dfn>The price per 1 currency in AUD, or blank to disallow.</dfn>', 'aud', ( $converse['cost']['aud'] ? $converse['cost']['aud'] : '' ));
	print_input_row('Canadian Dollars<dfn>The price per 1 currency in CAD, or blank to disallow.</dfn>', 'cad', ( $converse['cost']['cad'] ? $converse['cost']['cad'] : '' ));

	$shipping = array(0 => 'None', 2 => 'Optional', 4 => 'Required');
	print_yes_no_row('PayPal Tax<dfn>For PayPal only if the buyer lives in an area you need to apply a sales tax to.</dfn>', 'tax', $converse['cost']['tax']);
	print_radio_row('PayPal Shipping Address<dfn>For PayPal only if you wish for the buyer to include their shipping address.</dfn>', 'shipping', $shipping, $converse['cost']['shipping']);
	print_input_row('CCBill Subscription ID<dfn>Required for CCBill only; may restrict price options.</dfn>', 'ccbillsubid', $converse['cost']['ccbillsubid']);
	print_input_row('2Checkout Product ID<dfn>Required for 2Checkout only; may restrict price options.</dfn>', 'twocheckout_prodid', $converse['cost']['twocheckout_prodid']);
	print_submit_row();

	$advanced = array('tax', 'shipping', 'ccbillsubid', 'twocheckout_prodid');
	print_advanced_toggle();
}

// ##################### Start Redemption Edit ###################################

if ($_REQUEST['do'] == 'edit_redemption')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'redemptionid'	=> TYPE_UINT,
		'docopy'		=> TYPE_BOOL
	));

	print_form_header('credits_admin', 'save_redemption');
	print_column_style_code(array('width: 70%', 'width: 30%'));
	$redeem = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "credits_redemption WHERE redemptionid = " . $vbulletin->GPC['redemptionid']);
	if ($redeem['usergroups']) $redeem['usergroups'] = unserialize($redeem['usergroups']);
	$codes = ( $redeem['codes'] ? unserialize($redeem['codes']) : array() );
	if ($vbulletin->GPC['docopy']) unset($redeem['redemptionid']);

	if (empty($redeem))
	{	//some defaults
		$redeem = array(
			'enabled' => 1,
			'startdate' => TIMENOW
		);
	}

	print_table_header( $redeem['redemptionid'] ? construct_phrase($vbphrase['x_y_id_z'], 'Redemption', $redeem['title'], $redeem['redemptionid']) : 'Create New Redemption' );
	construct_hidden_code('redemptionid', $redeem['redemptionid']);

	print_input_row($vbphrase['title'], 'title', $redeem['title']);
	print_textarea_row($vbphrase['description'], 'description', $redeem['description']);
	print_yes_no_row('Active', 'enabled', $redeem['enabled']);

	$currencies = $usergroups = array();
	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency) $currencies[$currencyid] = $currency['title'];
	foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup) $usergroups[$usergroupid] = $usergroup['title'];

	print_select_row('Currency<dfn>Make sure you created a Redeem event with this currency.</dfn>', 'currencyid', $currencies, $redeem['currencyid']);
	print_input_row('Amount<dfn>The value of the redemption that is passed to your events.</dfn>', 'amount', $redeem['amount']);

	print_time_row('Start Date<dfn>Earliest time that this redemption will be valid.</dfn>', 'startdate', $redeem['startdate']);
	print_time_row('End Date<dfn>Latest time when this redemption will expire or blank for endless.</dfn>', 'enddate', $redeem['enddate']);

	print_select_row('Usergroups<dfn>These users can trigger a redemption; the groups who can be awarded it are handled in your events. If none are selected then all users will be qualified. Ctrl/cmd+click or click+drag to select more than one.</dfn>', 'usergroups[]', $usergroups, $redeem['usergroups'], false, 8, true);
	print_textarea_row('Codes<dfn>These must be sent through the currency popup or visited at ' . $vbulletin->options['bburl'] . '/credits.php?code=[CODE] in order to trigger the redemption. Enter one single-use code per line. If you only put one code, it will be allowed as many times as allowed by the other settings. Codes are case sensitive and should be unique!<br /><br /><span id="redeemcodes">Generate <input type="text" class="bginput" size="3" name="redeemtot" /> random codes <input type="text" class="bginput" size="1" name="redeemsiz" value="8" /> characters long with:<br /><label><input type="checkbox" name="redeemucl" checked="checked" />uppercase</label> and <label><input type="checkbox" name="redeemlcl" />lowercase</label> letters, <label><input type="checkbox" name="redeemnum" checked="checked" />numbers</label>, and <label><input type="checkbox" name="redeemsym" />symbols</label> <input type="button" class="button" value="Add" /></span></dfn>', 'codes', implode("\n", $codes));
	print_input_row('Maximum Uses<dfn>The total number of times this can be redeemed at all or blank for unlimited. Whichever comes first with what is in the specific codes box.</dfn>', 'maxtimes', ( $redeem['maxtimes'] ? $redeem['maxtimes'] : '' ));
	print_input_row('Maximum Users<dfn>The total number of different users that can get this redemption or blank for unlimited. Whichever comes first with the other settings. You should set limits for frequency or repetition in your events.</dfn>', 'maxusers', ( $redeem['maxusers'] ? $redeem['maxusers'] : '' ));
	print_input_row('Redirect<dfn>The URL to send users who visit ' . $vbulletin->options['bburl'] . '/credits.php?code=[CODE] which will automatically apply to themselves only. Good for awarding for third party content, ads, etc.</dfn>', 'redirect', $redeem['redirect']);
	print_submit_row();

	$advanced = array('startdate', 'usergroups', 'maxusers', 'redirect');
	print_advanced_toggle();
}

// ##################### Start Display Edit ###################################

if ($_REQUEST['do'] == 'edit_display')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'displayid'	=> TYPE_STR
	));

	print_form_header('credits_admin', 'save_display');
	print_column_style_code(array('width: 70%', 'width: 30%'));
	$display = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "credits_display WHERE displayid = '" . $db->escape_string($vbulletin->GPC['displayid']) . "'");

	if ($display['displayid'])
	{
		construct_hidden_code('displayid', $display['displayid']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], 'Display', $display['title'], $display['displayid']));
		if ($display['currencies']) $display['currencies'] = unserialize($display['currencies']);
		if ($display['combined']) $display['combined'] = unserialize($display['combined']);
	}
	else
	{	//some defaults
		print_table_header('Create New Display');
		print_input_row('Display ID<dfn>Machine readable label made of lowercase letters and underscores.</dfn>', 'displayid', '');

		$display = array(
			'enabled' => 1,
			'comdec' => 0,
			'row_template' => 'credits_display',
			'wrap_main' => 1
		);
	}

	print_input_row($vbphrase['title'], 'title', $display['title']);
	print_textarea_row($vbphrase['description'], 'description', $display['description']);
	print_yes_no_row('Active', 'enabled', $display['enabled']);

	$currencies = array();
	$cursize = sizeof($vbulletin->vbcredits['currency']);
	if ($cursize < 2) $cursize = 2; else if ($cursize > 7) $cursize = 7;
	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency) $currencies[$currencyid] = $currency['title'];

	print_select_row($vbphrase['credits_display_currencies'], 'currencies[]', $currencies, $display['currencies'], false, $cursize, true);
	print_input_row('Combination Field<dfn>If you wish to create an extra field that combines the values of other fields, name it here.</dfn>', 'combine', $display['combine']);
	print_select_row($vbphrase['credits_display_combined'], 'combined[]', $currencies, $display['combined'], false, $cursize, true);
	print_input_row('Combination Rounding<dfn>The number of decimal points to show for the combination field.</dfn>', 'comdec', $display['comdec']);
	print_yes_no_row('Wrap Currencies with Main Template<dfn>Otherwise it will just be cached for plugin use.</dfn>', 'wrap_main', $display['wrap_main']);
	print_input_row('Main Template<dfn>This optional template can wrap the currency fields for this particular display. Without it, the fields will just be collected and displayed alone.</dfn>', 'main_template', $display['main_template']);
	print_input_row('Currency Template<dfn>This template will be used for each displayed currency.</dfn>', 'row_template', $display['row_template']);
	print_input_row('Hook Name<dfn>Name of the template hook key that the plugin for this display will attach to within the parent template. Without it, the display method will just return the rendered HTML.</dfn>', 'hookname', $display['hookname']);
	print_input_row('Hook Template<dfn>The name of the template if the hook is custom and must be inserted.</dfn>', 'customhook', $display['customhook']);
	print_input_row('Applicable Pages<dfn>Comma separated list of THIS_SCRIPT values to cache the templates for. Blank will load for all pages.</dfn>', 'showpages', $display['showpages']);
	print_submit_row();

	$advanced = array('comdec', 'wrap_main', 'main_template', 'row_template', 'hookname', 'customhook', 'showpages');
	print_advanced_toggle();
}

// ##################### Start Action Edit ###################################

if ($_REQUEST['do'] == 'edit_action')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'actionid'	=> TYPE_STR
	));

	$categories = array(); //credits_category_[varname]
	foreach (preg_grep('/^credits_category_/', array_keys($vbphrase)) AS $varname) $categories[substr($varname, 17)] = $vbphrase[$varname];
	asort($categories);

	print_form_header('credits_admin', 'save_action');
	print_column_style_code(array('width: 70%', 'width: 30%'));
	$action = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "credits_action WHERE actionid = '" . $db->escape_string($vbulletin->GPC['actionid']) . "'");

	if ($action['actionid'])
	{
		construct_hidden_code('actionid', $action['actionid']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['action'], $action['title'], $action['actionid']));
	}
	else
	{
		print_table_header('Create New Action');
		print_input_row('Action ID<dfn>Machine readable label made of lowercase letters and underscores.</dfn>', 'actionid', '');
	}

	print_input_row($vbphrase['title'], 'title', $action['title']);
	print_textarea_row($vbphrase['description'], 'description', $action['description']);

	if ($action['multiplier'] == 'Size') $mult = 2; else if ($action['currency']) $mult = 3; else if ($action['multiplier'])
	{	//figure out selected one and labels
		$multlabel = explode('|', $action['multiplier'], 2);
		$mult = 1;
	}
	if ($vbulletin->debug)
	{
		print_select_row('Category', 'category', $categories, $action['category']);
		print_input_row('Parent<dfn>The name of any main entity that this action occurs within.</dfn>', 'parent', $action['parent']);

		$multiplier = array('No multiplier', 'Single: <input type="text" class="bginput" name="mult_single" value="' . $multlabel[1] . '" size="15" /> Plural: <input type="text" class="bginput" name="mult_plural" value="' . $multlabel[0] . '" size="15" />', 'Uses content size', 'Uses currency');
		print_radio_row('Multiplier<dfn>If this action implements its magnitude or includes the direct transfer of currency, these are the labels for it. Content size uses the settings at the top of <a href="options.php?' . $vbulletin->session->vars['sessionurl'] . 'dogroup=credits_settings_action">this page</a>.</dfn>', 'multiplier', $multiplier, intval($mult));

		print_yes_no_row('Global<dfn>Otherwise this action occurs within different forums.</dfn>', 'global', $action['global']);
		print_yes_no_row('Reversable<dfn>This action can be undone later which negates the effect of the event.</dfn>', 'revert', $action['revert']);
		print_yes_no_row('Cancelable<dfn>If an event deducts more credits from a user than they have, the action can be stopped.</dfn>', 'cancel', $action['cancel']);
		print_yes_no_row('Rebuildable<dfn>This action can calculate old transactions from past records maintained in the database.</dfn>', 'rebuild', $action['rebuild']);
		print_input_row('Reference Format<dfn>If this action stores reference IDs, then this is the base URL to link to the item involved in the action. It should assume it is in the forum root and the reference ID will be appended to it.</dfn>', 'referformat', $action['referformat']);
	}
	else
	{
		construct_hidden_code('mult_single', $multlabel[1]);
		construct_hidden_code('mult_plural', $multlabel[0]);
		construct_hidden_code('multiplier', intval($mult));
		construct_hidden_code('category', $action['category']);
		construct_hidden_code('parent', $action['parent']);
		construct_hidden_code('global', $action['global']);
		construct_hidden_code('revert', $action['revert']);
		construct_hidden_code('cancel', $action['cancel']);
		construct_hidden_code('rebuild', $action['rebuild']);
		construct_hidden_code('referformat', $action['referformat']);

		if ($mult == 2) $multlabel = $sizetext;
		if ($mult == 3) $multlabel = array('Uses currency');
		print_label_row('Multiplier<dfn>If this action implements its magnitude or includes the direct transfer of currency, these are the labels for it. Content size uses the settings at the top of <a href="options.php?' . $vbulletin->session->vars['sessionurl'] . 'dogroup=credits_settings_action">this page</a>.</dfn>', ( $multlabel[1] ? 'Single: <b>' . $multlabel[1] . '</b> Plural: <b>' . $multlabel[0] . '</b>' : '<b>' . $multlabel[0] . '</b>' ));

		print_label_row('Category', '<b>' . $categories[$action['category']] . '</b>');
		print_label_row('Global<dfn>Otherwise this action occurs within different forums.</dfn>', '<b>' . ( $action['global'] ? 'Yes' : 'No' ) . '</b>');
		print_label_row('Reversable<dfn>This action can be undone later which negates the effect of the event.</dfn>', '<b>' . ( $action['revert'] ? 'Yes' : 'No' ) . '</b>');
		print_label_row('Cancelable<dfn>If an event deducts more credits from a user than they have, the action can be stopped.</dfn>', '<b>' . ( $action['cancel'] ? 'Yes' : 'No' ) . '</b>');
		print_label_row('Rebuildable<dfn>This action can calculate old transactions from past records maintained in the database.</dfn>', '<b>' . ( $action['rebuild'] ? 'Yes' : 'No' ) . '</b>');
	}

	print_submit_row();

	if ($vbulletin->debug)
	{
		$advanced = array('parent', 'referformat');
		print_advanced_toggle();
	}
	if ($action['actionid'])
	{	//credits_action_[label]_[varname]
		require_once(DIR . '/includes/adminfunctions_options.php');
		require_once(DIR . '/includes/functions_misc.php');
		$setcache = array();

		//needed to be queries here for some reason
		$settingphrase = array();
		$phrases = $db->query_read("
			SELECT varname, text
			FROM " . TABLE_PREFIX . "phrase
			WHERE fieldname = 'vbsettings' AND
				languageid IN(-1, 0, " . LANGUAGEID . ")
			ORDER BY languageid ASC
		");
		while($phrase = $db->fetch_array($phrases))
		{
			$settingphrase["$phrase[varname]"] = $phrase['text'];
		}

		$settings = $db->query_read("
			SELECT * FROM " . TABLE_PREFIX . "setting
			WHERE varname LIKE 'credits_action_" . $action['actionid'] . "_%'
			ORDER BY displayorder
		");

		if ($db->num_rows($settings))
		{
			echo '<script type="text/javascript" src="../clientscript/vbulletin_cpoptions_scripts.js"></script>';
			print_form_header('options', 'dooptions', false, true, 'optionsform', '90%', '', true, 'post" onsubmit="return count_errors()');
			construct_hidden_code('dogroup', 'credits_settings_action');
			print_column_style_code(array('width: 45%', 'width: 55%'));
			echo "<thead>\r\n";
			print_table_header($action['title'] . ' Action Options');
			echo "</thead>\r\n";
			$bgcounter = 1;

			while ($setting = $db->fetch_array($settings))
			{
				if (!$setting['advanced'] AND !empty($setting['varname']))
				{
					print_setting_row($setting, $settingphrase);
				}
			}

			print_submit_row();

			?>
			<div id="error_output" style="font: 10pt courier new"></div>
			<script type="text/javascript">
			<!--
			var error_confirmation_phrase = "<?php echo $vbphrase['error_confirmation_phrase']; ?>";
			//-->
			</script>
			<script type="text/javascript" src="../clientscript/vbulletin_settings_validate.js"></script>
			<?php
		}

		$db->free_result($settings);
	}
}

/// ##################### Start Event Edit ###################################

if ($_REQUEST['do'] == 'edit_event')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'eventid'	=> TYPE_UINT,
		'actionid'	=> TYPE_STR,
		'docopy'	=> TYPE_BOOL
	));

	print_form_header('credits_admin', 'save_event');
	print_column_style_code(array('width: 70%', 'width: 30%'));
	$event = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "credits_event WHERE eventid = " . $vbulletin->GPC['eventid']);
	$negative = 0;

	//actionid doesnt exist? error
	$action =& $vbulletin->vbcredits['action'][( $event['actionid'] ? $event['actionid'] : $vbulletin->GPC['actionid'] )];
	
	if ($event['eventid'])
	{
		if ($vbulletin->GPC['docopy']) unset($event['eventid']);
		if ($event['usergroups']) $event['usergroups'] = unserialize($event['usergroups']);
		if ($event['forums']) $event['forums'] = unserialize($event['forums']);

		if ($event['main_add'] < 0 OR (!$event['main_add'] AND $event['mult_add'] < 0))
		{	//detect and set when charging
			$negative = 1;

			foreach (array('main_add', 'mult_add', 'main_sub', 'mult_sub') AS $field)
			{	//charging, so flip everything
				$event[$field] *= -1;
			}
		}
		foreach (array('main_sub', 'mult_sub') AS $field)
		{	//these ones are normally negative
			$event[$field] *= -1;
		}
	}
	if (empty($event))
	{
		$event = array(
			'enabled' => 1,
			'main_add' => 0,
			'upperrand' => 0,
			'main_sub' => 0,
			'mult_add' => 0,
			'mult_sub' => 0,
			'minaction' => 0,
			'curtarget' => 0,
			'delay' => 0,
			'frequency' => 1,
			'alert' => $action['currency']
		);
	}

	print_table_header( $event['eventid'] ? construct_phrase($vbphrase['x_y_id_z'], 'Event', '', $event['eventid']) : 'Create New Event' );
	construct_hidden_code('eventid', $event['eventid']);
	construct_hidden_code('actionid', $action['actionid']);
	print_yes_no_row('Active', 'enabled', $event['enabled']);
	print_label_row('Action<dfn>Determines the available options for this event.</dfn>', '<b>' . $action['title'] . '</b><dfn>' . $action['description'] . '</dfn>');

	$currencies = $usergroups = array();
	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency) $currencies[$currencyid] = $currency['title'];
	foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup) $usergroups[$usergroupid] = $usergroup['title'];
	print_select_row('Currency<dfn>Multiple currencies can be set to the same action in different events.</dfn>', 'currencyid', $currencies, $event['currencyid']);
	print_select_row($vbphrase['credits_event_usergroups'], 'usergroups[]', $usergroups, $event['usergroups'], false, 8, true);
	if (!$action['global']) print_forum_chooser($vbphrase['credits_event_forums'], 'forums[]', $event['forums'], null, false, true);

	if ($action['parent'])
	{
		$allowpers = array('Only someone else\'s ' . $action['parent'], 'Only their own ' . $action['parent'], '' => 'Always allowed for either');
		print_radio_row('Exclusion<dfn>Specify whether or not to allow the user to trigger this event if they own the parent ' . $action['parent'] . '.</dfn>', 'owner', $allowpers, $event['owner']);
	}

	print_yes_no_row('Charge for Action<dfn>The calculated value of this event will either be awarded to the user, or deducted from them if this is enabled. When charging and the user does not have enough, the action will' . ( $action['cancel'] ? ' ' : ' NOT ' ) . 'be stopped.</dfn>', 'negative', $negative);
	print_input_row(( $action['currency'] ? 'Flat Rate<dfn>Extra amount <span class="earn">added to</span><span class="spend">deducted from</span> the amount being transferred.</dfn>' : $action['title'] . ' Amount<dfn><span class="earn">Awarded when</span><span class="spend">Charged every time</span> this action occurs.</dfn>' ), 'main_add', $event['main_add']);
	print_input_row('Random Addition<dfn>A random amount between 0 and this number can be added to the action amount. Decimal and negative numbers are okay. Use 0 to disable.</dfn>', 'upperrand', $event['upperrand']);
	if ($action['revert']) print_input_row(( $action['currency'] ? 'Flat Rate Negation<dfn>Extra amount <span class="earn">deducted from</span><span class="spend">added to</span> the amount being restored.</dfn>' : $action['title'] . ' Negation Amount<dfn><span class="earn">Charged</span><span class="spend">Awarded</span> when this event is reverted.</dfn>' ), 'main_sub', $event['main_sub']);

	if ($action['multiplier'] OR $action['currency'])
	{
		$multlabel = ( ($action['multiplier'] == 'Size') ? $sizetext : ( $action['currency'] ? array('Currency', 'Currency') : explode('|', $action['multiplier'], 2) ) );

		print_input_row(( $action['currency'] ? 'Taxation<dfn>Extra percentage in decimal form <span class="earn">added to</span><span class="spend">deducted from</span> amount being transferred.</dfn>' : $multlabel[1] . ' Amount<dfn><span class="earn">Awarded</span><span class="spend">Charged</span> for each ' . $multlabel[1] . ' along with the ' . $action['title'] . ' amount <span class="earn">when</span><span class="spend">every time</span> this action occurs.' ), 'mult_add', $event['mult_add']);
		if ($action['revert']) print_input_row(( $action['currency'] ? 'Taxation Negation<dfn>Extra percentage in decimal form <span class="earn">deducted from</span><span class="spend">added to</span> amount being restored.</dfn>' : $multlabel[1] . ' Negation Amount<dfn><span class="earn">Charged</span><span class="spend">Awarded</span> for each ' . $multlabel[1] . ' along with the ' . $action['title'] . ' negation amount when this event is reverted.' ), 'mult_sub', $event['mult_sub']);

		if ($action['currency'])
		{
			$curtargets = array('Sending user', 'Receiving user', 'Both users');
			print_radio_row('Affected Participant<dfn>Generally when currency is involved you only want to adjust the amount for one user. Choose who will be affected in such a transaction between users.</dfn>', 'curtarget', $curtargets, $event['curtarget']);
		}

		print_input_row('Minimum ' . $multlabel[0] . '<dfn>Use blank for no minimum.</dfn>', 'multmin', $event['multmin']);
		print_input_row('Maximum ' . $multlabel[0] . '<dfn>Exceeded amounts are ignored. Use blank for no maximum.</dfn>', 'multmax', $event['multmax']);

		$multactions = array('Exclude ' . $multlabel[1] . ' amounts', 'Exclude entire event');
		if ($action['cancel']) $multactions[] = 'Prevent the action';
		print_radio_row('Below Minimum Handling', 'minaction', $multactions, $event['minaction']);
	}

	//these only apply if not charging
	print_yes_no_row('Moderate<dfn>Earning events can be held for approval in the <a href="credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=transactions">transaction manager</a>. Until rejected, moderated events count towards the maximum below.</dfn>', 'moderate', $event['moderate']);
	print_input_row('Delay<dfn>All events require the user to load another vBulletin powered page before it is applied. You can delay the application further by this many seconds. Use 0 to apply as soon as possible.</dfn>', 'delay', $event['delay']);
	print_input_row('Frequency<dfn>Stagger how often this event is applied. Causes this event to be applied to every Nth valid action. Use 1 to apply this event every time this action occurs.</dfn>', 'frequency', $event['frequency']);
	print_input_row('Maximum Times<dfn>After this event has occured this many times, the action will occur without triggering the event again. Use blank for no maximum.</dfn>', 'applymax', $event['applymax']);
	print_input_row('Limit Period<dfn>Timespan in seconds that the above maximum is enforced. Use blank for all time.</dfn>', 'maxtime', $event['maxtime']);
	print_yes_no_row('Alert<dfn>Sends a message to the user when they trigger this event, according to your <a href="options.php?' . $vbulletin->session->vars['sessionurl'] . 'dogroup=credits_settings_global">settings</a>.</dfn>', 'alert', $event['alert']);

	print_submit_row();

	$depends = 'vbcredits_depend = { setting: \'negative\', value: 0, depends: [\'' . implode("', '", array('moderate', 'delay', 'frequency', 'applymax', 'maxtime')) . '\'] }';
	$advanced = array('owner', 'negative', 'upperrand', 'multmin', 'multmax', 'minaction', 'moderate', 'delay', 'frequency', 'applymax', 'maxtime', 'alert');
	print_advanced_toggle();
}

// ##################### Start Currencies Update ###################################

if ($_REQUEST['do'] == 'update_currencies')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'order' => TYPE_ARRAY
	));

	$currencies = $db->query_read("SELECT currencyid FROM " . TABLE_PREFIX . "credits_currency");

	while ($currency = $db->fetch_array($currencies))
	{
		$db->query_write("UPDATE " . TABLE_PREFIX . "credits_currency SET displayorder = " . intval($vbulletin->GPC['order'][$currency['currencyid']]) . " WHERE currencyid = " . $currency['currencyid']);
	}

	$db->free_result($currencies);
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=currencies');
	print_stop_message('credits_currencies_saved');
}

// ##################### Start Conversions Update ###################################

if ($_REQUEST['do'] == 'update_conversions')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'active'	=> TYPE_ARRAY,
		'minimum'	=> TYPE_ARRAY,
		'tiered'	=> TYPE_ARRAY,
		'usd'		=> TYPE_ARRAY,
		'gbp'		=> TYPE_ARRAY,
		'eur'		=> TYPE_ARRAY,
		'aud'		=> TYPE_ARRAY,
		'cad'		=> TYPE_ARRAY
	));

	$conversions = $db->query_read("SELECT conversionid, cost FROM " . TABLE_PREFIX . "credits_conversion");

	while ($converse = $db->fetch_array($conversions))
	{
		$total = 0;
		$cost = unserialize($converse['cost']);
		foreach (array('usd', 'gbp', 'eur', 'aud', 'cad') AS $cur) $total += $cost[$cur] = $vbulletin->GPC[$cur][$converse['conversionid']] + 0;
		$db->query_write("UPDATE " . TABLE_PREFIX . "credits_conversion SET minimum = " . doubleval($vbulletin->GPC['minimum'][$converse['conversionid']]) . ", enabled = " . intval($total AND $vbulletin->GPC['active'][$converse['conversionid']]) . ", tiered = " . intval($vbulletin->GPC['tiered'][$converse['conversionid']]) . ( $total ? ", cost = '" . $db->escape_string(serialize($cost)) . "'" : '' ) . " WHERE conversionid = " . $converse['conversionid']);
	}

	$db->free_result($conversions);
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=conversions');
	print_stop_message('credits_conversions_saved');
}

// ##################### Start Redemptions Update ###################################

if ($_REQUEST['do'] == 'update_redemptions')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'active' => TYPE_ARRAY,
		'amount' => TYPE_ARRAY
	));

	$redemptions = $db->query_read("SELECT redemptionid FROM " . TABLE_PREFIX . "credits_redemption");

	while ($redeem = $db->fetch_array($redemptions))
	{
		$newamount = $vbulletin->GPC['amount'][$redeem['redemptionid']] + 0;
		$db->query_write("UPDATE " . TABLE_PREFIX . "credits_redemption SET enabled = " . intval($vbulletin->GPC['active'][$redeem['redemptionid']]) . ( $newamount ? ', amount = ' . $newamount : '' ) . " WHERE redemptionid = " . $redeem['redemptionid']);
	}

	$db->free_result($redemptions);
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=redemptions');
	print_stop_message('credits_redemptions_saved');
}

// ##################### Start Events Update ###################################

if ($_REQUEST['do'] == 'process_events')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'action' => TYPE_STR,
		'select' => TYPE_ARRAY,
		'active' => TYPE_ARRAY,
		'moderate' => TYPE_ARRAY,
		'charge' => TYPE_ARRAY,
		'amount' => TYPE_ARRAY
	));

	if (!sizeof($vbulletin->GPC['select'])) print_stop_message('no_events_selected');

	if ($export = ($vbulletin->GPC['action'] == 'export'));
	{	//init xml file
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_XML_Builder($vbulletin);
		$xml->add_group('vbcredits');
		$xml->add_group('events');
	}
	if (!$delete = ($vbulletin->GPC['action'] == 'delete'))
	{	//only care about this if not deleting
		$events = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_event WHERE eventid IN (" . implode(', ', array_map('intval', array_keys($vbulletin->GPC['select']))) . ")");

		while ($event = $db->fetch_array($events))
		{
			if ($export)
			{	//build xml tags
				unset($event['eventid'], $event['currencyid']);
		
				foreach (array('usergroups', 'forums') AS $field)
				{	//readable list version
					$event[$field] = implode(',', unserialize($event[$field]));
				}
				foreach ($event AS $key => $val)
				{	//strip out blank or null fields
					if ($val == '' OR is_null($val)) unset($event[$key]);
				}
		
				$xml->add_tag('event', '', $event);
			}
			else
			{	//only update what was displayed
				$prevneg = ($event['main_add'] < 0);
				$event['main_add'] = ( $prevneg ? -1 : 1 ) * doubleval($vbulletin->GPC['amount'][$event['eventid']]);
	
				if ($prevneg != !empty($vbulletin->GPC['charge'][$event['eventid']]))
				{
					foreach (array('main_add', 'mult_add', 'main_sub', 'mult_sub') AS $field)
					{	//charging, so flip everything
						$event[$field] *= -1;
					}
				}
	
				$db->query_write("UPDATE " . TABLE_PREFIX . "credits_event SET main_add = " . $event['main_add'] . ", mult_add = " . $event['mult_add'] . ", main_sub = " . $event['main_sub'] . ", mult_sub = " . $event['mult_sub'] . ", moderate = " . intval($vbulletin->GPC['moderate'][$event['eventid']]) . ", enabled = " . intval($vbulletin->GPC['active'][$event['eventid']]) . " WHERE eventid = " . $event['eventid']);
			}
		}

		$db->free_result($events);
	}
	if ($export)
	{	//finish and send xml
		$xml->close_group();	
		$xml->close_group();
		//deliver the file
		require_once(DIR . '/includes/functions_file.php');
		file_download($xml->fetch_xml(), 'vbcredits-events.xml', 'text/xml');
	}
	else
	{	//regular page
		print_cp_header($vbphrase['credits_menu_event']);

		if ($delete)
		{	//delete confirmation
			echo "<p>&nbsp;</p><p>&nbsp;</p>";
			print_form_header('credits_admin', 'delete_event', 0, 1, '', '75%');
			print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], $vbphrase['event']));
			foreach (array_keys($vbulletin->GPC['select']) AS $id => $selected) construct_hidden_code('eventids[' . $id . ']', intval($selected));
			print_description_row('<blockquote><br />' . construct_phrase($vbphrase['are_you_sure_want_to_delete_events_x'], implode(', ', array_map('intval', array_keys($vbulletin->GPC['select'])))) . "<br /></blockquote>\n\t");
			construct_hidden_code('confirm', 1);
			print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
		}
		else
		{	//update done
			vbcredits_cache();
			define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=events');
			print_stop_message('credits_events_saved');
		}
	}
}

// ##################### Start Events Import ###################################

if ($_REQUEST['do'] == 'import_events')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'currencyid' => TYPE_UINT
	));
	$vbulletin->input->clean_array_gpc('f', array(
		'import_file' => TYPE_FILE
	));
	
	// got an uploaded file?
	if (!file_exists($vbulletin->GPC['import_file']['tmp_name'])) print_stop_message('no_file_uploaded_and_no_local_file_found');
	$count = vbcredits_import($vbulletin->GPC['import_file']['tmp_name'], $vbulletin->GPC['currencyid']);
	vbcredits_cache();

	define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=events');
	print_stop_message('credits_events_imported');
}

// ##################### Start Currency Index ###################################

if ($_REQUEST['do'] == 'currencies')
{
	$currencies = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_currency ORDER BY displayorder ASC");
	if (!$db->num_rows($currencies)) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_currency");

	print_form_header('credits_admin', 'update_currencies');
	print_description_row($vbphrase['credits_currencies_info']);
	print_table_break();

	print_column_style_code(array('width: 15%', 'width: 30%', 'width: 20%', 'width: 10%', 'width: 20%'));

	print_table_header('Available Currencies', 5);
	print_cells_row(array($vbphrase['title'], $vbphrase['description'], 'Field', $vbphrase['display_order'], $vbphrase['controls']), 1);

	while ($currency = $db->fetch_array($currencies))
	{	//check marks for yes, else blank
		print_cells_row(array(
			'<a href="credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_currency&currencyid=' . $currency['currencyid'] . '">' . $currency['title'] . '</a>',
			$currency['description'],
			( (!$currency['useprefix'] OR $currency['table'] != 'user') ? ( $currency['useprefix'] ? TABLE_PREFIX : '' ) . $currency['table'] . '.' : '' ) . $currency['column'],
			'<input type="text" class="bginput" name="order[' . $currency['currencyid'] . ']" size="3" value="' . $currency['displayorder'] . '" />',
			construct_link_code('Events', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=events&currencyid=' . $currency['currencyid']) . construct_link_code('Copy', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_currency&currencyid=' . $currency['currencyid'] . '&docopy=1') . construct_link_code('Delete', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delete_currency&currencyid=' . $currency['currencyid'])
		));
	}

	print_submit_row($vbphrase['save_display_order'], false, 5);
	echo '<p align="center">' . construct_link_code('Add New Currency', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_currency') . '</p>';
}

// ##################### Start Conversion Index ###################################

if ($_REQUEST['do'] == 'conversions')
{
	if (!$found = $db->query_first("SELECT paymentapiid FROM " . TABLE_PREFIX . "paymentapi WHERE active = 1")) print_cp_redirect("subscriptions.php?" . $vbulletin->session->vars['sessionurl'] . "do=api");
	if (empty($vbulletin->vbcredits['currency'])) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_currency");
	$conversions = $db->query_read("SELECT r.* FROM " . TABLE_PREFIX . "credits_conversion AS r LEFT JOIN " . TABLE_PREFIX . "credits_currency AS c ON (c.currencyid = r.currencyid) ORDER BY c.displayorder ASC, c.currencyid ASC, r.minimum ASC");
	if (!$db->num_rows($conversions)) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_conversion");

	print_form_header('credits_admin', 'update_conversions');
	print_description_row($vbphrase['credits_conversions_info']);
	print_table_break();//no column styles

	print_table_header('Available Conversions', 9);
	print_cells_row(array('Minimum', '<label>Tiered <input type="checkbox" name="all_tiered" /></label>', 'U.S. Dollars', 'Pounds Sterling', 'Euros', 'Australian Dollars', 'Canadian Dollars', '<label>' . $vbphrase['active'] . ' <input type="checkbox" name="all_active" checked="checked" /></label>', $vbphrase['controls']), 9);
	$curcat = false;

	while ($converse = $db->fetch_array($conversions))
	{	//check marks for yes, else blank
		$cost = unserialize($converse['cost']);

		if ($curcat !== $converse['currencyid'])
		{	//this needs to be collapsable
			print_description_row($vbphrase['currency'] . ' : ' . $vbulletin->vbcredits['currency'][$converse['currencyid']]['title'], 0, 9, 'tfoot');
			$curcat = $converse['currencyid'];
		}

		print_cells_row(array(
			'<input type="text" class="bginput" name="minimum[' . $converse['conversionid'] . ']" size="8" value="' . $converse['minimum'] . '" />',
			'<input type="checkbox" name="tiered[' . $converse['conversionid'] . ']" value="1"' . ( $converse['tiered'] ? ' checked="checked"' : '' ) . ' />',
			'<input type="text" class="bginput" name="usd[' . $converse['conversionid'] . ']" size="8" value="' . ( $cost['usd'] ? $cost['usd'] : '' ) . '" />',
			'<input type="text" class="bginput" name="gbp[' . $converse['conversionid'] . ']" size="8" value="' . ( $cost['gbp'] ? $cost['gbp'] : '' ) . '" />',
			'<input type="text" class="bginput" name="eur[' . $converse['conversionid'] . ']" size="8" value="' . ( $cost['eur'] ? $cost['eur'] : '' ) . '" />',
			'<input type="text" class="bginput" name="aud[' . $converse['conversionid'] . ']" size="8" value="' . ( $cost['aud'] ? $cost['aud'] : '' ) . '" />',
			'<input type="text" class="bginput" name="cad[' . $converse['conversionid'] . ']" size="8" value="' . ( $cost['cad'] ? $cost['cad'] : '' ) . '" />',
			'<input type="checkbox" name="active[' . $converse['conversionid'] . ']" value="1"' . ( $converse['enabled'] ? ' checked="checked"' : '' ) . ' />',
			construct_link_code('Edit', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_conversion&conversionid=' . $converse['conversionid']) . construct_link_code('Copy', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_conversion&conversionid=' . $converse['conversionid'] . '&docopy=1') . construct_link_code('Delete', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delete_conversion&conversionid=' . $converse['conversionid'])
		));
	}

	print_submit_row('Update Conversions', false, 9);
	echo '<p align="center">' . construct_link_code('Add New Conversion', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_conversion') . '</p>';
}

// ##################### Start Redemption Index ###################################

if ($_REQUEST['do'] == 'redemptions')
{
	if (empty($vbulletin->vbcredits['currency'])) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_currency");
	$redemptions = $db->query_read("SELECT r.* FROM " . TABLE_PREFIX . "credits_redemption AS r LEFT JOIN " . TABLE_PREFIX . "credits_currency AS c ON (c.currencyid = r.currencyid) ORDER BY c.displayorder ASC, c.currencyid ASC, r.enddate DESC");
	if (!$db->num_rows($redemptions)) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_redemption");

	print_form_header('credits_admin', 'update_redemptions');
	print_description_row($vbphrase['credits_redemptions_info']);
	print_table_break();

	print_column_style_code(array('width: 15%', 'width: 20%', 'width: 10%', 'width: 10%', 'width: 20%', 'width: 10%', 'width: 15%'));

	print_table_header('Available Redemptions', 7);
	print_cells_row(array($vbphrase['title'], $vbphrase['description'], 'Codes', $vbphrase['amount'], $vbphrase['expires'], '<label>' . $vbphrase['active'] . ' <input type="checkbox" name="all_active" checked="checked" /></label>', $vbphrase['controls']), 7);
	$curcat = false;

	while ($redeem = $db->fetch_array($redemptions))
	{	//check marks for yes, else blank
		$expire = array();
		$codes = unserialize($redeem['codes']);
		if ($redeem['maxtimes']) $expire[] = vb_number_format($redeem['maxtimes']) . ' Times';
		if ($redeem['maxusers']) $expire[] = vb_number_format($redeem['maxusers']) . ' Users';
		$enabled = ( $redeem['enabled'] ? array('', '', ' checked="checked"') : array('<s>', '</s>', '') );
		$total = sizeof($codes);

		if ($curcat !== $redeem['currencyid'])
		{	//this needs to be collapsable
			print_description_row($vbphrase['currency'] . ' : ' . $vbulletin->vbcredits['currency'][$redeem['currencyid']]['title'], 0, 7, 'tfoot');
			$curcat = $redeem['currencyid'];
		}

		print_cells_row(array(
			$enabled[0] . '<a href="credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_redemption&redemptionid=' . $redeem['redemptionid'] . '">' . $redeem['title'] . '</a>' . $enabled[1],
			$redeem['description'],
			'<span title="' . ( ($total > 1) ? '' : $vbulletin->options['bburl'] . '/credits.php?code=' . urlencode($codes[0]) ) . '">' . ( $redeem['redirect'] ? '<a href="' . $redeem['redirect'] . '" target="_blank">' : '' ) . ( ($total > 1) ? 'Set of ' . vb_number_format($total) : $codes[0] ) . ( $redeem['redirect'] ? '</a>' : '' ) . '</span>',
			'<input type="text" class="bginput" name="amount[' . $redeem['redemptionid'] . ']" size="8" value="' . $redeem['amount'] . '" />',
			'<span title="' . implode(' or ', $expire) . '">' . ( $redeem['enddate'] ? vbdate($vbulletin->options['logdateformat'], $redeem['enddate']) : 'Never' ) . '</span>',
			'<input type="checkbox" name="active[' . $redeem['redemptionid'] . ']" value="1"' . $enabled[2] . ' />',
			construct_link_code('Copy', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_redemption&redemptionid=' . $redeem['redemptionid'] . '&docopy=1') . construct_link_code('Delete', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delete_redemption&redemptionid=' . $redeem['redemptionid'])
		));
	}

	print_submit_row('Update Redemptions', false, 7);
	echo '<p align="center">' . construct_link_code('Add New Redemption', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_redemption') . '</p>';
}

// ##################### Start Display Index ###################################

if ($_REQUEST['do'] == 'displays')
{
	if (empty($vbulletin->vbcredits['currency'])) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_currency");
	$displays = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_display ORDER BY title ASC");

	if (!$db->num_rows($displays))
	{
		if ($vbulletin->debug) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_display");
		else print_stop_message('credits_no_displays');
	}

	print_form_header('credits_admin', 'update_displays');
	print_description_row($vbphrase['credits_displays_info']);
	print_table_break();

	print_column_style_code(array('width: 15%', 'width: 40%', 'width: 10%', 'width: 25%', 'width: 10%'));

	print_table_header('Available Displays', 5);
	print_cells_row(array($vbphrase['title'], $vbphrase['description'], 'Currencies', 'Hook', '<label>' . $vbphrase['active'] . ' <input type="checkbox" name="all_active" checked="checked" /></label>'), 1);

	while ($display = $db->fetch_array($displays))
	{	//check marks for yes, else blank
		$thecurrs = array();
		$enabled = ( $display['enabled'] ? array('', '', ' checked="checked"') : array('<s>', '</s>', '') );
		foreach (unserialize($display['currencies']) AS $currencyid) $thecurrs[] = $vbulletin->vbcredits['currency'][$currencyid]['title'];
		if (empty($thecurrs)) $thecurrs[] = 'All Currencies'; 

		print_cells_row(array(
			$enabled[0] . '<a href="credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_display&displayid=' . $display['displayid'] . '">' . $display['title'] . '</a>' . ( $display['customhook'] ? '<span title="The ' . $display['hookname'] . ' template hook must also be placed in the ' . $display['customhook'] . ' template!">*</span>' : '' ) . $enabled[1],
			$display['description'],
			'<img src="../images/misc/question_icon.gif" title="' . implode(', ', $thecurrs) . '" />',
			$display['hookname'],
			'<input type="checkbox" name="active[' . $display['displayid'] . ']" value="1"' . $enabled[2] . ' />'
		));
	}

	print_submit_row($vbphrase['save_active_status'], false, 5);
	if ($vbulletin->debug) echo '<p align="center">' . construct_link_code('Add New Display', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_display') . '</p>';
}

// ##################### Start Action Index ###################################

if ($_REQUEST['do'] == 'actions')
{
	$actions = $db->query_read("SELECT * FROM " . TABLE_PREFIX . "credits_action ORDER BY category ASC, title ASC");

	if (!$db->num_rows($actions))
	{
		if ($vbulletin->debug) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_action");
		else print_stop_message('credits_no_actions');
	}

	print_form_header('credits_admin', 'update_actions');//doesnt need a form does it?
	print_description_row($vbphrase['credits_actions_info']);
	print_table_break();

	print_column_style_code(array('width: 15%', 'width: 35%', 'width: 15%', 'width: 5%', 'width: 5%', 'width: 5%', 'width: 20%'));

	print_table_header('Available Actions', 7);
	print_cells_row(array($vbphrase['title'], $vbphrase['description'], 'Multiplier', 'Global', 'Reversable', 'Cancelable', $vbphrase['controls']), 1);
	$curcat = '';

	while ($action = $db->fetch_array($actions))
	{	//check marks for yes, else blank
		if ($curcat != $action['category'])
		{	//this needs to be collapsable
			print_description_row('Category : ' . $vbphrase['credits_category_' . $action['category']], 0, 7, 'tfoot');
			$curcat = $action['category'];
		}

		$multlabel = ( ($action['multiplier'] == 'Size') ? $sizetext : explode('|', $action['multiplier'], 2) );

		print_cells_row(array(
			'<a href="credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_action&actionid=' . $action['actionid'] . '">' . $action['title'] . '</a>',
			$action['description'],
			( $action['currency'] ? $vbphrase['currency'] : $multlabel[0] ),
			( $action['global'] ? '<img src="../images/misc/tick.png" />' : '' ),
			( $action['revert'] ? '<img src="../images/misc/tick.png" />' : '' ),
			( $action['cancel'] ? '<img src="../images/misc/tick.png" />' : '' ),
			construct_link_code('Events', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=events&actionid=' . $action['actionid']) . construct_link_code('New Event', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_event&actionid=' . $action['actionid'])
		));
	}

	print_table_footer();
	if ($vbulletin->debug) echo '<p align="center">' . construct_link_code('Add New Action', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_action') . '</p>';
}

// ##################### Start Event Index ###################################

if ($_REQUEST['do'] == 'events')
{
	if (empty($vbulletin->vbcredits['currency'])) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_currency");

	if (empty($vbulletin->vbcredits['action']))
	{
		if ($vbulletin->debug) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_action");
		else print_stop_message('credits_no_actions');
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'actionid'	=> TYPE_STR,
		'currencyid' => TYPE_UINT,
		'usergroupid' => TYPE_UINT,
		'forumid' => TYPE_UINT,
		'group' => TYPE_STR
	));

	$where = array();
	if ($vbulletin->GPC['currencyid']) $where[] = "e.currencyid = " . $vbulletin->GPC['currencyid'];
	if ($vbulletin->GPC['actionid']) $where[] = "e.actionid = '" . $db->escape_string($vbulletin->GPC['actionid']) . "'";
	if (!in_array($vbulletin->GPC['group'], array('actionid', 'currencyid'))) $vbulletin->GPC['group'] = 'currencyid';
	$byaction = ($vbulletin->GPC['group'] == 'actionid');

	$events = $db->query_read("SELECT e.* FROM " . TABLE_PREFIX . "credits_event AS e LEFT JOIN " . TABLE_PREFIX . "credits_action AS a ON (e.actionid = a.actionid) LEFT JOIN " . TABLE_PREFIX . "credits_currency AS c ON (e.currencyid = c.currencyid)" . ( sizeof($where) ? " WHERE " . implode(' AND ', $where) : '' ) . " ORDER BY " . ( $byaction ? "a.title ASC, c.displayorder ASC, c.currencyid ASC" : "c.displayorder ASC, c.currencyid ASC, a.title ASC" ));

	print_form_header('credits_admin', '');
	print_description_row($vbphrase['credits_events_info']);
	print_table_break();
//show different stuff here
	print_column_style_code(array('width: 15%', 'width: 10%', 'width: 10%', 'width: 10%', 'width: 10%', 'width: 10%', 'width: 10%', 'width: 25%'));
	print_table_header('Available Events', 8);
	print_cells_row(array(( $byaction ? '<a href="credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=events&group=currencyid">Currency</a>' : '<a href="credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=events&group=actionid">Action</a>' ), 'Applies', '<label>Charge <input type="checkbox" name="all_charge" /></label>', 'Amount', '<label>Moderate <input type="checkbox" name="all_moderate" /></label>', '<label>' . $vbphrase['active'] . ' <input type="checkbox" name="all_active" checked="checked" /></label>', '<label>Select <input type="checkbox" name="all_select" checked="checked" /></label>', $vbphrase['controls']), 1);
	$shown = $curcat = false;

	while ($event = $db->fetch_array($events))
	{	//check marks for yes, else blank
		$forums = unserialize($event['forums']);
		$groups = unserialize($event['usergroups']);

		if ((empty($vbulletin->GPC['forumid']) OR in_array($vbulletin->GPC['forumid'], $forums)) AND (empty($vbulletin->GPC['usergroupid']) OR in_array($vbulletin->GPC['usergroupid'], $groups)))
		{
			$shown = true;
			$theforums = $thegroups = array();
			$action =& $vbulletin->vbcredits['action'][$event['actionid']];
			$which = ( $byaction ? $event['actionid'] : $event['currencyid'] );
			foreach ($forums AS $forumid) $theforums[] = strip_tags($vbulletin->forumcache[$forumid]['title']);
			foreach ($groups AS $groupid) $thegroups[] = strip_tags($vbulletin->usergroupcache[$groupid]['title']);
			$enabled = ( $event['enabled'] ? array('', '', ' checked="checked"') : array('<s>', '</s>', '') );
			if (empty($theforums)) $theforums[] = 'All Forums';
			if (empty($thegroups)) $thegroups[] = 'All Usergroups'; 

			if ($curcat !== $which)
			{	//this needs to be collapsable
				print_description_row(( $byaction ? $vbphrase['action'] : $vbphrase['currency'] ) . ' : ' . ( $byaction ? $vbulletin->vbcredits['action'][$which]['title'] : $vbulletin->vbcredits['currency'][$which]['title'] ), 0, 8, 'tfoot');
				$curcat = $which;
			}
			if ($event['main_add'] < 0)
			{
				$event['charge'] = true;
				$event['main_add'] *= -1;
			}

			print_cells_row(array(
				$enabled[0] . '<a href="credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_event&eventid=' . $event['eventid'] . '">' . ( $byaction ? $vbulletin->vbcredits['currency'][$event['currencyid']]['title'] : $vbulletin->vbcredits['action'][$event['actionid']]['title'] ) . '</a>' . $enabled[1] . ( !is_null($event['owner']) ? '<span title="Must' . ( $event['owner'] ? '' : ' not' ) . ' own ' . $action['parent'] . '">*</span>' : '' ),
				'<img src="../images/misc/question_icon.gif" title="' . implode(', ', $thegroups) . '" />' . ( !$action['global'] ? ' <img src="../images/misc/question_icon.gif" title="' . implode(', ', $theforums) . '" />' : '' ),
				'<input type="checkbox" name="charge[' . $event['eventid'] . ']" value="1"' . ( $event['charge'] ? ' checked="checked"' : '' ) . ' />',
				'<input type="text" class="bginput" name="amount[' . $event['eventid'] . ']" size="8" value="' . $event['main_add'] . '" />',
				( !$event['charge'] ? '<input type="checkbox" name="moderate[' . $event['eventid'] . ']" value="1"' . ( $event['moderate'] ? ' checked="checked"' : '' ) . ' />' : '' ),
				'<input type="checkbox" name="active[' . $event['eventid'] . ']" value="1"' . $enabled[2] . ' />',
				'<input type="checkbox" name="select[' . $event['eventid'] . ']" value="1" checked="checked" />',
				construct_link_code('Transactions', 'credits_admin.php?do=transactions&eventid=' . $event['eventid']) . construct_link_code('Copy', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit_event&eventid=' . $event['eventid'] . '&docopy=1') . construct_link_code('Delete', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delete_event&eventid=' . $event['eventid'])
			));
		}
	}

	if (!$shown) print_description_row('No Events Found', false, 8, '', 'center');
	print_table_footer(8, ( $shown ? 'Selected Events: <select name="action" class="bginput"><option value="update">Update</option><option value="export">Export</option><option value="delete">Delete</option></select><input type="submit" class="button" tabindex="1" rel="process_events" value="' . $vbphrase['go'] . '" />' : '' ));
	unset($action);//doubling bug

	$actions = $currencies = array();
	foreach ($vbulletin->vbcredits['action'] AS $actionid => $action) $actions[$vbphrase['credits_category_' . $action['category']]][$actionid] = $action['title'];
	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency) $currencies[$currencyid] = $currency['title'];

	print_form_header('credits_admin', '', true, true, 'cpform_event');
	print_label_row('Add New <select name="actionid" class="bginput">' . construct_select_options($actions) . '</select> Event <input type="submit" class="button" value="' . $vbphrase['go'] . '" rel="edit_event" />', '<div align="right">Import <input type="file" name="import_file" /> <select name="currencyid" class="bginput">' . construct_select_options($currencies) . '</select> Events <input type="submit" class="button" value="' . $vbphrase['go'] . '" rel="import_events" /></div>');
	print_table_footer();

//dropdowns for specific usergroup or forum
//group by currency or action - default action
//link in users to view transactions
//link in usergroup to view events/transactions
//link in forums to view events/transactions
//link in currency to view events/transactions?
//link in actions to do anything?
}

// ##################### Start Transaction Index ###################################

if ($_REQUEST['do'] == 'transactions')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'			=> TYPE_UINT,
		'eventid'			=> TYPE_UINT,
		'limitstart'        => TYPE_UINT,
		'limitnumber'       => TYPE_UINT,
		'messages'			=> TYPE_ARRAY,
		'moderated'			=> TYPE_ARRAY,
		'start_date'		=> TYPE_UNIXTIME,
		'end_date'			=> TYPE_UNIXTIME,
		'new_limitstart'	=> TYPE_UINT,
		'new_limitnumber'	=> TYPE_UINT,
		'new_start_date'	=> TYPE_UNIXTIME,
		'new_end_date'		=> TYPE_UNIXTIME,
		'new_time_stats'	=> TYPE_NOHTML,
		'new_group_stats'	=> TYPE_ARRAY,
		'update'			=> TYPE_STR,
		'display'			=> TYPE_STR,
		'earns'				=> TYPE_ARRAY,
		'spend'				=> TYPE_ARRAY,
		'credits'			=> TYPE_ARRAY,
		'time_stats'		=> TYPE_NOHTML,
		'group_stats'		=> TYPE_ARRAY
	));

	if ($vbulletin->GPC['display'])
	{	//if display clicked, update values
		foreach (array('limitstart', 'limitnumber', 'start_date', 'end_date', 'time_stats', 'group_stats') AS $field)
		{
			$vbulletin->GPC[$field] = $vbulletin->GPC['new_' . $field];
		}
	}

	if (empty($vbulletin->GPC['time_stats'])) $vbulletin->GPC['time_stats'] = 'a';
	if (empty($vbulletin->GPC['end_date'])) $vbulletin->GPC['end_date'] = TIMENOW;
	if (empty($vbulletin->GPC['start_date'])) $vbulletin->GPC['start_date'] = $earliest['joindate'];
	$myself = ($vbulletin->userinfo['userid'] == $vbulletin->GPC['userid']);
	$user = ( $myself ? $vbulletin->userinfo : fetch_userinfo($vbulletin->GPC['userid']) );
	print_form_header('credits_admin', 'transactions');

	if ($vbulletin->GPC['update'])
	{	//updating previous list
		if ($vbulletin->GPC['limitstart']) $vbulletin->GPC['limitstart'] -= $vbulletin->GPC['limitnumber'];//roll back
		$updated = 0;

		if ($user['userid'])
		{
			$perms = array(array(), array());
			$tuser =& $user;

			foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
			{
				$user['vbcredits_' . $currencyid] = doubleval($vbulletin->GPC['credits'][$currencyid]);
				if (empty($vbulletin->GPC['earns'][$currencyid])) $perms[0][] = $currencyid;
				if (empty($vbulletin->GPC['spend'][$currencyid])) $perms[1][] = $currencyid;
			}

			$user['creditspermissions'] = serialize($perms);
			$db->query_write("UPDATE " . TABLE_PREFIX . "user SET creditspermissions = '" . $db->escape_string($user['creditspermissions']) . "' WHERE userid = " . $user['userid']);
			if ($myself) $vbulletin->userinfo = $user;
			else VBCREDITS::update($user); //if myself, update on shutdown
		}
		foreach ($vbulletin->GPC['messages'] AS $transid => $message)
		{
			$newmod = intval($vbulletin->GPC['moderated'][$transid]);
			$trans = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "credits_transaction WHERE transactionid = " . intval($transid));
			$oldmod = intval($trans['status'] == 2);

			if ($newmod != $oldmod)
			{	//moderation changed, change user
				if (!$user['userid'])
				{	//use temp if myself, else load it
					$myself = ($vbulletin->userinfo['userid'] == $trans['userid']);
					$tuser = ( $myself ? $vbulletin->userinfo : fetch_userinfo($trans['userid']) );
				}

				$trans['status'] = ( $newmod ? 2 : 1 );
				$tuser['vbcredits_' . $trans['currencyid']] += ($oldmod - $newmod) * $trans['amount'];

				if ($myself) $vbulletin->userinfo = $tuser;
				else VBCREDITS::update($tuser); //if myself, update on shutdown
			}

			$db->query_write("UPDATE " . TABLE_PREFIX . "credits_transaction SET status = " . $trans['status'] . ", message = '" . $db->escape_string($vbulletin->GPC['messages'][$transid]) . "' WHERE transactionid = " . $trans['transactionid']);
			$updated++;
		}
		if ($updated)
		{
			print_description_row(construct_phrase($vbphrase['credits_transactions_updated'], vb_number_format($updated), ( $user['userid'] ? ' for ' . $user['username'] : '' )));
			print_table_break();
		}
	}

	$condition = 't.eventid != 0 AND t.status IN (1, 2) AND t.timestamp >= ' . $vbulletin->GPC['start_date'] . ' AND t.timestamp <= ' . $vbulletin->GPC['end_date'];
	if ($vbulletin->GPC['eventid']) $condition .= ' AND t.eventid = ' . $vbulletin->GPC['eventid'];
	if ($vbulletin->GPC['userid']) $condition .= ' AND t.userid = ' . $vbulletin->GPC['userid'];

	$times = $db->query_first("
		SELECT
			MAX(t.timestamp) AS latest,
			MIN(t.timestamp) AS earliest
		FROM " . TABLE_PREFIX . "credits_transaction AS t
		WHERE $condition
	");

	$curs = array();
	$stats = array(array(), array());
	$sql[4] = $str[4] = '';
	$i = 0;

	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
	{
		$sql[0] = 't.currencyid = ' . $currencyid; $str[0] = '';
		$curs[] = 'SUM(IF(' . $sql[0] . ', amount, 0)) AS total_' . $currencyid;//use for totals

		foreach (( in_array('f', $vbulletin->GPC['group_stats']) ? $vbulletin->forumcache : array(0) ) AS $id => $forum)
		{
			$sql[1] = ( $id ? 't.forumid = ' . $id : '' ); $str[1] = ( $id ? $forum['title'] : '' );

			foreach (( in_array('u', $vbulletin->GPC['group_stats']) ? $vbulletin->usergroupcache : array(0) ) AS $id => $group)
			{
				$sql[2] = ( $id ? 'u.usergroupid = ' . $id : '' ); $str[2] = ( $id ? $group['title'] : '' );
				$events = array(0);

				if (in_array('e', $vbulletin->GPC['group_stats']))
				{
					$events = array();
					$evts = $db->query_read("SELECT e.eventid, a.title FROM " . TABLE_PREFIX . "credits_event AS e LEFT JOIN " . TABLE_PREFIX . "credits_action AS a ON (e.actionid = a.actionid) ORDER BY e.eventid ASC");
					while ($event = $db->fetch_array($evts)) $events[$event['eventid']] = $event['title'];
					$db->free_result($evts);
				}
				foreach ($events AS $id => $name)
				{
					$starttime = intval($times['earliest']);
					$endtime = intval($times['latest']) + 1;
					$t =& $vbulletin->GPC['time_stats'];
					$sql[3] = ( $id ? 't.eventid = ' . $id : '' ); $str[3] = ( $id ? $name : '' );
					$sql[4] = $str[4] = '';

					if ($t != 'a')
					{
						$s = array_map('intval', explode(' ', vbdate('Y n w j G', intval($times['earliest']))));
						$starttime = vbmktime(( ($t == 'h') ? $s[4] : 0 ), 0, 0, ( ($t != 'y') ? $s[1] : 1 ), ( !in_array($t, array('y', 'm')) ? $s[3] - (($t == 'w') * $s[2]) : 1 ), $s[0]);
					}
					while ($starttime <= intval($times['latest']))
					{
						if ($t != 'a')
						{
							$s = array_map('intval', explode(' ', vbdate('G n j Y', $starttime)));
							$endtime = vbmktime($s[0] + ($t == 'h'), 0, 0, $s[1] + ($t == 'm'), $s[2] + ($t == 'd') + (($t == 'w') * 7), $s[3] + ($t == 'y'));
						}

						$sql[4] = 't.timestamp >= ' . $starttime . ' AND t.timestamp < ' . $endtime;
						$str[4] = 'From ' . vbdate($vbulletin->options['logdateformat'], $starttime) . '<br />To ' . vbdate($vbulletin->options['logdateformat'], $endtime);
						$stats[0][$i] = 'SUM(IF(' . implode(' AND ', array_diff($sql, array(''))) . ', amount, 0)) AS stats_' . $currencyid . '_' . $i;
						$stats[1][$i] = implode('<br />', array_diff($str, array('')));
						$starttime = $endtime;
						$i++;
					}
				}
			}
		}
	}

	$counttrans = $db->query_first("
		SELECT
			COUNT(*) AS total,
			" . implode(', ', $curs) . ",
			" . implode(', ', $stats[0]) . "
		FROM " . TABLE_PREFIX . "credits_transaction AS t
		LEFT JOIN " . TABLE_PREFIX . "user AS u ON (u.userid = t.userid)
		WHERE $condition
	");

	if ($times['earliest'] AND $vbulletin->GPC['start_date'] < $times['earliest']) $vbulletin->GPC['start_date'] = $times['earliest'];
	if ($times['latest'] AND $vbulletin->GPC['end_date'] > $times['latest']) $vbulletin->GPC['end_date'] = $times['latest'] + 60;
	if (empty($vbulletin->GPC['limitnumber'])) $vbulletin->GPC['limitnumber'] = 50;
	if (empty($vbulletin->GPC['limitstart']) OR $vbulletin->GPC['limitstart'] > $counttrans['total']) $vbulletin->GPC['limitstart'] = 0;
	else $vbulletin->GPC['limitstart']--;

	$transactions = $db->query_read("SELECT t.*, u.username, a.multiplier AS hasmult FROM " . TABLE_PREFIX . "credits_transaction AS t INNER JOIN " . TABLE_PREFIX . "user AS u ON (t.userid = u.userid) LEFT JOIN " . TABLE_PREFIX . "credits_action AS a ON (a.actionid = t.actionid) WHERE $condition ORDER BY t.status DESC, t.timestamp DESC LIMIT " . $vbulletin->GPC['limitstart'] . ", " . $vbulletin->GPC['limitnumber']);
	$numtrans = $db->num_rows($transactions);

	print_description_row($vbphrase['credits_transactions_info']);
	print_table_break();

	print_column_style_code(array('width: 70%', 'width: 30%'));
	print_table_header('Mass Update Transactions');

	print_time_row('Start Date<dfn>Earliest inclusive time of the selected transactions.</dfn>', 'new_start_date', $vbulletin->GPC['start_date']);
	print_time_row('End Date<dfn>Latest inclusive time of the selected transactions.</dfn>', 'new_end_date', $vbulletin->GPC['end_date']);
	print_input_row($vbphrase['starting_at_result'], 'new_limitstart', $vbulletin->GPC['limitstart'] + 1);
	print_input_row($vbphrase['maximum_results'], 'new_limitnumber', $vbulletin->GPC['limitnumber']);
	print_select_row('Statistics Timespans<dfn>Be careful to pick a span that fits the start and end dates without too much or too little shown.</dfn>', 'new_time_stats', array('a' => $vbphrase['all'], 'y' => $vbphrase['year'], 'm' => $vbphrase['month'], 'w' => $vbphrase['week'], 'd' => $vbphrase['day'], 'h' => $vbphrase['hour']), $vbulletin->GPC['time_stats']);
	print_select_row($vbphrase['credits_transaction_statsgroup'], 'new_group_stats[]', array('u' => $vbphrase['primary_usergroup'], 'f' => $vbphrase['forum'], 'e' => $vbphrase['event']), $vbulletin->GPC['group_stats'], false, 3, true);

	if ($user['userid'])
	{
		print_description_row($vbphrase['user'] . ' : <a href="user.php?' . $vbulletin->session->vars['sessionurl'] . 'do=edit&u=' . $user['userid'] . '">' . $user['username'] . '</a>', 0, 2, 'tfoot');
		$perms = ( $user['creditspermissions'] ? unserialize($user['creditspermissions']) : array(array(), array()) );

		foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
		{
			print_yes_no_row(construct_phrase($vbphrase['credits_earn_currency'], $currency['title']), 'earns[' . $currencyid . ']', intval(!in_array($currencyid, $perms[0])));
			print_yes_no_row(construct_phrase($vbphrase['credits_spend_currency'], $currency['title']), 'spend[' . $currencyid . ']', intval(!in_array($currencyid, $perms[1])));
			print_input_row($currency['title'], 'credits[' . $currencyid . ']', $user['vbcredits_' . $currencyid]);
		}
	}

	print_description_row('<div align="center"><input type="submit" class="button" name="update" value=" ' . $vbphrase['credits_update_transactions'] . ' " tabindex="1" /> <input type="submit" class="button" name="display" value=" ' . $vbphrase['credits_change_search'] . ' " tabindex="1" /></div>', false, 2, 'tfoot');
	print_table_break();

	$limitfinish = $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'];
	print_column_style_code(array('width: 17%', 'width: 10%', 'width: 10%', 'width: 20%', 'width: 15%', 'width: 15%', 'width: 13%'));

	print_table_header(
		construct_phrase(
			$vbphrase['showing_transactions_x_to_y_of_z'],
			( $numtrans ? ($vbulletin->GPC['limitstart'] + 1) : 0 ),
			iif($limitfinish > $counttrans['total'], $counttrans['total'], $limitfinish),
			$counttrans['total']
		), 7);

	print_cells_row(array($vbphrase['date'], $vbphrase['event'], $vbphrase['user'], $vbphrase['message'], $vbphrase['amount'], '<label>' . $vbphrase['moderated'] . ' <input type="checkbox" name="all_moderated" /></label>', $vbphrase['controls']), 1);

	while ($trans = $db->fetch_array($transactions))
	{
		$which = 'earned';
		$action =& $vbulletin->vbcredits['action'][$trans['actionid']];
		$negate = ( $trans['negate'] ? array('<i>', '</i>', '<s>', '</s>') : array('', '', '', '') );
		$multlabel = ( ($action['multiplier'] == 'Size') ? $sizetext : explode('|', $action['multiplier'], 2) );
		$mult = ( $trans['hasmult'] ? array('<span title="' . $multlabel[0] . ': ' . $trans['multiplier'] . '">', '</span>') : array('', '') );
		$currency =& $vbulletin->vbcredits['currency'][$trans['currencyid']];
		$moderated = ($trans['status'] == 2);

		if ($trans['amount'] < 0)
		{
			$which = 'spent';
			$trans['amount'] *= -1;
		}

		print_cells_row(array(
			vbdate($vbulletin->options['logdateformat'], $trans['timestamp']),
			$negate[2] . '<a href="credits_admin.php?do=transactions&userid=' . $vbulletin->GPC['userid'] . '&eventid=' . $trans['eventid'] . '">' . $action['title'] . '</a>' . $negate[3],
			'<a href="credits_admin.php?do=transactions&userid=' . $trans['userid'] . '&eventid=' . $vbulletin->GPC['eventid'] . '">' . $trans['username'] . '</a>',
			'<input type="text" class="bginput" name="messages[' . $trans['transactionid'] . ']" value="' . htmlspecialchars($trans['message']) . '" size="30" />',
			$negate[0] . $mult[0] . construct_phrase($vbphrase['credits_transaction_' . $which], vb_number_format($trans['amount'], max($currency['decimals'], $vbulletin->options['credits_transaction_decimals'])), $currency['title']) . $mult[1] . $negate[1],
			'<input type="checkbox" name="moderated[' . $trans['transactionid'] . ']" value="1"' . ( $moderated ? ' checked="checked"' : '' ) . ' />',
			( $trans['referenceid'] ? construct_link_code($vbphrase['link'], '../' . $action['referformat'] . $trans['referenceid'], true) : '' ) . ( $moderated ? construct_link_code($vbphrase['delete'], 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=delete_transaction&transactionid=' . $trans['transactionid']) : '' )
		));
	}
	if (!$numtrans)
	{
		print_description_row($vbphrase['credits_no_transactions'], false, 7, '', 'center');
	}

	construct_hidden_code('userid', $vbulletin->GPC['userid']);
	construct_hidden_code('eventid', $vbulletin->GPC['eventid']);
	construct_hidden_code('start_date', $vbulletin->GPC['start_date']);
	construct_hidden_code('end_date', $vbulletin->GPC['end_date']);
	construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
	construct_hidden_code('limitnumber', $vbulletin->GPC['limitnumber']);
	construct_hidden_code('time_stats', $vbulletin->GPC['time_stats']);
	foreach ($vbulletin->GPC['group_stats'] AS $gstats) construct_hidden_code('group_stats[]', $gstats);

	if ($vbulletin->GPC['limitstart'] == 0 AND $counttrans['total'] > $vbulletin->GPC['limitnumber']) print_submit_row($vbphrase['next_page'], 0, 7);
	else if ($limitfinish < $counttrans['total']) print_submit_row($vbphrase['next_page'], 0, 7, $vbphrase['prev_page'], '', true);
	else if ($vbulletin->GPC['limitstart'] > 0 AND $limitfinish >= $counttrans['total']) print_submit_row($vbphrase['first_page'], 0, 7, $vbphrase['prev_page'], '', true);
	else print_table_footer();

	if ($numtrans)
	{	//stats only make sense if there are any
		print_table_start();
		print_column_style_code(array('width: 25%', 'width: 45%', 'width: 10%', 'width: 10%', 'width: 10%'));
		print_table_header($vbphrase['statistics'], 5);
		print_cells_row(array($vbphrase['label'], '', $vbphrase['amount'], $vbphrase['credits_percent_section'], $vbphrase['credits_percent_total']), 1);
		$counttotal = $db->query_first("SELECT " . implode(', ', $curs) . " FROM " . TABLE_PREFIX . "credits_transaction AS t WHERE t.eventid != 0 AND t.status IN (1, 2)");
		$prevcur = 0;

		if ($vb4)
		{	// we'll need a poll image
			$style = $db->query_first("
				SELECT styleid, newstylevars FROM " . TABLE_PREFIX . "style
				WHERE styleid = " . $vbulletin->options['styleid'] . "
				LIMIT 1
			");
			$vbulletin->stylevars = unserialize($style['newstylevars']);
			fetch_stylevars($style, $vbulletin->userinfo);
		}
		else
		{	// we'll need a poll image
			$style = $db->query_first("
				SELECT stylevars FROM " . TABLE_PREFIX . "style
				WHERE styleid = " . $vbulletin->options['styleid'] . "
				LIMIT 1
			");
			$stylevars = unserialize($style['stylevars']);
			unset($style);
		}
		foreach ($counttrans AS $field => $amount)
		{
			$bits = explode('_', $field);

			if ($bits[0] == 'stats')
			{
				if ($bits[1] != $prevcur)
				{
					$i = 0;
					$prevcur = intval($bits[1]);
					$sectotal =& $counttrans['total_' . $prevcur];
					$alltotal =& $counttotal['total_' . $prevcur];
					$currency =& $vbulletin->vbcredits['currency'][$prevcur];
					print_description_row(construct_phrase($vbphrase['credits_transaction_stats'], vb_number_format($sectotal, $currency['decimals']), $currency['title'], vb_number_format(( $alltotal ? $sectotal / $alltotal * 100 : 0 ), 1), vb_number_format($alltotal, $currency['decimals'])), 0, 5, 'tfoot');
				}
				if ($amount)
				{	//skip 0 rows
					$bar = (++$i % 6) + 1;
					$percent = ( $sectotal ? $amount / $sectotal * 100 : 0 );
					ob_start();//need to alter what happens
					print_statistic_result($stats[1][$bits[2]], $bar, vb_number_format($amount, $currency['decimals']), ceil($percent));
					$row = ob_get_clean();

					preg_match('/class="([^"]+)"/i', $row, $found);
					$row = preg_replace('/td width="\d+%?"/i', 'td', $row);
					$row = str_replace('</tr>', '<td class="' . $found[1] . '">' . vb_number_format($percent, 1) . '%</td><td class="' . $found[1] . '">' . vb_number_format(( $alltotal ? $amount / $alltotal * 100 : 0 ), 1) . '%</td></tr>', $row);

					echo $row;
				}
			}
		}

		print_table_footer(5);
	}
}

// ###################### Start Users Update #######################

if ($_REQUEST['do'] == 'findusers')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'user'              => TYPE_ARRAY,
		'profile'           => TYPE_ARRAY,
		'orderby'           => TYPE_STR,
		'totalusers'        => TYPE_UINT,
		'limitstart'        => TYPE_UINT,
		'limitnumber'       => TYPE_UINT,
		'direction'         => TYPE_STR,
		'serializedprofile' => TYPE_STR,
		'serializeduser'    => TYPE_STR,
		'actions'			=> TYPE_ARRAY,
		'start_date'		=> TYPE_UNIXTIME,
		'end_date'			=> TYPE_UNIXTIME,
		'formula'			=> TYPE_ARRAY,
		'credits'			=> TYPE_ARRAY,
		'earns'				=> TYPE_ARRAY,
		'spend'				=> TYPE_ARRAY,
		'update'			=> TYPE_STR,
		'recalculate'		=> TYPE_BOOL,
		'deletion'			=> TYPE_BOOL,
		'reversal'			=> TYPE_BOOL,
		'allactions'		=> TYPE_BOOL,
		'alltime'			=> TYPE_BOOL,
		'allusers'			=> TYPE_BOOL
	));

	if (!empty($vbulletin->GPC['serializeduser']))
	{
		$vbulletin->GPC['user']    = @unserialize(verify_client_string($vbulletin->GPC['serializeduser']));
		$vbulletin->GPC['profile'] = @unserialize(verify_client_string($vbulletin->GPC['serializedprofile']));
	}

	$user = $vbulletin->GPC['user'];
	if (!$vbulletin->GPC_exists['end_date']) $vbulletin->GPC['end_date'] = TIMENOW;
	if (!$vbulletin->GPC_exists['start_date']) $vbulletin->GPC['start_date'] = $earliest['joindate'];
	$condition = fetch_user_search_sql($vbulletin->GPC['user'], $vbulletin->GPC['profile']);
	if (!in_array($vbulletin->GPC['orderby'], array('username', 'joindate', 'posts', 'reputation', 'ipoints')) AND substr($vbulletin->GPC['orderby'], 0, 9) != 'vbcredits') $vbulletin->GPC['orderby'] = 'username';
	$updated = 0;

	$header = array(
		$vbphrase['username'],
		$vbphrase['usergroup'],
		$vbphrase['credits_days_registered'],
		$vbphrase['post_count'],
		$vbphrase['reputation'],
		$vbphrase['infraction_points']
	);

	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
	{
		$column = ( in_array($currency['table'], array('user', 'userfield', 'usertextfield')) ? $currency['table'] : 'vbcreditst_' . $currencyid ) . '.' . $currency['column'];
		if ($user['vbcredits_' . $currencyid . '_lower'] != '') $condition .= " AND $column >= " . doubleval($user['vbcredits_' . $currencyid . '_lower']);
		if ($user['vbcredits_' . $currencyid . '_upper'] != '') $condition .= " AND $column < " . doubleval($user['vbcredits_' . $currencyid . '_upper']);
		if ($vbulletin->GPC['orderby'] == 'vbcredits_' . $currencyid) $vbulletin->GPC['orderby'] = $column;

		$header = array_merge($header, array(
			construct_phrase($vbphrase['credits_earn_currency'], '<label>' . $currency['title']) . ' <input type="checkbox" name="all_earns[' . $currencyid . ']" checked="checked" /></label>',
			construct_phrase($vbphrase['credits_spend_currency'], '<label>' . $currency['title']) . ' <input type="checkbox" name="all_spend[' . $currencyid . ']" checked="checked" /></label>',
			$currency['title']
		));
	}
	if ($vbulletin->GPC['update'])
	{	//updating previous list
		echo '<div id="credits_output">' . $vbphrase['credits_processing_users'] . '<br />';
		$prunes = $actions = $resets = $rebuilds = array();
		$formulas = $vbulletin->GPC['formula'];//copy

		if (sizeof($vbulletin->GPC['actions']))
		{
			foreach ($vbulletin->GPC['actions'] AS $actionid)
			{
				if ($action =& $vbulletin->vbcredits['action'][$actionid])
				{
					$clean = "'" . $db->escape_string($actionid) . "'";
					if ($action['rebuild']) $rebuilds[] = $actionid; else $resets[] = $clean;
					$actions[] = $clean;
				}
			}
		}

		if ($vbulletin->GPC['start_date'] <= $vbulletin->GPC['end_date']) $prunes[] = ( $vbulletin->GPC['alltime'] ? 1 : 'timestamp >= ' . $vbulletin->GPC['start_date'] . ' AND timestamp <= ' . $vbulletin->GPC['end_date'] );
		$reset = ( sizeof($resets) ? "actionid IN (" . implode(', ', $resets) . ") AND timestamp >= " . $vbulletin->GPC['start_date'] . " AND timestamp <= " . $vbulletin->GPC['end_date'] : '' );
		if (sizeof($actions) OR $vbulletin->GPC['allactions']) $prunes[] = ( $vbulletin->GPC['allactions'] ? 1 : 'actionid IN (' . implode(', ', $actions) . ')' );
		$prune = implode(' AND ', $prunes);
		unset($action);//doubling bug

		$hook_code = vBulletinHook::fetch_hook('credits_actions_rebuild'); //for later
		$vars = array('D', 'P', 'R', 'I');

		foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
		{	//clean up the formulas
			$invalid = false;
			$parens = array(0, 0);//numparens
			$vars[] = '[' . $currencyid . ']';
			$formula =& $formulas[$currencyid];			
			$formula = preg_replace('/\( *(\d+) *\)/', '$1', $formula);//bad lookalikes
			foreach ($vbulletin->vbcredits['currency'] AS $cur) $formula = str_replace('[' . $cur['title'] . ']', '(' . $cur['currencyid'] . ')', $formula);
			$formula = preg_replace('/[^\dDPRI\.\+\-\*\/\(\)]/', '', trim(strtoupper($formula)));

			for ($x = 0; $x < strlen($formula); $x++)
			{
				$bef = ( $x == 0 ? '?' : $formula[$x - 1] );
				$aft = ( $x == strlen($formula) - 1 ? '?' : $formula[$x + 1] );

				$checks = array(
					array('DPRI(', $bef, true, '*'),
					array('DPRI)', $aft, true, '*'),
					array('+-', $bef, false, '0'),
					array('+-', $aft, false, '0'),
					array('*/', $bef, false, '1'),
					array('*/', $aft, false, '1')
				);

				if ($formula[$x] == '.' AND (string) intval($bef) != $bef AND (string) intval($aft) != $aft) $formula[$x] = '0';
				if ($formula[$x] == '(' AND $aft == ')') $invalid = true;
				if ($formula[$x] == '(') $parens[0]++;
				if ($formula[$x] == ')') $parens[1]++;

				foreach ($checks AS $check)
				{
					if (strpos($check[0], $formula[$x]) !== false AND (strpos('+-/*?', $check[1]) === false) == $check[2])
					{
						$after = ($check[1] == $aft);
						$formula = substr($formula, 0, $x + intval($after)) . $check[3] . substr($formula, $x + intval($after));
						$x -= intval(!$after);
						break;
					}
				}

				if (strpos('*/', $formula[0]) !== false) $invalid = true;
			}
			//check if formula is still good and restore selectable currencyids
			if ($invalid OR $formula == '' OR $parens[0] != $parens[1]) print_stop_message('credits_bad_formula');
			foreach (array_keys($vbulletin->vbcredits['currency']) AS $curid) $formula = str_replace('(' . $curid . ')', '[' . $curid . ']', $formula);
		}
		foreach ($vbulletin->GPC['credits'] AS $userid => $currencies)
		{
			if ($notmyself = ($vbulletin->userinfo['userid'] != $userid))
			{	//load fresh cache each time
				unset($usercache[$userid]);
				$user = fetch_userinfo($userid);
				cache_permissions($user, false);
			}	//myself should be up to date
			else $user =& $vbulletin->userinfo;

			$perms =& $user['creditspermissions'];
			$perms = array(array(), array());

			if ($user['userid'])
			{
				$maths = array(
					floor((TIMENOW - $user['joindate']) / 86400),
					$user['posts'],
					'(' . $user['reputation'] . ')',
					$user['ipoints']
				);

				if ($vbulletin->GPC['recalculate'] AND $reset) $db->query_write("UPDATE " . TABLE_PREFIX . "credits_transaction SET eventid = 0 WHERE userid = $userid AND $reset");
				echo "\n<br />" . construct_phrase($vbphrase['processing_x'], $userid);
				vbflush();

				foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
				{
					$maths[] = '(' . doubleval($currencies[$currencyid]) . ')';
					if (empty($vbulletin->GPC['earns'][$currencyid][$userid])) $perms[0][] = $currencyid;
					if (empty($vbulletin->GPC['spend'][$currencyid][$userid])) $perms[1][] = $currencyid;
				}
				foreach ($formulas AS $currencyid => $fmla)
				{
					eval('$user[\'vbcredits_' . $currencyid . '\'] = ' . str_replace($vars, $maths, $fmla) . ';');

					if ($vbulletin->GPC['deletion'] AND $prune)
					{	//set to delete and something selected
						if ($vbulletin->GPC['reversal'])
						{	//fetch value of the transactions about to be deleted, and apply to user
							$total = $db->query_first("SELECT SUM(amount) AS total FROM " . TABLE_PREFIX . "credits_transaction WHERE userid = $userid AND currencyid = $currencyid AND status = 1 AND $prune");
							$user['vbcredits_' . $currencyid] -= $total['total'];
						}

						$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_transaction WHERE eventid != 0 AND userid = $userid AND currencyid = $currencyid AND $prune");
					}
				}

				$db->query_write("UPDATE " . TABLE_PREFIX . "user SET creditspermissions = '" . $db->escape_string(serialize($perms)) . "' WHERE userid = $userid");
				if ($notmyself) VBCREDITS::update($user); //if myself, update on shutdown
				$updated++;

				if ($vbulletin->GPC['recalculate'])
				{	//finish resetting the !rebuild actions
					if ($reset) $db->query_write("UPDATE " . TABLE_PREFIX . "credits_transaction SET amount = 0, status = 0 WHERE userid = $userid AND $reset");

					foreach ($rebuilds AS $actionid)
					{	//switch($actionid) - use $user['userid'] and $vbulletin->GPC['start_date'] <= $vbulletin->GPC['end_date']
						eval($hook_code);//cached 'credits_actions_rebuild'
						echo '.';//show some output
					}
				}
			}
			//fix bugs
			unset($user);
		}
		if ($vbulletin->GPC['allusers'])
		{
			if ($vbulletin->GPC['totalusers'])
			{	//finished auto processing
				$vbulletin->GPC['allusers'] = false;
				$updated = $vbulletin->GPC['totalusers'];
			}	//automatically move to the next page
			else echo '</div><style type="text/css">#cpform { display: none; }</style><script type="text/javascript">jQuery(function($) { $("#update_users").click(); });</script>';
		}
		if (!$vbulletin->GPC['allusers'])
		{	//hide processing messages
			if ($vbulletin->GPC['limitstart']) $vbulletin->GPC['limitstart'] -= $vbulletin->GPC['limitnumber'];//roll back
			echo '</div><style type="text/css">#credits_output { display: none; }</style>';
		}
	}

	$hook_query_fields = $hook_query_joins = '';
	VBCREDITS::user($hook_query_fields, $hook_query_joins);

	$countusers = $db->query_first("
		SELECT COUNT(*) AS users
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
		$hook_query_joins
		WHERE $condition
	");

	if (empty($vbulletin->GPC['limitnumber'])) $vbulletin->GPC['limitnumber'] = 50;
	if ($vbulletin->GPC['direction'] != 'DESC') $vbulletin->GPC['direction'] = 'ASC';
	if (empty($vbulletin->GPC['limitstart']) OR $vbulletin->GPC['limitstart'] > $countusers['users']) $vbulletin->GPC['limitstart'] = 0;
	else $vbulletin->GPC['limitstart']--;

	$searchquery = "
		SELECT
		user.*, (options & " . $vbulletin->bf_misc_useroptions['coppauser'] . ") AS coppauser, userfield.*
		$hook_query_fields
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
		$hook_query_joins
		WHERE $condition
		ORDER BY " . $db->escape_string($vbulletin->GPC['orderby']) . " " . $db->escape_string($vbulletin->GPC['direction']) . "
		LIMIT " . $vbulletin->GPC['limitstart'] . ", " . $vbulletin->GPC['limitnumber']
	;

	$users = $db->query_read($searchquery);
	if ($countusers['users'] == 0) print_stop_message('no_users_matched_your_query');
	print_form_header('credits_admin', 'findusers');

	if ($updated)
	{	//show number updated and pending process link
		print_description_row(construct_phrase($vbphrase['credits_users_updated'], vb_number_format($updated), ( $vbulletin->GPC['recalculate'] ? $vbphrase['credits_rebuild_recount'] : '' )));
		print_table_break();
	}

	print_column_style_code(array('width: 70%', 'width: 30%'));
	print_table_header($vbphrase['credits_acp_editusers']);
	print_description_row($vbphrase['credits_recalculate_info']);

	$actions = array();
	$cursize = sizeof($vbulletin->vbcredits['action']);
	if ($cursize < 2) $cursize = 2; else if ($cursize > 7) $cursize = 7;

	foreach ($vbulletin->vbcredits['action'] AS $actionid => $action)
	{	//only allow actions that have events
		if (is_array($vbulletin->vbcredits['event']) AND array_key_exists($actionid, $vbulletin->vbcredits['event'])) $actions[$vbphrase['credits_category_' . $action['category']]][$actionid] = $action['title'] . ( !$action['rebuild'] ? '*' : '' );
	}
	if ($actions)
	{	//needs events to work
		print_select_row($vbphrase['credits_users_actions'], 'actions[]', $actions, $vbulletin->GPC['actions'], false, $cursize, true);
		print_time_row($vbphrase['credits_users_startdate'], 'start_date', $vbulletin->GPC['start_date']);
		print_time_row($vbphrase['credits_users_enddate'], 'end_date', $vbulletin->GPC['end_date']);
		print_yes_no_row($vbphrase['credits_users_recalculate'], 'recalculate', $vbulletin->GPC['recalculate']);
	}

	print_yes_no_row($vbphrase['credits_users_deletion'], 'deletion', $vbulletin->GPC['deletion']);
	print_yes_no_row($vbphrase['credits_users_reversal'], 'reversal', $vbulletin->GPC['reversal']);
	print_yes_no_row($vbphrase['credits_users_alltime'], 'alltime', $vbulletin->GPC['alltime']);
	print_yes_no_row($vbphrase['credits_users_allactions'], 'allactions', $vbulletin->GPC['allactions']);

	print_description_row($vbphrase['credits_formulas_info']);

	$depends = 'vbcredits_depend = { setting: \'deletion\', value: 1, depends: [\'' . implode("', '", array('reversal', 'allactions', 'alltime')) . '\'] }';
	$advanced = array('formula', 'actions', 'start_date', 'end_date', 'recalculate', 'deletion', 'reversal', 'allactions', 'alltime', 'allusers');

	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
	{
		print_input_row(construct_phrase($vbphrase['credits_currency_formula'], $currency['title']), 'formula[' . $currencyid . ']', ( $vbulletin->GPC_exists['formula'] ? $vbulletin->GPC['formula'][$currencyid] : '[' . $currency['title'] . ']' ));
	}

	print_yes_no_row($vbphrase['credits_users_allusers'], 'allusers', $vbulletin->GPC['allusers']);
	print_table_footer(2, '<div align="center"><input type="submit" class="button" name="update" value=" ' . $vbphrase['credits_update_users'] . ' " id="update_users" tabindex="1" /></div>', '', false);
	print_advanced_toggle();
	print_table_start();
	$limitfinish = $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'];
	$colspan = sizeof($header);

	print_table_header(
		construct_phrase(
			$vbphrase['showing_users_x_to_y_of_z'],
			($vbulletin->GPC['limitstart'] + 1),
			iif($limitfinish > $countusers['users'], $countusers['users'], $limitfinish),
			$countusers['users']
		), $colspan);
	print_cells_row($header, 1);

	while ($user = $db->fetch_array($users))
	{
		if ($vbulletin->userinfo['userid'] == $user['userid']) $user = $vbulletin->userinfo;
		$perms = ( is_array($user['creditspermissions']) ? $user['creditspermissions'] : ( $user['creditspermissions'] ? unserialize($user['creditspermissions']) : array(array(), array()) ) );

		$cells = array(
			"<a href=\"credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=transactions&amp;u=$user[userid]\"><b>$user[username]</b></a>&nbsp;",
			$vbulletin->usergroupcache[$user['usergroupid']]['title'],
			intval(floor((TIMENOW - $user['joindate']) / 86400)),//0 bug
			vb_number_format($user['posts']),
			vb_number_format($user['reputation']),
			vb_number_format($user['ipoints'])
		);

		foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
		{
			$cells = array_merge($cells, array(
				'<input type="checkbox" name="earns[' . $currencyid . '][' . $user['userid'] . ']" value="1"' . ( !in_array($currencyid, $perms[0]) ? ' checked="checked"' : '' ) . ' />',
				'<input type="checkbox" name="spend[' . $currencyid . '][' . $user['userid'] . ']" value="1"' . ( !in_array($currencyid, $perms[1]) ? ' checked="checked"' : '' ) . ' />',
				'<input type="text" class="bginput" name="credits[' . $user['userid'] . '][' . $currencyid . ']" value="' . $user['vbcredits_' . $currencyid] . '" size="10" />'
			));
		}

		print_cells_row($cells);
	}

	construct_hidden_code('serializeduser', sign_client_string(serialize($vbulletin->GPC['user'])));
	construct_hidden_code('serializedprofile', sign_client_string(serialize($vbulletin->GPC['profile'])));
	construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
	construct_hidden_code('limitnumber', $vbulletin->GPC['limitnumber']);
	construct_hidden_code('orderby', $vbulletin->GPC['orderby']);
	construct_hidden_code('direction', $vbulletin->GPC['direction']);

	if ($lastpage = ($limitfinish >= $countusers['users'])) construct_hidden_code('totalusers', $countusers['users']);
	if ($vbulletin->GPC['limitstart'] == 0 AND $countusers['users'] > $vbulletin->GPC['limitnumber']) print_submit_row($vbphrase['next_page'], 0, $colspan);
	else if ($limitfinish < $countusers['users']) print_submit_row($vbphrase['next_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	else if ($vbulletin->GPC['limitstart'] > 0 AND $lastpage) print_submit_row($vbphrase['first_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	else print_table_footer();
}

// ##################### Start Account Index ###################################

if ($_REQUEST['do'] == 'users')
{
	if (empty($vbulletin->vbcredits['currency'])) print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit_currency");

	print_table_start();
	print_description_row($vbphrase['credits_users_info']);
	print_table_footer(1, '', '', false);

	$currencies = '';
	print_form_header('credits_admin', 'findusers');
	print_table_header($vbphrase['advanced_search']);
	print_description_row($vbphrase['if_you_leave_a_field_blank_it_will_be_ignored']);
	print_description_row('<img src="../' . $vbulletin->options['cleargifurl'] . '" alt="" width="1" height="2" />', 0, 2, 'thead');

	foreach ($vbulletin->vbcredits['currency'] AS $currencyid => $currency)
	{
		print_input_row(construct_phrase($vbphrase['credits_greater_equal'], $currency['title']), 'user[vbcredits_' . $currencyid . '_lower]');
		print_input_row(construct_phrase($vbphrase['credits_less_than'], $currency['title']), 'user[vbcredits_' . $currencyid . '_upper]');
		$currencies .= '<option value="vbcredits_' . $currencyid . '">' . $currency['title'] . '</option>';
	}

	print_description_row('<img src="../' . $vbulletin->options['cleargifurl'] . '" alt="" width="1" height="2" />', 0, 2, 'thead');
	print_user_search_rows();
	print_table_break();

	print_table_header($vbphrase['sorting_options']);
	print_label_row($vbphrase['order_by'], '
		<select name="orderby" tabindex="1" class="bginput">
		<option value="username" selected="selected">' . 	$vbphrase['username'] . '</option>
		<option value="joindate">' . $vbphrase['join_date'] . '</option>
		<option value="posts">' . $vbphrase['post_count'] . '</option>
		<option value="reputation">' . $vbphrase['reputation'] . '</option>
		<option value="ipoints">' . $vbphrase['infraction_points'] . '</option>
		' . $currencies . '
		</select>
		<select name="direction" tabindex="1" class="bginput">
		<option value="">' . $vbphrase['ascending'] . '</option>
		<option value="DESC">' . $vbphrase['descending'] . '</option>
		</select>
	', '', 'top', 'orderby');
	print_input_row($vbphrase['starting_at_result'], 'limitstart', 1);
	print_input_row($vbphrase['maximum_results'], 'limitnumber', 50);

	print_submit_row($vbphrase['find'], $vbphrase['reset'], 2, '', '<input type="submit" class="button" value="' . $vbphrase['exact_match'] . '" tabindex="1" name="user[exact]" />');
}

// ##################### Start Transaction Delete ###################################

if ($_REQUEST['do'] == 'delete_transaction')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'transactionid'	=> TYPE_UINT,
		'confirm'		=> TYPE_BOOL
	));

	if ($vbulletin->GPC['confirm'])
	{
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "credits_transaction WHERE transactionid = " . $vbulletin->GPC['transactionid']);
		define('CP_REDIRECT', 'credits_admin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=transactions');
		print_stop_message('credits_transaction_deleted');
	}
	else print_delete_confirmation('credits_transaction', $vbulletin->GPC['transactionid'], 'credits_admin', 'delete_transaction', 'transaction', array('confirm' => 1), '', 'actionid');
}

// ##################### Start Account Index ###################################

if ($_REQUEST['do'] == 'process_transactions')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'	=> TYPE_UINT
	));

	if (empty($vbulletin->GPC['perpage'])) $vbulletin->GPC['perpage'] = 1000;

	echo $vbphrase['credits_processing_transactions'] . '<br />';
	$users = $db->query_read("
		SELECT userid
		FROM " . TABLE_PREFIX . "user
		WHERE userid >= " . $vbulletin->GPC['startat'] . "
		ORDER BY userid
		LIMIT " . $vbulletin->GPC['perpage']
	);

	$finishat = $vbulletin->GPC['startat'];

	while ($userid = $db->fetch_array($users))
	{	//we need to become this user
		if ($notmyself = ($vbulletin->userinfo['userid'] != ($userid = $userid['userid'])))
		{	//load fresh cache each time
			unset($usercache[$userid]);
			$user = fetch_userinfo($userid);
			cache_permissions($user, false);
		}	//myself should be up to date
		else $user =& $vbulletin->userinfo;

		echo "\n<br />" . construct_phrase($vbphrase['processing_x'], $user['userid']);
		vbflush();

		while (true)
		{	//keep processing until nothing new added
			$inserts = false;
			VBCREDITS::commit();//insert from last loop
			$transactions = $db->query_read("SELECT t.* FROM " . TABLE_PREFIX . "credits_transaction AS t LEFT JOIN " . TABLE_PREFIX . "credits_event AS e ON (t.eventid = e.eventid) WHERE t.userid = " . $user['userid'] . " AND t.status = 0 AND t.timestamp <= (" . TIMENOW . " - IF(ISNULL(e.eventid), 0, e.delay)) ORDER BY t.transactionid ASC");

			while ($trans = $db->fetch_array($transactions))
			{
				VBCREDITS::process($trans, $user);
				if (!$trans['eventid']) $inserts = true;
				echo '.';//show some output
			}

			$db->free_result($transactions);
			if (!$inserts) break;
		}

		if ($notmyself) VBCREDITS::update($user); //update myself on shutdown
		$finishat = ($userid > $finishat ? $userid : $finishat);
		unset($user);//bugfix
	}

	$finishat++; // move past the last processed user

	if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=process_transactions&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href=\"credits_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=process_transactions&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'misc.php');
		print_stop_message('transactions_processed_successfully');
	}
}

// ##################### Cleanup ###################################

$jquery = array();
if ($depends) $jquery[] = $depends;
if ($advanced) $jquery[] = 'vbcredits_advset = [\'' . implode("', '", $advanced) . '\']';
if ($transfer) $jquery[] = 'vbcredits_transfer = [' . implode(', ', $transfer) . ']';

if (sizeof($jquery))
{
	?><script type="text/javascript">var <?php echo implode(', ', $jquery); ?>;</script><?php
}

print_cp_footer();

function print_advanced_toggle()
{
	global $vbphrase;
	print_table_start();
	print_table_footer(1, '<button id="advanced_toggle" class="button">' . $vbphrase['credits_advanced_toggle'] . '</button>', '', false);
}
?>