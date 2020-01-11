#/bin/bash

# As this script is run quite late in the boot, switch the indicator to steady state here
/webroot/gStation/sh/ledbeat.sh steady

until python3 /webroot/gStation/sh/oled_run.py > /dev/null 2>&1; do
    #echo "OLED driver crashed with exit code $?.  Respawning.." >&2
    sleep 1
done
