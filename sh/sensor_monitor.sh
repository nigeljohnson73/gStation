#/bin/bash

until php /webroot/gStation/sh/sensor_reader.php $* > /tmp/sensor_monitor_$*.log 2>&1; do
    echo "Sensor reader crashed with exit code $?.  Respawning.." >&2
    sleep 1
done
