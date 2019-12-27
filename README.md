## Contents

 * [Overview](#Overview)
 * [Limitations](#Limitations)
 * [Setting up the Pi](#Setting-up-the-Pi)
 * [Hardware pinouts used](#Pinouts)
 * [The Journey so far (Wiki)](https://github.com/nigeljohnson73/gStation/wiki/History)
 * [Roadmap (GitHub)](https://github.com/nigeljohnson73/gStation/projects/1)
 * [Other stuff (Wiki)](https://github.com/nigeljohnson73/gStation/wiki/Useful-resources)

## Overview

Being designed to read 5 sensors (4 one-wire and a serial CO2 monitor) and control 6 triggers meaning you can have up to 4 reptile vivariums or 
Deep Water Culture (DWC) buckets (Assuming they have the same envirnomental requirements), or maybe a much more complex single growing environment, 
requiring heating/cooling/dehumidfying/humidifying/venting in 3 zones and lighting across the whole environment... or a combination of any of that.

Currently, at it's core this is simply a controller for a heat pad and a light. Out of the box it will use a northern hemisphere 
concept of when summer is (solstice in June) and a 28.8°C high. You can also sign up for a [DarkSky](https://darksky.net/dev) account
which will let you configure your environment to a place on the earth. The project currently controls a single vivarium/DWC/germination station.

There is also a web interface which provides some feedback about how the temperature/humidity has been over the last 24 hours and 
graphs on what is being planned for the environment.

![Web Interface](https://drive.google.com/uc?id=1BGmyfNSUEZMX6sQKC-R5baNO2RQblfPA)

## Limitations

This app and everything in it are so pre-release that there isn't a version number yet, but it is affectionately referred to as 
Version 1 or 'the beast'. Version 2 is coming as a much smaller, modular version... I'll need a new name for it.

This application is not designed to be exposed to the internet and be secure.

You're stuck with temperatures in Centigrade for now and quite some time.

This app uses a solid state relay to control the lights and heat pad. The one I have found reasonbably cheaply
is only rated at 2 amps per channel. Version 2 of the beast will handle 5A per channel.

There is only 1 sensor and 2 output triggers. Version 2 will allow up to 4 one-wire sensors and a serial CO2 sensor
as well as up to 6 control triggers. The sensors supported are the DS18B20 for temerature, the DHT11, and DHT22 for temperature and humidity, 
and I'm adding the MH-Z19B for Carbon Dioxide.

There is only 1 'environment' modelled. This means the demands for heat, humidity and light are across all of the 'zones' you set up, so you cannot 
have a temperature plan for a root zone as a separate plan for the air zone. I do not expect this to change in the foreseable future.
You can have separate triggers run heaters in different zones based on the sensors you assign to that zone but the demanded temps 
will be the same, or at least will be based on this. I will add the ability to have a delta in the command structure so you can set things to 
be -2 degrees of the demanded value for example.

The web interface is very limited and needs to be refreshed manually. This will change to automatically updating and 
allowing for some parameter control in the future.

The control (sensor definition, trigger logic and environmental modelling) is handled  through a config file on the pi. This will move 
to the web interface in time. If you can install everything then this will not be a problem for you. The config file is located at 
`/webroot/gStation/config_override.php`.

Please see the [Roadmap](https://github.com/nigeljohnson73/gStation/projects/1) section for details on some of things that will come and fix some of these points.

## Setting up the Pi

There are a couple of assumptions if you want to use this stuff. First is that you have a bit of knowledge about what
youre doing with regard to Installing an operating system on a raspberry pi (Zero in this case). It is also assumed 
that you have command line access to ssh (mac/linux for example) or know how to configure something like PuTTY on 
windows. How you write images to SD cards is also highly dependant on your operating system, but you can find out 
more information in the [Raspian documentation](https://www.raspberrypi.org/documentation/installation/installing-images/README.md) and google is your friend.

 * Burn the latest [Raspian **Lite**](https://www.raspberrypi.org/downloads/raspbian/) image to an Micro/SD card (8GB is more than enough).
 * Drop res/wpa_supplicant.conf from here into the boot disk
 * Update that file with the details from your router
 * Drop res/ssh file from here into the boot disk (or just create an empty file in the file system)

Boot the PI and log in (password will initally be raspberry).

    rm -rf ~/.ssh/known_hosts
    ssh pi@raspberrypi.local

Change the password and run the configurator.

    passwd
    sudo raspi-config

Make the folllowing changes.

 * Network Options -> Hostname
 * Interfacing options -> I2C -> yes
 * Interfacing options -> Serial -> no to console, yes to hardware
 * Exit (and do the reboot)

Next, log back in to the new hostname with the new password, then, update Raspian and install all the software packages we need.

    sudo apt update -y
    sudo apt upgrade -y
    sudo apt install -y apache2 php php-mbstring php-gd mariadb-server php-mysql git python3-dev python3-pip python3-pil i2c-tools python3-gpiozero wiringpi
    sudo pip3 install --upgrade pip setuptools wheel Adafruit_DHT datetime adafruit-circuitpython-ssd1306
    sudo phpenmod mysqli

Make the directories we require for our software

    sudo mkdir /logs
    sudo chown -R pi:www-data /logs
    sudo chmod -R g+w /logs
    sudo mkdir /webroot

Clone the software into its home.

    cd /webroot
    sudo git clone https://github.com/nigeljohnson73/gStation.git
    sudo chown -R pi:www-data /webroot
    sudo chmod -R g+w /webroot
    cd gStation
    cat config.php | grep -v "^$" | grep -v "^//" > config_override.php

You want to set up the GPIO pins for your sensors, so configure them in `config_override.php` and then 
run the setup. The parameter supplied is the pin layout in the [Pinout section](#Pinouts) below. When you 
run this command it should print out the pins that can be used. More detail below.

    php sh/setup_gpio.php 2.1g

Move the new DHT11 overlay into the correct place.

    sudo rm -f /boot/overlays/dht11.dtbo
    sudo cp res/dht11.dtbo /boot/overlays

Move the webroot stuff around.

    cd /var/www/
    sudo mv html html_orig
    sudo ln -s /webroot/gStation html
    sudo /etc/init.d/apache2 restart

Set up MySQL with the correct root account and a user for the application.

    sudo mysql --user=root < /webroot/gStation/res/install.sql

Update the user login script so it has a pretty banner and stuff.

    echo "source /webroot/gStation/res/bashrc" | tee -a ~/.bashrc

Change the I2C refresh rate.

    echo "dtparam=i2c_baudrate=1000000" | sudo tee -a /boot/config.txt

Configure the system to set up the GPIOs on boot in the rc.local file.

    cat /etc/rc.local | grep -v 'exit 0' | sudo tee /etc/rc.local
    echo ". /webroot/gStation/res/rc.local" | sudo tee -a /etc/rc.local
    echo "exit 0" | sudo tee -a /etc/rc.local

Update the crontab to have our update commands.

    crontab -e

Add these lines:

    1 0 * * * /usr/bin/php /webroot/gStation/sh/gstation_update.php > /tmp/gstation_update.txt 2>/dev/null
    * * * * * /usr/bin/php /webroot/gStation/sh/gstation_tick.php > /tmp/gstation_tick.txt 2>/dev/null
    * * * * * /usr/bin/php /webroot/gStation/gfx/generate_static_graphs.php > /dev/null 2>&1

Once you reboot, things should start kicking off in a few minutes.

## Pinouts

More detailed information is available including voltage tolerances and resistor values can be found on the 
[Hardware Pinout](https://github.com/nigeljohnson73/gStation/wiki/Hardware-Pin-outs) page in the wiki, but 
here is the lasted version.

![Pinout defintion](https://drive.google.com/uc?id=12JmH5ScMZp-obORhgVlYbhvnULQuYN1d)

```
setupGpio(): runtime_version = 2.1g
setupGpio(): sensor_pin_1 = 4
setupGpio(): sensor_pin_2 = 17
setupGpio(): sensor_pin_3 = 7
setupGpio(): sensor_pin_4 = 22
setupGpio(): trigger_pin_1 = 18
setupGpio(): trigger_pin_2 = 23
setupGpio(): trigger_pin_3 = 24
setupGpio(): trigger_pin_4 = 25
setupGpio(): trigger_pin_5 = 8
setupGpio(): trigger_pin_6 = 11
setupGpio(): button_pin = 10
setupGpio(): led_pin = 9
```
