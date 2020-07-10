<?php
include_once (dirname ( __FILE__ ) . "/../../functions.php");
$ot = new ProcessTimer ();
$ret = startJsonRespose ();
$ret->history = ( object ) [ ];
global $api_sensor_display_history;

// Timers
$activity_db = 0;
$activity_proc = 0;

echo "Sample point duration : " . durationStamp ( $api_sensor_display_history ) . "\n";

$pt = new ProcessTimer ();
$res = getSpecificHistoryData ( "PI", "TEMPERATURE" );
$activity_db = $pt->duration ();

$pt = new ProcessTimer ();
if ($res && count ( $res )) {
	$ret->history = processHistoryData ( $res );
}
$activity_proc = $pt->duration ();

echo "Database activity took " . durationStamp ( $activity_db ) . "\n";
echo "Processing activity took " . durationStamp ( $activity_proc ) . "\n";
echo "Total call duration: " . durationStamp ( $ot->duration () ) . "\n";

endJsonRespose ( $ret, true );
?>
