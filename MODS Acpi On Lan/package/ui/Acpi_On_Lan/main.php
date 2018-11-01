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
	
	<!-- php validated with http://phpcodechecker.com -->
	<!-- icons found on https://www.iconfinder.com -->
	<!-- animated gif created with http://preloaders.net/ -->
	<!-- Rest Services tested with https://chrome.google.com/webstore/detail/advanced-rest-client/hgmloofddffdnphfgcellkdfbfbjeloo -->
	<!-- Had a look on http://jquerymobile.com -->
	<!-- Login screen by Marco Biedermann http://codepen.io/m412c0/pen/Fybpf -->

	<!-- JQuery -->
	<!--script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script-->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

	<!-- http://bootboxjs.com/ -->
	<link href="bootstrap.min.css" rel="stylesheet" type="text/css" />
	<script src="bootstrap.min.js"></script>
	<!--script src="bootbox.min.js" type="text/javascript"></script-->
	<script src="bootbox.js" type="text/javascript"></script>
	<!-- Bootstrap MUST after jquery/min.css otherwise it fails -->

	<!-- JQuery UI -->	
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
	<link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/ui-lightness/jquery-ui.css" rel="stylesheet" type="text/css" />
	<!-- Bootstrap MUST BE before jquery-ui.css otherwise style does not applies correctly http://stackoverflow.com/questions/17367736/jquery-ui-dialog-missing-close-icon -->
	
	<!-- http://medialize.github.io/jQuery-contextMenu/ -->
	<link href="jquery.contextMenu.css" rel="stylesheet" type="text/css" />
	<script src="jquery.ui.position.js" type="text/javascript"></script>
	<script src="jquery.contextMenu.js" type="text/javascript"></script>
	
	<!-- http://imagesloaded.desandro.com/ -->
	<script src="imagesloaded.pkgd.min.js" type="text/javascript"></script>

	<!-- http://johnculviner.com/jquery-file-download-plugin-for-ajax-like-feature-rich-file-downloads/ -->
	<script src="jquery.fileDownload.js" type="text/javascript"></script>
	
	<!-- internal stuff -->
	<script src="acpi.on.lan.js" type="text/javascript"></script>
	<link href="acpi.on.lan.css" rel="stylesheet" type="text/css" />

	<!-- customization for mobiles -->
	<link href="acpi.on.lan.css.php" rel="stylesheet" type="text/css" />	
</head>

<!-- oncontextmenu="return false;" is used to disable the context menu -->
<body id='acpi-on-lan' oncontextmenu="return false;" style:visibility="hidden">
<!--body id='acpi-on-lan' style:visibility="hidden"-->
<?php
	//ini_set('display_errors', 1);
	//ini_set('display_startup_errors', 1);
	//error_reporting(E_ALL);

	//session_save_path("/tmp");
	//session_start();
	require_once 'acpi.on.lan.php';
	
	/*if(!isset($_SESSION['AcpiOnLanUser'])){
		header("location:login.php");
		exit();
	}*/

	ob_start();
	
	//=========================================================================================
	$settings = InitializeSettings();
	InitializeOsDefinitions();
	
	$skipInit = 0;
	if (isset($_GET["sort"])) {
		$sort = $_GET["sort"];
	}
	if (!empty($sort)) {
		$settings->sortOrder = $sort;
		SaveSettings($settings);
		
		$skipInit = 1;
	}
	
	//=========================================================================================
	$computers = LoadComputers();
	if ($computers->count() == 0) {
	
		// No Computers available => Send a Waiting page for the first run
		// ------------------------------------------------------------------------------------
		echo "\n\r<div class='center-div'>";
		echo "\r\n<div id='WaitMessage'>";
		echo "\r\n<img id='InitWaitSpin' src='images/loading.gif' width='128px'><br/><br/>";
		echo "\r\n<div id='InitWaitText'>Looking for network(s) and devices</div>";
		echo "\r\n</div>";
		echo "\n\r</div>";
		
		echo "\n\r<div id='page-wrap' class='ACPI'>";
	} else {
		if ($skipInit == 0) {		
			// List of Computers available => Look for changes
			// ------------------------------------------------------------------------------------
			if ($computers->count() == 0) {
				$computers = SearchNewComputers($computers->NetMAC);
			} else {
				$computers = UpdateComputers($computers);
			}
			SaveComputers($computers);
		}
		
		// Add the Events Notification area
		// ------------------------------------------------------------------------------------
		echo "\r\n<div class='notifications' id='ACPInotifications'>";
		for( $i = 1 ; $i <= 15 ; $i++ ) {
			echo "\r\n<div id='not".$i."' style='display: none;' class='eventInfo'>";
				echo "\r\n<span>This is a Notification floating pane</span>";
			echo "\r\n</div>";
		}
		echo "\r\n</div>";

		// Display the List of Computers
		// ------------------------------------------------------------------------------------		
		echo "\n\r<div id='page-wrap' class='ACPI'>";
		ShowComputers($computers, $settings);
		echo "\n\r<br/><br/>";
	}		
	echo "\n\r<!-- Error Message field -->";
	echo "\n\r<div id='results' class='menu' style='text-align:left;display: none;'>AcpiOnLan running fine</div>";
	
	ob_end_flush();
