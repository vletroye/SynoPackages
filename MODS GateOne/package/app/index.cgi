#!/bin/sh

# Set redirect_status to 1 to get php cgi working.
REDIRECT_STATUS=1 export REDIRECT_STATUS

# Strip web base from SCRIPT_URL, concat it to parent directory
# and apply realpath to construct absolute path to requested script.
SCRIPT_FILENAME=$(pwd)/index.php export SCRIPT_FILENAME

if command -v php56-cgi > /dev/null 2>&1; then
  CALL=$(which php56-cgi)
else
  if command -v php70-cgi > /dev/null 2>&1; then
    CALL=$(which php70-cgi)
  else
    if command -v php-cgi > /dev/null 2>&1; then
      CALL=$(which php-cgi)
    fi
  fi
fi

CALL="$CALL -d open_basedir=none $SCRIPT_FILENAME 2>&1"

eval "$CALL"
