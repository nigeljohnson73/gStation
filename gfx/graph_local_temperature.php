<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

$zone = "ZONE1";

function getLocalTemps($name) {
	global $mysql;
	// $res = $mysql->query ( "SELECT * FROM temperature_logger where temperature != 999999 and demanded != 999999 and entered >= DATE_SUB(NOW(), INTERVAL 12 HOUR)" );
	$res = $mysql->query ( "SELECT * FROM sensors where name = '$name' and param = 'temperature'" );
	// echo "Local temp count: ".count($res)."\n";
	// var_dump($res);
	if (is_array ( $res ) && count ( $res ) > 0) {
		$dem = array ();
		$act = array ();
		foreach ( $res as $r ) {
			// echo ob_print_r($r);
//			$dem [timestamp2Time ( $r ["entered"] )] = $r ["demanded"];
			$act [timestamp2Time ( $r ["event"] )] = $r ["value"];
		}
		return array (
				"temperature" => $act,
				//"demanded" => $dem
		);
	}
	return null;
}

$dbg = false;
$temps = getLocalTemps ($zone);
// echo "<pre>".ob_print_r($temps)."</pre>";
// Lets have some axes regardless of data
$legend = "Not enough local temperature measurements have been gathered";
$min_y = 0;
$max_y = 5;
$y_ticks = array ();

if ($temps && count ( $temps [array_keys ( $temps ) [0]] ) > 2) {
	$legend = "Measured $zone temperature over the last 24 hours";
	// foreach ( $temps as $k => $v ) {
	// $temps [$k] = decimateArray ( $v, 5 );
	// }
	//$dcount = count ( $temps ["demanded"] );
	//logger ( LL_INFO, "graphLocalTemps(): Got demanded count: " . count ( $temps ["demanded"] ) );

// 	$dcount_max = 100;
// 	if ($dcount >= (2 * $dcount_max)) {
// 		logger ( LL_INFO, "graphLocalTemps(): calling decimateArray()" );
// 		$temps ["demanded"] = decimateArray ( $temps ["demanded"], floor ( $dcount / $dcount_max ) );
// 	}
// 	logger ( LL_INFO, "graphLocalTemps(): Rendering demanded count: " . count ( $temps ["demanded"] ) );

	// $temps ["temperature"] = deltaDecimateArray ( smoothArray ( $temps ["temperature"], 1, 1 ), 0.1, 30 );
	// $temps ["temperature"] = smoothArray ( deltaDecimateArray ( $temps ["temperature"], 0.1, 20 ), 1, 1 );
	$tcount = count ( $temps ["temperature"] );
	logger ( LL_INFO, "graphLocalTemps(): Got temp count: " . count ( $temps ["temperature"] ) );

	$tcount_max = 400;
	if ($tcount >= (2 * $tcount_max)) {
		logger ( LL_INFO, "graphLocalTemps(): calling deltaDecimateArray()" );
		$temps ["temperature"] = deltaDecimateArray ( $temps ["temperature"], 0.1, floor ( $tcount / $tcount_max ) );
	} else if ($tcount >= ($tcount_max)) {
		logger ( LL_INFO, "graphLocalTemps(): calling smoothArray()" );
		$temps ["temperature"] = smoothArray ( $temps ["temperature"], 1, 1 );
	} else {
		// leave it
	}
	logger ( LL_INFO, "graphLocalTemps(): Rendering temp count: " . count ( $temps ["temperature"] ) );
	;

	$min_y = floor ( graphValMin ( $temps ) );
	$max_y = ceil ( graphValMax ( $temps ) );
	$y_ticks = array ();
	for($i = $min_y; $i <= $max_y; $i ++) {
		$y_ticks [$i] = $i . "C";
	}
} else {

	// $tsnow = timestampNow ();
	// $yr = timestampFormat ( $tsnow, "Y" );

	// $summer_solstice = "0621";
	// $day_temperature_min = 21;
	// $day_temperature_max = 33;
	// $high_delta_temperature = ($day_temperature_max - $day_temperature_min) / 2;
	// $high_mid_temperature = $day_temperature_min + $high_delta_temperature;

	// $night_temperature_min = 5;
	// $night_temperature_max = 10;
	// $low_delta_temperature = ($night_temperature_max - $night_temperature_min) / 2;
	// $low_mid_temperature = $night_temperature_min + $low_delta_temperature;

	// $deg_step = 360 / 365;
	// $tsnow = $yr . $summer_solstice . "000000";
	// for($i = 0; $i < 365; $i ++) {
	// if (timestampFormat ( $tsnow, "md" ) == "0229") {
	// $tsnow = timestampAdd ( $tsnow, numDays ( 1 ) );
	// }
	// $hi [timestamp2Time ( $tsnow )] = $high_mid_temperature + $high_delta_temperature * cos ( deg2rad ( $i * $deg_step ) );
	// $lo [timestamp2Time ( $tsnow )] = $low_mid_temperature + $low_delta_temperature * cos ( deg2rad ( $i * $deg_step ) );
	// $tsnow = timestampAdd ( $tsnow, numDays ( 1 ) );
	// }
	// $temps = array (
	// "high" => $hi,
	// "low" => $lo
	// );
}

$x_ticks = 12;
$x_subticks = 1;
$im = drawTimeGraph ( $temps, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks );

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>