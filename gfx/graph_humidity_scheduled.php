<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $loc, $local_timezone;
$legend = "Humidity Schedule";

$arr = array (
		"humidityHigh",
		"humidityLow"
);
$data = getModeledDataFields ( $arr );

$min_y = floor ( graphValMin ( $data ) );
$max_y = ceil ( graphValMax ( $data ) );

$y_ticks = array ();
for($i = $min_y; $i <= $max_y; $i ++) {
	$y_ticks [$i] = sprintf ( "% 2d", $i ) . "%";
}

$x_ticks = 12;
$x_subticks = 0;

$tsnow = timestampNow ();
// $pinpoint_act = array ();
// $pinpoint_act ["x"] = timestamp2Time ( $tsnow );
// $pinpoint_act ["y"] = getConfig ( "humidity" );

$env = json_decode(getConfig("env"));
$pinpoint_dem = array ();
$pinpoint_dem ["x"] = timestamp2Time ( $tsnow );
$pinpoint_dem ["y"] = ((array)$env)["DEMAND.HUMIDITY"];

$pinpoint = array (
		//( object ) $pinpoint_act,
		( object ) $pinpoint_dem
);

// echo "<pre>" . ob_print_r ( $pinpoint ). "</pre>";
$im = drawTimeGraph ( $data, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks, "M d", $pinpoint );

if ($ofn . "" == "") {
	header ( 'Content-type: image/png' );
	imagepng ( $im );
} else {
	imagepng ( $im, $ofn );
}
imagedestroy ( $im );

?>
