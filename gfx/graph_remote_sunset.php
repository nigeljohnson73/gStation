<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $loc;

$legend = "$loc Modelled Daily Sunset Time (UTC)";

$arr = array (
		"sunsetOffset",
//		"sunriseOffset"
);
$data = getModeledDataFields ( $arr );
foreach ( $data as $k => $v ) {
	foreach($v as $kk => $vv)
		$data [$k] [$kk] = $vv / 3600;
}

$min_y = floor ( graphValMin ( $data ) );
$max_y = ceil ( graphValMax ( $data ) );

$y_ticks = array ();
for($i = $min_y; $i <= $max_y; $i ++) {
	$y_ticks [$i] = sprintf ( "%02d", $i ) . ":00";
}

$x_ticks = 12;
$x_subticks = 0;

$im = drawTimeGraph ( $data, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks, "M d" );

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>