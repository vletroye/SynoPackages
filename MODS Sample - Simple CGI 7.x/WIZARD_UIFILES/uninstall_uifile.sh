#!/bin/sh

. `dirname $0`/../scripts/common `basename $0` $*

StartWizard

JSON=`dirname $0`/uninstall
DFLT=`dirname $0`/default

if [ -f $JSON ]; then
	ExitCode=1
	
	# Check if SSH connection is possible with credentials possibly stored during installation
	if [ -f /var/packages/$SYNOPKG_PKGNAME/etc/parameters ]; then
		LogInfo "Loading previous parameters"
		
		# Retrieve admin account and SSH port
		. /var/packages/$SYNOPKG_PKGNAME/etc/parameters
		
		if [ ! -z $MODS_ADMIN ] && [ -f /var/packages/$SYNOPKG_PKGNAME/etc/backup ]; then
			# Retrieve password
			MODS_PASSWORD=$(openssl rsautl -inkey /var/packages/$SYNOPKG_PKGNAME/etc/image -decrypt < /var/packages/$SYNOPKG_PKGNAME/etc/backup)

			# Check if SSH connection is working
			CheckSSH "$MODS_ADMIN" "$MODS_PASSWORD" "$MODS_PORT"
			ExitCode=$?
		fi
	else 
		MODS_ADMIN="admin"
		MODS_PORT=22
		ExitCode=1
	fi
		
	if [ $ExitCode == 0 ]; then
		# SSH connection possible with previously stored credentials
		LogInfo "Connected via SSH using stored credentials"
		cat $DFLT >> $SYNOPKG_TEMP_LOGFILE
		
		sed -i -e "s|@MODS_ADMIN@|$MODS_ADMIN|g" $SYNOPKG_TEMP_LOGFILE
	else
		# Need to prompt the user for SSH credentials
		cat $JSON >> $SYNOPKG_TEMP_LOGFILE

		sed -i -e "s|@MODS_ADMIN@|$MODS_ADMIN|g" $SYNOPKG_TEMP_LOGFILE
		sed -i -e "s|@MODS_PORT@|$MODS_PORT|g" $SYNOPKG_TEMP_LOGFILE
		sed -i -e "s|@MODS_PASSWORD@|$MODS_PASSWORD|g" $SYNOPKG_TEMP_LOGFILE
	fi
else
	echo "[]" >> $SYNOPKG_TEMP_LOGFILE
fi

EndWizard

exit 0