<html>
<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script>
	   window.jQuery || document.write('<script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.2.1.min.js"><\/script>');
	</script>
	<link rel="icon" type="image/png" href="favicon.png">
	<style>
		/* The Modal (background) */
		.modal {
		  display: none; /* Hidden by default */
		  position: fixed; /* Stay in place */
		  z-index: 1; /* Sit on top */
		  padding-top: 100px; /* Location of the box */
		  left: 0;
		  top: 0;
		  width: 100%; /* Full width */
		  height: 100%; /* Full height */
		  overflow: auto; /* Enable scroll if needed */
		  background-color: rgb(0,0,0); /* Fallback color */
		  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
		}

		/* Modal Content */
		.modal-content {
		  background-color: #fefefe;
		  margin: auto;
		  padding: 20px;
		  border: 1px solid #888;
		  width: 400px;
		}

		/* The Close Button */
		.close {
		  color: #aaaaaa;
		  float: right;
		  font-size: 28px;
		  font-weight: bold;
		}

		.close:hover,
		.close:focus {
		  color: #000;
		  text-decoration: none;
		  cursor: pointer;
		}


		fieldset
		{
			margin-top:12px;
			border: 1px solid #069;
			padding:12px;
			-moz-border-radius:8px;
			border-radius:8px;
			-moz-box-shadow: 2px 2px 6px #888;  
			-webkit-box-shadow: 2px 2px 6px #888;   
			box-shadow:2px 2px 6px #888;
			background-color: F5FAFF;
		}
		fieldset legend
		{
			color:#069;
		}

		#loading
		{
			display:none;
			position:fixed;
			left:0;
			top:0;
			width:100%;
			height:100%;
			background:rgba(255,255,255,0.8);
			z-index:1000;
		}
	  
		#loadingcontent
		{
			display:table;
			position:fixed;
			left:0;
			top:0;
			width:100%;
			height:100%;
		}
	  
		#loadingspinner
		{
			display: table-cell;
			vertical-align:middle;
			width: 100%;
			text-align: center;
			font-size:larger;
			padding-top:80px;
		}
	</style>
</head>
<?php
error_reporting(E_ALL ^ E_WARNING); 

#Regular Expression Online tester: https://regex101.com/
#PHP functions Online tester: https://fr.functions-online.com/preg_match.html
#Help HTML 5: https://www.scriptol.fr/html5/

ob_start();

#On DSM 7.x
#/usr/syno/bin/synosystemctl
#/usr/syno/sbin/synopkgctl
#/usr/syno/bin/synopkg
#/bin/systemctl

$PACKAGE = "";
if( isset($_POST['package']) ) {
	$PACKAGE = $_POST['package'];
}
$TARGET = "";
if( isset($_POST['volume']) ) {
	$TARGET = $_POST['volume'];
}
$ACTION = "";
if( isset($_POST['action']) ) {
	$ACTION = $_POST['action'];
}
// Test Reverse
//$PACKAGE = "PHP7.3";
//$ACTION = "reverse";

// Test Forward
//$PACKAGE = "WordPress";
//$ACTION = "forward";

