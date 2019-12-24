#/bin/bash

until php /webroot/gStation/sh/sensor_reader.php $*; do
    echo "Sensor reader crashed with exit code $?.  Respawning.." >&2
    sleep 1
done
