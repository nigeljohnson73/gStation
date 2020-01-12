<?php
// This is all the stuff that should go into the database configurator... plus probably some more... Later... Much... later.
$sensors [1]->type = "DS18B20"; // EMPTY, DS18B20, DHT11 or DHT22
$sensors [2]->type = "DHT22"; // EMPTY, DS18B20, DHT11 or DHT22
$triggers [1]->type = "SSR"; // EMPTY, SSR, iSSR or LED
$triggers [2]->type = "SSR"; // EMPTY, SSR, iSSR or LED

$conditions = [ ];
$conditions [] = "T1 IF [[ZONE1.TEMPERATURE]] < [[DEMAND.TEMPERATURE]]";
$conditions [] = "T2 IF [[DEMAND.LIGHT]] == 'SUN'";

$graphs = [ ];
$graphs [] = "temperature.Zone2, Zone1";
$graphs [] = "humidity.Zone2";

$local_timezone = "Europe/London";
$loc = "GS0";

$bulksms_username = "YourUserName";
$bulksms_password = "Pa55w0rd";
$bulksms_sender = "TxtsRFun";
$bulksms_notify = "447000000000";
// $bulksms_alert_sunrise = true;
// $bulksms_alert_sunset = true;
// $bulksms_alert_tod = true;

$summer_solstice = "0621";
$day_temperature_summer = 27.5;
$day_temperature_winter = 22.5;
$night_temperature_summer = 12.5;
$night_temperature_winter = 11.5;
$sunset_winter = 21 + (00 / 60);
$sunset_summer = 21 + (00 / 60);
$daylight_summer = 16 + (30 / 60);
$daylight_winter = 11 + (30 / 60);

// Humidity is usually higher when temps are lower because warm air can hold more moisture.
// Therefore, night time humidities are higher than daytime, and winter higher than summer.
$night_humidity_winter = 60;
$day_humidity_winter = 55;
$night_humidity_summer = 50;
$day_humidity_summer = 30;
?>