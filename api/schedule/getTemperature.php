<?php
include_once (dirname ( __FILE__ ) . "/../../functions.php");
$today = getPostArgs ( "today", timestampNow () );
$ret = startJsonRespose ();

$ret->data = [ ];
$ret->data [] = getTodayModelParamDataset ( "temperatureDay", "temperatureNight", "#c00", $today );
$ret->data [] = getModelParamDataset ( "temperature", "Day", "#090" );
$ret->data [] = getModelParamDataset ( "temperature", "Night", "#609" );

$ret->xlabels = [ ];
$model = getModel ();
foreach ( $model as $day => $data ) {
	$data = $data;
	$ret->xlabels [] = timestampFormat ( timestampFormat ( timestampNow (), "Y" ) . $day, "Y-m-d\TH:i:s\Z" );
}
$ret->model = $model;

endJsonRespose ( $ret, true );
?>