?>

<!-- Information popup area -->
<div class="info" id="message_info" style="visibility:hidden; position:absolute">Info message</div>
<div class="success" id="message_success" style="visibility:hidden; position:absolute">Successful operation message</div>
<div class="warning" id="message_warning" style="visibility:hidden; position:absolute">Warning message</div>
<div class="error" id="message_error" style="visibility:hidden; position:absolute">Error message</div>
<div class="help" id="message_help" style="visibility:hidden; position:absolute">Help message</div>
</div> <!-- close id='page-wrap' -->

<!-- icons area -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog ACPImodal-dialog">
      <div class="modal-content ACPImodal-content">
        <div class="modal-header ACPImodal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title" id="myModalLabel">Pick a new icon</h4>
        </div>
        <div class="modal-body ACPImodal-body" id="image-loader">
          <div id="image-container" class="LoadIcon"></div>
        </div>
        <div class="modal-footer ACPImodal-footer">
		  <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        </div>
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
 </div><!-- /.modal -->
 
 <!-- Download area -->
 <div id="preparing-file-modal" title="Preparing backup..." style="display: none;">
    Backup under construction, please wait...
 
    <div class="ui-progressbar-value ui-corner-left ui-corner-right" style="width: 100%; height:22px; margin-top: 20px;"></div>
</div> 
<div id="error-modal" title="Error" style="display: none;">
    There was a problem generating the Backup, please try again.
</div>

<!-- Import area -->
<div id="import_container" title="Upload the backup file to be imported" style="display: none;">
	<form id="import_container_form" enctype="multipart/form-data" onsubmit="startImport();" >
		 <div id="f1_import_process" align="center">Loading...<br/><img src="images/loader.gif" /><br/></div>
		 <div id="f1_import_form" align="center"><br/>
			 <label>
				  <input name="myfile" type="file" size="30" accept=".json" required />
			 </label>
			 <label>
				 <input type="submit" name="submitBtn" class="import_sbtn" value="Import" />
			 </label>
		 <div>
		 <br/><br/>
		 <div>NB.: The whole list of computers in this backup will be restored!<div>
	 </form>
 </div>
<div id="import_error" title="Error" style="display: none;">
    There was a problem importing the backup, please try again.
</div>       

<!-- Restore area -->
<div id="restore_container" title="Upload the backup file to be restored" style="display: none;">
	<form id="restore_container_form" enctype="multipart/form-data" onsubmit="startRestore();">
		 <div id="f1_restore_process" align="center">Loading...<br/><img src="images/loader.gif" /><br/></div>
		 <div id="f1_restore_form" align="center"><br/>
			 <label>
				  <input name="myfile" type="file" size="30" accept=".json" required />
			 </label>
			 <label>
				 <input type="submit" name="submitBtn" class="restore_sbtn" value="Restore" />
			 </label>
		 <div>
		 <br/><br/>
		 <div>NB.: Only the icons and the customized hostnamed from this backup will be restored!<div>
	 </form>
 </div>
<div id="restore_error" title="Error" style="display: none;">
    There was a problem importing the backup, please try again.
</div>

<!-- Icon area -->
<div id="icon_container" title="Upload a new Icon" style="display: none;">
	<form id="icon_container_form" enctype="multipart/form-data" onsubmit="startUploadIcon();">
		 <div id="f1_icon_process" align="center">Loading...<br/><img src="images/loader.gif" /><br/></div>
		 <div id="f1_icon_form" align="center"><br/>
			 <label>
				  <input name="myfile" type="file" size="30" accept="image/png" required />
			 </label>
			 <label>
				 <input type="submit" name="submitBtn" class="icon_sbtn" value="Upload" />
			 </label>
		 <div>
	 </form>
 </div>
