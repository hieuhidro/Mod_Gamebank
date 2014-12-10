<?php
/*
============================
QuickCaptcha 1.0 - A bot-thwarting text-in-image web tool.
Copyright (c) 2006 Web 1 Marketing, Inc.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
============================
See settings.php for common settings. You shouldn't need to change
anything in this file.
============================
*/
include "settings.php";

session_start();
$string = strtoupper($_SESSION['string']);
$userstring = strtoupper($_POST['userstring']); 
session_destroy();   

if (($string == $userstring) && (strlen($string) > 4)) {
	header("Location: $success");
	exit();
} else {
	header("Location: $failure");
	exit();
}
?>