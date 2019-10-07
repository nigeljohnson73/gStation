<?php
ini_set ( 'max_execution_time', 23 * 60 * 60 ); // this script can run forever... ish
include_once ("functions.php");

// Iddeally this:
// $yr_history = 20;
// $dy_history = 5;
// $dy_forecast = 2;

// This for full year
// $yr_history = 20;
// $dy_history = 365;
// $dy_forecast = 2;

$ts = timestampNow ();
$day = timestampFormat ( $ts, "d" );
$mon = timestampFormat ( $ts, "m" );

echo "\nProcessing weather at " . timestampFormat ( $ts, "Y-m-d\TH:i:sT" ) . "\n";
echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n";

print_r ( getData ( $lat, $lng, $day, $mon, true, $force_local_gets ) );

$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";
?>