<div id="icon_error" title="Error" style="display: none;">
    There was a problem importing the icon, please try again.
</div>

</body>
</html>

<?php
//-----------------------------------------------------------------------------------
function InitializeSettings() {
	$settings = LoadSettings();
	
	$data = "";
	$subnet = "";
	if (file_exists("./config/Network.json")) {
		$data = file_get_contents("./config/Network.json");
	
		$network = json_decode($data);
		foreach ($network as $ethernet => $bmask){
			$subnet = $bmask;
			break;
		}
	}
	if ($subnet != "") {
		$nums = explode(".", $subnet);
		$subnet = $nums[0].".".$nums[1].".".$nums[2] ; 
	} else {
		$subnet = "0.0.0.0";
	}
		
	echo "\r\n<script type='text/javascript'>";
	echo "\r\nrefreshDelay = ".$settings->refreshDelay.";";
	echo "\r\nactionDisplay = ".$settings->actionDisplay.";";
	echo "\r\nsubnet = '".$subnet.".';";
	echo "\r\n</script>";
	
	if (empty($settings->sortOrder)) 
		$settings->sortOrder = 'SortByIp';
	return $settings;
}

function InitializeOsDefinitions() {
	$osFeatures = file_get_contents("Os.json");
	$data = json_encode(json_decode($osFeatures)); // Remove Pretty Formatting
	echo "\r\n<script type='text/javascript'>";
	echo "\r\nosFeatures = jQuery.parseJSON( '".$data."' );";
	echo "\r\n</script>";
}

