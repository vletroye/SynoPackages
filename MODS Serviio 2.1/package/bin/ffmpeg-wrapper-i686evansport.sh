#!/bin/sh

#FFmpeg wrapper script to enable hardware decoding and encoding on Intel Evansport CPUs
#wrapper originally posted here: http://forum.serviio.org/viewtopic.php?f=7&t=6458

FOLDER="`dirname $0`"
PARAMS=""
COMMANDLINE=$@
FFMPEG_BIN="ffmpeg"
INPUT=0
for PARAM in "$@"; do
  if [ ${INPUT} = 1 ]; then
    #the FFmpeg input filename/URL needs quotes adding back on
    #because it may contain spaces, and the shell has removed them
    PARAMS="${PARAMS} \"${PARAM}\""    
    INPUT=0
  elif [ ${INPUT} = 2 ]; then
    #the FFmpeg subtitles filename needs quotes adding back on
    #because it may contain spaces, and the shell has removed them
    PARAM=${PARAM/\[*.*\]subtitles=filename=/subtitles=filename=\"}
    PARAM=${PARAM/:original_size/\":original_size}
    PARAMS="${PARAMS} ${PARAM}"    
    INPUT=0
  elif [ "${PARAM}" == "-filter_complex" ]; then
    #next parameter will be subtitles filtergraph including subtitle filename
    #which needs special treatment, so set a flag
    INPUT=2
    PARAMS="${PARAMS} ${PARAM}"
  elif [ "${PARAM}" == "-i" ]; then
    #next parameter will be input filename which needs special treatment, so set a flag    
    INPUT=1
    #enable hardware decoding by default, but not when Serviio is either:
    # gathering media metadata,
    # remuxing video,
    # generating thumbnails,
    # or rendering subtitles into the video stream (pipeline mode not supported for subs),
    if [ $# -lt 3 ] || \
       [ "${COMMANDLINE}" != "${COMMANDLINE/ -c:v copy /}" ] || \
       [ "${COMMANDLINE}" != "${COMMANDLINE/ pipe:/}" ] || \
       [ "${COMMANDLINE}" != "${COMMANDLINE/ -filter_complex \[*:*\]subtitles=/}" ]; then
      PARAMS="${PARAMS} -i"
    # or gathering online stream metadata 
    elif [ $# -eq 4 ] && [ "${COMMANDLINE}" != "${COMMANDLINE/-analyzeduration /}" ]; then
      PARAMS="${PARAMS} -i"
    else
      PARAMS="${PARAMS} -prefer_smd -i"
    fi
  else
    PARAMS="${PARAMS} ${PARAM}"
  fi
done

#older Evansport FFmpeg version 2.7.1 needs "-strict -2" to use experimental aac encoder
if [ "${PARAMS}" != "${PARAMS/ -c:a:* aac /}" ]; then
  PARAMS="${PARAMS/ aac / aac -strict -2 }"
fi

#Is this FFmpeg commandline a candidate for the hardware encoder?
HWENC=0
if [ "${PARAMS}" != "${PARAMS/ -c:v libx264 /}" ]; then
  #check whether H.264 encoder is already busy with another Serviio/VideoStation session
  PID_HWENC=`cat /tmp/VideoStation/enabled 2> /dev/null | sed -r "s/.*\"PID\":([0-9]+),\"hardware_transcode.*$/\1/;s/\[//;s/\]//"`
  #is there an indicated PID?
  if [ -n "${PID_HWENC}" ]; then
    #there is an indicated PID - is it running? 
    if ! kill -0 ${PID_HWENC}; then
      #the indicated PID is not in fact running, HW encoder is therefore available
      rm /tmp/VideoStation/enabled
      #make hardware H.264 encoder substitution to replace libx264
      HWENC=1
    fi 
  else
    #there is no indicated PID, HW encoder is therefore available
    HWENC=1
  fi  
fi

if [ ${HWENC} = 1 ]; then
  #make hardware H.264 encoder substitution to replace libx264
  PARAMS=${PARAMS/ libx264 / h264_smd }

  #by default Serviio's libx264 transcoding is intended for speed over quality, with SMD we can opt for better quality
  PARAMS="`echo ${PARAMS} | sed -r "s/ -crf [0-9]+ / /; s/ -g [0-9]+ / /"`"
  PARAMS=${PARAMS/ -preset:v veryfast / }
  PARAMS=${PARAMS/ -profile:v baseline / -profile:v high }
  PARAMS=${PARAMS/ -level 3 / -level 4.1 }
fi

#invoke FFmpeg
#log commandline, except media probes
#[ $# -gt 2 ] && echo "${FOLDER}/${FFMPEG_BIN} ${PARAMS}" >> ${FOLDER}/../log/ffmpeg-wrapper.log

#need to use eval here otherwise the quotes aren't handled properly
#http://fvue.nl/wiki/Bash:_Why_use_eval_with_variable_expansion%3F
eval ${FOLDER}/${FFMPEG_BIN} ${PARAMS}

#return FFmpeg status
exit $?
