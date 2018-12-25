#!/bin/sh
LOG="/var/log/MODS_AdvancedTestCGI"

REQUEST_URI=${REQUEST_URI/helloworld.cgi/helloworld.php} export REQUEST_URI

# the web server account (Ex.: http) must be granted write access
if [ -w $LOG ]; then
  echo `date` "------------------------------------------------------------------" >> $LOG
  echo `date` "HELLOWORLD.cgi has been called" >> $LOG
  echo `date` "REDIRECTING to router.cgi to handle" $REQUEST_URI >> $LOG
  #printenv >> $LOG
fi

sh ./router.cgi
