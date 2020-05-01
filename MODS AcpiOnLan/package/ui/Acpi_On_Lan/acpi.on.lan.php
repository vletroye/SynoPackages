<?php
// https://code.google.com/p/php-mobile-detect
require_once 'mobile_detect.php';

$lastExecVendor=time();

class Settings {
	var $refreshDelay;
	var $actionDisplay;
	var $tokenVendor;
	
	function __construct() {
		$this->refreshDelay = 5;
		$this->actionDisplay = 0;
		$this->sortOrder = 'SortByIp';
		$this->tokenVendor = '';
    }
}

class Computer {
	var $id;
	var $mac;
	var $ip;
	var $hostname;
	var $os;
	var $acpiOnLan;
	var $state;
	var $webpage;
	var $icon;
	var $hidden;
	var $bmask;
	var $ethernet;
	var $update;
	var $features;
	var $nas;
	var $vendor;
	
	function __construct() {
		$this->mac = '00:00:00:00:00:00';
		$this->hidden = 0;
		$this->icon = 'search.png';
		$this->nas = 0;
    }
}

Class Computers {
	var $NetMAC;
	var $NetIP;
	var $items;
	var $network;
	
	function __construct() {
        $this->items = array();
		$this->NetIP = array();
		$this->NetMAC = array();
		$this->network = array();
    }
	
	function GetByMac($mac) {
		$computer = null;
		
		if (array_key_exists($mac, $this->NetMAC)) {
			$id = $this->NetMAC[$mac];
			$computer = $this->items[$id];
		}
		
		return $computer;
	}
	
	function GetById($id) {
		return $this->items[$id];
	}

	function GetByIp($ip) {
		$computer = null;
		
		if (array_key_exists($ip, $this->NetIP)) {
			$id = $this->NetIP[$ip];
			$computer = $this->items[$id];
		}
		
		return $computer;
	}

	function replace($mac, $computer) {
		$id = $this->NetIP[$mac];
		$this->items[$id] = $computer;
	}
	
	function getBmask($ethernet) {
		$bmask = $this->network[$ethernet];
		return $bmask;
	}
	
	function setBmask($ethernet, $bmask) {
		$this->network[$ethernet] = $bmask;
	}
	
	function count() {
		return count($this->items);
	}
	
	function delete($computer) {
		$computer->hidden = -1;
		$id = $computer->id;
		unset($this->items[$id]);
	}
			
	function add($computer) {
		// arp entry with reassigned IP has a mac = '00:00:00:00:00:00'
		if (($computer->mac != '') && ($computer->mac != '00:00:00:00:00:00')){
			$id = $computer->id;
			
			$computer->mac = strtoupper($computer->mac);
			$existingId = "";
			
			//echo "handle ".$computer->mac." with id ".$id." (list ".count($this->items).")<br>";
			if (array_key_exists($id, $this->items)) {
				// Find existing computer to update its Ip, ...
				$existingId = $id;
				//echo "Existing ID!<br>";
			} else {
				//echo "New ID!<br>";

				//echo "<br/>Looking for ".$computer->mac." in ";echo '<pre>'; print_r($this->NetMAC); echo '</pre><br/>';
				if (array_key_exists($computer->mac, $this->NetMAC)) {
					// Find existing computer to update its Ip
					$existingId = $this->NetMAC[$computer->mac];
					//echo "But existing MAC of computer ID ".$existingId."<br>";
				}
			}
			
			if (isset($existingId) && $existingId != "") {
				//echo "update existing PC<br>";
				$existing = $this->items[$existingId];
				if ($existing->mac != $computer->mac) {
					// ??!!?? report an fatal issue !
					//echo "existing PC has a different MAC ?!<br>";
				} else {
					if(!isset($existing->ip)) $existing->ip = "0.0.0.0";
					if ($existing->ip != $computer->ip) {
						$existing->ip = $computer->ip;
						//echo "IP of existing PC updated<br>";
					}
					if(!isset($existing->hostname)) $existing->hostname = "";
					if (($existing->hostname != '') && isset($computer->hostname) && ($computer->hostname != '')) {
						$existing->hostname = $computer->hostname;
						//echo "Hostname of existing PC updated<br>";
					}
					if(!isset($existing->ethernet)) $existing->ethernet = "";
					if ($existing->ethernet != $computer->ethernet) {
						$existing->ethernet = $computer->ethernet;
						//echo "Ethernet interface of existing PC updated<br>";
					}
					if(!isset($existing->nas)) $existing->nas = 0;
					if ($existing->nas != $computer->nas) {
						$existing->nas = $computer->nas;
						//echo "Existing PC is now a NAS<br>";
					}				}
			} else {
				$this->items[$id] = $computer;
				//echo "Computer added to the list<br>";
			}
			
			// Reset IP of computer which possibly got that one previously
			if ($computer->ip != "0.0.0.0") {
				$previous = $this->GetByIp($computer->ip);
			}
			if (isset($previous) && ($computer->mac != $previous->mac) && ($previous->ip != "0.0.0.0")) {
				//echo "Reset ".$previous->mac." with ".$previous->ip." to 0.0.0.0<BR>";
				$previous->ip = "0.0.0.0";
			}
			
			// Record the computer by IP
			$this->NetIP[$computer->ip] = $id;
			$this->NetMAC[$computer->mac] = $id;
			//echo "<hr>";
		}
	}
}

