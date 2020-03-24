#/bin/bash

until nice -n -19 php /webroot/gCam/sh/server_check.php >/dev/null 2>&1; do
    #echo "Server monitor exited with code $?.  Respawning.." >&2
    sleep 1
done
