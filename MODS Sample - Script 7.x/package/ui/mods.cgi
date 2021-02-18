#!/bin/sh

# Set redirect_status to 1 to get php cgi working.
REDIRECT_STATUS=1 export REDIRECT_STATUS

# Define the name of the php page to be executed
SCRIPT_FILENAME=$(pwd)/mods.php export SCRIPT_FILENAME

cmd=(/usr/local/bin/php73-cgi -c /usr/local/etc/php73/cli/php.ini -d open_basedir=none "$SCRIPT_FILENAME")
"${cmd[@]}" 2>&1