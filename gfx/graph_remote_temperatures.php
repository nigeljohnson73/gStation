<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $loc;
$legend = "$loc Modelled Daily Hi/Low Temperatures";

$model = getModel ();
$yr = timestampFormat ( timestampNow (), "Y" );
$hi = array ();
$lo = array ();
foreach ( $model as $k => $v ) {
	$time = timestamp2Time ( $yr . $k );
	$hi [$time] = $v->temperatureHigh;
	$lo [$time] = $v->temperatureLow;
}

$data = array (
		"high" => $hi,
		"low" => $lo
);

$min_y = floor ( graphValMin ( $data ) );
$max_y = ceil ( graphValMax ( $data ) );

$y_ticks = array ();
for($i = $min_y; $i <= $max_y; $i ++) {
	$y_ticks [$i] = sprintf ( "% 2d", $i ) . "C";
}

$x_ticks = 12;
$x_subticks = 0;

$im = drawTimeGraph ( $data, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks, "M d" );

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>