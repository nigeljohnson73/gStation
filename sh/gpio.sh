#!/bin/bash


usage() {
	echo "$0 <pin> 1|0"
	exit 1
}

if [ -z $2 ]
then
	usage
fi

# Check if gpio is already exported
if [ ! -d /sys/class/gpio/gpio$1 ]
then
	echo $1 > /sys/class/gpio/export
	sleep 1 ;# Short delay while GPIO permissions are set up
fi

# Set to output
echo out > /sys/class/gpio/gpio$1/direction

# Set to high value
echo $2 > /sys/class/gpio/gpio$1/value