$COMMAND = "";
$output = "";
$volumes = [];
$usedVolumes = [];
$arr = [];
switch ($ACTION) {
	case "load":
		$PACKAGE = "";
		break;
	case "start":
		$COMMAND = "sudo /usr/syno/bin/synopkg start '$PACKAGE' 2>&1";
		exec($COMMAND, $output, $result);
		break;
	case "stop":
		$COMMAND = "sudo /usr/syno/bin/synopkg stop '$PACKAGE' 2>&1";
		exec($COMMAND, $output, $result);
		break;
	case "move":
		$COMMAND = "sudo sh ".dirname(__FILE__)."/mvpkg.sh '$TARGET' '$PACKAGE' 2>&1";				
		exec($COMMAND, $output, $result);
		break;
	case "erase":
		$COMMAND = "sudo sh ".dirname(__FILE__)."/rmpkg.sh '$PACKAGE' 2>&1";
		exec($COMMAND, $output, $result);
		break;
	case "reverse":
		$COMMAND = "systemctl list-dependencies <pkgctl> | grep pkgctl-$PACKAGE 2>&1";
		break;
	case "forward":
		$COMMAND = "systemctl list-dependencies pkgctl-$PACKAGE | grep -v  pkgctl-$PACKAGE | grep pkgctl 2>&1";
		break;
	case "admin":
		$ADMIN = "";
		if( isset($_POST['admin_account']) ) {
			$ADMIN = $_POST['admin_account'];
		}
		$PASSWORD = "";
		if( isset($_POST['admin_password']) ) {
			$PASSWORD = $_POST['admin_password'];
		}
		$SSH_PORT = "";
		if( isset($_POST['ssh_port']) ) {
			$SSH_PORT = $_POST['ssh_port'];
		}				
		$GRANT = "";
		if( isset($_POST['grant']) ) {
			$GRANT = $_POST['grant'];
		}				
		$COMMAND = dirname(__FILE__)."/grant.sh '$ADMIN' '$PASSWORD' '$SSH_PORT' '$GRANT' 2>&1";
		exec($COMMAND, $output, $result);
		break;
}
if ($ACTION != "") sleep(2);
?>
<body style="display:none">
<script type="text/javascript" src="spin.min.js"></script>
<script>
	$(document).ready(function(){
<?php				
		echo "		$(\"body\").show();\n";
		if ($ACTION == "") {
			echo "		$(\"#action\").val(\"load\");\n";
			echo "		$(\"#loadingspinner\").text(\"Loading Packages...\");\n";
			echo "		waitAndSubmit();\n";
		} else {
			echo "		$(\"#volume\").prop(\"disabled\", true);\n";
		}
?>

		var selectPackage = function($name) {
			var package = $name;
			var packageId = package.replace(".", "-");
			
			//volume = $("#packages option[value='" + package + "']").attr('data-volume');
			volume = $("#packages option[id='" + packageId + "']").data('volume');
			//alert('Select ' + package + ' on ' + volume);

			$("#volume").val('');

			if (volume != null) {
				$("#currentVolume").text("Currently on " + volume);
			} else {
				$("#currentVolume").text('');
			}
			
			$("#move").prop("disabled", true);
			
			var id = $("#packages option").filter(function() {
				return this.id == packageId;
			}).attr('id');

			var elem = $("#" + packageId);
			var elemState = elem.css("color");
			
			if (packageId == id) {
				$("#volume").prop("disabled", false);
				if ( elemState == 'rgb(0, 0, 255)') {
					$("#start").prop("disabled", true);
					$("#stop").prop("disabled", false);
				} else {
					$("#start").prop("disabled", false);
					$("#stop").prop("disabled", true);					
				}
				$("#reverse").prop("disabled", false);
				$("#forward").prop("disabled", false);
				$("#erase").prop("disabled", false);
			} else {
				$("#volume").prop("disabled", true);
				$("#start").prop("disabled", true);
				$("#stop").prop("disabled", true);
				$("#reverse").prop("disabled", true);
				$("#forward").prop("disabled", true);
				$("#erase").prop("disabled", true);				
			}
			
			hideVolume(volume);
		}
		
		var preload = $("#package").val();
		if (preload != '') {
			selectPackage(preload);
		}
		
		$("#package").on('input', function(){
			var package = $(this).val();
			selectPackage(package);
		});	

		$("#volume").on('input', function(){
			var packageId = $(this).val().replace(".","-");
			var id = $('#volumes option').filter(function() {
				return this.id == packageId;
			}).data("volume");
			if (packageId == id) {
				$("#move").prop("disabled", false);
			} else {
				$("#move").prop("disabled", true);
			}
		});
		
		$( "#reset" ).click(function() {
			$("#package").val('');
			$("#volume").val('');
			$("#currentVolume").text('');
			$("#move").prop("disabled", true);
			$("#start").prop("disabled", true);
			$("#stop").prop("disabled", true);
			$("#reverse").prop("disabled", true);
			$("#forward").prop("disabled", true);
			$("#erase").prop("disabled", true);
			$("#volume").prop("disabled", true);
		});

		$( "#refresh" ).click(function() {
			$("#reset").click();
			$("#action").val('refresh');
			$("#loadingspinner").text("Reloading Packages...");
			waitAndSubmit();
		});

		$( "#start" ).click(function() {
			$("#action").val('start');
			$("#loadingspinner").text("Starting Package...");
			waitAndSubmit();
		});

		$( "#stop" ).click(function() {
			$("#action").val('stop');
			$("#loadingspinner").text("Stopping Package...");
			waitAndSubmit();
		});
		
		$( "#forward" ).click(function() {
			$("#action").val('forward');
			$("#loadingspinner").text("Querying Package...");
			waitAndSubmit();
		});

		$( "#reverse" ).click(function() {
			$("#action").val('reverse');
			$("#loadingspinner").text("Querying Package...");
			waitAndSubmit();
		});		

		$( "#erase" ).click(function() {
			if (confirm("Do you really want to definitively delete this Package? This may not be undone!")) {
			$("#action").val('erase');
			$("#loadingspinner").text("Erasing Package...");
			waitAndSubmit();
			}
		});

		$( "#admin" ).click(function() {
			$("#grant").val('');
			$("#admin_prompt").css('display', 'block');
		});

		$( "#deladmin" ).click(function() {
			$("#grant").val('revoke');
			$("#admin_prompt").css('display', 'block');
		});

		$( "#admin_close" ).click(function() {
			$("#admin_prompt").css('display', 'none');
		});

		$( "#admin_show" ).click(function() {
			  var x = document.getElementById("admin_password");
			  if (x.type === "password") {
				x.type = "text";
			  } else {
				x.type = "password";
			  }
		});
				
		$( "#admin_runas" ).click(function() {
			if ($("#admin_account").val() == "") {
				alert("Account may not be empty");
				return false;
			}
			if ($("#admin_password").val() == "") {
				alert("Password may not be empty");
				return false;
			}
			if ($("#ssh_port").val() == "") {
				alert("SSH Port may not be empty");
				return false;
			}
			if (confirm("Do you really want to run this application as Admin? This will grant some root access to the internal user running this application!")) {
				$("#action").val('admin');
				$("#loadingspinner").text("Running as Admin...");
				waitAndSubmit();
			} else {
				$("#admin_prompt").css('display', 'none');
				return false;
			}
		});
		
		$( ".package" ).click(function() {
			var package = $(this).attr('name');
			$("#package").val(package);
			
			selectPackage(package);
		});
		
		$("#move").click(function () {
			$("#action").val('move');
			$("#loadingspinner").text("Moving Package...");
			waitAndSubmit();
		});		
	});

	function waitAndSubmit() {
		$("#loading").fadeIn();
		var opts = {
			lines: 12, // The number of lines to draw
			length: 7, // The length of each line
			width: 4, // The line thickness
			radius: 10, // The radius of the inner circle
			color: '#000', // #rgb or #rrggbb
			speed: 1, // Rounds per second
			trail: 60, // Afterglow percentage
			shadow: false, // Whether to render a shadow
			hwaccel: false // Whether to use hardware acceleration
		};
		var target = document.getElementById('loading');
		var spinner = new Spinner(opts).spin(target);
		$( "form" ).submit();
	}
	
	function hideVolume(volume) {
		$("#volumes option").each(function() {
			var id = $(this).val();
			var name = $(this).text();
			//alert('Found val=' + id + ' - text=' + id);
			
			if (id == volume) {
				//alert(volume + ' must be hidden');
				$(this).prop("disabled", true);
			} else {
				$(this).prop("disabled", false);
			}
		});		
	}
	
	function unhighlight(x) {
	  x.style.backgroundColor = "transparent"
	}

	function highlight(x) {
	  x.style.backgroundColor = "#888"
	}
