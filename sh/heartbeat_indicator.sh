#/bin/bash

echo "heartbeat" | sudo tee /sys/class/leds/led0/trigger >/dev/null
