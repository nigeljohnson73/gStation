<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

$ts = timestampNow ();
$day = timestampFormat ( $ts, "d" ); // 4;
$mon = timestampFormat ( $ts, "m" ); // 10;

echo "\nChecking status at " . timestampFormat ( $ts, "Y-m-d\TH:i:s T" ) . "\n";
echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n\n";

logger ( LL_INFO, "tick(): started" );
$call_delay = 5;
$last_tick = 55;

function nowSecond() {
	return timestampFormat ( timestampNow (), "s" ) + 0;
}

$quiet = false;
while ( nowSecond () <= $last_tick ) {
	echo "tick()\n";
	tick ( $quiet );
// 	if (nowSecond () < ($last_tick - $call_delay)) {
		logger ( LL_EDEBUG, "tick(): sleep" );
		sleep ( $call_delay );
// 	} else {
// 		echo "tick(): not time for next loop\n";
// 	}
	$quiet = true;
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