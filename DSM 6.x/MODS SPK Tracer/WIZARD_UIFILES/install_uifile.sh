#!/bin/sh
exec 4<&1
exec 5<&2

exec 2>&1 
exec 1>>/var/log/MODS_SPKTracer.log

echo "<center><pre>*****************************"
echo "***                      ***"
echo "***   NEW INSTALLATION   ***"
echo "***                      ***"
echo "****************************</pre></center>"

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
    "step_title": "'#NAME' Wizard Tracer for #MODEL",
    "items": [{
        "type": "multiselect",
        "subitems": [{
            "key": "pkgwizard_hello",
            "desc": "Say Hello!",
            "defaultValue":false
        }]
	}]
}]
EOF
)

WIZARD="${WIZARD/\#NAME/$NAME}"
WIZARD="${WIZARD//\\/\\\\}"
WIZARD="${WIZARD//\"/\\\"}"

SCRIPT=$(cat << 'EOF'
<?php
$ini_array = parse_ini_file("/etc.defaults/synoinfo.conf");
$name=$ini_array["upnpmodelname"];
echo str_replace("#MODEL", "$name", "#WIZARD");
?>
EOF
)

SCRIPT="${SCRIPT/\#WIZARD/$WIZARD}"

echo $SCRIPT
echo ""

echo $SCRIPT > /tmp/wizard.php
php -n /tmp/wizard.php > $SYNOPKG_TEMP_LOGFILE
rm /tmp/wizard.php


echo "<hr>"

exec 1<&4
exec 2<&5

exit 0