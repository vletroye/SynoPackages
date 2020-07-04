<?php
if ( "@MODS_HTTP@" == "true") {
	$protocol="http://";
} else {
	$protocol="https://";
}

$server = $protocol.$_SERVER['SERVER_NAME'].':@MODS_PORT@';
header('Location:'.$server,TRUE,307);
?>
