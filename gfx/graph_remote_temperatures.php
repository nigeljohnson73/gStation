<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $loc;
$legend = "$loc Average Daily Hi/Low Temperatures (over ".$yr_history." years)";
$ndays = 365;
$data = getTempData ( $ndays );
// unset($data["daylight"]);
// unset($data["sunrise"]);


$min_y = floor ( graphValMin ( $data ) );
$max_y = ceil ( graphValMax ( $data ) );

$y_ticks = array ();
for($i = $min_y; $i <= $max_y; $i ++) {
	$y_ticks [$i] = sprintf ( "% 2d", $i ) . "C";
}

$x_ticks = 13;
$x_subticks = 3;

$im = drawTimeGraph ( $data, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks, "Y-m-d" );

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>