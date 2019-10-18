<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

$ts = timestampNow ();

echo "\nProcessing weather at " . timestampFormat ( $ts, "Y-m-d\TH:i:s T" ) . "\n";
echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n\n";

clearSensorLogger ();

echo "Retrieving historic data from Dark Sky\n";
getDarkSkyApiData ( $force_api_history );
echo "\n";

echo "Rebuilding data model\n";
rebuildDataModel ();

echo "\n";

echo "Update complete\n\n";

$model = getModel ();
echo "June 21:\n" . ob_print_r ( $model ["0621"] );
echo "\n";
echo "December 21:\n" . ob_print_r ( $model ["1221"] );
echo "\n";

$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";
?>