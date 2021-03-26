#/bin/bash

# As this script is run quite late in the boot, switch the indicator to steady state here
/webroot/gStation/sh/ledbeat.sh steady

# 0x3d is the 1.5" display, 0x3c is the 0.96" one, so try and detect a device on 0x3d to use the new logic
avail="$( i2cget -y 1 0x3d 2>&1 )"

if [ "$avail" != "Error: Read failed" ]; then
	echo "Using new display"
	until python3 /webroot/gStation/sh/oled_1p5.py --height 128 --display ssd1327 --i2c-address 0x3d > /dev/null 2>&1; do
    		#echo "OLED driver crashed with exit code $?. Respawning..." >&2
    		sleep 1
	done
else
	echo "Using old display"
	until python3 /webroot/gStation/sh/oled_0p96.py > /dev/null 2>&1; do
    		#echo "OLED driver crashed with exit code $?. Respawning..." >&2
    		sleep 1
	done
fi

