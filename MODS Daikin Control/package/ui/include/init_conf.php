<?php
// Initialize configuration
//---------------------------------------------------
// If not exists (=first install) : create it
// If exists : load it and re-write it if requested
//---------------------------------------------------
// F. Bardin 06/09/2015
//---------------------------------------------------
// 30/09/2017 : Add re-write config query management
//---------------------------------------------------
// Anti-hack
//if (! defined('ANTI_HACK')){exit;}

$conf_file = "config.php";

ini_set('default_socket_timeout', 1);

// If config file does not exist : initialization
if (file_exists("$conf_file")) {
	include $conf_file;
	
} else {
	// Initialize configuration
	define('INIT', true);

	@$confstep = $_REQUEST['confstep'];
	@$subnet = $_REQUEST['subnet'];
	@$devices = $_REQUEST['devices'];

	// Load translations
	echo "<div style='text-align: center; display: block;width: 100%;'><FORM METHOD=post>";
	
	// Config steps
	switch($confstep){
		case "0" : // Confirm subnet
			echo "<H3>Confirm subnet to be searched</H3>";
			echo "<H4>Type an IP in the range to be searched</H4>";
			$subnet=getSubnet();
			echo "<INPUT TYPE=text NAME='subnet' VALUE='$subnet' SIZE='10'>";
			echo "<INPUT TYPE=hidden NAME='confstep' VALUE='1'>";
			break;

		case "1" : // Look for Devices
			echo "<H3>Looking for Daikin Devices</H3>";
			echo "<H4>Wait while scanning network</H4>";
			$devices=searchForDevices($subnet);
			echo "<INPUT TYPE=hidden NAME='confstep' VALUE='2'>";
			echo "<INPUT TYPE=hidden NAME='devices' VALUE='$devices'>";
			break;

		case "2" : // Save Devices
			echo "<H3>Save Configuration</H3>";
			echo "<H4>$devices</H4>";
			if (writeConf($conf_file, $devices)) {
				echo "Successful !";
			} else {
				echo "Failed :(";
			}
			break;
			
		default : // Init step
			echo "<H2>Configuration missing - Automatic setup begins</H2>";
			echo "<INPUT TYPE=hidden NAME='confstep' VALUE='0'>";
			echo "</H3>";
	}
	echo "<br><br><INPUT TYPE=submit VALUE='Continue'>";
	echo "</FORM></div>";
	die();
}

//----------------------------------------------------------------
// Function to initialize the subnet where to look for Hue bridge
//----------------------------------------------------------------
function getSubnet(){
	// Init default subnet from web server ip
	$ip = $_SERVER["SERVER_ADDR"];
	return preg_replace("/(.*)[.]([^.]*)/","$1",$ip);
} // getSubnet

//----------------------------------------
// Function to look for Hue bridge IP
//----------------------------------------
function searchForDevices($subnet){
	$request = "/aircon/get_control_info";
	$search_str = "ret=OK";
	$pattern = "/".$search_str."/";

	echo "Detection in progress on subnet $subnet<BR>";
	ob_flush();
	flush();
	
	// Check if user typed an ip
	$valid = ip2long($subnet) !== false;
	if ($valid) {
		$url="http://".$subnet.$request;
		$result1 = @file($url);
		if (is_array($result1)){
			$result = preg_grep($pattern,$result1);
			if (count($result) > 0){$found = true;} 
		} 
		$subnet = preg_replace("/(.*)[.]([^.]*)/","$1",$subnet);
	}
	
	// It's assumed that bridge is on the sub-network with subnet mask 255.255.255.0
	$devices = array();
	$i=0;
	while ($i < 254){ // Scan subnet with ip range from 1 to 254
		$i++;
		$device = $subnet.".".$i;
		$url="http://".$device.$request;
		echo ".";
		ob_flush();
		flush();

		$result1 = @file($url);
		if (is_array($result1)){
			$result = preg_grep($pattern,$result1);
			if (count($result) > 0){
				array_push($devices,$device);
				echo "[$device]";
			} 
		} 
	}
	$found = json_encode($devices);
	echo "<BR>Found: $found";
	return $found;
} // searchForDevices

//-----------------------------------------------------------------------------------------------
// Function to (re)write completly config.php file
// Return : true/false (true=writing ok, false=error)
// All config parameters must be already set as global
//-----------------------------------------------------------------------------------------------
function writeConf($conf_file, $devices){

	// Init config content
	$conf_array = array(
		"<?php",
		"if (! defined('ANTI_HACK')){exit;}",
		"/*****************",
		" * Configuration *",
		" *****************/",
		"\$devices = '$devices';",
		"?>"
	);

	// Format array content to be usable
	$conf_count = count($conf_array);
	$conf_rec = "";
	$conf_html = "";
	for ($i = 0; $i < $conf_count; $i++){
		$conf_rec .= $conf_array[$i]."\n";
		$conf_html .= str_replace(" ","&nbsp;",htmlentities($conf_array[$i]))."<BR>\n";
	}

	$file = "include/$conf_file";

	// Write conf file
	if (file_put_contents("$conf_file",$conf_rec)){
		return true;
	} else {
		return false;
	}
} // writeConf
?>
