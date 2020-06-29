<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

// // Delete from here
// $tsnow = timestampNow ();
// echo "Normalising Database\n";
// setupTables ();
// echo "Setting up GPIO\n";
// setupGpio ();
// tick ();
// exit ( 0 );
// // Delete to here

logger ( LL_DEBUG, "tick(): started" );
$call_delay = 5;
$last_tick = 59;

$start = microtime ( true );
$end = timestamp2Time ( timestampFormat ( timestampNow (), "YmdHi" ) . sprintf ( "%02d", $last_tick ) );
echo "Building tick scheduler\n";
echo "    Start: " . timestampFormat ( time2Timestamp ( floor ( $start ) ), "Y-m-d H:i:s" ) . ", end: " . timestampFormat ( time2Timestamp ( floor ( $end ) ), "Y-m-d H:i:s" ) . ", step: " . $call_delay . "\n";

$secs = array ();
// echo "Starting point: " . floor ( $start ) . ", end point: " . $end . ", step: " . $call_delay . "\n";
for($s = floor ( $start ); $s <= $end; $s += $call_delay) {
	// echo " Adding: $s (" . timestampFormat ( time2Timestamp ( floor ( $s ) ), "H:i:s" ) . ")\n";
	echo "    Adding: " . timestampFormat ( time2Timestamp ( floor ( $s ) ), "H:i:s" ) . "\n";
	$secs [] = $s;
}

$tsnow = timestampNow ();

// Do the setup and clear up
echo "tick(): Normalising Database\n";
setupTables ();
echo "tick(): Setting up GPIO\n";
setupGpio ();

echo "\ntick(): Startup at " . timestampFormat ( $tsnow, "Y-m-d\TH:i:s T" ) . "\n";
// if ($darksky_key != "") {
// 	echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n\n";
// } else {
// 	echo "Location: SIMULATED ENVIRONMENT\n\n";
// }
//clearSensorLogger ();

$quiet = false;
foreach ( $secs as $k => $s ) {
	$tsnow = timestampNow ();
	echo "tick(): " . timestampFormat ( $tsnow, "H:i:s " ) . ": Start loop\n";
	tick ( $quiet );
	$quiet = true;

	echo "\nChecking Alarm Status\n";
	checkAlarms ();
	
	echo "\nwriting env to oled file\n";
	$estr = getConfig("env");
	file_put_contents ( "/tmp/oled.json", $estr );
	print_r(json_decode($estr));
	echo "\n";

	$now = microTime ( true );
	$tsnow = time2Timestamp ( floor ( $now ) );
	
	echo "TICK: " . timestampFormat ( $tsnow, "H:i:s " ) . ": ";
	if (isset ( $secs [$k + 1] )) {
		$wake = $secs [$k + 1];
		$sleep = ($wake - $now);
		echo "need to sleep " . sprintf ( "%0.2f", $sleep ) . " seconds\n";
		usleep ( $sleep * 1000000 );
	} else {
		echo "Job done\n";
	}
}

echo "tick(): complete\n\n";
logger ( LL_DEBUG, "tick(): completed" );

$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";

?>
