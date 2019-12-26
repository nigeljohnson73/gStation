<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

ob_start ();
$zone = "ZONE1";
$ofn = dirname ( __FILE__ ) . "/static_graph_humidity_".$zone.".png";
include (dirname ( __FILE__ ) . "/graph_humidity_measured.php");
ob_end_clean ();

ob_start ();
$zone = "ZONE1";
$ofn = dirname ( __FILE__ ) . "/static_graph_temperature_".$zone.".png";
include (dirname ( __FILE__ ) . "/graph_temperature_measured.php");
ob_end_clean ();

ob_start ();
if ($zone != "") {
	unset ( $zone );
}
$ofn = dirname ( __FILE__ ) . "/static_graph_daylight_scheduled.png";
include (dirname ( __FILE__ ) . "/graph_daylight_scheduled.php");
ob_end_clean ();

ob_start ();
if ($zone != "") {
	unset ( $zone );
}
$ofn = dirname ( __FILE__ ) . "/static_graph_humidity_scheduled.png";
include (dirname ( __FILE__ ) . "/graph_humidity_scheduled.php");
ob_end_clean ();

ob_start ();
if ($zone != "") {
	unset ( $zone );
}
$ofn = dirname ( __FILE__ ) . "/static_graph_sun_scheduled.png";
include (dirname ( __FILE__ ) . "/graph_sun_scheduled.php");
ob_end_clean ();

ob_start ();
if ($zone != "") {
	unset ( $zone );
}
$ofn = dirname ( __FILE__ ) . "/static_graph_temperature_scheduled.png";
include (dirname ( __FILE__ ) . "/graph_temperature_scheduled.php");
ob_end_clean ();

?>