#!/bin/sh
REDIRECT_STATUS=1 export REDIRECT_STATUS
SCRIPT_FILENAME=$(pwd)/test.php export SCRIPT_FILENAME
/usr/local/bin/php56-cgi -d open_basedir=none $SCRIPT_FILENAME 2>&1