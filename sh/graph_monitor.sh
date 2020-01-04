#/bin/bash

sleep 17 # Just a random amount of time to pass
until nice -n19 php /webroot/gStation/gfx/generate_static_graphs.php > /tmp/graph_generation.log 2>&1; do
    sleep 15
done
