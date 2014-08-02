<?php 

/**
 * 
 * Widget save image ... 
 * Template hook save stick thread ....
 * Hook thread event .... 
 * ($hook = vBulletinHook::fetch_hook('threadmanage_update')) ? eval($hook) : false;
 * <vb:if condition="$show['removeoption']">
 */
global $threadinfo;
print_r($threadinfo);
echo $_POST['do'];

if($threadinfo['sticky']){
	
}

echo $threadinfo['postusername'];

echo $threadinfo['sticky'];
exit();

?>