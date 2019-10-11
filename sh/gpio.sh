#!/bin/bash


usage() {
	echo "$0 <pin> on|off|toggle"
	exit
}

on() {
	echo "Switch GPIO$1 to 1"
	echo 1 > $val
}

off() {
	echo "Switch GPIO$1 to 0"
	echo 0 > $val
}

if [ -z $2 ]
then
	usage
fi

export dir="/sys/class/gpio/gpio`echo $1`/direction"
export val="/sys/class/gpio/gpio`echo $1`/value"

if [ ! -e $val ]
then
echo "Setup port"
	echo $1 > /sys/class/gpio/export
	echo "out" > $dir
fi

#let "sleep = $RANDOM + 10000"
#sleep "0.$sleep"

case $2 in
	1)
		on
		;;
	on)
		on
		;;
	0)
		off
		;;
	off)
		off
		;;
	toggle)
		value=`cat $val`
		if [ $value -ne 0 ]
		then
			off
		else
			on
		fi
		;;
	*)
		usage
		;;
esac

