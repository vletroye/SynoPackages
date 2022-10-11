#!/bin/bash

TARGET=$1
PACKAGE=$2

if [[ $TARGET == "list" ]]
then
    ls -1 /var/packages
    exit
fi

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
output=$(synopkg status "$PACKAGE")

if [[ $output == *"non_installed"* ]]
then
	echo "The service $PACKAGE can't be found."
	exit
else
	#find the current volume of the package and its various links
	
	link=$(ls -la /var/packages/*/target | grep "/$PACKAGE/" | grep -oP "\/var/packages/.*/target")
	etc=$(ls -la /var/packages/*/etc | grep "/$PACKAGE/" | grep -oP "\/var/packages/.*/etc")
	home=$(ls -la /var/packages/*/home | grep "/$PACKAGE/" | grep -oP "\/var/packages/.*/home")
	var=$(ls -la /var/packages/*/var | grep "/$PACKAGE/" | grep -oP "\/var/packages/.*/var")
	tmp=$(ls -la /var/packages/*/tmp | grep "/$PACKAGE/" | grep -oP "\/var/packages/.*/tmp")

	volume=$(ls -la /var/packages/*/target | grep "/$PACKAGE/" | grep -oP "volume(USB)?\d*")
	path_link=$(ls -la /var/packages/*/target | grep "/$PACKAGE/" | grep -oP "\/volume(USB)?.*")
	path_etc=$(ls -la /var/packages/*/etc | grep "/$PACKAGE/" | grep -oP "\/volume(USB)?.*")
	path_home=$(ls -la /var/packages/*/home | grep "/$PACKAGE/" | grep -oP "\/volume(USB)?.*")
	path_var=$(ls -la /var/packages/*/var | grep "/$PACKAGE/" | grep -oP "\/volume(USB)?.*")
	path_tmp=$(ls -la /var/packages/*/tmp | grep "/$PACKAGE/" | grep -oP "\/volume(USB)?.*")

	if [[ $link != "/var/packages/$PACKAGE/target"* ]]
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
	
	if [[ "$path_link" != "/$volume/@appstore/$PACKAGE" ]]
	then
		echo "The service $PACKAGE does not have a standard location."
		exit
	fi
	
	
	#List Packages with dependency on this one
	#synopkg --reverse-dependency $PACKAGE
			
	#Stop the package and all its dependencies
	output=$(synopkg stop "$PACKAGE")
	
	if [[ $output != *"stopped"* ]]
	then
		echo "The service $PACKAGE couldn't be stopped."
		exit
	fi	 
  
	if [ -d "/$TARGET/@appstore/$PACKAGE" ]; then
		mv "/$TARGET/@appstore/$PACKAGE" "/$TARGET/@appstore/$PACKAGE-$(date -d "today" +"%Y%m%d%H%M").log"
	fi

	#remove the links on the previous volume	
	if [ "$link" != "" ]; then rm -f "$link"; fi
	if [ "$etc" != "" ]; then rm -f "$etc"; fi
	if [ "$home" != "" ]; then rm -f "$home"; fi
	if [ "$tmp" != "" ]; then rm -f "$tmp"; fi
	if [ "$var" != "" ]; then rm -f "$var"; fi
	
	#move the package
	if [ "$link" != "" ]; then mv "$path_link" /$TARGET/@appstore; fi
	if [ "$etc" != "" ]; then mv "$path_etc" /$TARGET/@appconf; fi
	if [ "$home" != "" ]; then mv "$path_home" /$TARGET/@apphome; fi
	if [ "$var" != "" ]; then mv "$path_var" /$TARGET/@appdata; fi
	if [ "$tmp" != "" ]; then mv "$path_tmp" /$TARGET/@apptemp; fi
	
	#link with the  package on the new volume	
	if [ "$link" != "" ]; then ln -s "/$TARGET/@appstore/$PACKAGE" "$link"; fi
	if [ "$etc" != "" ]; then ln -s "/$TARGET/@appconf/$PACKAGE" "$etc"; fi
	if [ "$home" != "" ]; then ln -s "/$TARGET/@apphome/$PACKAGE" "$home"; fi
	if [ "$var" != "" ]; then ln -s "/$TARGET/@appdata/$PACKAGE" "$var"; fi
	if [ "$tmp" != "" ]; then ln -s "/$TARGET/@apptemp/$PACKAGE" "$tmp"; fi
	
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

		if [[ ! -z "$output" ]]
		then
			rm -f "$trdparty"
			ln -s "/$TARGET/@appstore/$PACKAGE" "$trdparty"
		fi
	fi

	#Replace link also in syno/etc if pointing at @appconf
	trdparty="/usr/syno/etc/packages/$PACKAGE"
	if [ -L "$trdparty" ]; then
		#check the link of the conf
		output=$( ls -la $trdparty | grep "/$volume/@appconf")

		if [[ ! -z "$output" ]]
		then
			rm -f "$trdparty"
			ln -s "/$TARGET/@appconf/$PACKAGE" "$trdparty"
		fi
	fi
	
	#update settings
	sed -i "s/$volume/$TARGET/" "/usr/syno/etc/packages/$PACKAGE/*" &>/dev/null
	
	#Restart packages depending on the one moved
	#output=$(/usr/syno/sbin/synoservicecfg --reverse-dependency "pkgctl-$PACKAGE")

	#output="$(echo $output | grep -Po "pkgctl-([^\]]*)")"
	#for string in $output
	#do
	#	synopkg start "$string"
	#done	

	#Restart the package and all its dependencies
	#output=$(/usr/syno/sbin/synoservicecfg --hard-start "pkgctl-$PACKAGE" | grep Service)
        output=$(synopkg start "$PACKAGE")
	
	#Check if the package has been correctly restarted
	#output=$(/usr/syno/sbin/synoservicecfg --is-enabled "pkgctl-$PACKAGE")

	if [[ $output != *"started"* ]]
	then
		echo "The service $PACKAGE didn't restart properly once moved from $volume to $TARGET."
	else	
		echo "The service $PACKAGE has been moved successfuly from $volume to $TARGET."
	fi
fi
