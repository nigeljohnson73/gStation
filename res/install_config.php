<?php
// Update this to where you are and the name you want to see in the browser
$local_timezone = "Europe/London";
//$loc = "gsDev";

// Sensor zero is the PI itself.
$sensors [1]->type = "DS18B20"; // EMPTY, DS18B20, DHT11 or DHT22 // Root Zone (ZONE1)
$sensors [2]->type = "DHT22"; // EMPTY, DS18B20, DHT11 or DHT22   // Air Zone (ZONE2)
$sensors [3]->type = "DHT22"; // EMPTY, DS18B20, DHT11 or DHT22   // Ambient (ZONE3)
//$sensors [4]->type = "EMPTY"; // EMPTY, DS18B20, DHT11 or DHT22
//$sensors [5]->type = "EMPTY"; // EMPTY or MH-Z19B                // Carbon Dioxide monitor - once implemented (ZONE2)

// Set Graph and data labels
// $sensors [1]->label = "ZONE1";
// $sensors [2]->label = "ZONE2";
// $sensors [3]->label = "ZONE3";
// $sensors [4]->label = "ZONE4";

// Set Graph golours
//$sensors [1]->colour = "#609";  // Purple
//$sensors [2]->colour = "#00c";  // Dark blue
//$sensors [3]->colour = "#66f";  // Blue
//$sensors [4]->colour = "#99f";  // Light blue

// Triggers start at zero
$triggers [0]->type = "SSR"; // EMPTY, SSR, iSSR or LED           // Heater
$triggers [1]->type = "SSR"; // EMPTY, SSR, iSSR or LED           // Lighting
$triggers [2]->type = "SSR"; // EMPTY, SSR, iSSR or LED           // Air-zone vent
//$triggers [3]->type = "EMPTY"; // EMPTY, SSR, iSSR or LED
//$triggers [4]->type = "EMPTY"; // EMPTY, SSR, iSSR or LED
//$triggers [5]->type = "EMPTY"; // EMPTY, SSR, iSSR or LED

// Set Graph and data labels
// $triggers [0]->label = "T1";
// $triggers [1]->label = "T2";
// $triggers [2]->label = "T3";
// $triggers [3]->label = "T4";
// $triggers [4]->label = "T5";
// $triggers [5]->label = "T6";

// Set Graph golours
//$triggers [0]->colour = "#c00";  // Dark red
//$triggers [1]->colour = "#fc0";  // Orange
//$triggers [2]->colour = "#ccc";  // Grey
//$triggers [3]->colour = "#ccc";  // Grey
//$triggers [4]->colour = "#ccc";  // Grey
//$triggers [5]->colour = "#ccc";  // Grey

$conditions = [ ];
$conditions [] = "T1 IF [[ZONE1.TEMPERATURE]] < [[DEMAND.TEMPERATURE]]";       // Root zone up to demanded temperature
$conditions [] = "T2 IF [[DEMAND.LIGHT]] == 'SUN'";                            // Light on if it's day time
$conditions [] = "T3 IF [[ZONE2.TEMPERATURE]] > ([[ZONE3.TEMPERATURE]] + 5)";  // Vent the atmosphere if its 5 degrees over ambient
$conditions [] = "T3 IF [[DATA.HOUR]] >= 0.1 && [[DATA.HOUR]] < 0.2";          // vent the air overnight regarldess of temperature

$graphs = [ ];
$graphs [] = "temperature.Zone3 (Ambient), Zone2 (Air zone), Zone1 (Root zone), Demand";
$graphs [] = "humidity.Zone3 (Ambient), Zone2 (Air zone), Demand";
$graphs [] = "trigger.T3 (Vent), T2 (Light), T1 (Heat)";
$graphs [] = "cpu_load.PI";
$graphs [] = "temperature.PI";
//$graphs [] = "sensor_age.Zone3 (".$sensors[3]->type."), Zone2 (".$sensors[2]->type."), Zone1 (".$sensors[1]->type.")";
$graphs [] = "cpu_wait.PI";
$graphs [] = "sd_load.PI";
$graphs [] = "mem_load.PI";

// If you're using DarkSky, this is where you configure the parameters
// You can override the use of DarkSky with the parameters below, but you can also continue to collect data
// $darksky_key = "cc1f19147be757c853cffdeb62a8c403";
// $api_call_cap = 900;
// $lat = "-26.549711";
// $lng = "31.197664";
//$rebuild_from = "Demands";
$rebuild_from = "Simulation"; // 'Simulation', 'Demands' or a filename in the locations folder for example 'SZ.Malkerns.json'
$season_adjust_days = 0; // Adjust the file based data model
$timeszone_adjust_hours = 0; // Adjust the file based data model

// Set this to the month and day you want the ramp to start on, for example 23rd of January is 0123
//$demand_solstice = "0000";

// This config will have an 18hr day for 14 days, then ramp down to 12 hours over the following 21 days.
// There will be a small temp drop over the day length cycle as well
$demand_solstice = "0621"; // When should the demand simulation start (Format: MMDD)
$demand = [ ];
$demand [] = ( object ) [
		"period_length" => 14,
		"sunset" => 21 + (59/60) + (59/(60 * 60)),
		"daylight_hours" => 18.1,
		"day_temperature" => 24.1,
		"night_temperature" => 21.5,
		"day_humidity" => 40.5,
		"night_humidity" => 45.5
];

$demand [] = ( object ) [
		"period_length" => 21,
		"sunset" => 21 + (59/60) + (59/(60 * 60)),
		"daylight_hours" => 18.1,
		"day_temperature" => 24.1,
		"night_temperature" => 21.5,
		"day_humidity" => 40.5,
		"night_humidity" => 45.5
];

$demand [] = ( object ) [
		"sunset" => 21 + (59/60) + (59/(60 * 60)),
		"daylight_hours" => 12.1,
		"day_temperature" => 23.5,
		"night_temperature" => 19.5,
		"day_humidity" => 30.5,
		"night_humidity" => 40.5
];

// If you're using the envronmental simulation, this is where you configure the paramaters
$summer_solstice = "0621"; // (Format: MMDD)
$solstice_temp_delta_days = 60;
$day_temperature_summer = 27.5;
$day_temperature_winter = 19.5;
$night_temperature_summer = 21.5;
$night_temperature_winter = 15.5;
$sunset_winter = 21 + (00 / 60);
$sunset_summer = 21 + (00 / 60);
$daylight_summer = 16 + (30 / 60);
$daylight_winter = 11 + (30 / 60);
$night_humidity_winter = 60;
$day_humidity_winter = 55;
$night_humidity_summer = 50;
$day_humidity_summer = 30;

// If you're using bulksms for alerting, configure it here
$bulksms_username = "YourUserName";
$bulksms_password = "Pa55w0rd";
$bulksms_sender = "TxtsRFun";
$bulksms_notify = "447000000000";
// $bulksms_alert_sunrise = true;
// $bulksms_alert_sunset = true;
// $bulksms_alert_tod = true;

?>