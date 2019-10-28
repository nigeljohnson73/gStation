<?php
$local_timezone = "Europe/London"; // Where are you locally based for time references

/**
 * START SIMULATION ENVIRONMENT
 */
$summer_solstice = "0621"; // June 21 is summer solstice in the northern hemisphere
$high_temperature_max = 28.8; // In the summer, this is the max temp
$high_temperature_min = 21.2; // In the winter, this is the max temp
$low_temperature_max = 12.3; // In the summer, this is the lowest temperature
$low_temperature_min = 9.5; // In the winter, this is the lowest temperature
$sunset_min = 17 + (32 / 60); // In the winter, this is the time of sunset 17:32 UTC
$sunset_max = 19 + (21 / 60); // In the summer, this is the time of sunset 19:21 UTC
$daylight_max = 14.65; // In the summer, this is how many hours of daylight there will be
$daylight_min = 9.25; // In the winter, this is how many hours of daylight there will be
/**
 * END SIMULATION ENVIRONMENT
 */

/**
 * START DARK SKY SETUP
 */
$darksky_key = ""; // You can sign up for a free dark sky account to get location based weather information: https://darksky.net/dev/docs
$api_call_cap = 370; // If you have a free account, leave this under 900 and don't run the update more than once in a day until you're up to date

// The place you want your station to be remotely located at
$lat = "-26.549711"; // the latitude of the place you want to mimic
$lng = "31.197664"; // the longitude of the place you want to mimic
$loc = "gStation"; // Just to give it a name could be the name of the actual place

$season_adjust_days = 0; // If you want to move forward in the season, add this many days to the actual forcast. if set to 31, Real January 1 will be like February 1 at your location.
$timeszone_adjust_hours = 0; // If you want to move forward in the day (because your location is suitably ahead of you) add this many hours. If set to 2, Real 07:30 will be like 09:30 at your location.
$yr_history = 25; // How many years to go back for historic data. The further back the smoother 'today' will be but more processing and database is required for the data model.
$force_api_history = 5; // Forecast data is replaced with historic data over the few days after it happened, this flag will force API calls based on this number of days
$smoothing_days = 15; // Used to smooth the measurement data even more than history alone. Used as a sliding +/- window from 'today' 2n+1 points are used to calculate 'today'
$smoothing_loops = 3;// Using more loops is much more computationally intensive, but yeilds a much smoother outcome
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
