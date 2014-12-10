
//#######################################       GAME BANK      #######################################

import file product-game_bank.xml to install mod. 

Follow Setting->Option->Game Bank to active mode and setting 

import {vb:raw ad_location.your_payment} to FORUMHOME Template to show mod. 
insert 1 column to table user in database, add name column to setting value ... Payment Column 

//####################################################################################################


Copy Over write includes/class_dm_user.php, admincp/user.php


<!-- Header template
--
-- Insert before Welcome <li> 
--
-->
{vb:raw ad_location.payment_url}

<!-- End Header template -->

<!-- ######################################################################################## -->
<!-- Insert to navbar --> 
<!--
  --Before What New ... 
  -->
  {vb:raw template_hook.navtab_middle}
<!-- End Insert to navbar --> 
<!-- ######################################################################################## -->


<!-- ####################################    widget   ####################################### -->
<!-- Content widget -->
<!--
-- Don't change everything, copy and pate to widget content
-- Không được thay đổi gì ở đây. 
-- Copy pate toàn bộ vào content widget ... 
-->

<vb:if condition="$show['member']">
	<vb:if condition="$vboptions[payment_enable] == 1">
	<div class="cms_widget">
		<div class="block">
			<div class="cms_widget_header">
			<h3>{vb:raw payment_title}</h3>
			<h4>{vb:raw payment_detail}</h4>
			</div>
			<div class="cms_widget_content">
				 {vb:raw payment_content}
			</div>
		</div>
	</div>
	</vb:if>
</vb:if>
<!-- End content widget --> 
<!-- ######################################################################################## -->



<!-- ##################################  MAINFORM  ########################################### -->
<!-- Content MAINFORM template-->
<!--
-- Don't change everything 
-- Không được thay đổi gì ở đây.
-- include to tempate {vb:raw ad_location.your_payment}
-->

<vb:if condition="$show['member']">
	<vb:if condition="$vboptions[payment_enable] == 1">
	<div id="wgo_payment" class="collapse wgo_block block" style="float: left;">
		<h2 class="blockhead">{vb:raw payment_title}</h2>
		<h3 class="blockhead">{vb:raw payment_detail}</h3>
		<div class="blockbody formcontrols floatcontainer">
			{vb:raw payment_content}
		</div>
	</div>
	</vb:if>
</vb:if>
<!-- End content MAINFORM template-->
<!-- ######################################################################################## -->


<!-- ######################################################################################## -->
<!-- Content with header template -->
<!-- 
-- Pate to header template 
-- Url to page payment online 
-- Pate to inside this code from header template
-- <div id="toplinks" class="toplinks">
		<vb:if condition="$show['member']">
			<ul class="isuser">
--
-->
<vb:if condition="$show['member']">
	<vb:if condition="$vboptions[payment_enable] == 1">
		<li><a href="payment/index.php" rel="Nap tiền vào tài khoản">Nạp Tiền/Payment</a></li>
	</vb:if>
</vb:if>
<!-- Content with header template -->
<!-- ######################################################################################## -->



<!-- ######################################################################################## -->
<!-- Content Content with another content -->
<!-- 
-- Edit everything 
-- Don't cut or edit 3 validate ({vb:raw payment_title},{vb:raw payment_detail},{vb:raw payment_content}) and 2 if query <vb:if ... >
-- Có thể thay đổi bất cứ thứ gì nhưng không được thay đổi 3 trường {vb:raw} và 2 điều kiện <vb:if ... >
-- Copy pate vào trong vùng content bạn muốn hiển thị nội dung. 
-->

<vb:if condition="$show['member']">
	<vb:if condition="$vboptions[payment_enable] == 1">
	<div id="wgo_payment_other" class="collapse wgo_block block">
		<h2 class="blockhead">{vb:raw payment_title}</h2>
		<h3 class="blockhead">{vb:raw payment_detail}</h3>
		<div class="blockbody formcontrols floatcontainer">
			{vb:raw payment_content}
		</div>
	</div>
	</vb:if>
</vb:if>
<!-- Content Content with another content -->
<!-- ######################################################################################## -->
