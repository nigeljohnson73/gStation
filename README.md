## Contents

 * [Overview](#Overview)
 * [Limitations](#Limitations)
 * [Setting up the Pi](#Setting-up-the-Pi)
 * [Adding the timelase software](#Adding-the-camera-timelapse)
 * [Hardware pinouts used](#Pinouts)
 * [The Journey so far (Wiki)](https://github.com/nigeljohnson73/gStation/wiki/The-Journey)
 * [Roadmap (GitHub)](https://github.com/nigeljohnson73/gStation/projects/1)
 * [Other stuff (Wiki)](https://github.com/nigeljohnson73/gStation/wiki/Useful-resources)

## Overview

Moving stuff about this is all out of date at the moment.

Being designed to read 5 sensors (4 one-wire and a serial CO2 monitor) and control 6 triggers meaning you can have up to 4 reptile vivariums or 
Deep Water Culture (DWC) buckets (Assuming they have the same envirnomental requirements), or maybe a much more complex single growing environment, 
requiring heating/cooling/dehumidfying/humidifying/venting in 3 zones and lighting across the whole environment... or a combination of any of that.

Currently, at it's core this is simply a controller for a heat pad and a light. Out of the box it will use a northern hemisphere 
concept of when summer is (solstice in June) and a 28.8°C high. You can also sign up for a [DarkSky](https://darksky.net/dev) account
which will let you configure your environment to a place on the earth. The project currently controls a single vivarium/DWC/germination station.

There is also a web interface which provides some feedback about how the temperature/humidity has been over the last 24 hours and 
graphs on what is being planned for the environment.

![Web Interface](https://drive.google.com/uc?id=13xxW7B3PRotsCrF2qOhmx0aXhpSZAuoR)

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

There is only 1 'environment' modelled. This means the expects for heat, humidity and light are across all of the 'zones' you set up, so you cannot 
have a temperature plan for a root zone as a separate plan for the air zone. I do not expect this to change in the foreseable future.
You can have separate triggers run heaters in different zones based on the sensors you assign to that zone but the expected temps 
will be the same, or at least will be based on this. I will add the ability to have a delta in the command structure so you can set things to 
be -2 degrees of the expected value for example.

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
    sudo pip3 install --upgrade pip setuptools wheel Adafruit_DHT datetime adafruit-circuitpython-ssd1306 luma.oled
    sudo phpenmod mysqli

The current version of WiringPi does not work proerly on the Pi 4B so upgrade it.

    cd /tmp
    wget https://project-downloads.drogon.net/wiringpi-latest.deb
    sudo dpkg -i wiringpi-latest.deb

Download the pigpiod daemon to manage fast access to GPIO stuff (DHT22 for example)

    cd /tmp
    wget https://github.com/joan2937/pigpio/archive/master.zip
    unzip master.zip
    cd pigpio-master
    make
    sudo make install
    sudo pigpiod

Download the drivers for the 1.5 inch OLED display

    cd /tmp
    git clone https://github.com/rm-hull/luma.examples.git
    cd luma.examples
    sudo -H pip install -e .

Make the directories we require for our software

    sudo mkdir /logs
    sudo chown -R pi:www-data /logs
    sudo chmod -R g+w /logs
    sudo mkdir /webroot

Clone the software into its home.

    cd /webroot
    sudo git clone https://github.com/nigeljohnson73/gStation.git
    sudo chown -R pi:www-data gStation
    sudo chmod -R g+w gStation

Make and install the DHT22 access software

    cd gStation/sh/dhtxx
    gcc -Wall -pthread -o DHTXXD test_DHTXXD.c DHTXXD.c -lpigpiod_if2
    mv DHTXXD ../
    cd ../../

Set up MySQL with a secure root account.

    sudo mysql --user=root < res/install.sql

Set up application database and user.

    sudo mysql -uroot -pEarl1er2day < res/install_db.sql

Copy a simple installation config file so you can edit stuff

    cp res/install_config.php config_override.php

You want to set up the GPIO pins for your sensors, so configure them in `config_override.php` and then 
run the setup. The parameter supplied is the pin layout in the [Pinout section](#Pinouts) below. When you 
run this command it should print out the pins that can be used.

    php sh/setup_gpio.php 2.1g

Install the heartbeat indicator LED controller which will install the LED on the correct LED pin setup above.

	php sh/install_file.php res/install_boot_config.txt /boot/config.txt
	# Clean with: php sh/clean_file.php /boot/config.txt \#GSTATION
    sudo ln -s /webroot/gStation/res/ledbeat.service /etc/systemd/system
    sudo systemctl daemon-reload
    sudo systemctl enable ledbeat
    sudo systemctl start ledbeat 

Move the new DHT11 overlay into the correct place.

    sudo rm -f /boot/overlays/dht11.dtbo
    sudo cp res/dht11.dtbo /boot/overlays

Move the webroot stuff around.

    cd /var/www/
    sudo mv html html_orig
    sudo ln -s /webroot/gStation html
    sudo /etc/init.d/apache2 restart

Update the user login script so it has a pretty banner and stuff.

    echo "source /webroot/gStation/res/bashrc" | tee -a ~/.bashrc

Configure the system to set up the GPIOs on boot in the rc.local file.

    cat /etc/rc.local | grep -v 'exit 0' | sudo tee /etc/rc.local
    echo ". /webroot/gStation/res/rc.local" | sudo tee -a /etc/rc.local
    echo "exit 0" | sudo tee -a /etc/rc.local

Change the I2C refresh rate.

    echo "dtparam=i2c_baudrate=1000000" | sudo tee -a /boot/config.txt

Update the crontab to have our update commands.

    crontab -e

Add these lines:

    1 0 * * * /usr/bin/php /webroot/gStation/sh/gstation_update.php > /tmp/gstation_update.txt 2>/dev/null
    * * * * * /usr/bin/php /webroot/gStation/sh/gstation_tick.php > /tmp/gstation_tick.txt 2>/dev/null
    * * * * * /usr/bin/php /webroot/gStation/gfx/generate_static_graphs.php > /dev/null 2>&1

Once you reboot, things should start kicking off in a few minutes and you should see the IP address you can 
connect to for the web interface appear on the OLED screen.

## Adding the camera timelapse
This is a project itself [on github](https://github.com/nigeljohnson73/gCam) and is not quite ready for public release. These are the additional steps needed to get things working assuming you've completed the above setup.

First, go head over to [Dropbox developer console](https://www.dropbox.com/developers/apps) and get the API key where you want to store your timelapse files.

Install the missing software packages

    sudo apt install -y php-curl git imagemagick build-essential libv4l-dev libjpeg-dev cmake

Download, make and install the mjpeg streamer software

    cd /tmp
    git clone https://github.com/jacksonliam/mjpg-streamer.git
    cd mjpg-streamer/mjpg-streamer-experimental
    make
    sudo make install

Clone the gCam software into its home.

    cd /webroot
    sudo git clone https://github.com/nigeljohnson73/gCam.git
    sudo chown -R pi:www-data gCam
    sudo chmod -R g+w gCam
    cd gCam

Set up application database and user.

    sudo mysql -uroot -pEarl1er2day < res/install_db.sql

Copy a simple installation config file so you can edit stuff and put your Dropbox API key in.

    cp res/install_config.php config_override.php

Configure the system to run our startup commands in the rc.local file.

    cat /etc/rc.local | grep -v 'exit 0' | sudo tee /etc/rc.local
    echo ". /webroot/gCam/res/rc.local" | sudo tee -a /etc/rc.local
    echo "exit 0" | sudo tee -a /etc/rc.local

Install a "local" cam stream (you will need a dropbox API key in config_override.php)

    php sh/lapse_create.php local localhost:8081/?action=stream start

Update the crontab to have our update commands.

    crontab -e

Add these lines:

    1 0 * * * /usr/bin/php /webroot/gCam/sh/_update.php > /tmp/gcam_update.txt 2>&1
    * * * * * /usr/bin/php /webroot/gCam/sh/_tick.php > /tmp/gcam_tick.txt 2>&1

## Pinouts

More detailed information is available including voltage tolerances and resistor values can be found on the 
[Hardware Pinout](https://github.com/nigeljohnson73/gStation/wiki/Raspberry-Pi-pin-outs) page in the wiki, but 
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
