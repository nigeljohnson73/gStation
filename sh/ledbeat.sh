#/bin/bash
#
### BEGIN INIT INFO
# Provides:          LEDbeat
# Required-Start:    $local_fs
# Required-Stop:     $local_fs
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Heartbeat LED
# Description:       Controls the heartbeat LED
### END INIT INFO
#

case "$1" in
	start)
		echo "LEDbeat: enabling startup heartbeat"
		echo "heartbeat" | sudo /usr/bin/tee /sys/class/leds/led0/trigger >/dev/null
	;;

	stop)
		echo "LEDbeat: enabling shutdown heartbeat"
		echo "heartbeat" | sudo /usr/bin/tee /sys/class/leds/led0/trigger >/dev/null
	;;

	steady)
		echo "LEDbeat: enabling steady state"
		echo "default-on" | sudo /usr/bin/tee /sys/class/leds/led0/trigger >/dev/null
	;;

	status)
		cat /sys/class/leds/led0/trigger
	;;

	*)
		echo "$0 [start|stop|steady|status]"
		exit 99
	;;
esac
