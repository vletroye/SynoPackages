#!/bin/sh

#--------SERVIIO installer script
#--------package maintained at pcloadletter.co.uk


DOWNLOAD_PATH="http://download.serviio.org/releases"
DOWNLOAD_FILE="serviio-2.1-linux.tar.gz"
EXTRACTED_FOLDER="serviio-2.1"
DOWNLOAD_URL="${DOWNLOAD_PATH}/${DOWNLOAD_FILE}"
SYNO_CPU_ARCH="`uname -m`"
[ "${SYNOPKG_DSM_ARCH}" == "comcerto2k" ] && SYNO_CPU_ARCH="armneon"
[ "${SYNOPKG_DSM_ARCH}" == "armada375" ] && SYNO_CPU_ARCH="armneon"
[ "${SYNOPKG_DSM_ARCH}" == "armada38x" ] && SYNO_CPU_ARCH="armneon"
[ "${SYNOPKG_DSM_ARCH}" == "alpine" ] && SYNO_CPU_ARCH="armneon"
[ "${SYNOPKG_DSM_ARCH}" == "alpine4k" ] && SYNO_CPU_ARCH="armneon"
[ "${SYNOPKG_DSM_ARCH}" == "monaco" ] && SYNO_CPU_ARCH="armneon"
[ "${WIZARD_ENC_HW}" == "true" ] && SYNO_CPU_ARCH="i686evansport"
NATIVE_BINS_URL="https://syno.pcloadletter.co.uk/bin/serviio-native-${SYNO_CPU_ARCH}.tar.xz"   
NATIVE_BINS_FILE="`echo ${NATIVE_BINS_URL} | sed -r "s%^.*/(.*)%\1%"`"
FONTS_URL="http://sourceforge.net/projects/dejavu/files/dejavu/2.37/dejavu-fonts-ttf-2.37.tar.bz2"
FONTS_FILE="`echo ${FONTS_URL} | sed -r "s%^.*/(.*)%\1%"`"
#'ua' prefix means wget user-agent will be customized
INSTALL_FILES="ua${DOWNLOAD_URL} ${NATIVE_BINS_URL} ${FONTS_URL}"
PID_FILE="${SYNOPKG_PKGDEST}/serviio.pid"
COMMENT="# Synology Serviio Package"
TEMP_FOLDER="`find / -maxdepth 2 -path '/volume?/@tmp' | head -n 1`"
PUBLIC_FOLDER="`synoshare --get public | sed -r "/Path/!d;s/^.*\[(.*)\].*$/\1/"`"
PLUGINS_PATH="${PUBLIC_FOLDER}/serviio"
source /etc/profile


pre_checks ()
{
  #These checks are called from preinst and from preupgrade functions to prevent failures resulting in a partially upgraded package
  if [ -z ${JAVA_HOME} ]; then
    echo "Java is not installed or not properly configured. JAVA_HOME is not defined. " >> $SYNOPKG_TEMP_LOGFILE
    echo "Download and install the Java8 Synology package." >> $SYNOPKG_TEMP_LOGFILE
    exit 1
  fi

  if [ ! -f ${JAVA_HOME}/bin/java ]; then
    echo "Java is not installed or not properly configured. The Java binary could not be located. " >> $SYNOPKG_TEMP_LOGFILE
    echo "Download and install the Java8 Synology package." >> $SYNOPKG_TEMP_LOGFILE
    exit 1
  fi

  JAVA_VER=`java -version 2>&1 | sed -r "/^.* version/!d;s/^.* version \"[0-9]\.([0-9]).*$/\1/"`
  if [ ${JAVA_VER} -lt 8 ]; then
    echo "This version of Serviio requires Java 8 or newer. Please update your Java package. " >> $SYNOPKG_TEMP_LOGFILE
    exit 1
  fi

  if [ -z ${PUBLIC_FOLDER} ]; then
    echo "A shared folder called 'public' could not be found - note this name is case-sensitive. " >> $SYNOPKG_TEMP_LOGFILE
    echo "Please create this using the Shared Folder DSM Control Panel and try again." >> $SYNOPKG_TEMP_LOGFILE
    exit 1
  fi
}


