<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

$ts = timestampNow ();
$day = timestampFormat ( $ts, "d" ); // 4;
$mon = timestampFormat ( $ts, "m" ); // 10;

echo "\nChecking status at " . timestampFormat ( $ts, "Y-m-d\TH:i:sT" ) . "\n";
echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n";

$call_delay = 10;

$quiet = false;
while ((timestampFormat(timestampNow(), "s")+0) <= 56) {
	tick ($quiet);
	logger ( LL_EDEBUG, "tick(): sleep" );
	sleep($call_delay);
	$quiet = true;
}

logger ( LL_INFO, "tick(): completed" );
// logger ( LL_DEBUG, "system command: '" . $cmd . "'" );
// logger ( LL_DEBUG, "last line: '" . $last_line . "'" );

$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";

?>