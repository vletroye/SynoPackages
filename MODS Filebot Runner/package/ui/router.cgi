#!/bin/sh

if [ "$REQUEST_URI" == "$SCRIPT_NAME" ]; then
  echo -e "HTTP/1.1 200 OK\n\n"
else

  # Set redirect_status to 1 to get php cgi working.
  REDIRECT_STATUS=1 export REDIRECT_STATUS
  
  # Fix several $_SERVER globals.
  PHP_SELF=$REQUEST_URI export PHP_SELF
  SCRIPT_NAME=$REQUEST_URI export SCRIPT_NAME
  
  # Generate the request url without the Query String parameters
  SCRIPT_FILENAME=$DOCUMENT_ROOT${REQUEST_URI%\?$QUERY_STRING}

  # Prepare the Query String parameters
  SCRIPT_PARAMETERS=${QUERY_STRING//[&]/ }

  SCRIPT_FILENAME=`realpath $SCRIPT_FILENAME` export SCRIPT_FILENAME

  /usr/local/bin/php73-cgi -c /etc/php/php.ini -d open_basedir=none $SCRIPT_FILENAME $SCRIPT_PARAMETERS 2>&1
fi