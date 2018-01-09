#!/bin/sh

# Set redirect_status to 1 to get php cgi working.
REDIRECT_STATUS=1 export REDIRECT_STATUS

# Fix several $_SERVER globals.
PHP_SELF=$SCRIPT_URL export PHP_SELF
SCRIPT_NAME=$SCRIPT_URL export SCRIPT_NAME

# Strip web base from SCRIPT_URL, concat it to parent directory
# and apply realpath to construct absolute path to requested script.
WEB_BASE="/webman/3rdparty"
SCRIPT_FILENAME=$(pwd)/..${SCRIPT_URL:${#WEB_BASE}}
SCRIPT_FILENAME=`realpath $SCRIPT_FILENAME`
export SCRIPT_FILENAME

# Execute the requested PHP file.
/usr/local/bin/php70-cgi -d open_basedir=none $SCRIPT_FILENAME 2>&1