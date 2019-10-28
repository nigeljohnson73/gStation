## Setting up the Pi

* Burn the latest Raspian image to an SD card
* Drop wpa_supplicant.conf into the boot disk
* Drop ssh into the boot disk

Boot the PI and log in (password will initally be raspberry)

    rm -rf ~/.ssh/known_hosts
    ssh pi@raspberrypi.local

Change the password and run the configurator

    passwd
    sudo raspi-config

Make the folllowing changes

* Network Options -> Hostname
* Interfaceing options -> I2C -> yes
* Interfaceing options -> serial -> no
* Interfaceing options -> One wire -> yes
* Exit (will reboot)

Next, log back in to the new hostname with the new password, update Raspian and install all the software we need

    sudo apt update -y
    sudo apt upgrade -y
    sudo apt install -y apache2 php php-mbstring php-gd mariadb-server php-mysql git python3-dev python3-pip python3-pil i2c-tools python3-gpiozero
    sudo pip3 install --upgrade pip setuptools wheel Adafruit_DHT datetime adafruit-circuitpython-ssd1306
    sudo phpenmod mysqli

Make the directories we require

    sudo mkdir /logs
    sudo chown -R pi:www-data /logs
    sudo chmod -R g+w /logs
    sudo mkdir /webroot
    #sudo chown -R pi:www-data /webroot
    #sudo chmod -R g+w /webroot

Get the software in place

    cd /webroot
    git config --global credential.helper store
    git config --global user.email "nigel@nigeljohnson.net"
    git config --global user.name "Nigel Johnson"
    sudo git clone https://github.com/nigeljohnson73/gStation.git
    sudo chown -R pi:www-data /webroot
    sudo chmod -R g+w /webroot
    cd gStation
    cat config.php | grep -v "^$" | grep -v "^//" > config_override.php

Move the webroot stuff around

    cd /var/www/
    sudo mv html html_orig
    sudo ln -s /webroot/gStation html
    sudo /etc/init.d/apache2 restart

Set up MySQL with the correct root account and a user for the application

    sudo mysql --user=root < /webroot/gStation/res/install.sql

Update the user login script so it has a pretty banner and stuff.

    echo "source /webroot/gStation/res/bashrc" | tee -a ~/.bashrc

Change the I2C refresh rate

    echo "dtparam=i2c_baudrate=1000000" | sudo tee -a /boot/config.txt

Configure the system to set GPIOs up on boot. Fix the rc.local file

    cat /etc/rc.local | grep -v 'exit 0' | sudo tee /etc/rc.local
    cat /webroot/gStation/res/localrc | sudo tee -a /etc/rc.local

Update the crontab

    crontab -e

Add these lines

    1 0 * * * /usr/bin/php /webroot/gStation/sh/gstation_update.php > /tmp/gstation_update.txt 2>/dev/null
    * * * * * /usr/bin/php /webroot/gStation/sh/gstation_tick.php > /tmp/gstation_tick.txt 2>/dev/null

## Resources used
* [Configure wireless before booting][SUPLICANT]
* [Enable SSH before booting][SSH]
* [LAMP on a Raspberry PI][LAMP]

## Software guides used

* [Button press off][BUTTON]
* [Solid State Relay tutorial][SSR]
* [Solid State Relay video][SSRVIDEO]
* [DS18B20 Digital termocouple tutorial][DS18B20]
* [OLED wiring diagram and tutorial][OLED]
* [Decent GPIO pinout][GPIOPIOUT]

## Improvments

An I2C bus temp/humidity sensor for environmental monitoring

* [AM2315 code on google][AM2315CODE]
* [AM2315 on Amazon][AM2315]
* [HDC1080 on Amazon][HDC1080]
* [BME280 on Amazon][BME280]

Raspi PPID tuned Heat controller for stopping the oscilation in the heater

* [PhD Thesis on PID tuning][PIDTUNE]

## Software discarded

### DHT22 Humidity and temperature module

* [General overview][DHT22OVERVIEW]
* [Data logger example][DH22LOGGER]
* [Adafruit library for python][DH22ADAFRUIT]

[GPIOPIOUT]: https://raw.githubusercontent.com/DigitalLumberjack/mk_arcade_joystick_rpi/master/wiki/images/mk_joystick_arcade_GPIOsb+.png
[DH22ADAFRUIT]: https://github.com/adafruit/Adafruit_Python_DHT
[DH22LOGGER]: https://www.instructables.com/id/Raspberry-PI-and-DHT22-temperature-and-humidity-lo/
[DHT22OVERVIEW]: https://pimylifeup.com/raspberry-pi-humidity-sensor-dht22/
[OLED]: https://www.raspberrypi-spy.co.uk/2018/04/i2c-oled-display-module-with-raspberry-pi/
[DS18B20]: http://www.circuitbasics.com/raspberry-pi-ds18b20-temperature-sensor-tutorial/
[SSRVIDEO]: https://www.youtube.com/watch?v=Q6v8BnDT47I
[BUTTON]: https://github.com/TonyLHansen/raspberry-pi-safe-off-switch/
[SSR]: https://tech.iprock.com/?p=10030
[SUPLICANT]: https://howchoo.com/g/ndy1zte2yjn/how-to-set-up-wifi-on-your-raspberry-pi-without-ethernet
[SSH]: https://howchoo.com/g/ote0ywmzywj/how-to-enable-ssh-on-raspbian-without-a-screen
[LAMP]: https://howtoraspberrypi.com/how-to-install-web-server-raspberry-pi-lamp/
[AM2315CODE]:https://code.google.com/archive/p/am2315-python-api/
[AM2315]: https://smile.amazon.co.uk/dp/B07VF17C7N
[HDC1080]: https://smile.amazon.co.uk/dp/B07DJ7FLHS
[BME280]: https://smile.amazon.co.uk/dp/B07KY8WY4M
[PIDTUNE]: https://studentnet.cs.manchester.ac.uk/resources/library/thesis_abstracts/MSc14/FullText/Ioannidis-Feidias-fulltext.pdf

