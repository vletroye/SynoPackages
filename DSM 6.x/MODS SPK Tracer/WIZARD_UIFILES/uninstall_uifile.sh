#!/bin/sh
exec 4<&1
exec 5<&2

exec 2>&1 
exec 1>>/var/log/MODS_SPKTracer.log

NAME=`basename $0`
DIRECTORY=`dirname $0`

echo "<b>'$NAME'</b> called in $DIRECTORY at $(date)" 
echo ""

echo ""
declare -xp | grep " SYNOPKG" | sed 's/declare -x //'
echo ""
declare -xp | grep " SYNO" | grep -v " SYNOPKG" | sed 's/declare -x //'
echo ""

: '
declare -xp | grep -v " SYNO" | sed 's/declare -x //'
echo ""
'

WIZARD=$(cat << 'EOF'
[{
    "step_title": "'#NAME' Wizard Tracer",
    "items": [{
        "type": "multiselect",
        "subitems": [{
            "key": "pkgwizard_delete",
            "desc": "Delete Log file",
            "defaultValue":true
        }]
	}]
}]
EOF
)

WIZARD="${WIZARD/\#NAME/$NAME}"


echo $WIZARD
echo ""

echo $WIZARD > $SYNOPKG_TEMP_LOGFILE

echo "-------------------------------------------------------------"
exec 1<&4
exec 2<&5

exit 0