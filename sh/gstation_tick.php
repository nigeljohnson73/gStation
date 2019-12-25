<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

function tick() {
	global $mysql;

	// Set the parameters for the tick
	$tsnow = timestampNow ();
	
	// Prepare the storage for later
	$data = array (); // Whre we will store sensors and trigger data

	// Do the setup and clear up
	setupTables ();

	$sensors = gatherSensors ();
	foreach ( $sensors as $s ) {
		$name = $s->name;
		$param = $s->param;
		$val = $s->val;
		$data [strtoupper ( $name . "." . $param )] = $val;
		$ret = $mysql->query ( "REPLACE INTO sensors (event, name, param, value) VALUES (?, ?, ?, ?)", "isss", array (
				$tsnow,
				$name,
				$param,
				$val
		) );
	}
	print_r ( $trig );
}

// $guid = GUID();
$tsnow = timestampNow ();

// echo "GUID: ".$guid." - (".strlen($guid).")\n";
echo "TS:   " . $tsnow . " - (" . strlen ( $tsnow ) . ")\n";
tick ();

exit ( 0 );

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
	// echo " Adding: $s (" . timestampFormat ( time2Timestamp ( floor ( $s ) ), "H:i:s" ) . ")\n";
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
	$tsnow = timestampNow ();
	echo "TICK: " . timestampFormat ( $tsnow, "H:i:s " ) . ": Start loop\n";
	tick ( $quiet );
	$quiet = true;

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