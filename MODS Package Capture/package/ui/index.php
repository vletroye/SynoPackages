<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Package Capture</title>

	<!-- JQuery -->
	<!--script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script-->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	
	<!-- JQuery UI -->	
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
	<link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/ui-lightness/jquery-ui.css" rel="stylesheet" type="text/css" />

	<!-- http://johnculviner.com/jquery-file-download-plugin-for-ajax-like-feature-rich-file-downloads/ -->
	<script src="jquery.fileDownload.js" type="text/javascript"></script>


	<style>
	.center-div
	{
	  position: absolute;
	  margin: auto;
	  top: 0;
	  right: 0;
	  bottom: 0;
	  left: 0;
	  width: 200px;
	  height: 100px;
	  background-color: #ccc;
	  border-radius: 3px;
	}	

	#container {
	  width: 200px;
	  height: 100px;
	}

	#content {
	  position: relative;
	  float: left;
	  top: 50%;
	  left: 50%;
	  transform: translate(-50%, -50%);
	  text-align: center;
	}	
	</style>
</head>
<body style="background-color:grey;">
<script type="text/javascript"> 
//<![CDATA[ 

var cancel=false;

//Custom rich user experience - jquery.fileDownload.js & jQuery UI Dialog
//uses the optional "options" argument
//
//      the below uses jQuery "on" http://api.jquery.com/on/ (jQuery 1.7 + required, otherwise use "delegate" or "live") so that any 
//      <a class="fileDownload..."/> that is ever loaded into an Ajax site will automatically use jquery.fileDownload.js
//      if you are using "on":
//          you should generally be able to reduce the scope of the selector below "document" but it is used in this example so it
//          works for possible dynamic manipulation in the entire DOM
//
$(function () {
    $(document).on("click", "a.fileDownload", function () { 
        $("#preparing-file-modal").dialog({ modal: true });
		cancel=false;
		
		var volume = $("#volume").find('option:selected').val();
        $.fileDownload("services.php?service=capture&parameters="+volume, {
            successCallback: function (url) { 
				cancel=true;
                //$("#preparing-file-modal").dialog('close');
				$(".ui-dialog-content").dialog("close");
            },
            failCallback: function (responseHtml, url) {
                //$("#preparing-file-modal").dialog('close');
				$(".ui-dialog-content").dialog("close");
				if (responseHtml=="Cancelled") {
					$("#cancel-modal").dialog('close');
				} else if (cancel==true) {
				} else if (responseHtml=="Timeout") {
					cancel=true;
					$("#timeout-modal").dialog({ modal: true });
				} else if (responseHtml=="Not Found") {
					cancel=true;
					$("#notfound-modal").dialog({ modal: true });
				} else {
					cancel=true;
					$("#error-modal").dialog({ modal: true });
				}
            }
        });
        return false; //this is critical to stop the click event which will trigger a normal file download!
    });
	
    $(document).on("click", "a.recover", function () { 
        $("#recovering-file-modal").dialog({ modal: true });
		cancel=true;
		
		var volume = $("#volume").find('option:selected').val();
        $.fileDownload("services.php?service=recover&parameters="+volume, {
            successCallback: function (url) { 
				$(".ui-dialog-content").dialog("close");
                //$("#recovering-file-modal").dialog('close');
            },
            failCallback: function (responseHtml, url) {
				$(".ui-dialog-content").dialog("close");
                //$("#recovering-file-modal").dialog('close');
				$("#error-modal").dialog({ modal: true });
            }
        });
        return false; //this is critical to stop the click event which will trigger a normal file download!
    });
	
	$(document).on("click", "span.ui-icon-closethick", function () {
		if (cancel==false) {			
		  cancel=true;
		  //$("#recovering-file-modal").dialog('close');
		  $(".ui-dialog-content").dialog("close");
	      $("#cancel-modal").dialog({ modal: true });
		  $.getJSON( "services.php", { service: "cancel", parameters: "volume1" } )
		}
		return false; //this is critical to stop the click event which will trigger a normal file download!
	});
});
//]]> 
</script> 

<div class="center-div">
<div id="container">
  <div id="content">
	Volume:<select id="volume">
	<?php
	for( $i=1; $i<12; $i++) {
		$path = "/volume$i/@tmp";
		if (is_dir($path)) {
			echo "<option value='volume$i'>Volume$i</option>";
		}
	}
	?>
	</select><br/><br/>
	<a class="fileDownload" href="#">Click here to<br/> start the capture</a>
  </div>
</div>
<br />
<div align="right">
	<a class="recover" href="#">Click here to recover</a>
<div>
</div>

<div id="preparing-file-modal" title="Capturing..." style="display: none;">
    Please, install now your package. If it cannot be captured within 3 minutes, this process will die.
 
    <div class="ui-progressbar-value ui-corner-left ui-corner-right" style="width: 100%; height:22px; margin-top: 20px;"></div>
</div>

<div id="recovering-file-modal" title="Recovering..." style="display: none;">
    Please wait while looking if an SPK is available in the Capture folder.
	
	<div class="ui-progressbar-value ui-corner-left ui-corner-right" style="width: 100%; height:22px; margin-top: 20px;"></div>
</div>
 
<div id="error-modal" title="Error" style="display: none;">
    There was a problem when capturing your package. Please look into the log file for more details.
</div>

<div id="timeout-modal" title="Time Out" style="display: none;">
    The SPK has not been found within the limit of 2 minutes.
</div>

<div id="notfound-modal" title="Not Found" style="display: none;">
    There was a problem when capturing your package. The SPK is there but couldn't be captured ?!?.
	Look for it, using a SSH console, into the folder <volumex>\@tmp\SynoCapture.
</div>

<div id="cancel-modal" title="Cancelled" style="display: none;">
    You have cancelled the capture of your package. Please wait until the process die.
</div>

</body>
</html>