#!/bin/sh

# Set redirect_status to 1 to get php cgi working.
REDIRECT_STATUS=1 export REDIRECT_STATUS

echo `date` "helloworld.cgi has been called" >> /var/log/MODS_BasicTestCGI

# Define the name of the php page to be executed
SCRIPT_FILENAME=$(pwd)/helloworld.php export SCRIPT_FILENAME
echo `date` "it will execute" $SCRIPT_FILENAME >> /var/log/MODS_BasicTestCGI

/usr/local/bin/php73-cgi -c /etc/php/php.ini -d open_basedir=none $SCRIPT_FILENAME $SCRIPT_PARAMETERS 2>&1