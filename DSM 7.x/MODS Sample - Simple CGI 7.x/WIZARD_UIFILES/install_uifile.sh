#!/bin/sh

JSON=`dirname $0`/install 

if [ -f $JSON ]; then
	cat $JSON >> $SYNOPKG_TEMP_LOGFILE
else
	echo "[]" >> $SYNOPKG_TEMP_LOGFILE
fi

exit 0