<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

logger ( LL_INFO, "tick(): started" );
$call_delay = 5;
$last_tick = 59;

// function nowSecond() {
// return timestampFormat ( timestampNow (), "s" ) + 0;
// }

$start = microtime ( true );
$end = timestamp2Time ( timestampFormat ( timestampNow (), "YmdHi" ) . sprintf ( "%02d", $last_tick ) );
echo "Building tick scheduler\n";
echo "    Start: " . timestampFormat ( time2Timestamp ( floor ( $start ) ), "Y-m-d H:i:s" ) . ", end: " . timestampFormat ( time2Timestamp ( floor ( $end ) ), "Y-m-d H:i:s" ) . ", step: " . $call_delay . "\n";

$secs = array ();
// echo "Starting point: " . floor ( $start ) . ", end point: " . $end . ", step: " . $call_delay . "\n";
for($s = floor ( $start ); $s <= $end; $s += $call_delay) {
	//echo "    Adding: $s (" . timestampFormat ( time2Timestamp ( floor ( $s ) ), "H:i:s" ) . ")\n";
	echo "    Adding: " . timestampFormat ( time2Timestamp ( floor ( $s ) ), "H:i:s" ) . "\n";
	$secs [] = $s;
}

$tsnow = timestampNow ();
// $day = timestampFormat ( $ts, "d" ); // 4;
// $mon = timestampFormat ( $ts, "m" ); // 10;

echo "\nChecking status at " . timestampFormat ( $tsnow, "Y-m-d\TH:i:s T" ) . "\n";
if ($darksky_key != "") {
	echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n\n";
} else {
	echo "Location: SIMULATED ENVIRONMENT\n\n";
}
clearSensorLogger ();

$quiet = false;
foreach ( $secs as $k => $s ) {
	$tsnow = timestampNow();
	echo "TICK: " . timestampFormat ( $tsnow, "H:i:s " ) . ": Start loop\n";
	tick ( $quiet );
	$quiet = true;

	$now = microTime ( true );
	$tsnow = time2Timestamp ( floor ( $now ) );

	echo "TICK: " . timestampFormat ( $tsnow, "H:i:s " ) . ": ";
	if (isset ( $secs [$k + 1] )) {
		$wake = $secs [$k + 1];
		$sleep = ($wake - $now);
		echo "need to sleep " . sprintf("%0.2f", $sleep) . " seconds\n";
		usleep ( $sleep * 1000000 );
	} else {
		echo "Job done\n";
	}
}

echo "tick(): complete\n\n";
logger ( LL_INFO, "tick(): completed" );

$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";

?>