function CheckPort($host,$port=80,$timeout=2) {
	$check = 0;
	$fsock = @fsockopen($host, $port, $errno, $errstr, $timeout);

    if (is_resource($fsock)) {
		fclose($fsock);
        $type = getservbyport($port, 'tcp');
		
		// Support http and mmcc (Synology DSM)
		if ($type == 'http' || $type == 'mmcc') {
			$check = 1;
		}
    }
	
	return $check;
}

function NativeWakeOnLan($mac, $ethernet) {
	//echo "ether-wake -i $ethernet $mac<br/>";
	$return = `ether-wake -i $ethernet $mac`;
	//echo "done<br/>";
}

function WakeOnLan($addr, $mac, $socket_number) {
   $addr_byte = explode(':', $mac);
   $hw_addr   = '';
   for($a=0; $a <6; $a++) 
      $hw_addr .= chr(hexdec($addr_byte[$a]));
      
   $msg = chr(255).chr(255).chr(255).chr(255).chr(255).chr(255);
   
   for($a = 1; $a <= 16; $a++) 
      $msg .= $hw_addr;
      
   $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
   
   if($s == false) 
   {
      echo "Can't create socket!<BR>\n";
      echo "Error: '".socket_last_error($s)."' - " . socket_strerror(socket_last_error($s));
      return FALSE;
   }
   else 
   {
      $opt_ret = socket_set_option($s, 1, 6, TRUE);
      
      if($opt_ret < 0) 
      {
         echo "setsockopt() failed, error: " . strerror($opt_ret) . "<BR>\n";
         return FALSE;
      }
      
      if(socket_sendto($s, $msg, strlen($msg), 0, $addr, $socket_number)) 
      {
         $content = bin2hex($msg);
         //echo "Magic Packet Sent!<BR>\n";
         //echo "Data: <textarea readonly rows=\"1\" name=\"content\" cols=\"".strlen($content)."\">".$content."</textarea><BR>\n";
         //echo "Port: ".$socket_number."<br>\n";
         //echo "MAC: ".$_GET['wake_machine']."<BR>\n";
         socket_close($s);
         return TRUE;
      }
      else 
      {
         echo "Magic Packet failed to send!<BR>";
         return FALSE;
      } 
   }
}

