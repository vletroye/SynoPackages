#!/bin/sh
exec 1<&-
exec 1>>$SYNOPKG_TEMP_LOGFILE
exec 2<&-
exec 2>>$SYNOPKG_TEMP_LOGFILE

if [ -f "/var/packages/$SYNOPKG_PKGNAME/etc/parameters" ]; then
	#source /var/packages/$SYNOPKG_PKGNAME/etc/parameters
	. /var/packages/$SYNOPKG_PKGNAME/etc/parameters
fi

if [ -v $WIZARD_PASSWORD ]; then
	cat /var/packages/$SYNOPKG_PKGNAME/WIZARD_UIFILES/uninstall >> $SYNOPKG_TEMP_LOGFILE
	sed -i -e "s|@MODS_ADMIN@|$WIZARD_ADMIN|g" $SYNOPKG_TEMP_LOGFILE
	sed -i -e "s|@MODS_PASS@|$WIZARD_PASSWORD|g" $SYNOPKG_TEMP_LOGFILE
	sed -i -e "s|@MODS_PORT@|$WIZARD_PORT|g" $SYNOPKG_TEMP_LOGFILE
else
	echo "[]" >> $SYNOPKG_TEMP_LOGFILE
fi

exit 0