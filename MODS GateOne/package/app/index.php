<?php

//file_get_contents("http://bot.whatismyipaddress.com"); 

$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
$server = $protocol.$_SERVER['SERVER_NAME'].':8271';

//temporary redirection
header('Location:'.$server,TRUE,307);
exit();
?>