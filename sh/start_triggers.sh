#!/bin/sh

# Set up the LED and SSR GPIO pins as outputs
gpio -g mode 15 out
gpio -g mode 17 out
gpio -g mode 18 out

# Enable the pull down resistors
gpio -g mode 15 down
# The logic is inverted for the current SSRs
gpio -g mode 17 up
gpio -g mode 18 up

# switch the LED on
gpio -g write 15 1

# Switch the SSRs off - this will need to change if we get different SSRs
gpio -g write 17 1
gpio -g write 18 1

