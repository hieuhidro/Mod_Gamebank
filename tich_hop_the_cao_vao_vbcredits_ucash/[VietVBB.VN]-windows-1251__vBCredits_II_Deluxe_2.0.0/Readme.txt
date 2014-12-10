/*=======================================================================*\
|| ##################################################################### ||
|| # vBCredits II Deluxe 2.0.0: Evolution of the Points Hack		   # ||
|| # ------------------------------------------------------------------# ||
|| # Author: Darkwaltz4 {blackwaltz4@msn.com}						   # ||
|| # Copyright � 2009 - 2010 John Jakubowski. All Rights Reserved.	   # ||
|| # This file may not be redistributed in whole or significant part.  # ||
|| # -----------------vBulletin IS NOT FREE SOFTWARE!------------------# ||
|| #			 Support: http://www.dragonbyte-tech.com/			   # ||
|| ##################################################################### ||
\*=======================================================================*/



/*======================================================================*\
|| License                                                           ||
\*======================================================================*/

vBCredits II Deluxe (Pro) is released under the All Rights Reserved license.
You may not redistribute the package in whole or significant part.
All copyright notices must remain unchanged and visible.
You may provide phrase .xml files for other languages on any site,
but you may not provide the full product .xml file - only the phrases.


/*======================================================================*\
|| First Time Installation / Upgrade                                 ||
\*======================================================================*/

1. Upload all files from the "forum" folder to your forums directory (rename the admincp folder if you have done so).

2. Import the product-credits.xml file from the "XML" folder at AdminCP -> Plugins & Products -> Manage Products ->
   Add/Import Product

3. That's it! You can start editing settings which you will be initially directed to, as well as usergroup permissions.


/*======================================================================*\
|| Note About Transactions			                                 ||
\*======================================================================*/

vBCredits II Deluxe queues all transactions so that the server load is spread out. You will process one transaction
for yourself per pageload. This means actions that affect others will need for them to log in. If this is unacceptable,
there is a scheduled task available for enabling, which you can find linked in the main settings. Large or busy forums
should not enable this cron, and all of the activity will mask this delay anyway.

So, keep in mind that you may not always immediately see your credits show up in your account after an action. If you are
unsure, there is also a tool for processing all pending transactions in the update counters page, or you can run once
the scheduled task a few times.

If you think your server can handle it, you can also enable immediate transactions in the global configuration, which
eliminates most of the pending stuff described above.

New: Automatic transactions are now enabled by default.


/*======================================================================*\
|| Donate/Transfer/Adjust/Purchase/Redeem/Charge                     ||
\*======================================================================*/

To enable these, go to the event manager and choose them from the list to create new events.
You may simply save them to activate, or adjust the normal event settings as you see fit, such as for fees or limits.

Whenever currencies are displayed, you may click on the value to open up the transfers popup.
From here, sending to yourself is a transfer, and sending to someone else is a donate.
If you enable the usergroup permission, adjust is available from the popup as well, which does not use your currency account.

New: Upon fresh install, the first currency should already be filled out with events for these.


/*======================================================================*\
|| Memberlist						                                 ||
\*======================================================================*/

To use the memberlist display, you need to insert the following into your memberlist_resultsbit template above the closing </tr>:

For vB4.x
{vb:raw template_hook.memberlist_resultsbit}
For vB3.x
$template_hook[memberlist_resultsbit]