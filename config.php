<?php
$local_timezone = "Europe/London"; // Where are you locally based for time references

$sensors = [ ];
$sensors [] = ( object ) [
		"name" => "PI", // RZ Root zone
		"type" => "PI" // EMPTY, DS18B20, DHT11 or DHT22
];
$sensors [] = ( object ) [
		"name" => "ZONE1", // RZ Root zone
		"type" => "EMPTY" // EMPTY, DS18B20, DHT11 or DHT22
];
$sensors [] = ( object ) [ 
		"name" => "ZONE2", // AZ Generic air zone
		"type" => "EMPTY" // EMPTY, DS18B20, DHT11 or DHT22
];
$sensors [] = ( object ) [ 
		"name" => "ZONE3", // CZ Canopy zone
		"type" => "EMPTY" // EMPTY, DS18B20, DHT11 or DHT22
];
$sensors [] = ( object ) [ 
		"name" => "ZONE4", // LZ Lighting zone
		"type" => "EMPTY" // EMPTY, DS18B20, DHT11 or DHT22
];
$sensors [] = ( object ) [ // This is the 5th sensor for the CO2 monitor in the Air Zone (AZ)
		"name" => "ZONE2", // Generic air zone
		"type" => "EMPTY" // EMPTY or MH-Z19B
];

$triggers = [ ];
$triggers [] = ( object ) [ 
		"name" => "T1", // Generally used for heat
		"type" => "EMPTY" // EMPTY, SSR, iSSR or LED
];
$triggers [] = ( object ) [ 
		"name" => "T2", // Generally used for light
		"type" => "EMPTY" // EMPTY, SSR, iSSR or LED
];
$triggers [] = ( object ) [ 
		"name" => "T3",
		"type" => "EMPTY" // EMPTY, SSR, iSSR or LED
];
$triggers [] = ( object ) [ 
		"name" => "T4",
		"type" => "EMPTY" // EMPTY, SSR, iSSR or LED
];
$triggers [] = ( object ) [ 
		"name" => "T5",
		"type" => "EMPTY" // EMPTY, SSR, iSSR or LED
];
$triggers [] = ( object ) [ 
		"name" => "T6",
		"type" => "EMPTY" // EMPTY, SSR, iSSR or LED
];

$conditions = [ ];
$conditions [] = "T1 IF [[ZONE1.TEMPERATURE]] < [[DEMAND.TEMPERATURE]]";
$conditions [] = "T2 IF [[DEMAND.LIGHT]] == 'SUN'";
$conditions [] = "BAD_TRIGGER_TEST IF [[ZONE1.TEMPERATURE]] < [[DEMAND.TEMPERATURE]]";
$conditions [] = "T6 IF [[BAD_SENSOR_TEST]]";

$graphs = [];
$graphs[] = "temperature.ZONE1";
$graphs[] = "humidity.ZONE1";

$sensor_pin_1 = 99;
$sensor_pin_2 = 99;
$sensor_pin_3 = 99;
$sensor_pin_4 = 99;

$trigger_pin_1 = 99;
$trigger_pin_2 = 99;
$trigger_pin_3 = 99;
$trigger_pin_4 = 99;
$trigger_pin_5 = 99;
$trigger_pin_6 = 99;

$led_pin = 99;
$button_pin = 99;

$outlier_temperature_min = 12;
$outlier_temperature_max = 35;
$outlier_humidity_min = 15;
$outlier_humidity_max = 95;

/**
 * START SIMULATION ENVIRONMENT
 */
$summer_solstice = "0621"; // June 21 is summer solstice in the northern hemisphere
$day_temperature_summer = 28.5; // In the summer, this is the max temp
$day_temperature_winter = 21.5; // In the winter, this is the max temp
$night_temperature_summer = 12.5; // In the summer, this is the lowest temperature
$night_temperature_winter = 9.5; // In the winter, this is the lowest temperature
$day_humidity_winter = 81; // In the winter, this is the max day-time humidity
$day_humidity_summer = 62; // In the summer, this is the max day-time humidity
$night_humidity_winter = 89; // In the winter, this is the max night-time humidity
$night_humidity_summer = 78; // In the summer, this is the max night-time humidity
$sunset_winter = 15 + (55 / 60); // In the winter, this is the time of sunset in Malkerns/SZ - 15:55 UTC (London is 17:32 UTC)
$sunset_summer = 20 + (21 / 60); // In the summer, this is the time of sunset in Malkerns/SZ - 20:21 UTC (London is 19:21 UTC)
$daylight_summer = 16 + (38 / 60); // In the summer, this is how many hours of daylight there will be
$daylight_winter = 7 + (51 / 60); // In the winter, this is how many hours of daylight there will be
/**
 * END SIMULATION ENVIRONMENT
 */

/**
 * START DARK SKY SETUP
 */
$darksky_key = ""; // You can sign up for a free dark sky account to get location based weather information: https://darksky.net/dev/docs
$api_call_cap = 370; // If you have a free account, leave this under 900 and don't run the update more than once in a day until you're up to date

// The place you want your station to be remotely located at
$lat = "-26.549711"; // the latitude of the place you want to mimic (Malkerns/SZ)
$lng = "31.197664"; // the longitude of the place you want to mimic (Malkerns/SZ)
$loc = "gStationDev"; // Just to give it a name in the browser could be the name of the actual place

$season_adjust_days = 0; // If you want to move forward in the season, add this many days to the actual forcast. if set to 31, Real January 1 will be like February 1 at your location.
$timeszone_adjust_hours = 0; // If you want to move forward in the day (because your location is suitably ahead of you) add this many hours. If set to 2, Real 07:30 will be like 09:30 at your location.
$yr_history = 25; // How many years to go back for historic data. The further back the smoother 'today' will be but more processing and database is required for the data model.
$force_api_history = 5; // Forecast data is replaced with historic data over the few days after it happened, this flag will force API calls based on this number of days
$smoothing_days = 15; // Used to smooth the measurement data even more than history alone. Used as a sliding +/- window from 'today' 2n+1 points are used to calculate 'today'
$smoothing_loops = 3; // Using more loops is much more computationally intensive, but yeilds a much smoother outcome
/**
 * END DARK SKY
 */

/**
 * START BULKSMS SETUP
 */
$bulksms_username = ""; // BulkSMS is used to send info on status bits, go set up an account, it's cool.
$bulksms_password = "";
$bulksms_sender = "TxtsRFun";
$bulksms_notify = "447000000000"; // This is where text alerts will go.
$bulksms_alert_sunrise = false;
$bulksms_alert_sunset = false;
$bulksms_alert_tod = false;
/**
 * END BULKSMS
 */

/**
 * You REALLY shouldn't need to be fiddling below here
 */
$hl_heat_pin = 17; // The GPIO pin used for the heat trigger
$hl_light_pin = 18; // The GPIO pin used for the light trigger
$hl_high_value = 0; // Some Solid state relays need a high value, some a low one. This is what makes it go on
$hl_low_value = 1;
$app_title = "gStation"; // makes the browser name properly
$log_level = 3; // LL_INFO
$db_server = "localhost"; // Database things. Don't be editing
$db_name = "gs";
$db_user = "gs_user";
$db_pass = "gs_passwd";
// $temperature_buffer = 0.1; // used to soften direction noise
?>
