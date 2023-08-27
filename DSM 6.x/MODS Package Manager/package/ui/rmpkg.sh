#!/bin/bash

PACKAGE=$1

if [[ $PACKAGE == "" ]]
then
    echo "Usage: rmpkg  Package"
    echo "       Package must be the name of a package."
    exit
fi

#Check the package and check the result: "enable" (is start), "disable" (is stop) or "does not exist"
output=$(/usr/syno/sbin/synoservicecfg --status "pkgctl-$PACKAGE" | grep Service)

if [[ $output == *"does not exist"* ]]
then
    echo "The service $PACKAGE can't be found."
    exit
else
    #Stop the package and all its dependencies
    output=$(/usr/syno/sbin/synoservicecfg --hard-stop "pkgctl-$PACKAGE" | grep warn)
    
    if [[ $output != *"have been set"* ]]
    then
        echo "The service $PACKAGE couldn't be stopped. A dirty delete is going to be executed."
        #exit
    fi
    
    #find the current volume of the package and its link
    output=$( ls -la /var/packages/*/target | grep "/$PACKAGE/")
    
    volume=$(echo $output | grep -oP "volume(USB)?\d*")
    path=$(echo $output | grep -oP "\/volume(USB)?.*")
    
    local="/usr/syno/synoman/webman/3rdparty/$PACKAGE"
    if [ -L "$local" ]; then
        rm -f "$local"
    fi
    
    if [[ "$path" == "/$volume/@appstore/$PACKAGE" ]]
    then
        if [ -d "$path" ]; then
            rm -R "$path"
        fi
    fi

    path="/var/packages/$PACKAGE"
    if [ -d "$path" ]; then
        rm -R "$path"
    fi

    path="/usr/syno/etc/packages/$PACKAGE"
    if [ -d "$path" ]; then
        rm -R "$path"
    fi
    
    local="/usr/local/$PACKAGE"
    if [ -L "$local" ]; then
        rm -f "$local"
    fi
fi
