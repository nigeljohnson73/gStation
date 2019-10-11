#!/bin/bash


usage() {
	echo "$0 <pin> on|off|toggle"
	exit
}

switch() {
	echo "Switch GPIO$p to $1"
	echo $1 > $val
}

if [ -z $2 ]
then
	usage
fi

export p=$1
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
		switch 1
		;;
	on)
		switch 1
		;;
	0)
		switch 0
		;;
	off)
		switch 0
		;;
	toggle)
		value=`cat $val`
		if [ $value -ne 0 ]
		then
			switch 0
		else
			switch 1
		fi
		;;
	*)
		usage
		;;
esac