function Ping($ip) {
	if ($ip == "0.0.0.0") {
		$response = 0;
	} else {
		$cmd = "ping";
		$exist = exec('if [ -f /bin/ping ] ; then echo 1; else echo 0; fi');
		
		if ($exist == "0") {
			$cmd = "inet";
			$exist = exec('if [ -f /opt/bin/inetutils-ping ] ; then echo 1; else echo 0; fi');
		}
		
		if ($exist == "1") {
			if ($cmd == "inet") {
				`/opt/bin/inetutils-ping -c1 -n -r -q $ip > ./temp/ACPI.ping.$ip & WPID=\$!; sleep 1 && kill \$WPID > /dev/null 2>&1 & wait \$WPID`;
			} else {
				`ping -c1 -n -r -q -W 1 $ip > ./temp/ACPI.ping.$ip`;
			}
			$result = trim(`grep transmitted ./temp/ACPI.ping.$ip | cut -f3 -d"," | cut -f1 -d"%"`);
			`rm -f ./temp/ACPI.ping.$ip`;
			
			if ($result == "0") {
				$response = 1;
			} else {
				$response = 0;
			}
		} else {
			// Package inetutils must be installed via opkg (Entware)
			$response = -1;
		}
	}	
	return $response;
}

function LoadComputers() {
	$computers = new Computers();
	if (file_exists("./config/Computers.json")) {
		$data = file_get_contents("./config/Computers.json");
		$items = json_decode($data);
		foreach ($items as $computer){
			$computers->add($computer);
		}
	}

	if (file_exists("./config/Network.json")) {
		$data = file_get_contents("./config/Network.json");
		$network = json_decode($data);
		foreach ($network as $ethernet => $bmask){
			$computers->setBmask($ethernet, $bmask);
		}
	}

	return $computers;
}

function SaveComputers($computers) {
	$items = $computers->items;
	$data=json_encode($items, JSON_PRETTY_PRINT);
	file_put_contents("./config/Computers.json", $data);
}

function SaveBroadcastInfo($computers) {
	$network = array();	
	foreach ($computers->items as $computer) {
		if ($computer->nas==1) {
			if (!array_key_exists($computer->ethernet, $network)) {
				$network[$computer->ethernet] = $computer->bmask;
			}
		}
	}

	$data=json_encode($network, JSON_PRETTY_PRINT);
	file_put_contents("./config/Network.json", $data);
}

function FindHostname($ip) {
	$host = '';
	if ($p != '0.0.0.0') {
		`nmblookup -A $ip > ./temp/ACPI.hostname.$ip & WPID=\$!; sleep 1 && kill \$WPID > /dev/null 2>&1 & wait \$WPID`;
		$host = `grep '#00' ./temp/ACPI.hostname.$ip | grep -v GROUP | awk '{print $1}'`;
		`rm -f ./temp/ACPI.hostname.$ip`;
		$host = FixHostCase(CleanValue($host)); // remove all control chars
	}
	return $host;
}

function FixHostCase($host) {
	return ucwords(strtolower(trim($host)));
}

function CleanValue($value) {
	return trim(preg_replace('~[[:cntrl:]]~', ' ', $value)); // remove all control chars
}

function InitComputers() {
	$interfaces = new Computers();

	$address_ovsbond0 = ScanEthernet('ovs_bond0');
	if ($address_ovsbond0) {
		$interfaces->add($address_ovsbond0);
			
		$address_ovsbond1 = ScanEthernet('ovs_bond1');
		if ($address_ovsbond1) {
			$interfaces->add($address_ovsbond1);
		}
		$address_ovs0 = ScanEthernet('ovs_eth0');
		if ($address_ovs0) {
			$interfaces->add($address_ovs0);
		}
			
		$address_ovs1 = ScanEthernet('ovs_eth1');
		if ($address_ovs1) {
			$interfaces->add($address_ovs1);
		}					
	}
	else {
		$address_bond0 = ScanEthernet('bond0');
		if ($address_bond0) {
			$interfaces->add($address_bond0);
		}
		
		$address_bond1 = ScanEthernet('bond1');
		if ($address_bond1) {
			$interfaces->add($address_bond1);
		}		

		$address0 = ScanEthernet('eth0');
		if ($address0) {
			$interfaces->add($address0);
		}
			
		$address1 = ScanEthernet('eth1');
		if ($address1) {
			$interfaces->add($address1);
		}

		$address2 = ScanEthernet('eth2');
		if ($address2) {
			$interfaces->add($address2);
		}
		
		$address3 = ScanEthernet('eth3');
		if ($address3) {
			$address_3->nas=1;
			$interfaces->add($address3);
		}
	}
	return $interfaces;
}

