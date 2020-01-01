<?php

$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

echo timestampFormat(timestampNow(), "H:i:s"). ": generateStaticGraphs(): Process started\n";

foreach ( $graphs as $g ) {
	//ob_start ();
	list ( $what, $zone ) = explode ( ".", $g );
	$ofn = dirname ( __FILE__ ) . "/static_graph_" . $what . "_" . $zone . ".png";
	echo timestampFormat(timestampNow(), "H:i:s"). ": generateStaticGraphs(): Creating '$ofn'\n";
	$im = drawMeasuredGraph ( $what, $zone );
	imagepng ( $im, $ofn );
	imagedestroy ( $im );
	//ob_end_clean ();
	//ob_end_flush ();
	sleep(15); // So the system can catch up with itself
}

//ob_start ();
$ofn = dirname ( __FILE__ ) . "/static_graph_daylight_scheduled.png";
echo timestampFormat(timestampNow(), "H:i:s"). ": generateStaticGraphs(): Creating '$ofn'\n";
include (dirname ( __FILE__ ) . "/graph_daylight_scheduled.php");
//ob_end_clean ();
//ob_end_flush ();
sleep(5);

//ob_start ();
$ofn = dirname ( __FILE__ ) . "/static_graph_humidity_scheduled.png";
echo timestampFormat(timestampNow(), "H:i:s"). ": generateStaticGraphs(): Creating '$ofn'\n";
include (dirname ( __FILE__ ) . "/graph_humidity_scheduled.php");
//ob_end_clean ();
//ob_end_flush ();
sleep(5);

//ob_start ();
$ofn = dirname ( __FILE__ ) . "/static_graph_sun_scheduled.png";
echo timestampFormat(timestampNow(), "H:i:s"). ": generateStaticGraphs(): Creating '$ofn'\n";
include (dirname ( __FILE__ ) . "/graph_sun_scheduled.php");
//ob_end_clean ();
//ob_end_flush ();
sleep(5);

//ob_start ();
$ofn = dirname ( __FILE__ ) . "/static_graph_temperature_scheduled.png";
echo timestampFormat(timestampNow(), "H:i:s"). ": generateStaticGraphs(): Creating '$ofn'\n";
include (dirname ( __FILE__ ) . "/graph_temperature_scheduled.php");
//ob_end_clean ();
//ob_end_flush ();
sleep(5);

echo timestampFormat(timestampNow(), "H:i:s"). ": generateStaticGraphs(): Process complete\n";

// Exit with a status code so the monitor will restart it later
exit(99);
?>
