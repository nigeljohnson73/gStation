<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

$x = 640;
$y = 480;
$border = 40;

$im = imagecreatetruecolor ( $x, $y );
$grey = imagecolorallocate ( $im, 0xee, 0xee, 0xee );
$black = imagecolorallocate ( $im, 0x00, 0x00, 0x00 );
$red = imagecolorallocate ( $im, 0xff, 0x00, 0x00 );

function point($im, $x, $y, $colour) {
	$dist = 2;
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
	//var_dump($res);
	if (is_array ( $res ) && count ( $res ) > 0) {
		$ret = array ();
		foreach ( $res as $r )
			//echo ob_print_r($r);
			$ret [timestamp2Time ( $r ["entered"] )] = $r ["temperature"];
		return $ret;
	}
	return null;
}

// header('Content-Disposition: Attachment;filename=image.png');

$temps = getTemps();
//print_r($temps);
$min_x = min(array_keys($temps));
$max_x = max(array_keys($temps));
$min_y = floor(min(array_values($temps)));
$max_y = ceil(max(array_values($temps)));

imagefill ( $im, 0, 0, $grey );

imageline ( $im, $border, $y - $border, $x - $border, $y - $border, $black );
imageline ( $im, $border, $y - $border, $border, $border, $black );

foreach($temps as $k => $v) {
	$xv = (scaleVal($k, $min_x, $max_x) * ($x - 2* $border)) + $border;
	$yv = $y-((scaleVal($v, $min_y, $max_y) * ($y - 2* $border)) + $border);
	point ( $im, $xv, $yv, $red );
}

//$font = imageloadfont(dirname(__FILE__)."/../fonts/andalemo.ttf");
$font = 4;
imagestring($im, $font, 10, $y - $border-10, $min_y."C", $black);
imagestring($im, $font, 10, $border-10, $max_y."C", $black);

imagestring($im, $font, $border, $y - $border+5, timestampFormat(time2Timestamp($min_x), "Y-m-d H:i"), $black);
imagestring($im, $font, $x - 4*$border, $y - $border+5, timestampFormat(time2Timestamp($max_x), "Y-m-d H:i"), $black);

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>