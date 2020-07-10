<?php
include_once (dirname ( __FILE__ ) . "/../../functions.php");
$today = getPostArgs ( "today", timestampNow () );
$ret = startJsonRespose ();

$ret->data = [ ];
$ret->data [] = getTodayModelParamDataset ( "daylightHours", 0.25, "#c00", $today );
$ret->data [] = getModelParamDataset ( "daylightHours", "Daylight", "#090", true );

$ret->xlabels = [ ];
$model = getModel ();
foreach ( $model as $day => $data ) {
	$data = $data;
	$ret->xlabels [] = timestampFormat ( timestampFormat ( timestampNow (), "Y" ) . $day, "Y-m-d\TH:i:s\Z" );
}
$ret->model = $model;

endJsonRespose ( $ret, true );
?>
