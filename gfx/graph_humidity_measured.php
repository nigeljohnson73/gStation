<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $zone;
if ($zone == "") {
	$zone = "ZONE1";
}

if (! function_exists ( "getLocalHums" )) {

	function getLocalHums($name) {
		global $mysql;
		// $res = $mysql->query ( "SELECT * FROM humidity_logger where humidity != 999999 and demanded != 999999 and entered >= DATE_SUB(NOW(), INTERVAL 12 HOUR)" );
		$res = $mysql->query ( "SELECT * FROM sensors where name = '$name' and param = 'humidity'" );
		// echo "Local humidity count: ".count($res)."\n";
		// var_dump($res);
		if (is_array ( $res ) && count ( $res ) > 0) {
			$dem = array ();
			$act = array ();
			foreach ( $res as $r ) {
				// echo ob_print_r($r);
				// $dem [timestamp2Time ( $r ["entered"] )] = $r ["demanded"];
				$act [timestamp2Time ( $r ["event"] )] = $r ["value"];
			}
			return array (
					"humidity" => $act
				// "demanded" => $dem
			);
		}
		return null;
	}
}

$dbg = false;
$hums = getLocalHums ( $zone );
// echo "<pre>".ob_print_r($hums)."</pre>";
// Lets have some axes regardless of data
$legend = "Not enough local humidity measurements have been gathered";
$min_y = 0;
$max_y = 5;
$y_ticks = array ();

if ($hums && count ( $hums [array_keys ( $hums ) [0]] ) > 2) {
	$legend = "Measured $zone humidity over the last 24 hours";
	// foreach ( $hums as $k => $v ) {
	// $hums [$k] = decimateArray ( $v, 5 );
	// }
	// $dcount = count ( $hums ["demanded"] );
	// logger ( LL_DEBUG, "graphLocalHums(): Got demanded count: " . count ( $hums ["demanded"] ) );

	// $dcount_max = 100;
	// if ($dcount >= (2 * $dcount_max)) {
	// logger ( LL_DEBUG, "graphLocalHums(): calling decimateArray()" );
	// $hums ["demanded"] = decimateArray ( $hums ["demanded"], floor ( $dcount / $dcount_max ) );
	// }
	// logger ( LL_DEBUG, "graphLocalHums(): Rendering demanded count: " . count ( $hums ["demanded"] ) );

	// $hums ["humidity"] = deltaDecimateArray ( smoothArray ( $hums ["humidity"], 1, 1 ), 0.1, 30 );
	// $hums ["humidity"] = smoothArray ( deltaDecimateArray ( $hums ["humidity"], 0.1, 20 ), 1, 1 );
	$hcount = count ( $hums ["humidity"] );
	logger ( LL_DEBUG, "graphLocalHums(): Got humidity count: " . count ( $hums ["humidity"] ) );

	$hcount_max = 400;
	if ($hcount >= (2 * $hcount_max)) {
		logger ( LL_DEBUG, "graphLocalHums(): calling deltaDecimateArray()" );
		$hums ["humidity"] = deltaDecimateArray ( $hums ["humidity"], 0.1, floor ( $hcount / $hcount_max ) );
	} else if ($hcount >= ($hcount_max)) {
		logger ( LL_DEBUG, "graphLocalHums(): calling smoothArray()" );
		$hums ["humidity"] = smoothArray ( $hums ["humidity"], 1, 1 );
	} else {
		// leave it
	}
	logger ( LL_DEBUG, "graphLocalHums(): Rendering humidity count: " . count ( $hums ["humidity"] ) );
	;

	$min_y = floor ( graphValMin ( $hums ) );
	$max_y = ceil ( graphValMax ( $hums ) );
	$y_ticks = array ();
	for($i = $min_y; $i <= $max_y; $i ++) {
		$y_ticks [$i] = $i . "%";
	}
} else {

	// $tsnow = timestampNow ();
	// $yr = timestampFormat ( $tsnow, "Y" );

	// $summer_solstice = "0621";
	// $day_humidity_min = 21;
	// $day_humidity_max = 33;
	// $high_delta_humidity = ($day_humidity_max - $day_humidity_min) / 2;
	// $high_mid_humidity = $day_humidity_min + $high_delta_humidity;

	// $night_humidity_min = 5;
	// $night_humidity_max = 10;
	// $low_delta_humidity = ($night_humidity_max - $night_humidity_min) / 2;
	// $low_mid_humidity = $night_humidity_min + $low_delta_humidity;

	// $deg_step = 360 / 365;
	// $tsnow = $yr . $summer_solstice . "000000";
	// for($i = 0; $i < 365; $i ++) {
	// if (timestampFormat ( $tsnow, "md" ) == "0229") {
	// $tsnow = timestampAdd ( $tsnow, numDays ( 1 ) );
	// }
	// $hi [timestamp2Time ( $tsnow )] = $high_mid_humidity + $high_delta_humidity * cos ( deg2rad ( $i * $deg_step ) );
	// $lo [timestamp2Time ( $tsnow )] = $low_mid_humidity + $low_delta_humidity * cos ( deg2rad ( $i * $deg_step ) );
	// $tsnow = timestampAdd ( $tsnow, numDays ( 1 ) );
	// }
	// $hums = array (
	// "high" => $hi,
	// "low" => $lo
	// );
}

$x_ticks = 12;
$x_subticks = 1;
$im = drawTimeGraph ( $hums, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks );

header ( 'Content-type: image/png' );
if ($ofn . "" == "") {
	imagepng ( $im );
} else {
	imagepng ( $im, $ofn );
}
imagedestroy ( $im );

?>