preinst ()
{
  pre_checks
  cd ${TEMP_FOLDER}
  for WGET_URL in ${INSTALL_FILES}
  do
    WGET_FILENAME="`echo ${WGET_URL} | sed -r "s%^.*/(.*)%\1%"`"
    [ -f ${TEMP_FOLDER}/${WGET_FILENAME} ] && rm ${TEMP_FOLDER}/${WGET_FILENAME}
    #this will allow serviio.org to track the number of downloads from Synology users
    WGET_URL=`echo ${WGET_URL} | sed -e "s/^ua/--user-agent=Synology --referer=http:\/\/pcloadletter.co.uk\/2012\/01\/25\/serviio-syno-package /"`
    wget ${WGET_URL}
    if [ $? != 0 ]; then
      if [ -d ${PUBLIC_FOLDER} ] && [ -f ${PUBLIC_FOLDER}/${WGET_FILENAME} ]; then
        cp ${PUBLIC_FOLDER}/${WGET_FILENAME} ${TEMP_FOLDER}
      else
        echo "There was a problem downloading ${WGET_FILENAME} from the official download link, " >> $SYNOPKG_TEMP_LOGFILE
        echo "which was \"${WGET_URL}\" " >> $SYNOPKG_TEMP_LOGFILE
        echo "Alternatively, you may download this file manually and place it in the 'public' shared folder. " >> $SYNOPKG_TEMP_LOGFILE
        exit 1
      fi
    fi
  done

  exit 0
}


