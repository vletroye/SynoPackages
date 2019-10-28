#!/bin/bash
VOLUME=$1

if [ ! -d "/$VOLUME/@tmp" ]; then
  echo "Temporary folder not found: /$VOLUME/@tmp"
  exit -1
fi

if [ -d "/$VOLUME/@tmp/@synopkg" ]; then
  rm -R "/$VOLUME/@tmp/@synopkg"
fi

if [ -d "/$VOLUME/@tmp/SynoCapture" ]; then
  rm -R "/$VOLUME/@tmp/SynoCapture"
fi
mkdir "/$VOLUME/@tmp/SynoCapture"

if [ -f "/$VOLUME/@tmp/SynoCapture/found" ]; then
  rm "/$VOLUME/@tmp/SynoCapture/found"
fi
if [ -f "/$VOLUME/@tmp/SynoCapture/cancel" ]; then
  rm "/$VOLUME/@tmp/SynoCapture/cancel"
fi
if [ -f "/$VOLUME/@tmp/SynoCapture/stop" ]; then
  rm "/$VOLUME/@tmp/SynoCapture/stop"
fi

while [ ! -f "/$VOLUME/@tmp/SynoCapture/stop" ]; do
  if [ -d "/$VOLUME/@tmp/@synopkg/@download/" ]; then
    cp -nlR "/$VOLUME/@tmp/@synopkg/@download/." "/$VOLUME/@tmp/SynoCapture/"
  fi
done

if [ -f "/$VOLUME/@tmp/SynoCapture/found" ]; then
  sleep 5
  find "/$VOLUME/@tmp/SynoCapture/" -type f -name '@SYNOPKG_DOWNLOAD_*' -exec sh -c 'x="{}"; mv "$x" "${x}.spk"' \;
fi

exit 0