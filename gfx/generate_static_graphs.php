<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

foreach ( $graphs as $g ) {
	ob_start ();
	list ( $what, $zone ) = explode ( ".", $g );
	$ofn = dirname ( __FILE__ ) . "/static_graph_" . $what . "_" . $zone . ".png";
	$im = drawMeasuredGraph ( $what, $zone );
	imagepng ( $im, $ofn );
	imagedestroy ( $im );
	ob_end_clean ();
}

ob_start ();
$ofn = dirname ( __FILE__ ) . "/static_graph_daylight_scheduled.png";
include (dirname ( __FILE__ ) . "/graph_daylight_scheduled.php");
ob_end_clean ();

ob_start ();
$ofn = dirname ( __FILE__ ) . "/static_graph_humidity_scheduled.png";
include (dirname ( __FILE__ ) . "/graph_humidity_scheduled.php");
ob_end_clean ();

ob_start ();
$ofn = dirname ( __FILE__ ) . "/static_graph_sun_scheduled.png";
include (dirname ( __FILE__ ) . "/graph_sun_scheduled.php");
ob_end_clean ();

ob_start ();
$ofn = dirname ( __FILE__ ) . "/static_graph_temperature_scheduled.png";
include (dirname ( __FILE__ ) . "/graph_temperature_scheduled.php");
ob_end_clean ();

?>