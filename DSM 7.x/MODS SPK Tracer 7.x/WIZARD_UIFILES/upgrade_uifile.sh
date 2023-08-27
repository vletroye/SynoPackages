#!/bin/sh
LOG="/tmp/$SYNOPKG_PKGNAME.log"

echo "###############################################################################" >> $LOG
echo "******************" >> $LOG
echo "* Upgrade Wizard *" >> $LOG
echo "******************" >> $LOG

JSON=`dirname $0`/upgrade

if [ -f $JSON ]; then
	if [ -f "/var/packages/$SYNOPKG_PKGNAME/etc/parameters" ]; then
		#source /var/packages/$SYNOPKG_PKGNAME/etc/parameters
		. /var/packages/$SYNOPKG_PKGNAME/etc/parameters
	fi
	cat $JSON >> $SYNOPKG_TEMP_LOGFILE
	if [ -z "$WIZARD_UPGRADE" ]; then
		echo "WIZARD_UPGRADE value not available" >> $LOG
		if [ -z "$WIZARD_INSTALL" ]; then
			echo "WIZARD_INSTALL value not available" >> $LOG
			sed -i -e "s|@MODS_UPGRADE@||g" $SYNOPKG_TEMP_LOGFILE
		else
			echo "WIZARD_INSTALL=$WIZARD_INSTALL" >> $LOG
			sed -i -e "s|@MODS_UPGRADE@|$WIZARD_INSTALL|g" $SYNOPKG_TEMP_LOGFILE
		fi
	else
		echo "WIZARD_UPGRADE=$WIZARD_UPGRADE" >> $LOG
		sed -i -e "s|@MODS_UPGRADE@|$WIZARD_UPGRADE|g" $SYNOPKG_TEMP_LOGFILE
	fi
else
	echo "[]" >> $SYNOPKG_TEMP_LOGFILE
fi

exit 0