#!/bin/sh

DIR="/var/packages/@SYNOPKG_PKGNAME@/var/log"
LOG="$DIR/router.log"

# create the Log dir is it does not exists
if [ ! -d "$DIR" ]; then
    mkdir -p "$DIR"
fi

# the web server account (Ex.: http) must be granted write access
if [ -w $LOG ]; then
  echo `date` "------------------------------------------------------------------" >> $LOG
  echo `date` "ROUTER.cgi has been called" >> $LOG
  echo `date` "HANDLING request for" $REQUEST_URI >> $LOG
  
  # Log all environment variables if the Query String contains 'LogRouterCgi'
  if [[ $QUERY_STRING = *"LogRouterCgi"* ]]; then  
    printenv >> $LOG
  fi  
fi

if [ "$REQUEST_URI" == "$SCRIPT_NAME" ]; then
  if [ -w $LOG ]; then
    echo `date` "NO REQUEST:" $SCRIPT_NAME >> $LOG
  fi
  #echo "Content-type: text/html"
  echo -e "HTTP/1.1 200 OK\n\n"
else

  # Set redirect_status to 1 to get php cgi working.
  REDIRECT_STATUS=1 export REDIRECT_STATUS
  
  # Fix several $_SERVER globals.
  PHP_SELF=$REQUEST_URI export PHP_SELF
  SCRIPT_NAME=$REQUEST_URI export SCRIPT_NAME
  
  # Generate the request url without the Query String parameters
  SCRIPT_FILENAME=$DOCUMENT_ROOT${REQUEST_URI%\?$QUERY_STRING}
  if [ -w $LOG ]; then
    echo `date` "SCRIPT_FILENAME:" $SCRIPT_FILENAME >> $LOG
  fi

  # Prepare the Query String parameters
  SCRIPT_PARAMETERS=${QUERY_STRING//[&]/ }
  if [ -w $LOG ]; then
    echo `date` "SCRIPT_PARAMETERS:" $SCRIPT_PARAMETERS >> $LOG
  fi

  SCRIPT_FILENAME=`realpath $SCRIPT_FILENAME` export SCRIPT_FILENAME
  if [ -w $LOG ]; then
    echo `date` "REALPATH:" $SCRIPT_FILENAME >> $LOG
  fi
  
  cmd=(/usr/local/bin/php73-cgi -c /usr/local/etc/php73/cli/php.ini -d open_basedir=none "$SCRIPT_FILENAME" "$SCRIPT_PARAMETERS")
  
  if [ -w $LOG ]; then
    #echo `date` "EXECUTE:" "/etc/php/php.ini -c  /usr/local/etc/php56/php.ini -d open_basedir=none "$SCRIPT_FILENAME" "$SCRIPT_PARAMETERS" 2>&1" >> $LOG
    #echo `date` "EXECUTE:" "/usr/local/bin/php70-cgi -c  /usr/local/etc/php70/php.ini -d open_basedir=none "$SCRIPT_FILENAME" "$SCRIPT_PARAMETERS" 2>&1" >> $LOG
	#echo `date` "EXECUTE:" "/usr/local/bin/php73-cgi -c /usr/local/etc/php73/cli/php.ini -d open_basedir=none "$SCRIPT_FILENAME" "$SCRIPT_PARAMETERS" 2>&1" >> $LOG
    #echo `date` "EXECUTE:" "/usr/local/bin/php74-cgi -c /usr/local/etc/php74/cli/php.ini -d open_basedir=none "$SCRIPT_FILENAME" "$SCRIPT_PARAMETERS" 2>&1" >> $LOG
	echo "${cmd[@]}" >> $LOG
  fi
  #/usr/local/bin/php56-cgi -c /etc/php/php.ini -d open_basedir=none $SCRIPT_FILENAME $SCRIPT_PARAMETERS 2>&1
  #/usr/local/bin/php70-cgi -c /usr/local/etc/php70/php.ini -d open_basedir=none $SCRIPT_FILENAME $SCRIPT_PARAMETERS 2>&1
  #/usr/local/bin/php73-cgi -c /usr/local/etc/php73/cli/php.ini -d open_basedir=none $SCRIPT_FILENAME $SCRIPT_PARAMETERS 2>&1
  #/usr/local/bin/php74-cgi -c /usr/local/etc/php74/cli/php.ini -d open_basedir=none $SCRIPT_FILENAME $SCRIPT_PARAMETERS 2>&1
  
  "${cmd[@]}" 2>&1
fi