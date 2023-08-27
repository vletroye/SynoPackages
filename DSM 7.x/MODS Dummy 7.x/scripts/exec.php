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
	die("User account to call SSH is missing");
}
if (empty($password)) {
	die("Password of user account to call SSH is missing");
}
if (empty($command)) {
	die("Command to execute via SSH is missing");
}
if (empty($port)) {
	$port = "22";
}
if (empty($server)) {
	$server = "127.0.0.1";
}

if (!function_exists("ssh2_connect")) die("fail: php module ssh2.so is not loaded");
if(!($con = ssh2_connect($server, $port))){
    #echo "fail: unable to establish connection to '$server:$port'\n";
	die("Unable to establish a connection to SSH. Check that it is enabled in your Control Panel (See Terminal and SNMP) and using the port '$Port'.\n");
} else {
    if(!ssh2_auth_password($con, $user, $password)) {
		if (strlen($password) > 2){
			$pass =  substr($password, 0,1).str_repeat("*",strlen($password)-2).substr($password, -1);
		} else {
			$pass = $password;
		}
        #echo "fail: unable to authenticate with user '$user' and password '$pass'\n";
		die("Unable to authenticate via SSH using the account '$user' and password '$pass'.\n");
    } else {
        // allright, we're in!
        #echo "okay: logged in...\n";

        // execute a command via sudo. The account used to run this php script must be an admnistrator/sudoer
		//set +o history
		$command = "echo '$password' | sudo -S -- sh  -c \"echo;$command\" 2>&1 | grep -v \"Password:\"";
		//set -o history
        if (!($stream = ssh2_exec($con, $command ))) {
            echo "Unable to execute the command '$command'\n";
        } else {
            // collect returning data from command
            stream_set_blocking($stream, true);
            $data = "";
            while ($buf = fread($stream,4096)) {
                #$data .= $buf;
				echo $buf;
            }
            fclose($stream);
        }
    }
}
?>