# gStation

Germination station was originally designed to control the heat and light for a germination station for my succulents by mimicing the seasonal 
conditions anywhere in the world. The upside is that it will also control the heat and light requirements for full sized plant instalations, 
hydropnics stations even reptile vivariums. 

## Possible implementation things to do

An I2C bus temp/humidity sensor

 * https://code.google.com/archive/p/am2315-python-api/
 * AM2315: https://smile.amazon.co.uk/dp/B07VF17C7N
 * HDC1080: https://smile.amazon.co.uk/dp/B07DJ7FLHS
 * BME280: https://smile.amazon.co.uk/dp/B07KY8WY4M

Raspi PPID tuned Heat controller

 * https://studentnet.cs.manchester.ac.uk/resources/library/thesis_abstracts/MSc14/FullText/Ioannidis-Feidias-fulltext.pdf

## Completed

A more reliable temp only sensor:

 * http://www.circuitbasics.com/raspberry-pi-ds18b20-temperature-sensor-tutorial/

Here is how to oled display over I2C

 * https://www.raspberrypi-spy.co.uk/2018/04/i2c-oled-display-module-with-raspberry-pi/
 * https://learn.adafruit.com/monochrome-oled-breakouts/python-wiring

Here is Rasperry pi set up with DHT22: 

 * general overview: https://pimylifeup.com/raspberry-pi-humidity-sensor-dht22/
 * includes data logger: https://www.instructables.com/id/Raspberry-PI-and-DHT22-temperature-and-humidity-lo/
 * insdtall Adafruit library for python: https://github.com/adafruit/Adafruit_Python_DHT

these are SSR related

 * https://www.youtube.com/watch?v=Q6v8BnDT47I
 * https://tech.iprock.com/?p=10030

Button press off

 * https://github.com/TonyLHansen/raspberry-pi-safe-off-switch/

 -------------------------------

## Setting things up 

Burn image to SD card

From: https://howchoo.com/g/ndy1zte2yjn/how-to-set-up-wifi-on-your-raspberry-pi-without-ethernet

Drop wpa_supplicant.conf into the boot disk

From: https://howchoo.com/g/ote0ywmzywj/how-to-enable-ssh-on-raspbian-without-a-screen

Drop ssh into the boot disk

Eject boot disk

boot PI

Log on to the PI

    rm -rf ~/known_hosts
    ssh pi@raspberrypi.local
    passwd (change pi password)

    sudo raspi-config

* Network Options -> Hostname (gs-eswatini)
* Interfaceing options -> I2C -> yes
* Interfaceing options -> serial -> no
* Update
* Finish (will reboot)

log back in with the new password

    ssh pi@gs-eswatini.local

From: https://howtoraspberrypi.com/how-to-install-web-server-raspberry-pi-lamp/

    sudo apt update -y
    sudo apt upgrade -y
    sudo apt update -y
    
    sudo apt install apache2 -y
    
    sudo chown -R pi:www-data /var/www/html/
    sudo chmod -R 770 /var/www/html/
    
    wget -O check_apache.html http://127.0.0.1
    cat ./check_apache.html
    rm -f ./check_apache.html
    
    sudo apt install php php-mbstring -y
    sudo rm /var/www/html/index.html
    echo "<?php phpinfo ();?>" > /var/www/html/index.php

Test that from a remote browser

    sudo apt install mariadb-server php-mysql -y
    
    sudo mysql --user=root
    
    DROP USER 'root'@'localhost';
    CREATE USER 'root'@'localhost' IDENTIFIED BY '';
    GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
    quit
    
    sudo apt install phpmyadmin -y
	(will ask some questions)
    
    sudo mysql --user=root
    DROP USER 'root'@'localhost';
    CREATE USER 'root'@'localhost' IDENTIFIED BY 'Earl1er2day';
    GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
    CREATE DATABASE gs;
    DROP USER IF EXISTS 'gs'@'localhost';
    CREATE USER 'gs_user'@'localhost' IDENTIFIED BY 'gs_passwd';
    ALTER USER 'gs_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'gs_passwd';
    GRANT ALL PRIVILEGES ON gs.* TO 'gs_user'@'localhost';
    FLUSH PRIVILEGES;
    quit
    
    sudo phpenmod mysqli -y
    
    sudo mkdir /logs
    sudo chown -R pi:www-data /logs
    sudo chmod -R g+w /logs
    
    apt install git -y
    cd /webroot
    git config --global credential.helper store
    sudo git clone https://github.com/nigeljohnson73/gStation.git
    sudo chown -R pi:www-data /webroot
    sudo chmod -R g+w /webroot
    cd gstation
    cp config.php config_override.php
    ###php sh/gstation_update.php
    
    cd /var/www/
    sudo mv html html_orig
    sudo ln -s /webroot/gstation html
    
    sudo /etc/init.d/apache2 restart
    
    crontab -e
    #Add these lines:
    1 0 * * * /usr/bin/php /webroot/gstation/sh/gstation_update.php
    * * * * * /usr/bin/php /webroot/gstation/sh/gstation_tick.php
    
    sudo apt-get install python3-dev python3-pip
    sudo python3 -m pip install --upgrade pip setuptools wheel
    sudo pip3 install Adafruit_DHT
    sudo pip3 install datetime
    
    # OLED stuff
    sudo apt-get install -y python3-pil i2c-tools
    sudo pip3 install adafruit-circuitpython-ssd1306
    
    sudo vi /boot/config.txt
    #Add to the end of the file
    dtparam=i2c_baudrate=1000000
    
    #button stuff
    sudo apt install python3-gpiozero

Finally the rc.local

    vi /etc/rc.local
    ## add to the bottom

	# Set up the GPIO pins for the heat/light
    if [ ! -d /sys/class/gpio/gpio17 ]
    then
        echo 17 > /sys/class/gpio/export
        sleep 1 ;# Short delay while GPIO permissions are set up
    fi
    if [ ! -d /sys/class/gpio/gpio18 ]
    then
        echo 18 > /sys/class/gpio/export
        sleep 1 ;# Short delay while GPIO permissions are set up
    fi
    
	# Set up the GPIO pins for output
    echo out > /sys/class/gpio/gpio17/direction
    echo out > /sys/class/gpio/gpio18/direction
    
	# Switch them off for now
    echo 1 > /sys/class/gpio/gpio17/value
    echo 1 > /sys/class/gpio/gpio18/value
    
	# Start the OLED display
    python3 /webroot/gStation/sh/oledpower.py &

That should do it for now