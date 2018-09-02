<?php

$server = 'http://'.$_SERVER['SERVER_ADDR'].':8271';

header('Location:'.$server,TRUE,301);
exit();
?>