function ScanEthernet($eth) {
	//Add NAS
	$mac = strtoupper(CleanValue(`ifconfig $eth | grep -Eo '([[:xdigit:]]{1,2}[:-]){5}[[:xdigit:]]{1,2}' | head -n1`));
	if ($mac) {
		//echo "Found MAC ".$mac." on ".$eth."<br/>\r\n";
		$ip = CleanValue(`ifconfig $eth | grep inet | awk '{print $2}' | cut -f2 -d":"`);
		//echo "Found IP ".$ip." on ".$eth."<br/>\r\n";
		$bmask = CleanValue(`ifconfig $eth | grep inet | awk '{print $3}' | cut -f2 -d":"`);
		//echo "Found bmask ".$bmask." on ".$eth."<br/>\r\n\r\n";
		
		if ($ip) {		
			//echo "Found IP ".$ip." on ".$eth."<br/>\r\n";
			$computer = new Computer();
			$computer->id = uniqid();
			$computer->mac=strtoupper($mac);
			$computer->ip=$ip;
			$computer->hostname=getHostName();
			$computer->os='syno';
			$computer->acpiOnLan='';
			$computer->state='';
			$computer->bmask=$bmask;
			$computer->ethernet=$eth;
			
			$computer->nas=1;
			$computer->vendor="Synology";

			if ($ip == '0.0.0.0') {
				$computer->State = "off.png";
			} else {
				$computer->State = "search.png";
			}
		}
	}
	return $computer;
}

function UpdateComputersFromArp($computers) {	
	$newComputers = new Computers();
	
	// Get the arp executable path 
	$location = `which arp`; 
	$location = rtrim($location); 
	
	// Execute the arp command and store the output in $arpTable 
	$arpTable = `$location -n`;
	
	// Split the output so every line is an entry of the $arpSplitted array 
	$arpSplitted = explode("\n",$arpTable); 

	// Initialize On-Lan-computers' state
	foreach ($arpSplitted as $value) {	
		$IP = '';
		$MAC = '';				
		if (preg_match("/[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f]/i",$value,$matches)) { 
				$MAC=$matches[0];
			}
		if ($MAC != '')	{
			$MAC=strtoupper($MAC);			
			if (preg_match("/(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}".
				"(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/i",$value,$matches)) {
					$IP=$matches[0];
			}			
						
			$computer = $computers->GetByMac($MAC);
			if (isset($computer) && ($computer->ip != $IP)) {
				//echo $MAC." UPDATED with ".$IP."<BR>";
				$computer->ip = $IP;
				$newComputers->add($computer);
			}
		}
	}	
	return $newComputers;
}

