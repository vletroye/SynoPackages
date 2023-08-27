#!/bin/sh
LOG="/tmp/$SYNOPKG_PKGNAME.log"

echo "###############################################################################" >> $LOG
echo "***********************" >> $LOG
echo "* Installation Wizard *" >> $LOG
echo "***********************" >> $LOG

JSON=`dirname $0`/install 

if [ -f $JSON ]; then
	cat $JSON >> $SYNOPKG_TEMP_LOGFILE
else
	echo "[]" >> $SYNOPKG_TEMP_LOGFILE
fi

exit 0