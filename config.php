<?php
$loc = getHostname (); // Specific instance of the gStation - used in alerts and the browser

$sensor_age = 30; // sensor data older than this in seconds will be ignored
$sensor_age_alarm = 2 * 60;
$show_empty = false; // Do you want triggers and sensors and things that are empty to be displayed
$use_expect = true; // Do you want use expect values in the data
$api_sensor_display_history = 10 * 60; // seconds per history sample to return to the browser - each point will be the average of this amount of time in seconds

$control_temperature = true;
$control_humidity = true;

$sensors = [ ];
$sensors [] = ( object ) [ 
		"name" => "PI",
		"label" => "PI",
		"type" => "PI",
		"colour" => "#660000"
];
// $sensors [] = ( object ) [
// "name" => "ZONE1",
// "label" => "ZONE1",
// "type" => "EMPTY", // EMPTY, DS18B20, DHT11 or DHT22
// "colour" => "#609" // Purple
// ];
// $sensors [] = ( object ) [
// "name" => "ZONE2",
// "label" => "ZONE2",
// "type" => "EMPTY", // EMPTY, DS18B20, DHT11 or DHT22
// "colour" => "#00c" // Dark Blue
// ];
// $sensors [] = ( object ) [
// "name" => "ZONE3",
// "label" => "ZONE3",
// "type" => "EMPTY", // EMPTY, DS18B20, DHT11 or DHT22
// "colour" => "#66f" // Light blue
// ];
// $sensors [] = ( object ) [
// "name" => "ZONE4",
// "label" => "ZONE4",
// "type" => "EMPTY", // EMPTY, DS18B20, DHT11 or DHT22
// "colour" => "#99f" // Very light blue
// ];
// $sensors [] = ( object ) [ // This is the 5th sensor for the CO2 monitor in the Air Zone (AZ)
// "name" => "ZONE2", // Generic air zone
// "type" => "EMPTY" // EMPTY or MH-Z19B
// ];
$sensors [] = ( object ) [ // This sensor is for display purposes
		"name" => "EXPECT",
		"label" => "EXPECT",
		"type" => "EXPECT",
		"colour" => "#fac" // Pink
];

$triggers = [ ];
// $triggers [] = ( object ) [
// "name" => "T1", // Generally used for heat
// "label" => "T1",
// "type" => "EMPTY", // EMPTY, SSR, iSSR or LED
// "colour" => "#c00" // Dark red
// ];
// $triggers [] = ( object ) [
// "name" => "T2", // Generally used for light
// "label" => "T2",
// "type" => "EMPTY", // EMPTY, SSR, iSSR or LED
// "colour" => "#fc0" // Orange
// ];
// $triggers [] = ( object ) [
// "name" => "T3",
// "label" => "T3",
// "type" => "EMPTY", // EMPTY, SSR, iSSR or LED
// "colour" => "#ccc"
// ];
// $triggers [] = ( object ) [
// "name" => "T4",
// "label" => "T4",
// "type" => "EMPTY", // EMPTY, SSR, iSSR or LED
// "colour" => "#ccc"
// ];
// $triggers [] = ( object ) [
// "name" => "T5",
// "label" => "T5",
// "type" => "EMPTY", // EMPTY, SSR, iSSR or LED
// "colour" => "#ccc"
// ];
// $triggers [] = ( object ) [
// "name" => "T6",
// "label" => "T6",
// "type" => "EMPTY", // EMPTY, SSR, iSSR or LED
// "colour" => "#ccc"
// ];

$conditions = [ ];
// $conditions [] = "T1 IF [[ZONE1.TEMPERATURE]] < [[EXPECT.TEMPERATURE]]";
// $conditions [] = "T2 IF [[EXPECT.LIGHT]] == 'SUN'";
// $conditions [] = "BAD_TRIGGER_TEST IF [[ZONE1.TEMPERATURE]] < [[EXPECT.TEMPERATURE]]";
// $conditions [] = "T6 IF [[BAD_SENSOR_TEST]]";

$graphs = [ ];
// $graphs[] = "temperature.ZONE1";
// $graphs[] = "humidity.ZONE1";

$sensor_pin_0 = 99;
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

$install_tag = "#GSTATION";

$outlier_temperature_min = 12;
$outlier_temperature_max = 35;
$outlier_humidity_min = 15;
$outlier_humidity_max = 95;

/**
 * START REBUILD
 */
$rebuild_from = "Simulation"; // 'Simulation', 'Demands' or filename in the locations directory, eg 'SZ.Malkerns.json'
/**
 * END REBUILD
 */

/**
 * START FILE ENVIRONMENT
 */
$season_adjust_days = 0; // If you want to move forward in the season, add this many days to the actual forcast. if set to 31, Real January 1 will be like February 1 at your location.
$timezone_adjust_hours = 0; // If you want to move forward in the day (because your location is suitably ahead of you) add this many hours. If set to 2, Real 07:30 will be like 09:30 at your location.
/**
 * END FILE ENVIRONMENT
 */

/**
 * START EXPECT ENVIRONMENT
 */
$expect_solstice = "0621"; // When should the expect ramping start.
$expect = [ ];
/**
 * END EXPECT ENVIRONMENT
 */

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
$solstice_temp_delta_days = 60; // The number of days after the solstice that the temperatures will peak
/**
 * END SIMULATION ENVIRONMENT
 */

/**
 * START BULKSMS SETUP
 */
$bulksms_username = ""; // BulkSMS is used to send info on status bits, go set up an account, it's cool.
$bulksms_password = "";
$bulksms_sender = "TxtsRFun";
$bulksms_notify = "447000000000"; // This is where text alerts will go.
/**
 * END BULKSMS
 */

/**
 * START PUSHOVER SETUP
 */
$pushover_user_key = ""; // you
$pushover_api_token = ""; // the application
$pushover_server_url = ""; // The URL to go to the server in the message, must be accessible from your device
$pushover_server_title = "Go to $loc"; // The text that will be shown for the link
/**
 * END PUSHOVER
 */

/**
 * START ALERT SETUP
 */
$alert_sunrise = false; // can be false, "ALL", or a comma separated string of: SMS, PUSHOVER
$alert_sunset = false; // can be false, "ALL", or a comma separated string of: SMS, PUSHOVER
$alert_tod = false; // can be false, "ALL", or a comma separated string of: SMS, PUSHOVER
$alert_alarm = "PUSHOVER"; // can be false, "ALL", or a comma separated string of: SMS, PUSHOVER
/**
 * END ALERT
 */

/**
 * You REALLY shouldn't need to be fiddling below here
 */
$app_title = "gStation"; // makes the browser name properly
$log_level = 3; // LL_INFO
$db_server = "localhost"; // Database things. Don't be editing
$db_name = "gs";
$db_user = "gs_user";
$db_pass = "gs_passwd";
// $temperature_buffer = 0.1; // used to soften direction noise
?>