function ShowComputers($computers, $settings) {
	// Get the remote ip address (the ip address of the client, the browser) 
	$remoteIp = $_SERVER['REMOTE_ADDR'];
	$remoteIp = str_replace(".", "\\.", $remoteIp); 	

	$hiddenExist = 0;
	$iconSize = GetIconSize();
	$miniIconSize = GetMiniIconSize();
	$fontSize = GetFontSize();
	$mobileView = GetMobileView();
	
	echo "\r\n<br/><br/><br/>";
	echo "\r\n<table cellspacing='5' class='ACPItable'>";
	echo "\r\n<thead>";
	echo "\r\n<tr>";
		echo "\r\n<th>Device<br/><br/></th>";
		echo "\r\n<th></th>";
		if ($mobileView == 0) {
			echo "\r\n<th><a href='main.php?sort=SortByIp'>IP</a></th>";
			echo "\r\n<th><a href='main.php?sort=SortByMac'>MAC</a></th>";
			echo "\r\n<th><a href='main.php?sort=SortByName'>Hostname</a></th>";
			echo "\r\n<th>Actions</th>";
		} else {
			echo "\r\n<th>IP, MAC & Host</th>";				
		}		
		echo "\r\n<th>Admin</th>";
	echo "\r\n</tr>";
	echo "\r\n</thead>";
	 
	echo "\r\n<tbody>";	
	// Cycle the array to find the match with the remote ip address
	$items = $computers->items;
	usort($items, $settings->sortOrder);
	foreach ($items as $computer) {		
		$id = $computer->id;
		$ip = $computer->ip;
		$mac = strtoupper($computer->mac);
		$os = strtolower($computer->os);
		$icon = $computer->icon;
		$state = $computer->state;
		$acpiOnLan = $computer->acpiOnLan;
		$webpage = $computer->webpage;

		// Show or Hide the computer
		if ($computer->hidden == 1) {
			$visibility = "style='display:none'";
			$hiddenExist = 1;
		} else {
			$visibility = "";
		}		
		echo "\r\n<tr id='row".$id."' ".$visibility.">";
		
		// DEVICE ICON with OS
		$AcpiOnOff = '';
		$title = '';
		if (startsWith($icon, 'icon_')) {
			if ($state == 0) {
				$AcpiOnOff = 'ACPIOff';
				$title = 'Device not reachable';
			} else if ($acpiOnLan == 0) {
				if ($os != 'other') {
					$AcpiOnOff = 'ACPIOut';
					$title = 'ACPIOnLan not available';
				}
			} else {
				$title = 'ACPIOnLan is running';
			}
		}
		echo "\r\n<td><div class='context-menu-device ACPIHaptic' computerid='".$id."' style='display:inline-block;position:relative;'>";
		echo "\r\n<img class='context-menu-device ".$AcpiOnOff."' computerid='".$id."' src='images/".$icon."' width='".$iconSize."' id='img".$id."' title='".$title."'>";
		echo "\r\n<img class='context-menu-device' computerid='".$id."' src='images/os_".$os.".png' width='".$miniIconSize."' id='os".$id."' style='position:absolute;top:0;right:0;' title='".$os."'>";
		echo "\r\n</div></td>";
		
		// WEBPAGE LINK
		if (isset($webpage) && $webpage != 0) {
			$weblink = 'visible';
		} else {
			$weblink = 'hidden';
		}
		echo "\r\n<td valign='top'>";
		if ($ip != "0.0.0.0") {
			echo "\r\n<a target='blank' id='brwip".$id."' href='http://".$ip.":".$webpage."'><img src='images/browse.png' width='16' id='brw".$id."' style='visibility:".$weblink.";' title='Open web page'></a>";
		}
		echo "\r\n</td>";
		

		// HOSTNAME
		if ($computer->hostname) {
			$hostname = $computer->hostname;
		} else {
			$hostname = '<Edit>';
		}
		
		// ACTIONS
		$ActionHib = GetAction($settings, $computer, 'hib').'<a href="#" onclick="SetState(\''.$id.'\',\'hibernate\'); return false">'.GetActionIcon($computer, 'hib', 'action_hibernate-1.png', 'Hibernate').'</a></div>';
		$ActionSlp = GetAction($settings, $computer, 'slp').'<a href="#" onclick="SetState(\''.$id.'\',\'sleep\'); return false">'.GetActionIcon($computer, 'slp', 'action_sleep-1.png', 'Sleep').'</a></div>';
		$ActionStb = GetAction($settings, $computer, 'stb').'<a href="#" onclick="SetState(\''.$id.'\',\'standby\'); return false">'.GetActionIcon($computer, 'stb', 'action_standby-1.png', 'Standby').'</a></div>';
		$ActionShd = GetAction($settings, $computer, 'shd').'<a href="#" onclick="SetState(\''.$id.'\',\'shutdown\'); return false">'.GetActionIcon($computer, 'shd', 'action_shutdown-1.png', 'Shutdown').'</a></div>';
		$ActionRst = GetAction($settings, $computer, 'rst').'<a href="#" onclick="SetState(\''.$id.'\',\'restart\'); return false">'.GetActionIcon($computer, 'rst', 'action_restart-1.png', 'Restart').'</a></div>';
		$ActionWol = GetAction($settings, $computer, 'wol').'<a href="#" onclick="WakeOnLan(\''.$id.'\'); return false">'.GetActionIcon($computer, 'wol', 'action_wake-1.png', 'Wake On Lan').'</a></div>';
		
		$GetActionAbort = "<div id='abtAction".$id."' class='action' style='display: none;'>";
		$GetActionIconAbort = "<img class='ACPIHaptic' src='images/action_abort-1.png' width='".GetIconSize()."' id='abt".$id."' title='Abort'>";
		$ActionAbt = $GetActionAbort.'<a href="#" onclick="SetState(\''.$id.'\',\'abort\'); return false">'.$GetActionIconAbort.'</a></div>';

		
		// IP, MAC & Host
		if ($mobileView == 0) {
			echo "\r\n<td><div id='ip".$id."' class='oldip'>".$ip."</div></td>";

			echo "\r\n<td style='position:relative;'>";
			echo "<div id='mac".$id."' class='macaddress'>".$mac."</div>";
			echo "<div class='context-menu-macaddress macaction ACPIHaptic' computerid='".$id."'></div>";
			echo "</td>";
			echo "\r\n<td style='position:relative;'>";
			echo "<div id='host".$id."' class='hostname'>".htmlentities($hostname)."</div>";
			echo "<div class='context-menu-hostname hostaction ACPIHaptic' computerid='".$id."'></div>";
			echo "</td>";
		} else {
			echo "\r\n<td style='position:relative;'>";
			echo "\r\n<table><tr><td style='position:relative;border:0px'>";
			echo "<div id='ip".$id."' class='oldip'>".$ip."</div>";
			echo "\r\n</td></tr><tr><td style='position:relative;border:0px'>";
			echo "<div id='mac".$mac."' class='macaddress'>".$mac."</div>";
			echo "<div class='context-menu-macaddress macaction ACPIHaptic' computerid='".$id."'></div>";
			echo "\r\n</td></tr><tr><td style='position:relative;border:0px'>";
			echo "<div id='host".$id."' class='hostname'>".htmlentities($hostname)."</div>";
			echo "<div class='context-menu-hostname hostaction ACPIHaptic' computerid='".$id."'></div>";
			echo "\r\n</td></tr></table>";
			echo "</td>";
		}
		
		
		// ACTIONS
		echo "\r\n<td><div id='actions".$id."'>";
		echo "\r\n".$ActionHib;
		echo "\r\n".$ActionSlp;
		echo "\r\n".$ActionStb;
		echo "\r\n".$ActionShd;
		echo "\r\n".$ActionRst;
		echo "\r\n".$ActionWol;
		echo "\r\n</div>";
		
		echo "\r\n".$ActionAbt;
		echo "\r\n</td>";
		
		// HIDE
		echo "\r\n<td align='right'>";
		if ($computer->hidden == 0) {
			$icon="remove.png";
			$title="Hide";
		} else {
			$icon="restore.png";
			$title="Show";
		}
		echo "\r\n".'<a href="#" onclick="SwitchVisibility(\''.$id.'\'); return false"><img class="ACPIHaptic" src="images/'.$icon.'" width="40" id="rmv'.$id.'" title="'.$title.'"></a>';

		if ($computer->hidden != 0) {
			echo "\r\n".'<a href="#" onclick="Delete(\''.$id.'\'); return false"><img class="ACPIHaptic" src="images/delete.png" width="40" id="del'.$id.'" title="Delete"></a>';
		}
		
		echo "\r\n</td>";				
		echo "\r\n</tr>";
		echo "\r\n<tr id='emptyrow".$id."' ".$visibility."><td colspan='7'></tr>";
	}
	echo "\r\n</tbody>";
	echo "\r\n</table>";
	
	
	// Add the Menu Area
	if ($hiddenExist == 1) {
		$visibility = "";
	} else {
		$visibility = "style='display:none'";
	}
	if ($mobileView == 0) {
		$MenuReload="Reload";
		$MenuFlush="Flush";
		$MenuUpdate="Update";
		$MenuShow="Show All";
		$MenuHide="Hide";
		$MenuLogout="Log Out";
		$MenuAdvanced="Advanced";
		$MenuHelp="Help";
	} else {
		$MenuReload="Reload";
		$MenuFlush="Flush";
		$MenuUpdate="Upd";
		$MenuShow="Show";
		$MenuHide="Hide";
		$MenuLogout="Exit";
		$MenuAdvanced="Adv";
		$MenuHelp="Help";
	}
	echo "\r\n<div class='menus' id='ACPImenus'>";
	echo "\r\n<div id='reload' class='menu ACPIHaptic'><a href='#' onclick='Reload(); return false' title='Reload an updated list of devices'>".$MenuReload."</a></div> ";
	echo "\r\n<div id='flush' class='menu ACPIHaptic'><a href='#' onclick='Flush(); return false' title='Flush the network [clear ARP table]'>".$MenuFlush."</a></div> ";
	echo "\r\n<div id='localUpdate' class='menu ACPIHaptic'><a href='#' onclick='LocalUpdate(); return false' title='Update only status older than 10 minutes'>".$MenuUpdate."</a></div> ";
	//echo "\r\n<div id='forcedUpdate' class='menu ACPIHaptic'><a href='#' onclick='ForcedUpdate(); return false' title='Update all status'>Forced Update</a></div> ";
	//echo "\r\n<div id='resetAll' class='menu ACPIHaptic'><a href='#' onclick='ResetAll(); return false' title='Delete all devices in the list'>Reset All</a></div> ";
	echo "\r\n<div id='showAll' class='menu ACPIHaptic' ".$visibility."><a href='#' onclick='ShowAll(); return false'>".$MenuShow."</a></div> ";
	echo "\r\n<div id='hideAll' class='menu ACPIHaptic' style='display:none'><a href='#' onclick='HideAll(); return false'>".$MenuHide."</a></div> ";
	if (file_exists("service/AcpiOnLanInstaller.msi")) {
	echo "\r\n<div id='installAcpiSrvc' class='menu ACPIHaptic'><a href='#' onclick='GetService(); return false' title='Install the Windows AcpiOnLan service'>Install Srvc</a></div> ";
	}
	echo "\r\n<div id='logout' class='menu ACPIHaptic'><a href='#' onclick='LogOut(); return false' title='Exit'>".$MenuLogout."</a></div> ";
	echo "\r\n<div id='advanced' class='menu context-menu-advanced ACPIHaptic'><a href='#' onclick='return false' title='Advanced'>".$MenuAdvanced."</a></div> ";
	echo "\r\n<a href='help.html' target='_blank' class='menu'>".$MenuHelp."</a>";
	echo "\r\n</div>";
}
?>