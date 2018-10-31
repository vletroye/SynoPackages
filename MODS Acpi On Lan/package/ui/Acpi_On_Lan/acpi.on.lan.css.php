<?php
	// https://code.google.com/p/php-mobile-detect
	require_once 'mobile_detect.php';

	header('content-type: text/css');
	ob_start('ob_gzhandler');
	header('Cache-Control: max-age=31536000, must-revalidate');
	
	$detect = new Mobile_Detect;
	if ($detect->isMobile()) {
		$fontSize='200%';
		$menuIconSize='24px';
		$menuIconPadding='36px';
	} else {
		$fontSize='100%';
		$menuIconSize='18px';
		$menuIconPadding='28px';
	}
?>

.ACPI {
	font-Size:<?php echo $fontSize; ?>;
	color:#ffffff;
}

.notifications {
	position:fixed;
	bottom:10px;
	right:0;
	z-index:2000;
	padding: 15px;
	margin-bottom: 20px;
	border: 1px solid transparent;
	border-radius: 4px;
	font-Size:<?php echo $fontSize; ?>;
}

/*!
 * Overwritte context-menu-list to enforce font-Size
 */
.context-menu-list {
    margin:0; 
    padding:0;
    
    min-width: 80px;
    max-width: 500px;
    display: inline-block;
    position: absolute;
    list-style-type: none;
    
    border: 1px solid #DDD;
    background: #EEE;
    
    -webkit-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
       -moz-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        -ms-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
         -o-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
    
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: <?php echo $fontSize; ?>;
}

.context-menu-item {
    padding: 2px 2px 2px <?php echo $menuIconPadding; ?>;
    background-color: #EEE;
    position: relative;
    -webkit-user-select: none;
       -moz-user-select: -moz-none;
        -ms-user-select: none;
            user-select: none;
}

.context-menu-item.icon { min-height: <?php echo $menuIconSize; ?>; background-repeat: no-repeat; background-position: 4px 2px; }
.context-menu-item.icon-windows { background-image: url(images/os_windows.png); background-size: <?php echo $menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-mac { background-image: url(images/os_mac.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-linux { background-image: url(images/os_linux.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-android { background-image: url(images/os_android.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-other { background-image: url(images/os_other.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-syno { background-image: url(images/os_syno.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-check { background-image: url(images/check.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-operating { background-image: url(images/operating.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-type { background-image: url(images/type.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-fetch { background-image: url(images/fetch.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-icon { background-image: url(images/search.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-reset { background-image: url(images/os_empty.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}
.context-menu-item.icon-browse { background-image: url(images/browse.png); background-size: <?php echo $$menuIconSize." ".$menuIconSize; ?>;}