function NewComputersFromArp($computers, $max = 999999) {
	$KnowMac = $computers->NetMAC;
	$newComputers = new Computers();
	
	//echo "Search for new Computers<br>";
	
	// Get the arp executable path 
	$location = `which arp`; 
	$location = rtrim($location); 
	
	// Execute the arp command and store the output in $arpTable 
	$arpTable = `$location -n`;
	
	// Split the output so every line is an entry of the $arpSplitted array 
	$arpSplitted = explode("\n",$arpTable);

	// Initialize On-Lan-computers' state
	foreach ($arpSplitted as $value) {	
		$IP = '';
		$MAC = '';				
		if (preg_match("/[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f]/i",$value,$matches)) { 
				$MAC=$matches[0];
			}
		if ($MAC != '')	{
			$MAC=strtoupper($MAC);			
			if (!array_key_exists($MAC, $KnowMac)) {
				if (preg_match("/(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}".
					"(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/i",$value,$matches)) {
						$IP=$matches[0];
						
						if (Ping($IP) == 1) $host = FindHostname($IP);
				}
								
				//echo $MAC.":".$IP."<br/>";
					
				$computer = new Computer();
				$computer->id = uniqid();
				$computer->mac=strtoupper($MAC);
				$computer->ip=$IP;
				$computer->os='';
				$computer->acpiOnLan='';
				$computer->state='';
				$ethparts=explode(' ',$value);
				$eth=end($ethparts);
				$computer->ethernet=$eth;	
				
				$vendor=GetVendor($MAC);
				$computer->vendor=$vendor;
				
				if ($host)
					$computer->hostname=$host;
				else
					$computer->hostname="[".$vendor."]";
				
				$newComputers->add($computer);
			}			
		}
		
		if ($newComputers->count() > $max) break;
	}	
	return $newComputers;
}

function GetVendor($MAC) {
  $settings = LoadSettings();
  $token = $settings->tokenVendor;
    
  $url="https://api.macvendors.com/v1/lookup/".urlencode($MAC);
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  $authorization = "Authorization: Bearer ".$token; // **Prepare Autorisation Token**
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // **Inject Token into Header**
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  
  //Max one call per second
  $elapseExecVendor = time()-$lastExecVendor;
  if ($elapseExecVendor<1) sleep(1);
  $data = curl_exec($ch);
  $lastExecVendor=time();
  $vendor = json_decode($data);
  if (isset($vendor->data)) {
	$response = $vendor->data->organization_name;
	if ($response=="Vendor not found")
		$response = "Unknown";
  }
  else if (isset($vendor->errors)) {
	$response = $vendor->errors->detail;
  }
  else
	$response = "Unknown";
  return $response;
}

function GetCurrentIP($ExistingMac) {	
	//echo "Search current IP of a known MAC<br>";
		
	// Get the arp executable path 
	$location = `which arp`;
	$location = rtrim($location);
	
	// Execute the arp command and store the output in $arpTable 
	$arpTable = `$location -n`;

	//LogMessage($arpTable);
	
	// Split the output so every line is an entry of the $arpSplitted array 
	$arpSplitted = explode("\n",$arpTable); 

	// Initialize On-Lan-computers' state
	foreach ($arpSplitted as $value) {	
		$IP = '0.0.0.0';
		$MAC = '';				
		if (preg_match("/[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f]/i",$value,$matches)) { 
				$MAC=strtoupper($matches[0]);
			}
		if ($MAC == '')	{
			if (preg_match("/at .incomplete. /i",$value,$matches)) {
				$MAC="00:00:00:00:00:00";
			}
		}
		if (preg_match("/(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}".
			"(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/i",$value,$matches)) {
				$IP=$matches[0];
			}
		
		if ($MAC == $ExistingMac) {
			return $IP;
		}		
	}
	
	return '0.0.0.0';
}

function UpdateComputers($computers) {
	$updatecomputers = UpdateComputersFromArp($computers);
	foreach ($updatecomputers->items as $computer) {
		//echo "Update ".$computer->mac." with ".$computer->ip."<br>";
		$computers->add($computer);
	}
	$newcomputers = NewComputersFromArp($computers);
	foreach ($newcomputers->items as $computer) {						
		//echo "Add ".$computer->mac." with ".$computer->ip."<br>";
		$computers->add($computer);
	}
	return $computers;
}

function SortByIp($a, $b) {
	return ip2long($a->ip) > ip2long($b->ip);
}

function SortByName($a, $b) {
	return strcmp($a->hostname, $b->hostname);
}

