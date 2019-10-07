<?php
ini_set ( 'max_execution_time', 5 * 60 );
include_once ("functions.php");

$ts = timestampNow ();
$day = timestampFormat ( $ts, "d" ); // 4;
$mon = timestampFormat ( $ts, "m" ); // 10;

echo "\nChecking status at " . timestampFormat ( $ts, "Y-m-d\TH:i:sT" ) . "\n";
echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n";

tick ();

$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";

?>