</script>
<?php

#Get list of existing Package and their status information (Only those in /var/packages/*/target)
if ($ACTION != '') {
	#$packages = shell_exec('sudo /usr/syno/bin/synopkg list | cut -d ":" -f1 | cut -d "-" -f1');
	$packages = shell_exec('ls -la /var/packages/*/target 2>&1');
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $packages) as $package){
		$out = null;
		if ( preg_match('/(\\/var\\/packages\\/([^\\/]*)\\/target) -> (\\/(volume(USB)?\\d+)\\/@appstore\\/\\2)/', $package, $out) ) {
			$link = $out[1];
			$name = $out[2];
			$path = $out[3];
			$volume = $out[4];	
			$arr[$name] = [$volume, $path, $link];
			
			$usedVolumes[$volume] = $volume;
			$serviceStatus=shell_exec("sudo /usr/syno/bin/synopkg status $name 2>&1 | grep 'stopped\|started'");
			if (strpos($serviceStatus, 'started') != false) {
				$serviceStatus="enabled";
			} else {
				$serviceStatus="disabled";	
			}		
			//echo $name." is ".$serviceStatus."<br>\n";
			$PackageStatus[$name] = $serviceStatus;
		}
	}
	uksort($arr, "strnatcasecmp");
	uksort($usedVolumes, "strnatcasecmp");
}