function SortByMac($a, $b) {
	return strcmp($a->mac, $b->mac);
}

function GetActionIcon($computer, $action, $icon, $title) {
	$features = $computer->features;
	$id = $computer->id;
	
	if (is_array($features) && in_array($action, $features)) {
		$icon = str_replace("-0", "-1", $icon);
		$class="ACPIHaptic";
	} else {
		$icon = str_replace("-1", "-0", $icon);
		$title = $title." (disabled)";
		$class="disabled";
	}	
		
	return "<img class='".$class."' src='images/".$icon."' width='".GetIconSize()."' id='".$action.$id."' title='".$title."'>";
}

function GetIconSize() {
	$detect = new Mobile_Detect;
	if ($detect->isMobile()) {
		$iconSize = "100px";
	} else {
		$iconSize = "50px";
	}
	
	return $iconSize;
}

function GetMiniIconSize() {
	$detect = new Mobile_Detect;
	if ($detect->isMobile()) {
		$iconSize = "40px";
	} else {
		$iconSize = "20px";
	}
	
	return $iconSize;
}

function GetFontSize() {
	$detect = new Mobile_Detect;
	if ($detect->isMobile()) {
		$fontSize = "24pt";
	} else {
		$fontSize = "12pt";
	}
	
	return $fontSize;
}
function GetMobileView() {
	$detect = new Mobile_Detect;
	if ($detect->isMobile()) {
		$mobileView = 1;
	} else {
		$mobileView = 0;
	}
	
	return $mobileView;
}

function GetAction($settings, $computer, $action) {
	$actionDisplay = $settings->actionDisplay;
	$features = $computer->features;
	$id = $computer->id;
	
	if (is_array($features) && in_array($action, $features)) {		
		$visibility="";
	} else {
		if ($actionDisplay == "1") {		
			$visibility="";
		} else {
			//if (($computer->os == "windows") && ($action == "rst") || ($action == "shd")) {
			//	$visibility="";
			//} else {
				$visibility=" style='display: none;'";
			//}
		}
	}	
	
	return "<div id='".$action."Action".$id."' class='action' ".$visibility.">";
}

function LoadSettings() {
	$settings = new Settings();
	if (file_exists("./config/Settings.json")) {
		$data = file_get_contents("./config/Settings.json");
		$settings = json_decode($data);
	} else {
		SaveSettings($settings);
	}
	return $settings;
}

function SaveSettings($settings) {
	$data=json_encode($settings, JSON_PRETTY_PRINT);
	file_put_contents("./config/Settings.json", $data);
}

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== FALSE;
}

function CheckAcpiOnLan($ip) {
	$info = CallAPI("GET", "http://".$ip.":8888/api/computer");	
	if ($info == "") {
		$response = array("returnCode"=>0);
	} else {
		$response = json_decode($info, true); //true => cast to an array
	}	
	return $response;
}

function SetState($ip, $state) {
	$info = CallAPI("PUT", "http://".$ip.":8888/api/computer/state?mode=".$state);
	$response = json_decode($info);
	if (!$response) {
		$response = array("current"=>"none");
	}
	return $response;
}

function NetCall($ip, $state, $username, $password) {
	if ($state == 'shd'){
		$state = 'shutdown';
	}
	else if ($state == 'rst'){
		$state = 'shutdown -r';
	}
	else {
		$state = null;
	}

	if ($state)
	{
		$info = `net rpc $state --ipaddress $ip --user $username%$password`;
	} 
	$response = array("current"=>"none");
	return $response;
}

// Method: POST, PUT, GET etc
// Data: array("param" => "value") ==> index.php?param=value
function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
			
        default:
            if ($data) {			
                $url = sprintf("%s?%s", $url, http_build_query($data));
			}
    }

    // Optional Authentication:
    //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 500); 
	curl_setopt($curl, CURLOPT_TIMEOUT_MS, 1000);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

