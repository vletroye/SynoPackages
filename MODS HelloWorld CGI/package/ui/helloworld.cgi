#!/bin/sh

# Set redirect_status to 1 to get php cgi working.
REDIRECT_STATUS=1 export REDIRECT_STATUS

# Strip web base from SCRIPT_URL, concat it to parent directory
# and apply realpath to construct absolute path to requested script.
SCRIPT_FILENAME=$(pwd)/helloworld.php export SCRIPT_FILENAME
echo `date` $SCRIPT_FILENAME >> /var/log/MODS_HelloWorld_CGI
echo `date` "helloworld.cgi called" >> /var/log/MODS_HelloWorld_CGI
/usr/local/bin/php56-cgi -d open_basedir=none $SCRIPT_FILENAME 2>&1