#!/bin/bash


usage() {
	echo "$0 <pin>"
	exit 1
}

if [ -z $1 ]
then
	usage
fi

while [ 1 ]; do 
	sh /webroot/gStation/sh/gpio.sh $1 0
	sleep 1
	sh /webroot/gStation/sh/gpio.sh $1 1
	sleep 1
done