function RestoreBackup($restoreFile) {
	$result = 0;

	$restore = new Computers();
	if (file_exists($restoreFile)) {
		$data = file_get_contents($restoreFile);
		//unlink($restoreFile);

		$fp = GetLock();
		$computers = LoadComputers();				
		$items = json_decode($data);
		foreach ($items as $restoreItem){
			$mac=$restoreItem->mac;
			$computer = $computers->GetByMac($mac);
			if ($computer){
				//echo "$computer->icon <= $restoreItem->icon<br>";
				if (preg_match('#^icon_#i', $computer->icon) === 0) {
					if (preg_match('#^icon_#i', $restoreItem->icon) === 1) {
						//echo "replace icon ".$mac." <br>";
						$computer->icon = $restoreItem->icon;
					}
				}
				$hostname = $computer->hostname;
				//if (preg_match('/^\[.*\]$/', $computer->hostname) === 1) {
					if (preg_match('/^\[.*\]$/', $restoreItem->hostname) === 0) {							
						$computer->hostname = $restoreItem->hostname;
					}
				//}

				if (isset($restoreItem->os)) {
					$computer->os = $restoreItem->os;
				}

				if (isset($restoreItem->webpage) && ($restoreItem->webpage != 0)) {
					$computer->webpage = $restoreItem->webpage;
				}
			}
		}
		SaveComputers($computers);
		ReleaseLock($fp);		
		$result = 1;
	}

	return $result;
}

function ImportBackup($restoreFile) {
	$result = 0;

	$restore = new Computers();
	if (file_exists($restoreFile)) {
		$data = file_get_contents($restoreFile);
		//unlink($restoreFile);

		$fp = GetLock();
		$computers = LoadComputers();
		$items = json_decode($data);
		foreach ($items as $restoreItem){
			$mac=$restoreItem->mac;
			$computer = $computers->GetByMac($mac);
			if ($computer){
				//echo "$computer->icon <= $restoreItem->icon<br>";
				if (preg_match('#^icon_#i', $computer->icon) === 0) {
					if (preg_match('#^icon_#i', $restoreItem->icon) === 1) {
						//echo "replace icon ".$mac." <br>";
						$computer->icon = $restoreItem->icon;
					}
				}
				$hostname = $computer->hostname;
				if (preg_match('/^\[.*\]$/', $computer->hostname) === 1) {
					if (preg_match('/^\[.*\]$/', $restoreItem->hostname) === 0) {							
						$computer->hostname = $restoreItem->hostname;
					}
				}
			} else {
				$computers->add($restoreItem);
			}
		}
		SaveComputers($computers);
		ReleaseLock($fp);
		$result = 1;
	}

	return $result;
}

function UploadIcon($uploadIcon, $targetIcon) {
	$result = 0;

	if (file_exists($uploadIcon)) {
		$path_parts = pathinfo($targetIcon);
		$name = $path_parts['filename'];
		
		LogMessage("Do it");
		
		if (startsWith($name, "icon"))
			$name = str_replace("icon", "", $name);
		if (startsWith($name, "_"))
			$name = str_replace("_", "", $name);
		
		$ext = $path_parts['extension'];
		$targetIcon = "./images/icon_" . $name . "." . $ext;
		$i=1;
		while (file_exists($targetIcon)) {
			$targetIcon = "./images/icon_" . $name . $i . "." . $ext;
			$i++;
		}
		move_uploaded_file($uploadIcon, $targetIcon);
		$result = 1;
	} else {
		$result = 0;
	}

	return $result;
}

function LogMessage($log_msg) {
    $log_filename = "log";
    if (!file_exists($log_filename)) 
    {
        // create directory/folder uploads.
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename.'/log_' . date('d-M-Y') . '.log';
    file_put_contents($log_file_data, date("h:i:s") . ": " . $log_msg . "\n", FILE_APPEND);
}
?>