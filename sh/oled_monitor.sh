#/bin/bash

until python3 /webroot/gStation/sh/oled_run.py; do
    echo "OLED driver crashed with exit code $?.  Respawning.." >&2
    sleep 1
done