#Get selected Package forward dependencies
if ($ACTION == "forward") {
	//echo "ACTION: ".$COMMAND."<br>\n";	
	$list = shell_exec($COMMAND);
	//echo "OUTPUT: ".$list."<br>\n";
	
	$dependencies = preg_split("/((\r?\n)|(\r\n?))/", $list);	
	foreach($dependencies as $dependency){
		//echo "ENTRY: ".$dependency."<br>\n";
		$out = null;
		if ( preg_match('/pkgctl-(.*)\.service/', $dependency, $out) ) {
			$elem = $out[1];
			if ($elem != $dependency) {
				$dependent[$elem] = 1;
				//echo "MATCH: ".$elem."<br>\n";
			}
		}
	}
}

#Get selected Package reverse dependencies
if ($ACTION == "reverse") {
	//echo "ACTION: ".$COMMAND."<br>\n";
	foreach ($arr as $package => $data){
		$search = str_replace("<pkgctl>","pkgctl-".$package,$COMMAND);
		//echo "SEARCH: ".$search."<br>\n";
		$list = shell_exec($search);
		//echo "OUTPUT: ".$list."<br>\n";	
		
		$dependencies = preg_split("/((\r?\n)|(\r\n?))/", $list);
		foreach($dependencies as $dependency){
			//echo "ENTRY: ".$dependency."<br>\n";
			if ( preg_match('/pkgctl-(.*)\.service/', $dependency, $out) ) {
				$elem = $out[1];
				if ($elem != $package) {
					$dependent[$package] = 1;
					//echo "MATCH: ".$elem."<br>\n";
				}
			}
		}
	}
}

#Get list of existing volumes
$volumeNames = shell_exec('df -la --output=target | grep volume');
foreach(preg_split("/((\r?\n)|(\r\n?))/", $volumeNames) as $volumeName){
	$out = null;
	if ( preg_match('/volume(USB)?\\d+/', $volumeName, $out) ) {
		$volume = $out[0];
		$volumes[$volume] = $volume;
	}
}
uksort($volumes, "strnatcasecmp");

foreach ($usedVolumes as $key => $volume){
	echo "<fieldset><legend>Package(s) on $volume</legend>";
	$col = 1;
	echo "<table><tr>";
	foreach ($arr as $package => $data){
		if ($data[0] == $volume) {
			$packageId = str_replace('.', '-', $package);
			echo "<td width=200><span id='$packageId' name='$package' onmouseover='highlight(this);' onmouseout='unhighlight(this)' class='package'";
			if ($PackageStatus[$package] == "enabled") {
				if (isset($dependent) && array_key_exists($package, $dependent))
					echo " style='color:blue;text-decoration:underline'>";
				else
					echo " style='color:blue'>";
			} else {
				if (isset($dependent) && array_key_exists($package, $dependent))
					echo " style='color:grey;text-decoration:underline'>";				
				else
					echo " style='color:grey'>";
			}
			echo $package;
			echo "</span></td>\n";
			$col = $col +1;
			if ($col % 5 == 0) {
				$col = 1;
				echo "</tr>\n<tr>";
			}
		}
	}
	echo "</tr></table>";
	echo "<div style='color:grey;font-size:9px' align='right'>Greyed out services are disabled</div>";
	echo "</fieldset>\n";
}

