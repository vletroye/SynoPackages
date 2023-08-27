#!/bin/sh

ADMIN=$1
PASSWORD=$2
SSH_PORT=$3
ACTION=$4
BASEDIR=$(dirname "$0")

if [[ $ADMIN == "" ]] || [[ $PASSWORD == "" ]] || [[ $SSH_PORT == "" ]] 
then
    echo "Usage: grant user password ssh_port"
    echo "       user must be an account with sudo access and ssh must be enabled"
    exit
fi

# Execute a command via SSH on the local host, using a php script
function ExecSSH() {
	ARG_USER=$1	# Admin account
	ARG_PASSWORD=$2	# Admin password
	ARG_PORT=$3 # SSH port
	ARG_CMD=$4	# Command to execute
	
	# Find the php version to be used 
	version=`php -v | grep "PHP " | cut -f2 -d " "`
	major=`echo $version | cut -d. -f1`
	minor=`echo $version | cut -d. -f2`
	
	if [ $0 == "-sh" ]; then
		# If run from a -sh, assume that the php script os local
		path="."
	else
		path="$BASEDIR"
	fi
	script="$path/exec.php"
		
	# Call PHP with ssh2 modules
	output="$(php -dextension=/var/packages/PHP$major.$minor/target/usr/local/lib/php$major$minor/modules/ssh2.so "$script" -u "$ARG_USER" -p "$ARG_PASSWORD" -s "127.0.0.1" -o "$ARG_PORT" -c "$ARG_CMD")"
	ExitCode=$? #Do not add any line or comment between the command and this line retrieving its exit code!!	
	echo $output
	
	return $ExitCode
}

if [[ $ACTION == "" ]]
then
	USER=$(whoami)
	
	#Grant minimum required access
	echo "$USER ALL=(ALL) NOPASSWD: ALL" > $BASEDIR/RunningAsAdmin

	# sudoer file may not contain "dots" => this is not a valid name "MODS_Package_Manager_7.x"
	ExecSSH "$ADMIN" "$PASSWORD" "$SSH_PORT" "cp -f $BASEDIR/RunningAsAdmin /etc/sudoers.d/MODS_Package_Manager_7x"
	
	rm -f $BASEDIR/RunningAsAdmin
else
	# sudoer file may not contain "dots" => this is not a valid name "MODS_Package_Manager_7.x"
	ExecSSH "$ADMIN" "$PASSWORD" "$SSH_PORT" "rm -f /etc/sudoers.d/MODS_Package_Manager_7x"
fi