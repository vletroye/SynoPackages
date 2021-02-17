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

$options = getopt("u:p:s:o:c:");
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
$command = $options['c'];

if (empty($user)) {
	echo "User account to call SSH is missing";
	die(3);
}
if (empty($password)) {
	echo "Password of user account to call SSH is missing";
	die(3);
}
if (empty($command)) {
	echo "Command to execute via SSH is missing";
	die(4);
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
        // execute a command via sudo. The account used to run this php script must be an admnistrator/sudoer
		//set +o history
		$command = "echo '$password' | sudo -S -- sh -c \"echo;$command\" 2>&1 | grep -v \"Password:\"";
		//set -o history
        if (!($stream = ssh2_exec($con, $command ))) {
            echo "Unable to execute the command via SSH: '$command'\n";
			die(3);
        } else {
			echo "Command successfully executed via SSH.\n";

            // collect returning data from command
            stream_set_blocking($stream, true);
            $data = "";
            while ($buf = fread($stream,4096)) {
 				echo $buf;
            }
            fclose($stream);
        }
    }
}

exit(0)
?>