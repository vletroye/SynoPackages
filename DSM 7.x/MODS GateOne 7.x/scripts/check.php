<?php
error_reporting(E_ALL ^ E_WARNING); 

/*if (isset($argc)) {
	for ($i = 0; $i < $argc; $i++) {
		echo "Argument #" . $i . " - " . $argv[$i] . "\n";
	}
}
else {
	echo "argc and argv disabled\n";
}*/

$options = getopt("u:p:s:o:");
/*if ($options !== false) {
	echo var_export($options, true);
}
else {
	echo "Could not get value of command line option\n";
}*/

$user = $options['u'];
$password = $options['p'];
$server = $options['s'];
$port = $options['o'];

if (empty($user)) {
	echo "User account to call SSH is missing";
	die(3);
}
if (empty($password)) {
	echo "Password of user account to call SSH is missing";
	die(3);
}
if (empty($port)) {
	$port = "22";
}
if (empty($server)) {
	$server = "127.0.0.1";
}

if (!function_exists("ssh2_connect")) die("fail: php module ssh2.so is not loaded");
if(!($con = ssh2_connect($server, $port))){
	echo "Unable to establish a connection via SSH on '$server'. Check that SSH is enabled in your Control Panel (See Terminal and SNMP) and is using the port '$port'.\n";
	die(1);
} else {
    if(!ssh2_auth_password($con, $user, $password)) {
		if (strlen($password) > 2){
			$pass =  substr($password, 0,1).str_repeat("*",strlen($password)-2).substr($password, -1);
		} else {
			$pass = $password;
		}
		echo "Unable to authenticate via SSH, on port '$port', using the account '$user' and password '$pass'.\n";
		die(2);
    } else {
		echo "Successfully authenticated via SSH.\n";
    }
}
exit(0);
?>