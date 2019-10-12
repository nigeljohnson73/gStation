<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

$x = 640;
$y = 480;
$border = 40;

$im = imagecreatetruecolor ( $x, $y );
$dgrey = imagecolorallocate ( $im, 0xcc, 0xcc, 0xcc );
$lgrey = imagecolorallocate ( $im, 0xdd, 0xdd, 0xdd );
$grey = imagecolorallocate ( $im, 0xee, 0xee, 0xee );
$black = imagecolorallocate ( $im, 0x00, 0x00, 0x00 );
$red = imagecolorallocate ( $im, 0xff, 0x00, 0x00 );

function point($im, $x, $y, $colour, $dist = 1) {
	$points = array (
			$x - $dist,
			$y, // Point 1 (x, y)
			$x,
			$y + $dist, // Point 2 (x, y)
			$x + $dist,
			$y, // Point 3 (x, y)
			$x,
			$y - $dist // Point 4 (x, y)
	);

	return imagefilledpolygon ( $im, $points, count ( $points ) / 2, $colour );
}

function getTemps() {
	global $mysql;
	$res = $mysql->query ( "SELECT * FROM th_logger where temperature != 999" );
	// var_dump($res);
	if (is_array ( $res ) && count ( $res ) > 0) {
		$ret = array ();
		foreach ( $res as $r )
			// echo ob_print_r($r);
			$ret [timestamp2Time ( $r ["entered"] )] = $r ["temperature"];
		return $ret;
	}
	return null;
}

$legend = "Temperature over the last 24 hours";
$temps = getTemps ();
if (! $temps) {
	$legend = "A pretty little sine wave (as there is no real data yet)";

	$nmins = 60 * 24;
	$deg_step = 360 / $nmins;
	$mid_temp = 22;
	$delt_temp = 1.2;
	$tnow = time ();
	for($i = 0; $i < $nmins; $i ++) {
		$temps [$tnow - ($i * 60)] = $mid_temp + $delt_temp * sin ( deg2rad ( $i * $deg_step ) );
	}
}

// This doesn't work. needs a recompile on mac? dunno about raspian
// $font = imageloadfont(dirname(__FILE__)."/../fonts/andalemo.ttf");
$font = 4;

// Start with background
imagefill ( $im, 0, 0, $grey );

if ($temps) {
	// print_r($temps);
	$min_x = min ( array_keys ( $temps ) );
	$max_x = max ( array_keys ( $temps ) );
	$min_y = floor ( min ( array_values ( $temps ) ) );
	$max_y = ceil ( max ( array_values ( $temps ) ) );

	// Draw hour markers
	$assume_hours = 24;
	$x_step = ($x - 2 * $border) / ($assume_hours);
	for($i = 1; $i <= $assume_hours; $i ++) {
		imageline ( $im, $border + ($i * $x_step), $border, $border + ($i * $x_step), $y - $border, $lgrey );
	}

	// Draw hour markers
	$assume_hours = 12;
	$x_step = ($x - 2 * $border) / ($assume_hours);
	for($i = 1; $i <= $assume_hours; $i ++) {
		imageline ( $im, $border + ($i * $x_step), $border, $border + ($i * $x_step), $y - $border, $dgrey );
	}

	// Draw half degree lines
	for($i = $min_y; $i < $max_y; $i += 0.5) {
		$yv = (scaleVal ( $i, $min_y, $max_y ) * ($y - 2 * $border)) + $border;
		imageline ( $im, $border, $yv, $x - $border, $yv, $lgrey );
	}

	// Draw full degree lines
	for($i = $min_y; $i < $max_y; $i ++) {
		$yv = (scaleVal ( $i, $min_y, $max_y ) * ($y - 2 * $border)) + $border;
		imageline ( $im, $border, $yv, $x - $border, $yv, $dgrey );
	}

	// Draw Axes
	imageline ( $im, $border, $y - $border, $x - $border, $y - $border, $black );
	imageline ( $im, $border, $y - $border, $border, $border, $black );

	// Process in the data points
	foreach ( $temps as $k => $v ) {
		$xv = (scaleVal ( $k, $min_x, $max_x ) * ($x - 2 * $border)) + $border;
		$yv = $y - ((scaleVal ( $v, $min_y, $max_y ) * ($y - 2 * $border)) + $border);
		point ( $im, $xv, $yv, $red );
	}

	// Overall legend
	imagestring ( $im, $font, $border, $border / 2, $legend, $black );

	// Legend for temperatures
	imagestring ( $im, $font, 10, $y - $border - 10, $min_y . "C", $black );
	imagestring ( $im, $font, 10, $border - 10, $max_y . "C", $black );

	// Legend for timestamps
	imagestring ( $im, $font, $border, $y - $border + 5, timestampFormat ( time2Timestamp ( $min_x ), "Y-m-d H:i" ), $black );
	imagestring ( $im, $font, $x - 4 * $border, $y - $border + 5, timestampFormat ( time2Timestamp ( $max_x ), "Y-m-d H:i" ), $black );
} else {
	imagestring ( $im, $font, 100, 100, "No temperature data available", $red );
}

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>