echo "<br/><input type='button' id='refresh' style='float: right;' value='Refresh'><br/><br/>\n";
echo "<hr>\n";

echo "<form action='mvpkg.cgi' method='post'>";
echo "<fieldset><legend>Pick a Package to Move</legend>\n";
echo "<p><input type='text' list='packages' id='package' name='package' value='$PACKAGE'><span id='currentVolume'></span>";
echo "<datalist id='packages' >";
foreach ($arr as $package => $data){
	$packageId = str_replace(".","-",$package);
	echo "<option data-volume='$data[0]' id='$packageId' value='$package'>$data[0]\n";
}
echo "</datalist>\n";
echo "</p>\n";
echo "</fieldset>\n";


echo "<fieldset><legend>Target volume</legend>\n";
echo "<p><input type='text' list='volumes' id='volume' name='volume'>";
echo "<datalist id='volumes' >";
foreach ($volumes as $key => $volume){
	echo "<option data-volume='$key' id='$key'>$key";
}
echo "</datalist>";
echo "</p>";
echo "</fieldset>\n";
echo "<br/><br/>\n";
echo "<input type='button' id='reset' value='Reset' title='Clean the current selection'>\n";
echo "<input type='button' id='move' value='Move Package' disabled>\n";
echo "<input type='button' id='start' value='Start' title='Start the selected service' disabled>\n";
echo "<input type='button' id='stop' value='Stop' title='Stop the selected service' disabled>\n";
echo "<input type='button' id='forward' value='Forward Dep' title='Display all services on which the selected one depends.' disabled>\n";
echo "<input type='button' id='reverse' value='Reverse Dep' title='Display all services depending on the selected one.' disabled>\n";
echo "<input type='button' id='erase' value='Erase' style='float: right;' title='Delete a Package ignoring dependencies if any.' disabled>\n";
echo "<p><input type='hidden' id='action' name='action' >";
echo "<hr>\n";

$admin = true;
$user = trim(shell_exec('whoami'));
$sudomsg = "Respect the privacy of others";
$check = "sudo echo '' 2>&1 | grep '".$sudomsg."'";
exec($check, $out, $exit);
foreach ($out as $item => $data){
	if (trim($item) != "") {
		echo "You are not authorized to manage packages.<br>\n";
		echo "<input type='button' id='admin' value='Run As Admin' style='float: right;' title='Grant Admin rights on to this application for user \"".$user."\".'>\n";
		echo "Run this app as Admin.<br>\n";
		$admin = false;
		break;
	}
	echo "<p>$data</p>";
}
if ($admin) {
	echo "You are running as Admin.<br>\n";
	echo "<input type='button' id='deladmin' value='Exit Admin' style='float: right;' title='Remove Admin rights on to this application for user \"".$user."\".'>\n";
	echo "It is not safe to exit this app without removing Admin rights.<br>\n";	
}

if ($COMMAND != '') {

	echo "<br/><fieldset><legend>Package Mover's feedback</legend>";
	foreach ($output as $item => $data){
		echo "<p>$data</p>";	
	}
	echo "</fieldset>";	
}
ob_end_flush();
?>
<!-- The Modal -->
<div id="admin_prompt" class="modal">

  <!-- Modal content -->
  <div class="modal-content">
    <span id="admin_close" class="close">&times;</span>
    <p>Enter credentials of an account with sudo access:</p>
	<table> 	
	<tr><td>Account:</td><td><input type='text' id='admin_account' name='admin_account' value='admin'></td></tr>
	<tr><td>Password:</td><td><input type='password' id='admin_password' name='admin_password' value=''></td></tr>
	<tr><td>SSH Port:</td><td><input type='text' id='ssh_port' name='ssh_port' value='22'></td></tr>
	</table>
	<input type='hidden' id='grant' name='grant' value=''>
	<input type="checkbox" id="admin_show">Show Password
	<button id="admin_runas" style='float: right;'>Apply</button>
  </div>
</div>
<div id="loading">
    <div id="loadingcontent">
        <p id="loadingspinner">
            Moving Package...
        </p>
    </div>
</div>
</form>
</body>
</html>