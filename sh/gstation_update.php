<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

$tsnow = timestampNow ();

echo "\nChecking status at " . timestampFormat ( $tsnow, "Y-m-d\TH:i:s T" ) . "\n";
if ($darksky_key != "") {
	echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n\n";
} else {
	echo "Location: SIMULATED ENVIRONMENT\n\n";
}

clearSensorLogger ();

echo "Retrieving historic data from Dark Sky\n";
getDarkSkyApiData ( $force_api_history );
echo "\n";

echo "Rebuilding data model\n";
rebuildDataModel ();

echo "\n";

echo "Update complete\n\n";

$model = getModel ();
echo "Rows in Model: " . count ( $model ) . "\n";
echo "June 21:\n" . ob_print_r ( $model ["0621"] );
echo "\n";
echo "December 21:\n" . ob_print_r ( $model ["1221"] );
echo "\n";

// $model = getModel ( "2019-07-07" );
// echo "August 07:\n" . ob_print_r ( $model );
// echo "\n";

// $model = getModel ( array (
// 		"2019-03-23",
// 		"2019-08-29"
// ) );
// echo "March 23, and August 29:\n" . ob_print_r ( $model );
// echo "\n";

$str = $logger->getString ();
if (strlen ( trim ( $str ) ) == 0) {
	$str = "*** NO LOG OUTPUT ***";
} else {
	$str = trim ( $str ) . "\n*** END OF LOG ***";
}
echo "Log output:\n";
echo $str . "\n";
?>