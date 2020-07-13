<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Home Temperature</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style type="text/css">
   	body { padding-top: 70px; }
	</style>

	<!-- Font Awsome -->
	<link href="font-awesome/css/font-awesome.min.css" rel="stylesheet">
	
	<!-- Bootstrap -->
	<link href="bootstrap/css/bootstrap.css" rel="stylesheet">
	
	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
	   <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	<![endif]-->
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript">
	  function iframeLoaded(id) {
		  var iFrameID = document.getElementById(id);
		  if(iFrameID) {
				// here you can make the height, I delete it first, then I make it again
				iFrameID.height = "";
				iFrameID.height = iFrameID.contentWindow.document.body.scrollHeight + "px";
		  }   
	  }
	</script>   	
</head>
	<!--page content-->
<body>
	<div class="container">
		<?php
		define('ANTI_HACK', true);

		// Init env
		include "include/init_conf.php";
		$list = json_decode($devices);
		foreach($list as $item) {
			echo "<iframe  id=\"$item\" onload=\"iframeLoaded('$item');\" src=\"daikin.php?ip=$item&name=Daikin\" frameborder=\"0\" style=\"width: 100%\" scrolling=\"no\"></iframe>";
		}
		?>
		<!--<iframe  id="Guest" onload="iframeLoaded('Guest');" src="daikin.php?ip=192.168.0.31&name=Daikin Guest" frameborder="0" style="width: 100%" scrolling="no"></iframe>
		<iframe  id="Living" onload="iframeLoaded('Living');" src="daikin.php?ip=192.168.0.32&name=Daikin Living" frameborder="0" style="width: 100%" scrolling="no"></iframe>
		<iframe  id="Office" onload="iframeLoaded('Office');" src="daikin.php?ip=192.168.0.30&name=Daikin Office" frameborder="0" style="width: 100%" scrolling="no"></iframe>-->
	</div>
	<br>
</body>
</html>
