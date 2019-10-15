<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

function getLocalTemps() {
	global $mysql;
	// $res = $mysql->query ( "SELECT * FROM temperature_logger where temperature != 999 ORDER BY entered desc limit 10" );
	$res = $mysql->query ( "SELECT * FROM temperature_logger where temperature != 999" );
	// var_dump($res);
	if (is_array ( $res ) && count ( $res ) > 0) {
		$dem = array ();
		$act = array ();
		foreach ( $res as $r ) {
			// echo ob_print_r($r);
			$dem [timestamp2Time ( $r ["entered"] )] = $r ["demanded"];
			$act [timestamp2Time ( $r ["entered"] )] = $r ["temperature"];
		}
		return array (
				"temperature" => $act,
				"demanded" => $dem
		);
	}
	return null;
}

$legend = "Measured Temperature over the last 24 hours";
$temps = getLocalTemps ();
foreach ( $temps as $k => $v ) {
	$temps [$k] = decimateArray ( $v, 5 );
}

if (! $temps) {
	$legend = "A pretty little sine wave (as there is no real data yet)";

	$nmins = 60 * 24;
	$deg_step = 360 / $nmins;
	$mid_temp = 22;
	$delt_temp = 2.2;
	$tnow = time ();
	for($i = 0; $i < $nmins; $i ++) {
		$dem [$tnow - ($i * 60)] = $mid_temp + $delt_temp * sin ( deg2rad ( $i * $deg_step ) );
		$act [$tnow - ($i * 60)] = $mid_temp + $delt_temp * cos ( deg2rad ( $i * $deg_step ) );
	}
	$temps = array (
			"temperature" => $act,
			"demanded" => $dem
	);
}

$min_y = floor ( graphValMin ( $temps ) );
$max_y = ceil ( graphValMax ( $temps ) );
$y_ticks = array ();
for($i = $min_y; $i <= $max_y; $i ++) {
	$y_ticks [$i] = $i . "C";
}

$x_ticks = 24;
$x_subticks = 1;
$im = drawTimeGraph ( $temps, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks );

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>