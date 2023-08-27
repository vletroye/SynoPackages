#!/bin/bash

TARGET=$1
PACKAGE=$2

if [[ $PACKAGE == "" ]]
then
	echo "Usage: mvpkg Target Package"
	echo "       Target must be like 'volumex' or 'volumeUSBx' where x is a numeric."
	echo "       Package must be the name of a package."
	exit
fi

if [[ $TARGET != volume?(USB)[0-9]* ]]
then
	echo "Usage: mvpkg Target Package"
	echo "       Target must be like 'volumex' or 'volumeUSBx' where x is a numeric."
	echo "       Package [$PACKAGE] must be the name of a package."
	exit
fi

#Check the package and check the result: "enable" (is start), "disable" (is stop) or "does not exist"
#output=$(/usr/syno/sbin/synoservicecfg --status "pkgctl-$PACKAGE" | grep Service)
output=$(/usr/syno/bin/synopkg status "$PACKAGE" | head -n 1)
if [[ $output == *"No such package"* ]]
then
	echo "The package $PACKAGE can't be found."
	exit
else
	#find the current volume of the package and its link
	target=$( ls -la /var/packages/*/target | grep "/$PACKAGE/")
	
	link_target=$(echo $target | grep -oP "\/var/packages/.*/target")
	volume=$(echo $target | grep -oP "volume(USB)?\d*")
	path_target=$(echo $target | grep -oP "\/volume(USB)?.*")
	path=$(echo $link_target | grep -oP "\/var/packages/.*/")
	
	if [[ $link_target != "/var/packages/$PACKAGE/target"* ]]
	then
		echo "The service $PACKAGE is not correctly installed."
		exit
	fi
	
	if [[ $volume != "volume"* ]]
	then
		echo "The service $PACKAGE can't be located."
		exit
	fi

	if [[ $volume == $TARGET ]]
	then
		echo "The service $PACKAGE is already on $TARGET."
		exit
	fi
	
	if [[ "$path_target" != "/$volume/@appstore/$PACKAGE" ]]
	then
		echo "The service $PACKAGE does not have a standard location."
		exit
	fi
	
	#find home, tmp and var
	link_home="$path"home
	path_home=$( ls -la $path* | grep "/home -" | grep -oP "\/volume(USB)?.*")
	link_tmp="$path"tmp
	path_tmp=$( ls -la $path* | grep "/tmp -" | grep -oP "\/volume(USB)?.*")
	link_var="$path"var
	path_var=$( ls -la $path* | grep "/var -" | grep -oP "\/volume(USB)?.*")
	
	#List Packages with dependency on this one
	#/usr/syno/sbin/synoservicecfg --reverse-dependency pkgctl-$PACKAGE
			
	#Stop the all the dependencies
	#output=$(/usr/syno/sbin/synoservicecfg --hard-stop "pkgctl-$PACKAGE" | grep warn)
		
	#Stop the package 
	output=$(/usr/syno/bin/synopkg stop "$PACKAGE" | head -n 1)
	
	if [[ $output != *"success\":true"* ]]
	then
		echo "The service $PACKAGE couldn't be stopped."
		exit
	fi

	#Backup existing package at target path if any
	if [ -d "/$TARGET/@appstore/$PACKAGE" ]; then
		mv "/$TARGET/@appstore/$PACKAGE" "/$TARGET/@appstore/$PACKAGE-$(date -d "today" +"%Y%m%d%H%M").log"
	fi
	if [ -d "/$TARGET/@apphome/$PACKAGE" ]; then
		mv "/$TARGET/@apphome/$PACKAGE" "/$TARGET/@apphome/$PACKAGE-$(date -d "today" +"%Y%m%d%H%M").log"
	fi
	if [ -d "/$TARGET/@apptemp/$PACKAGE" ]; then
		mv "/$TARGET/@apptemp/$PACKAGE" "/$TARGET/@apptemp/$PACKAGE-$(date -d "today" +"%Y%m%d%H%M").log"
	fi
	if [ -d "/$TARGET/@appdata/$PACKAGE" ]; then
		mv "/$TARGET/@appdata/$PACKAGE" "/$TARGET/@appdata/$PACKAGE-$(date -d "today" +"%Y%m%d%H%M").log"
	fi
	
	#remove the link on the previous volume, #move the package, #link with the package on the new volume
	rm -f "$link_target"
	if [ -d "$path_target" ]; then
		mv "$path_target" /$TARGET/@appstore/
	fi
	ln -s "/$TARGET/@appstore/$PACKAGE" "$link_target"

	rm -f "$link_home"
	if [ -d "$path_home" ]; then
		mv "$path_home" /$TARGET/@apphome/
	fi
	ln -s "/$TARGET/@apphome/$PACKAGE" "$link_home"

	rm -f "$link_tmp"
	if [ -d "$path_tmp" ]; then
		mv "$path_tmp" /$TARGET/@apptemp/
	fi
	ln -s "/$TARGET/@apptemp/$PACKAGE" "$link_tmp"

	rm -f "$link_var"
	if [ -d "$path_var" ]; then
		mv "$path_var" /$TARGET/@appdata/
	fi
	ln -s "/$TARGET/@appdata/$PACKAGE" "$link_var"
	
	#Replace link also in local 
	local="/usr/local/$PACKAGE"
	if [ -L "$local" ]; then
		rm -f "$local"
		ln -s "/$TARGET/@appstore/$PACKAGE" "$local"
	fi

	#Replace link also in 3rdparty if pointing at @appstore
	trdparty="/usr/syno/synoman/webman/3rdparty/$PACKAGE"
	if [ -L "$trdparty" ]; then
		#check the link of the 3rdparty
		output=$( ls -la $trdparty | grep "/$volume/@appstore")

		if [[ ! -z "output" ]]
		then
			rm -f "$trdparty"
			ln -s "/$TARGET/@appstore/$PACKAGE" "$trdparty"
		fi
	fi
			
	#update settings
	sed -i "s/$volume/$TARGET/" "/usr/syno/etc/packages/$PACKAGE/*" &>/dev/null
	
	#Restart packages depending on the one moved
	#output=$(/usr/syno/sbin/synoservicecfg --reverse-dependency "pkgctl-$PACKAGE")
	#output="$(echo $output | grep -Po "pkgctl-([^\]]*)")"
	#for string in $output
	#do
	#	/usr/syno/sbin/synoservicecfg --start "$string"
	#done	

	#Restart the packages
	#output=$(/usr/syno/sbin/synoservicecfg --hard-start "pkgctl-$PACKAGE" | grep Service)
	output=$(/usr/syno/bin/synopkg start "$PACKAGE" | head -n 1)	
	if [[ $output != *"success\":true"* ]]
	then
		echo "The service $PACKAGE couldn't be restarted once moved from $volume to $TARGET."
	else	
		echo "The service $PACKAGE has been moved successfuly from $volume to $TARGET."
	fi
	
	#Due to the delay to start, this can't be tested safely
	#output=$(/usr/syno/bin/synopkg status "$PACKAGE" | head -n 1)
	#if [[ $output == *"is started"* ]]
	#then
	#	echo "The service $PACKAGE didn't restart properly once moved from $volume to $TARGET."
	#fi
fi
