<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $loc;
$legend = "$loc Sunrise Time";
$ndays = 365;
$data = getSunData ( $ndays );
unset($data["daylight"]);
unset($data["sunset"]);


$min_y = floor ( graphValMin ( $data ) );
$max_y = ceil ( graphValMax ( $data ) );

$y_ticks = array ();
for($i = $min_y; $i <= $max_y; $i ++) {
	$y_ticks [$i] = sprintf ( "%02d", $i ) . ":00";
}

$x_ticks = 12;
$x_subticks = 0;

$im = drawTimeGraph ( $data, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks, "Y-m-d" );

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>