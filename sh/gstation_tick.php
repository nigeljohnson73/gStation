<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

$ts = timestampNow ();
$day = timestampFormat ( $ts, "d" ); // 4;
$mon = timestampFormat ( $ts, "m" ); // 10;

echo "\nChecking status at " . timestampFormat ( $ts, "Y-m-d\TH:i:sT" ) . "\n";
echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n";

tick ();

$cmd = "python3 " . dirname ( __FILE__ ) . "/dht22.py 2>&1";
ob_start ();
$last_line = @system ( $cmd, $retval );
// $last_line = "T:25.9|H:53.3";
// $c = ob_get_contents ();
ob_end_clean ();

if ($last_line [0] != 'T') {
	echo "Unable to determine local temp/humidity\n\n";
} else {
	@list ( $temperature, $humidity ) = @explode ( "|", $last_line );
	$temperature = @explode ( ":", $temperature ) [1] + 0;
	$humidity = @explode ( ":", $humidity ) [1] + 0;

	$ret = $mysql->query ( "REPLACE INTO th_logger (temperature, humidity) VALUES (?, ?)", "dd", array (
			$temperature,
			$humidity
	) );
	
	echo "Local temperature: " . $temperature . "C\n";
	echo "Local humidity: " . $humidity . "%\n";
	echo "\n";
}

logger ( LL_DEBUG, "system command: '" . $cmd . "'" );
logger ( LL_DEBUG, "last line: '" . $last_line . "'" );
$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";

?>