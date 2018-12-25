<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>ENTER HERE YOUR TITLE</title>
    <meta name="description" content="ENTER HERE YOUR DESCRIPTION" />	
	<link rel="shortcut icon" href="resources/favicon.ico" />
	
	<!-- with chrome, it's not possible to disable the automatic 
	zoom-out when entering an input field so we force the viewport -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<!-- php can be validated with http://phpcodechecker.com -->
	<!-- icons can be found on https://www.iconfinder.com -->
	<!-- animated gif can be created with http://preloaders.net/ -->
	<!-- Rest Services can be tested with https://bit.ly/K5yopu -->
	
	<!-- JQuery -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<!--script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script-->
    
	<!-- Bootstrap (Must be loaded after jquery/min.css otherwise it fails) -->
	<!--           (Must be loaded before jquery-ui.css otherwise style does not applies correctly -->
	<!--           (See http://stackoverflow.com/questions/17367736/jquery-ui-dialog-missing-close-icon) -->
	<link href="resources/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<script src="resources/bootstrap.min.js"></script>
	
	<!-- Bootbox is a small JavaScript library which allows you to create programmatic dialog boxes -->
	<!-- http://bootboxjs.com/ -->
	<script src="resources/bootbox.min.js" type="text/javascript"></script>

	<!-- JQuery UI -->	
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
	<link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/ui-lightness/jquery-ui.css" rel="stylesheet" type="text/css" />

	<!-- JQuery Context Menu -->
	<!-- http://medialize.github.io/jQuery-contextMenu/ -->
	<link href="resources/jquery.contextMenu.css" rel="stylesheet" type="text/css" />
	<script src="resources/jquery.ui.position.js" type="text/javascript"></script>
	<script src="resources/jquery.contextMenu.js" type="text/javascript"></script>

	<!-- JQuery Donwload File -->
	<!-- http://johnculviner.com/jquery-file-download-plugin-for-ajax-like-feature-rich-file-downloads/ -->
	<script src="resources/jquery.fileDownload.js" type="text/javascript"></script>
	
	<!-- Services Scripts -->
	<script src="services.js" type="text/javascript"></script>
	<link href="services.css" rel="stylesheet" type="text/css" />
</head>

<!-- Create the Body of the application -->
<body id='application' oncontextmenu='return false;'>
<script>
$(window).load(function () {
    $('.error_popup').click(function(){
        $('.error_popup').hide();
    });
    $('.error_popupCloseButton').click(function(){
        $('.error_popup').hide();
    });
});
</script>
<div class='error_popup'>
    <span class='error_helper'></span>
    <div>
        <div class='error_popupCloseButton'>X</div>
        <div id='error_details' style='text-align:left;'></div>
    </div>
</div>
<!-- ---------------------------------------------------------------- -->
<!-- CREATE YOUR APPLICATION UI HERE UNDER -->
<!-- ---------------------------------------------------------------- -->
<script>
var jsonParam = {text: "Hello Json World"};
</script>
<div class='box' id='page'>
<?php

?>
<a href="#" onclick="CallService('Implemented','value','result'); return false">Implemented</a><br/>
<a href="#" onclick="CallService('Missing','value','result'); return false">Missing</a><br/>
<a href="#" onclick="CallService('Do nothing','value','result'); return false">Do nothing</a><br/>
<a href="#" onclick="CallService('DynamicString','Hello String World','result'); return false">Dynamic with String</a><br/>
<a href="#" onclick="CallService('DynamicJson', jsonParam,'result'); return false">Dynamic with Json</a><br/>
<a href="#" onclick="CallService('Throw','value','result'); return false">Throw error</a>
</div>
<hr>
<div class='box' id='result'>&nbsp;</div>
<div class='error' id='error_logs' style='text-align:left;display:none;'></div>
</body>