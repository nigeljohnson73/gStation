<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

$ts = timestampNow ();
$day = timestampFormat ( $ts, "d" ); // 4;
$mon = timestampFormat ( $ts, "m" ); // 10;

echo "\nChecking status at " . timestampFormat ( $ts, "Y-m-d\TH:i:sT" ) . "\n";
echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n";

tick ();

$use_dht = false;
if ($use_dht) {
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
} else {
	$cmd = "cat /sys/bus/w1/devices/28-*/w1_slave 2>&1";
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	//$last_line = "67 01 4c 46 7f ff 0c 10 c4 t=22437";
	// $last_line = "T:25.9|H:53.3";
	// $c = ob_get_contents ();
	ob_end_clean ();

	@list ( $dummy, $temperature ) = explode ( " t=", $last_line );
	if ($temperature == "") {
		echo "Unable to determine local temp\n\n";
		ob_start ();
		@system ( "echo '999C ".getConfig("STATUS", "---")."' > /tmp/oled.txt", $retval );
		ob_end_clean ();
	} else {
		$temperature = ($temperature + 0) / 1000;
		ob_start ();
		$t = round($temperature, 1);
		@system ( "echo '".$t."C ".getConfig("STATUS", "---")."' > /tmp/oled.txt", $retval );
		ob_end_clean ();
		
		$ret = $mysql->query ( "REPLACE INTO th_logger (temperature, humidity) VALUES (?, ?)", "dd", array (
				$temperature,
				999
		) );

		echo "Local temperature: " . $temperature . "C\n";
		echo "\n";
	}
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