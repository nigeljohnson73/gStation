<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

$ts = timestampNow ();
$day = timestampFormat ( $ts, "d" ); // 4;
$mon = timestampFormat ( $ts, "m" ); // 10;

echo "\nChecking status at " . timestampFormat ( $ts, "Y-m-d\TH:i:sT" ) . "\n";
echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n";

tick ();

$cmd = "python3 " . dirname ( __FILE__ ) . "/dht22.py 2>&1";
ob_start();
//passthru($cmd);
$last_line = @system ( $cmd, $retval );
$c = ob_get_contents ();
ob_end_clean ();

logger ( LL_INFO, "system command: '" . $cmd . "'" );
// logger ( LL_INFO, "system output: '" . $c . "'" );
logger ( LL_INFO, "last line: '" . $last_line . "'" );
$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";

?>