postinst ()
{
  #extract the downloaded Serviio archive
  cd ${TEMP_FOLDER}
  tar xzf ${TEMP_FOLDER}/${DOWNLOAD_FILE}
  rm ${TEMP_FOLDER}/${DOWNLOAD_FILE}
  cp -R ${TEMP_FOLDER}/${EXTRACTED_FOLDER}/* ${SYNOPKG_PKGDEST}
  if [ ! -z "${EXTRACTED_FOLDER}" ]; then
    rm -r ${TEMP_FOLDER}/${EXTRACTED_FOLDER}
  fi
  if [ ! -d "${PLUGINS_PATH}/plugins" ]; then
    mkdir -p ${PLUGINS_PATH}/plugins
  fi
  if [ ! -d "${PLUGINS_PATH}/fonts" ]; then
    mkdir -p ${PLUGINS_PATH}/fonts
  fi

  #extract CPU-specific additional binaries
  cd ${SYNOPKG_PKGDEST}/lib
  tar xJf ${TEMP_FOLDER}/${NATIVE_BINS_FILE} && rm ${TEMP_FOLDER}/${NATIVE_BINS_FILE}
  mv ${SYNOPKG_PKGDEST}/lib/ffmpeg ${SYNOPKG_PKGDEST}/bin
  mv ${SYNOPKG_PKGDEST}/lib/fc-cache ${SYNOPKG_PKGDEST}/bin
  [ -e ${SYNOPKG_PKGDEST}/lib/omxregister-bellagio ] mv ${SYNOPKG_PKGDEST}/lib/omxregister-bellagio ${SYNOPKG_PKGDEST}/bin

  #remove legacy package font versions
  [ -d ${PLUGINS_PATH}/fonts/dejavu-fonts-ttf-2.33/ ] && rm -rf ${PLUGINS_PATH}/fonts/dejavu-fonts-ttf-2.33/
  [ -d ${PLUGINS_PATH}/fonts/dejavu-fonts-ttf-2.34/ ] && rm -rf ${PLUGINS_PATH}/fonts/dejavu-fonts-ttf-2.34/
  [ -d ${PLUGINS_PATH}/fonts/dejavu-fonts-ttf-2.35/ ] && rm -rf ${PLUGINS_PATH}/fonts/dejavu-fonts-ttf-2.35/

  #extract open source font package for subtitle support during transcoding
  cd ${PLUGINS_PATH}/fonts
  tar xvjf ${TEMP_FOLDER}/${FONTS_FILE} && rm ${TEMP_FOLDER}/${FONTS_FILE}
  sed -i "s|WINDOWSFONTDIR|${PLUGINS_PATH}/fonts|" ${SYNOPKG_PKGDEST}/config/fonts/fonts.conf
  sed -i "s|WINDOWSTEMPDIR_FONTCONFIG_CACHE|~/.fontconfig.cache|" ${SYNOPKG_PKGDEST}/config/fonts/fonts.conf

  #wrapper script can be useful for testing different encoder options
  if [ -e "${SYNOPKG_PKGDEST}/bin/ffmpeg-wrapper-${SYNO_CPU_ARCH}.sh" ]; then
    #we need to use the wrapper to make FFmpeg use libshine on ARM systems and to use hardware decode/encode on Intel Evansport systems
    FFMPEG_PATH="\$SERVIIO_HOME/bin/ffmpeg-wrapper-${SYNO_CPU_ARCH}.sh"
  else
    FFMPEG_PATH="\$SERVIIO_HOME/bin/ffmpeg"
  fi

  #modifications to device profiles (evansport hardware transcoding)
  if [ -e "${SYNOPKG_PKGDEST}/config/profiles-${SYNO_CPU_ARCH}.xml" ]; then
    mv "${SYNOPKG_PKGDEST}/config/profiles.xml" "${SYNOPKG_PKGDEST}/config/profiles-orig.xml"
    mv "${SYNOPKG_PKGDEST}/config/profiles-${SYNO_CPU_ARCH}.xml" "${SYNOPKG_PKGDEST}/config/profiles.xml"
  fi

  #modifications to application profiles (evansport hardware transcoding)
  #removed owing to frame drops in FlowPlayer because encoder does not produce 100% valid streams, evansport has sufficient power for flv encoding
  #if [ -e "${SYNOPKG_PKGDEST}/config/application-profiles-${SYNO_CPU_ARCH}.xml" ]; then
  #  mv "${SYNOPKG_PKGDEST}/config/application-profiles-${SYNO_CPU_ARCH}.xml" "${SYNOPKG_PKGDEST}/config/application-profiles.xml"
  #fi

  #make changes to Serviio launcher script so that pid file is created for Java process
  sed -r -i "s%Execute the JVM in the foreground%Execute the JVM in the background%" ${SYNOPKG_PKGDEST}/bin/serviio.sh
  sed -r -i "s%^(exec \"$JAVA.*)$%\1 \&%" ${SYNOPKG_PKGDEST}/bin/serviio.sh
  echo "echo \$! > ${PID_FILE}" >> ${SYNOPKG_PKGDEST}/bin/serviio.sh

  #set some additional Serviio system properties (temp folder, FFmpeg path, plugins folder)
  #http://www.serviio.org/index.php?option=com_content&view=article&id=43
  EXTRA_OPTS="-Dserviio\.defaultTranscodeFolder=${TEMP_FOLDER} -Dffmpeg\.location=${FFMPEG_PATH} -Dplugins\.location=${PLUGINS_PATH}"
  #fix Java prefs checking which was preventing NAS hibernation http://forum.serviio.org/viewtopic.php?f=5&t=6878
  EXTRA_OPTS="${EXTRA_OPTS} -Djava.util.prefs.syncInterval=86400"
  if [ "${SYNO_CPU_ARCH}" == "armv5tel" ]; then
    #use integer math (not floating point) Dolby AC-3 encoder for better performance on ARM CPUs
    #http://ffmpeg.org/ffmpeg.html#ac3-and-ac3_005ffixed
    EXTRA_OPTS="${EXTRA_OPTS} -Dserviio\.fixedPointEncoders"
  fi
  sed -r -i "s% -Dffmpeg\.location=ffmpeg%%; s%^(JAVA_OPTS=.*)\"$%\1 ${EXTRA_OPTS}\"%" ${SYNOPKG_PKGDEST}/bin/serviio.sh

  #create log file to allow package start errors to be captured
  [ -e ${SYNOPKG_PKGDEST}/log ] || mkdir ${SYNOPKG_PKGDEST}/log
  [ -e ${SYNOPKG_PKGDEST}/log/serviio.log ] || touch ${SYNOPKG_PKGDEST}/log/serviio.log

  #add firewall config
  /usr/syno/bin/servicetool --install-configure-file --package /var/packages/${SYNOPKG_PKGNAME}/conf/${SYNOPKG_PKGNAME}.sc > /dev/null

  exit 0
}


preuninst ()
{
  `dirname $0`/stop-start-status stop

  exit 0
}


postuninst ()
{
  #remove fontconfig configuration
  sed -i "/${COMMENT}/d" /root/.profile

  #remove firewall config
  if [ "${SYNOPKG_PKG_STATUS}" == "UNINSTALL" ]; then
    /usr/syno/bin/servicetool --remove-configure-file --package ${SYNOPKG_PKGNAME}.sc > /dev/null
  fi

  #remove legacy daemon user and homedir
  [ -e /var/services/homes/serviio ] && synouser --del serviio
  [ -e /var/services/homes/serviio ] && rm -r /var/services/homes/serviio

  exit 0
}


preupgrade ()
{
  `dirname $0`/stop-start-status stop
  pre_checks
  #if a media database exists we need to preserve it
  if [ -d ${SYNOPKG_PKGDEST}/library/db ]; then
    mkdir ${SYNOPKG_PKGDEST}/../${SYNOPKG_PKGNAME}_db_migration
    mv ${SYNOPKG_PKGDEST}/library/db ${SYNOPKG_PKGDEST}/../${SYNOPKG_PKGNAME}_db_migration
  fi

  exit 0
}


postupgrade ()
{
  #use the backed up media database from the previous version
  if [ -d ${SYNOPKG_PKGDEST}/../${SYNOPKG_PKGNAME}_db_migration/db ]; then
    mv ${SYNOPKG_PKGDEST}/../${SYNOPKG_PKGNAME}_db_migration/db ${SYNOPKG_PKGDEST}/library
    rmdir ${SYNOPKG_PKGDEST}/../${SYNOPKG_PKGNAME}_db_migration
  fi
  chown -R root:root ${SYNOPKG_PKGDEST}

  exit 0
}
