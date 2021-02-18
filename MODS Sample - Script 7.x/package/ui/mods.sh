#!/bin/sh

echo "Your Synology model is:"
echo "-----------------------"
echo ""
cat /etc.defaults/synoinfo.conf | grep -m 1 'upnpmodelname' | cut -d "=" -f 2
echo ""
echo ""
echo "The Processor of this model is:"
echo "--------------------------------"
echo ""
cat /proc/cpuinfo | grep -m 1 'model name' | cut -d ":" -f 2 | cut -d "@" -f 1
echo ""
echo ""
echo "More useless info :"
echo "-------------------"
echo ""
uname -a | cut -d " " -f 1-3,13
cat /etc.defaults/VERSION
echo ""
echo ""
echo "The DSM version is :"
echo "--------------------"
echo ""
cat /etc.defaults/VERSION
echo ""
echo ""
echo "The MAC address is :"
echo "--------------------"
echo ""
ifconfig | grep eth0 | cut -f3- -d:
echo ""
echo ""
echo "The IP Address is :"
echo "--------------------"
echo ""
ifconfig | grep "inet addr" | grep -v "127.0.0.1" | cut -d ":" -f2 | cut -d " " -f1