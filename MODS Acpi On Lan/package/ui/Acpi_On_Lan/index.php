<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>ACPI-On-Lan Web Interface</title>
    <meta name="description" content="Simple UI to power-manage devices on the same LAN as your NAS" />
	
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- with chrome, it's not possible to disable the automatic zoom-out when entering an input field so we force the viewport -->
	
	<link rel="shortcut icon" href="images/ACPI.ico" />
		
	<!-- internal stuff -->
	<link href="acpi.on.lan.css" rel="stylesheet" type="text/css" />
	
	<!-- customization for mobiles -->
	<link href="acpi.on.lan.css.php" rel="stylesheet" type="text/css" />
	
</head>

<body onload='window.location="main.php";'>
<?php
	session_start();
	
	/*if(isset($_SESSION['AcpiOnLanUser'])){*/
		$message = $_GET["message"];
		if ($message == "") {
			$message = "looking for network(s)";
		}
		
		echo "\r\n<div class='center-div'>";
		echo "\r\n<div id='WaitMessage'>";
		echo "\r\n<img id='InitWaitSpin' src='images/loading.gif' width='128px'><br/><br/>";
		echo "\r\n<div id='InitWaitText'>Please wait while ".$message."</div>";
		echo "\r\n</div>";
		echo "\r\n</div>";
	/*} else {
		header("location:login.php");
		exit();
	}*/
		
?>
</body>
</html>