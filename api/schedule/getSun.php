<?php
include_once (dirname ( __FILE__ ) . "/../../functions.php");
$today = getPostArgs ( "today", timestampNow () );
$ret = startJsonRespose ();

$ret->data = [ ];
$ret->data [] = getTodayModelParamDataset ( "sunriseOffset", "sunsetOffset", "#c00", $today );
$ret->data [] = getModelParamDataset ( "sunriseOffset", "Sunrise", "#090", true );
$ret->data [] = getModelParamDataset ( "sunsetOffset", "Sunset", "#609", true );

// Reset all of these to event times
foreach ( $ret->data as $series ) {
	foreach ( $series->data as $point ) {
		$point->t = timestampFormat ( time2Timestamp ( timestamp2Time ( $point->t ) + $point->y ), "Y-m-d\TH:i:s" ) . "Z";
		unset ( $point->y );
	}
}

$ret->xlabels = [ ];
$model = getModel ();
foreach ( $model as $day => $data ) {
	$data = $data;
	$ret->xlabels [] = timestampFormat ( timestampFormat ( timestampNow (), "Y" ) . $day, "Y-m-d\TH:i:s\Z" );
}
$ret->model = $model;

endJsonRespose ( $ret, true );
?>
