#!/bin/sh

end=$((SECONDS+5))
while [ $SECONDS -lt $end ]; do
    echo -n "."
    sleep 0.2
done
echo ""
echo ""

echo "Your Synology model is:"
echo "-----------------------"
echo ""
cat /etc.defaults/synoinfo.conf | grep -m 1 'upnpmodelname' | cut -d "=" -f 2
sleep 0.5
echo ""
echo ""
echo "The Processor of this model is:"
echo "--------------------------------"
echo ""
cat /proc/cpuinfo | grep -m 1 'model name' | cut -d ":" -f 2 | cut -d "@" -f 1
sleep 0.5
echo ""
echo ""
echo "More useless info :"
echo "-------------------"
echo ""
uname -a | cut -d " " -f 1-3,13
sleep 0.25
cat /etc.defaults/VERSION
sleep 0.25
echo ""
echo ""
echo "The DSM version is :"
echo "--------------------"
echo ""
cat /etc.defaults/VERSION
sleep 0.5
echo ""
echo ""
echo "The MAC address is :"
echo "--------------------"
echo ""
ifconfig | grep eth0 | cut -f3- -d:
sleep 0.5
echo ""
echo ""
echo "The IP Address is :"
echo "--------------------"
echo ""
ifconfig | grep "inet addr" | grep -v "127.0.0.1" | cut -d ":" -f2 | cut -d " " -f1
sleep 0.5
end=$((SECONDS+3))
while [ $SECONDS -lt $end ]; do
    echo ""
    sleep 0.5
done