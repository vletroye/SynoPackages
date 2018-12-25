<?php
	require_once 'acpi.on.lan.php';
			
	define('IMAGESPATH', 'images/');
	define('BACKUPSPATH', 'backups/');
	define('TEMPPATH', 'temp/');
	define('LOCK', 'lock');
	$Service = $_GET["service"];

	function GetLock() {
		$filename = TEMPPATH.LOCK;
		$fp = fopen( $filename, "w");
		flock($fp, LOCK_EX);
		return $fp;
	}
	
	function ReleaseLock($fp) {
		flock($fp, LOCK_UN);
	}
	
	switch ($Service) {
		case "Ping":
			$ip = $_GET["ip"];
			$state = Ping($ip);
			$response = array('state' => $state);
			break;

		case "PingComputer":
			$id = $_GET["id"];
			$ip = $_GET["ip"];
			$fp = GetLock();
			$computers = LoadComputers();
			if ($id) {
				$computer = $computers->GetById($id);
			} else {
				$computer = $computers->GetByIp($ip);
			}
			$state = Ping($computer->ip);
			$computer->state = $state;
			SaveComputers($computers);
			ReleaseLock($fp);
			$response = $computer;
			break;
			
		case "CheckHttp":
			$id = $_GET["id"];
			$ip = $_GET["ip"];
			$webport = $_GET["webport"];
			$fp = GetLock();
			$computers = LoadComputers();
			if ($id) {
				$computer = $computers->GetById($id);
			} else {
				$computer = $computers->GetByIp($ip);
			}
			if ($webport == 0) 
				$webport = 80;			
			$state = CheckPort($computer->ip, $webport);
			if ($state == 1)
				$computer->webpage = $webport;
			else
				$computer->webpage = 0;
			SaveComputers($computers);
			ReleaseLock($fp);
			$response = $computer;
			break;
			
		case "CheckAcpiOnLan":
			$id = $_GET["id"];
			$ip = $_GET["ip"];
			$fp = GetLock();
			$computers = LoadComputers();
			if ($id) {
				$computer = $computers->GetById($id);
			} else {
				$computer = $computers->GetByIp($ip);
			}
			$result = CheckAcpiOnLan($computer->ip);
			$computer->acpiOnLan = $result["returnCode"];
			if (!$computer->os) {
				$computer->os = $result["os"];
			}
			if (!$computer->hostname) {
				$computer->hostname = $result["hostname"];
			}
			SaveComputers($computers);
			ReleaseLock($fp);
			$response = $computer;
			break;			
			
		case "NetCall":
			$id = $_GET["id"];
			$ip = $_GET["ip"];
			$state = $_GET["state"];
			$username = $_GET["username"];
			$password = $_GET["password"];
			$computers = LoadComputers();
			if ($id) {
				$computer = $computers->GetById($id);
			} else {
				$computer = $computers->GetByIp($ip);
			}
			$response = NetCall($computer->ip, $state, $username, $password);
			break;

		case "SetState":
			$id = $_GET["id"];
			$ip = $_GET["ip"];
			$state = $_GET["state"];
			$computers = LoadComputers();
			if ($id) {
				$computer = $computers->GetById($id);
			} else {
				$computer = $computers->GetByIp($ip);
			}
			$response = SetState($computer->ip, $state);
			break;
			
		case "Computers":
			$computers = LoadComputers();
			$response = $computers;
			break;
						
		case "FetchVendor":
			$mac = $_GET["mac"];
			$vendor=GetVendor($mac);
			$response = $vendor;
			break;
			
		case "FetchHost":
			$ip = $_GET["ip"];
			$host = FindHostname($ip);

			$fp = GetLock();
			$computers = LoadComputers();
			$computer = $computers->GetByIp($ip);
			if ($host == '') {
				$host="[".$computers->vendor."]";				
			}
			if ($host != '') {
				$computer->hostname = $host;
			}
			ReleaseLock($fp);
			$response = $computer;
			break;

		case "EditHost":
			$id = $_GET["id"];
			$host = FixHostCase($_GET["hostname"]);
			if ($host != '')
			{
				$fp = GetLock();
				$computers = LoadComputers();
				$computer = $computers->GetById($id);
				$computer->hostname = $host;
				SaveComputers($computers);
				ReleaseLock($fp);
			}
			$response = $computer;
			break;
			
		case "SwitchVisibility":
			$id = $_GET["id"];
			$state = $_GET["state"];
			$fp = GetLock();
			$computers = LoadComputers();
			$computer = $computers->GetById($id);
			$computer->hidden = $state;
			SaveComputers($computers);
			ReleaseLock($fp);
			$response = $computer;
			break;

		case "Delete":
			$id = $_GET["id"];
			$fp = GetLock();
			$computers = LoadComputers();
			$computer = $computers->GetById($id);
			$computers->delete($computer);
			SaveComputers($computers);
			ReleaseLock($fp);
			$response = $computer;
			break;
			
		case "SaveState":
			$id = $_GET["id"];
			$state = $_GET["state"];
			$icon = $_GET["icon"];
			$features = $_GET["features"];
			$acpiOnLan = $_GET["acpiOnLan"];

			$fp = GetLock();
			$computers = LoadComputers();
			$computer = $computers->GetById($id);
			if ($acpiOnLan)
				$computer->acpiOnLan = $acpiOnLan;
			if ($state)
				$computer->state = $state;
			if ($icon)
				$computer->icon = $icon;
			$computer->update = date("Y-m-d H:i:s");
			if ($features)
				$computer->features = $features;
				
			SaveComputers($computers);
			ReleaseLock($fp);
			$response = $computer;
			break;
			
		case "SetOS":
			$id = $_GET["id"];
			$os = $_GET["os"];
			$fp = GetLock();
			$computers = LoadComputers();
			$computer = $computers->GetById($id);
			$computer->os = $os;
			SaveComputers($computers);
			ReleaseLock($fp);
			$response = $computer;
			break;
			
		case "WakeOnLan":
			$id = $_GET["id"];
			$mac = $_GET["mac"];

			$computers = LoadComputers();
			if ($id)
				$computer = $computers->GetById($id);
			else
				$computer = $computers->GetByMac($mac);
			$mac = strtoupper($computer->mac);
			$eth = $computer->ethernet;
			$bmask = $computers->getBmask($eth);

			//echo $eth.", ".$mac.", ".$bmask."<br/>";
			//NativeWakeOnLan($mac, $eth);
			if ($bmask == '') {
				header('HTTP/1.0 400 Bad error');
				$response = array('type' => 'error', 'message' => 'Unknown ethernet mask');
			} else if (WakeOnLan($bmask, $mac, 1009) == FALSE) {
				header('HTTP/1.0 400 Bad error');
				$response = array('type' => 'error', 'message' => 'Wake-On-Lan failed');
			} else {
				$response = $computer;
			}
			break;
			
		case "InitACPI":
			if (!file_exists(TEMPPATH)) {
				mkdir(TEMPPATH, 0777, true);
				file_put_contents(TEMPPATH.LOCK, "");
			}
			if (!file_exists(TEMPPATH.LOCK)) {
				file_put_contents(TEMPPATH.LOCK, "");
			}			
			$fp = GetLock();
			
			$computers = LoadComputers();
			if ($computers->count() == 0) {
				$start = microtime(true);
				
				$computers = InitComputers();
				SaveComputers($computers);
				SaveBroadcastInfo($computers);
				
				$end = microtime(true);
				$duration = $end - $start;
				if ($duration < 3000)
				{
					$duration = (3000-$duration)/1000;
					sleep($duration);
				}
				$response = $computers;
			} else {
				$response = $computers;
			}
			
			if (file_exists("./config/token")) {
				$tokenVendor = file_get_contents("./config/token");
				$tokenVendor = trim(preg_replace('/\s+/', ' ', $tokenVendor));
				$settings = LoadSettings();
				$settings->tokenVendor = $tokenVendor;
				SaveSettings($settings);
			}
			
			ReleaseLock($fp);
			break;
			
		case "Flush":
			//echo 'Delete All <BR />';
			//echo 'ip  neigh  flush  all<BR />';
			`ip  neigh  flush  all`;

			$fp = GetLock();
			$computers = LoadComputers();
			$updatedInterfaces = InitComputers();
			foreach ($updatedInterfaces->items as $computer) {
				$computers->add($computer);
			}
			SaveComputers($computers);
			SaveBroadcastInfo($updatedInterfaces);
			ReleaseLock($fp);
			break;
			
		case "Delete":
			//echo 'Delete '.$Target.'<BR />';
			echo `arp -d $Target`;
			break;
			
		case "Refresh":
			//echo 'Refresh '.$Target.'<BR />';
			//Recheck the MAC address
			//echo 'arp -a '.$Target.'<BR />';
			`arp -a $Target`;
			break;
		
		case "ResetAll":
			if (file_exists("./config/Computers.json")) {
				if (!file_exists(BACKUPSPATH)) {
					mkdir(BACKUPSPATH, 0777, true);
				}
				$filecount = 0;
				$files = glob(BACKUPSPATH."Computers*.json");
				if ($files){
					$filecount = count($files);
				}
				rename("./config/Computers.json", BACKUPSPATH."Computers.".$filecount.".json");
				$response = array('state'=>1);
			} else {
				$response = array('state'=>0);
			}
			break;
			
		case "GetIcons":
			$response = array();
			foreach(glob(IMAGESPATH.'icon_*.png') as $filename){
				array_push($response, basename($filename));
			}
			break;
		
		case "LogOut":
			unset($_SESSION['AcpiOnLanUser']);
			break;
			
		case "CheckIp":
			$id = $_GET["id"];
			$fp = GetLock();
			$computers = LoadComputers();
			$computer = $computers->GetById($id);			
						
			if ($computer->nas == 1)
			{
				$eth = $computer->ethernet;
				$result = CleanValue(`ifconfig $eth | grep inet | awk '{print $2}' | cut -f2 -d":"`);
			}
			else
				$result = GetCurrentIP($computer->mac);
			
			$computer->ip = $result;
			SaveComputers($computers);
			if ($result == '0.0.0.0') {
				//Check if last known IP is reused
				foreach ($computers->items as $other) {
					if (($computer->ip == $other->ip) && ($computer->mac == $other->mac)) {
						$computer->ip = '';
						break;
					}
				}
			}			
			ReleaseLock($fp);
			$response = $computer;
			break;
			
		case "Backup":
			if (file_exists("./config/Computers.json")) {
				$file = "./config/Computers.json";
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($file).'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				header('Set-Cookie: fileDownload=true; path=/');
				readfile($file);
				exit;
			}
			break;

		case "GetService":
			if (file_exists("./service/AcpiOnLanInstaller.msi")) {
				$file = "./service/AcpiOnLanInstaller.msi";
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($file).'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				header('Set-Cookie: fileDownload=true; path=/');
				readfile($file);
				exit;
			}
			break;
			
		case "Restore":
			$restoreFile=$_FILES['myfile']['tmp_name'];
			$state = RestoreBackup($restoreFile);
			$response = array('state' => $state);
			break;
			
		case "Import":
			$restoreFile=$_FILES['myfile']['tmp_name'];
			$state = ImportBackup($restoreFile);
			$response = array('state' => $state);
			break;

		case "UploadIcon":
			$uploadIcon=$_FILES['myfile']['tmp_name'];
			$targetIcon=$_FILES['myfile']['name'];
			$state = UploadIcon($uploadIcon, $targetIcon);
			$response = array('state' => $state);
			break;
	}
	
	header('Content-Type: application/json');
	$return = json_encode($response);
	
	echo $return;
?>