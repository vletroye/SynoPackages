#!/bin/sh

CONFIG="/var/packages/$SYNOPKG_PKGNAME"
THEME=`cat $CONFIG/etc/theme`

WIZARD=$(cat << 'EOF'
[{
    "step_title": "Theme configuration",
    "invalid_next_disabled": "False",
    "items": [{
        "type": "combobox",
        "desc": "Name of the theme to be applied",
        "subitems": [{
            "key": "wizard_theme",
            "defaultValue": "#THEME",
            "desc": "Theme",
            "editable": false,
            "mode": "local",
            "valueField": "theme",
            "displayField": "theme",
            "store": {
              "data": [
                ["beatificabytes"],
                ["material"],
                ["classic"]
              ],
              "fields": ["theme"],
              "xtype": "arraystore"
            }
        }]
    }]
}]
EOF
)

WIZARD="${WIZARD/\#THEME/$THEME}"
#echo $WIZARD > /var/log/sspks.log

echo $WIZARD > $SYNOPKG_TEMP_LOGFILE
exit 0