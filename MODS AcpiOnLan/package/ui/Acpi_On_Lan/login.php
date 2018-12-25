<?php
	session_start();
	
	$Service = $_GET["service"];
	if ($Service == "LogOut") {
		header('content-type: text/css');
		
		$response = array('state'=>1);
		$return = json_encode($response);
		
		unset($_SESSION['AcpiOnLanUser']);
		echo $return;
		exit();
	}
?>	
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>ACPI-On-Lan Web Interface</title>
    <meta name="description" content="Simple UI to power-manage devices on the same LAN as your NAS" />
	
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- with chrome, it's not possible to disable the automatic zoom-out when entering an input field so we force the viewport -->

	<link rel="shortcut icon" href="images/ACPI.ico" />
		
	<!-- style sheet for the login -->
	<link rel="stylesheet" href="login.css" media="screen" type="text/css" />

    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:400,700">

    <!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
</head>

<body>
<?php		
	$username = $_POST['user'];
	$password = $_POST['pass'];

	if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443)) {
		$port = `/bin/get_key_value /etc/synoinfo.conf secure_admin_port`;
		if (empty($port)) {
			$port = `/bin/get_key_value /etc/synoinfo.conf external_port_dsm_https`;
		}		
		$protocol= "https";
	} else {
		$port = `/bin/get_key_value /etc/synoinfo.conf admin_port`;
		if (empty($port)) {
			$port = `/bin/get_key_value /etc/synoinfo.conf external_port_dsm_http`;
		}		
		$protocol= "http";
	}
	$domain = $_SERVER['SERVER_NAME'];
	$port = trim($port);

   if ($username != "") {
		
		$url = $protocol.'://'.$domain.':'.$port.'/webman/login.cgi?username='.$username.'&passwd='.$password;
		$data =  @file_get_contents($url);
		$response = json_decode($data);
				
		if(($response->result == "success") && ($response->success == true)){
			$_SESSION['AcpiOnLanUser'] = $username;
		}else{
			unset($_SESSION['AcpiOnLanUser']);
			echo "<div id='failure'>Login failed with. Please, try again:</div>";
		}
	} else {
	   echo "<div id='failure'>Please login with your DSM account:</div>";
	}

	if(isset($_SESSION['AcpiOnLanUser'])){
		header("location:index.php");
		exit();
	} else {
		echo "\r\n<div id='login'>";
		echo "\r\n<div id='triangle'></div>";
		echo "\r\n<h1>Log in</h1>";
		echo "\r\n<form action='' method='post'>";
		echo "\r\n<input type='login' name='user' placeholder='Name'/>";
		echo "\r\n<input type='password' name='pass' placeholder='Password'/>";
		echo "\r\n<input type='submit' value='Log in' />";
		echo "\r\n<br/>";
		echo "\r\n<br/>";
		echo "\r\n<br/>";
		echo "\r\n<hr/>";
		echo "\r\n<div align='right' style='font-size:75%'><a href='".$protocol."://".$domain.":".$port."'>DSM Administration UI</a></div>";
		echo "\r\n</form>";
		echo "\r\n</div>";
	}
?>
</body>
</html>