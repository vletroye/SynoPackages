#!/bin/sh
LOG="/tmp/$SYNOPKG_PKGNAME.log"

LOG="/tmp/$SYNOPKG_PKGNAME.log"

echo "###############################################################################" >> $LOG
echo "*************************" >> $LOG
echo "* Uninstallation Wizard *" >> $LOG
echo "*************************" >> $LOG

JSON=`dirname $0`/uninstall

if [ -f $JSON ]; then
	if [ -f "/var/packages/$SYNOPKG_PKGNAME/etc/parameters" ]; then
		# Load the variables of the Installation and Upgrade Wizards
		. /var/packages/$SYNOPKG_PKGNAME/etc/parameters
	fi
	cat $JSON >> $SYNOPKG_TEMP_LOGFILE
	if [ -z "$WIZARD_UPGRADE" ]; then
		echo "WIZARD_UPGRADE value not available" >> $LOG
		sed -i -e "s|@MODS_UPGRADE@||g" $SYNOPKG_TEMP_LOGFILE
	else
		echo "WIZARD_UPGRADE=$WIZARD_UPGRADE" >> $LOG
		sed -i -e "s|@MODS_UPGRADE@|$WIZARD_UPGRADE|g" $SYNOPKG_TEMP_LOGFILE
	fi
	if [ -z "$WIZARD_INSTALL" ]; then
		echo "WIZARD_UPGRADE value not available" >> $LOG
		sed -i -e "s|@MODS_INSTALL@||g" $SYNOPKG_TEMP_LOGFILE
	else
		echo "WIZARD_INSTALL=$WIZARD_INSTALL" >> $LOG
		sed -i -e "s|@MODS_INSTALL@|$WIZARD_INSTALL|g" $SYNOPKG_TEMP_LOGFILE
	fi
	
	echo "SYNOPKG_PKGDEST_VOL=$SYNOPKG_PKGDEST_VOL" >> $LOG
	sed -i -e "s|@MODS_VOLUME@|$SYNOPKG_PKGDEST_VOL|g" $SYNOPKG_TEMP_LOGFILE
else
	echo "[]" >> $SYNOPKG_TEMP_LOGFILE
fi

exit 0