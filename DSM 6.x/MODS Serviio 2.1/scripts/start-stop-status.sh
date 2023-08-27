#!/bin/sh

#--------SERVIIO start-stop-status script
#--------package maintained at pcloadletter.co.uk

PKG_FOLDER="/var/packages/Serviio"
ENGINE_CFG="${PKG_FOLDER}/target/bin/serviio.sh"
ENGINE_SCRIPT="${PKG_FOLDER}/target/bin/serviio.sh"
PID_FILE="${PKG_FOLDER}/target/serviio.pid"
DNAME="Serviio"
DLOG="${PKG_FOLDER}/target/log/serviio.log"
COMMENT="# Synology Serviio Package"
TIMESTAMP="`date "+%F %X,000"`"
source /etc/profile
source /root/.profile


EnvCheck ()
#updates to DSM will reset these changes so check them each startup
{
  #/root/.profile should contain 2 lines added by this package tagged with trailing comments
  COUNT=`grep -c "$COMMENT$" /root/.profile`
  if [ $COUNT != 2 ]; then

    #remove any existing mods
    sed -i "/${COMMENT}/d" /root/.profile

    #add required environment variables
    echo "export FONTCONFIG_FILE=fonts.conf ${COMMENT}" >> /root/.profile
    echo "export FONTCONFIG_PATH=${PKG_FOLDER}/target/config/fonts ${COMMENT}" >> /root/.profile
  fi
}

start_daemon ()
{
  EnvCheck
  source /root/.profile

  #create/refresh fontconfig cache - prevents delay the first time that FFmpeg renders hard subs
  #FC_DEBUG=128 fc-cache --verbose
  ${PKG_FOLDER}/target/bin/fc-cache

  #refresh hostname in Serviio instance name - recently DSM has modified the hosts file and broken localhost name resolution
  sed -i -r "s%<FriendlyName>Serviio \((\{computerName\})\)%<FriendlyName>Serviio \(`hostname`\)%" $PKG_FOLDER/target/config/profiles.xml

  #set appropriate Java max heap size
  RAM=$((`free | grep Mem: | sed -e "s/^ *Mem: *\([0-9]*\).*$/\1/"`/1024))
  if [ $RAM -le 128 ]; then
    JAVA_MAX_HEAP=80
  elif [ $RAM -le 256 ]; then
    JAVA_MAX_HEAP=192
  elif [ $RAM -le 512 ]; then
    JAVA_MAX_HEAP=384
  #Serviio's default max heap is 512MB
  elif [ $RAM -gt 512 ]; then
    JAVA_MAX_HEAP=512
  fi
  sed -i -r "s/(^..JAVA.) -Xmx[0-9]+[mM] (.*$)/\1 -Xmx${JAVA_MAX_HEAP}m \2/" "${ENGINE_CFG}"
  echo "${TIMESTAMP} Starting ${DNAME}" >> ${DLOG}
  ${ENGINE_SCRIPT} > /dev/null 2>> ${DLOG}
  if [ -z ${SYNOPKG_PKGDEST} ]; then
    #script was manually invoked, need this to show status change in Package Center
    [ -e ${PKG_FOLDER}/enabled ] || touch ${PKG_FOLDER}/enabled
  fi
}

stop_daemon ()
{
  echo "${TIMESTAMP} Stopping ${DNAME}" >> ${DLOG}
  kill `cat ${PID_FILE}`
  wait_for_status 1 20 || kill -9 `cat ${PID_FILE}`
  rm -f ${PID_FILE}
  if [ -z ${SYNOPKG_PKGDEST} ]; then
    #script was manually invoked, need this to show status change in Package Center
    [ -e ${PKG_FOLDER}/enabled ] && rm ${PKG_FOLDER}/enabled
  fi
}

daemon_status ()
{
  if [ -f ${PID_FILE} ] && kill -0 `cat ${PID_FILE}` > /dev/null 2>&1; then
    return
  fi
  rm -f ${PID_FILE}
  return 1
}

wait_for_status ()
{
  counter=$2
  while [ ${counter} -gt 0 ]; do
    daemon_status
    [ $? -eq $1 ] && return
    let counter=counter-1
    sleep 1
  done
  return 1
}

case $1 in
  start)
    if daemon_status; then
      echo ${DNAME} is already running with PID `cat ${PID_FILE}`
      exit 0
    else
      echo Starting ${DNAME} ...
      start_daemon
      exit $?
    fi
  ;;

  stop)
    if daemon_status; then
      echo Stopping ${DNAME} ...
      stop_daemon
      exit $?
    else
      echo ${DNAME} is not running
      exit 0
    fi
  ;;

  restart)
    stop_daemon
    start_daemon
    exit $?
  ;;

  status)
    if daemon_status; then
      echo ${DNAME} is running with PID `cat ${PID_FILE}`
      exit 0
    else
      echo ${DNAME} is not running
      exit 1
    fi
  ;;

  log)
    echo "${DLOG}"
    exit 0
  ;;

  *)
    echo "Usage: $0 {start|stop|status|restart}" >&2
    exit 1
  ;;

esac
