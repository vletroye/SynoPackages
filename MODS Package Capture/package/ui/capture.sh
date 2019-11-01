#!/bin/bash
VOLUME=$1

LOG="/var/log/MODS_Package_Capture"
echo `date` "----------------------------" >> $LOG
echo `date` "MODS Package Capture Running" >> $LOG

ERRLOG="$LOG"_ERR
rm -f "$ERRLOG"

if [ -z "$VOLUME" ]; then
  echo "Volume not specified" >> $LOG
  exit -1
fi

if [ ! -d "/$VOLUME/@tmp" ]; then
  echo "Temporary folder not found: /$VOLUME/@tmp" >> $LOG
  exit -1
fi

if [ -d "/$VOLUME/@tmp/SynoCapture" ]; then
  rm -R "/$VOLUME/@tmp/SynoCapture"
fi
mkdir "/$VOLUME/@tmp/SynoCapture"
echo . >> "/$VOLUME/@tmp/SynoCapture/Capturing"

if [ -d "/$VOLUME/@tmp/@synopkg" ]; then
  rm -R "/$VOLUME/@tmp/@synopkg"
fi

while [ ! -f "/$VOLUME/@tmp/SynoCapture/stop" ]; do
  if [ -d "/$VOLUME/@tmp/@synopkg/@download/" ]; then
    cp -nlR "/$VOLUME/@tmp/@synopkg/@download/." "/$VOLUME/@tmp/SynoCapture/"
  fi
done

if [ -f "/$VOLUME/@tmp/SynoCapture/found" ]; then
  echo `date` "MODS Package Capture found a spk" >> $LOG
  sleep 5
  find "/$VOLUME/@tmp/SynoCapture/" -type f -name '@SYNOPKG_DOWNLOAD_*' -exec sh -c 'x="{}"; mv "$x" "${x}.spk"' \;
else
  echo `date` "MODS Package Capture didn't find a spk" >> $LOG 
fi

echo `date` "MODS Package Capture Ending" >> $LOG

exit 0