<?php
//$sensor_age = 30; // sensor data older than this in seconds will be ignored
//$sensor_age_alarm = 2*60; // If you haven't seen any data for a sensor for this long, assume it's dead.

// Update this to where you are and the name you want to see in the browser
//$loc = getHostname(); // "gsDev"; // Used for the browser and alerts

$conditions = [ ];
// $conditions [] = "T1 IF [[ZONE1.TEMPERATURE]] < [[EXPECT.TEMPERATURE]]";       // Root zone up to expected temperature
// $conditions [] = "T2 IF [[EXPECT.LIGHT]] == 'SUN'";                            // Light on if it's day time
// $conditions [] = "T3 IF [[ZONE2.TEMPERATURE]] > ([[ZONE3.TEMPERATURE]] + 5)";  // Vent the atmosphere if its 5 degrees over ambient
// $conditions [] = "T3 IF [[DATA.HOUR]] >= 0.1 && [[DATA.HOUR]] < 0.2";          // vent the air overnight regarldess of temperature

$rebuild_from = "UK.Weybridge.json"; // 'Demands', 'Simulation',  or a filename in the locations folder for example 'SZ.Malkerns.json'
// $season_adjust_days = 0; // Adjust the file based data model
// $timezone_adjust_hours = 0; // Adjust the file based data model

// This config will have an 18hr day for 14 days, then ramp down to 12 hours over the following 21 days.
// There will be a small temp drop over the day length cycle as well
// $expect_solstice = "0621"; // When should the expect simulation start (Format: MMDD)
// $expect = [ ];
// $expect [] = ( object ) [
// 		"period_length" => 14,
// 		"sunset" => 21 + (59/60) + (59/(60 * 60)),
// 		"daylight_hours" => 18.1,
// 		"day_temperature" => 24.1,
// 		"night_temperature" => 21.5,
// 		"day_humidity" => 40.5,
// 		"night_humidity" => 45.5
// ];
//
// $expect [] = ( object ) [
// 		"period_length" => 21,
// 		"sunset" => 21 + (59/60) + (59/(60 * 60)),
// 		"daylight_hours" => 18.1,
// 		"day_temperature" => 24.1,
// 		"night_temperature" => 21.5,
// 		"day_humidity" => 40.5,
// 		"night_humidity" => 45.5
// ];

// $expect [] = ( object ) [
// 		"sunset" => 21 + (59/60) + (59/(60 * 60)),
// 		"daylight_hours" => 12.1,
// 		"day_temperature" => 23.5,
// 		"night_temperature" => 19.5,
// 		"day_humidity" => 30.5,
// 		"night_humidity" => 40.5
// ];

// // If you're using the envronmental simulation, this is where you configure the paramaters
// $summer_solstice = "0621"; // (Format: MMDD)
// $solstice_temp_delta_days = 60;
// $day_temperature_summer = 27.5;
// $day_temperature_winter = 19.5;
// $night_temperature_summer = 21.5;
// $night_temperature_winter = 15.5;
// $sunset_winter = 21 + (00 / 60);
// $sunset_summer = 21 + (00 / 60);
// $daylight_summer = 16 + (30 / 60);
// $daylight_winter = 11 + (30 / 60);
// $night_humidity_winter = 60;
// $day_humidity_winter = 55;
// $night_humidity_summer = 50;
// $day_humidity_summer = 30;

// If you're using bulksms for alerting, configure it here
$bulksms_username = "YourUserName";
$bulksms_password = "Pa55w0rd";
$bulksms_sender = "TxtsRFun";
$bulksms_notify = "447000000000";

// If you're using Pushover for alerting, configure it here
$pushover_user_key = ""; // you
$pushover_api_token = ""; // the application
$pushover_server_url = ""; // The URL to go to the server in the message, must be accessible from your device
//$pushover_server_title = "Go to $loc"; // The text that will be shown for the link

// $alert_sunrise = false;
// $alert_sunset = false;
// $alert_tod = false;
//$alert_alarm = "PUSHOVER";
?>