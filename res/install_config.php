<?php
// This is all the stuff that should go into the database configurator... plus probably some more... Later... Much... later.

// Sensor zero is the PI itself.
$sensors [1]->type = "DS18B20"; // EMPTY, DS18B20, DHT11 or DHT22 // Root Zone (ZONE1)
$sensors [2]->type = "DHT22"; // EMPTY, DS18B20, DHT11 or DHT22   // Air Zone (ZONE2)
//$sensors [3]->type = "DHT22"; // EMPTY, DS18B20, DHT11 or DHT22   // Ambient (ZONE3)
//$sensors [4]->type = "EMPTY"; // EMPTY, DS18B20, DHT11 or DHT22
//$sensors [5]->type = "EMPTY"; // EMPTY or MH-Z19B                // Carbon Dioxide monitor - once implemented (ZONE2)

// Triggers start at zero
$triggers [0]->type = "SSR"; // EMPTY, SSR, iSSR or LED           // Heater
$triggers [1]->type = "SSR"; // EMPTY, SSR, iSSR or LED           // Lighting
//$triggers [2]->type = "SSR"; // EMPTY, SSR, iSSR or LED           // Air-zone vent
//$triggers [3]->type = "SSR"; // EMPTY, SSR, iSSR or LED
//$triggers [4]->type = "SSR"; // EMPTY, SSR, iSSR or LED
//$triggers [5]->type = "SSR"; // EMPTY, SSR, iSSR or LED

$conditions = [ ];
$conditions [] = "T1 IF [[ZONE1.TEMPERATURE]] < [[DEMAND.TEMPERATURE]]";       // Root zone up to demanded temperature
$conditions [] = "T2 IF [[DEMAND.LIGHT]] == 'SUN'";                            // Light on if it's day time
//$conditions [] = "T3 IF [[ZONE2.TEMPERATURE]] > ([[ZONE3.TEMPERATURE]] + 5)";  // Vent the atmosphere if its 5 degrees over ambient
//$conditions [] = "T3 IF [[DATA.HOUR]] >= 0.1 && [[DATA.HOUR]] < 0.2";          // vent the air overnight regarldess of temperature

$graphs = [ ];
$graphs [] = "temperature.Zone2, Zone1";
$graphs [] = "humidity.Zone1";
$graphs [] = "temperature.PI";
$graphs [] = "mem_load.PI";
$graphs [] = "cpu_load.PI";
$graphs [] = "cpu_wait.PI";
$graphs [] = "sd_free.PI";
$graphs [] = "triggers.T1, T2";

// Set this to the month and day you want the ramp to start on, for example 24th of January is 0124
//$demand_solstice = "0000";

// This config ill have an 18hr day for 14 days, then ramp down to 12 hours over the following 21 days. 
// There will be a small temp drop over the day length cycle as well
//$demand = [ ];
//$demand [] = ( object ) [
//	"period_length" => 14,
//	"sunset" => 21 + (59/60) + (59/(60 * 60)),
//	"daylight_hours" => 18.1,
//	"day_temperature" => 24.1,
//	"night_temperature" => 21.5,
//	"day_humidity" => 40.5,
//	"night_humidity" => 45.5
//	];
//
//$demand [] = ( object ) [
//	"period_length" => 21,
//	"sunset" => 21 + (59/60) + (59/(60 * 60)),
//	"daylight_hours" => 18.1,
//	"day_temperature" => 24.1,
//	"night_temperature" => 21.5,
//	"day_humidity" => 40.5,
//	"night_humidity" => 45.5
//];
//
//$demand [] = ( object ) [
//	"sunset" => 21 + (59/60) + (59/(60 * 60)),
//	"daylight_hours" => 12.1,
//	"day_temperature" => 23.5,
//	"night_temperature" => 19.5,
//	"day_humidity" => 30.5,
//	"night_humidity" => 40.5
//];

// Update this to where you are
$local_timezone = "Europe/London";
$loc = "GS0";

// If you're using bulksms for alerting, configure it here
$bulksms_username = "YourUserName";
$bulksms_password = "Pa55w0rd";
$bulksms_sender = "TxtsRFun";
$bulksms_notify = "447000000000";
// $bulksms_alert_sunrise = true;
// $bulksms_alert_sunset = true;
// $bulksms_alert_tod = true;

// If you're using the envronmental simulation, this is where you configure the paramaters
$summer_solstice = "0621";
$day_temperature_summer = 27.5;
$day_temperature_winter = 22.5;
$night_temperature_summer = 12.5;
$night_temperature_winter = 11.5;
$sunset_winter = 21 + (00 / 60);
$sunset_summer = 21 + (00 / 60);
$daylight_summer = 16 + (30 / 60);
$daylight_winter = 11 + (30 / 60);
$night_humidity_winter = 60;
$day_humidity_winter = 55;
$night_humidity_summer = 50;
$day_humidity_summer = 30;
?>
