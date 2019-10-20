<?php
$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
$server = $protocol.$_SERVER['SERVER_NAME'].':8271';
header('Location:'.$server,TRUE,301);
?>