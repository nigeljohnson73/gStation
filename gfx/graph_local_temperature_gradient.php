<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

function getLocalTempGrads() {
	global $mysql;
	// $res = $mysql->query ( "SELECT * FROM temperature_logger where temperature != 999 ORDER BY entered desc limit 10" );
	$res = $mysql->query ( "SELECT * FROM temperature_gradient_logger ORDER BY entered desc limit 200" ); // WHERE gradient != 0 ORDER BY entered desc" );
	                                                                                                      // var_dump($res);
	if (is_array ( $res ) && count ( $res ) > 0) {
		$gradient = array ();
		foreach ( $res as $r ) {
			// echo ob_print_r($r);
			$gradient [timestamp2Time ( $r ["entered"] )] = $r ["gradient"];
		}
		return array (
				"gradient" => $gradient
		);
	}
	return null;
}

$legend = "Temperature gradients over the last 20 minutes";
$temps = getLocalTempGrads ();
foreach ( $temps as $k => $v ) {
	$temps [$k] = smoothValues ( $v, 3, 10 );
	$temps [$k . "_raw"] = $v;
	$temps [$k . "_dec"] = decimateArray ( $v, 5 );
}

$min_y = graphValMin ( $temps );
$max_y = graphValMax ( $temps );
$y_ticks = array ();
$y_ticks ["".($min_y)] = sprintf ( "%0.03f", $min_y );
$y_ticks [0] = "0.00";
$y_ticks ["".$max_y] = sprintf ( "%.03f", $max_y );

//echo "<pre>min: $min_y\nmax: $max_y\nSending: ".ob_print_r($y_ticks)."</pre>";
//$y_ticks = null;
$x_ticks = 0;
$x_subticks = 0;
$im = drawTimeGraph ( $temps, $legend, $x_ticks, $x_subticks, $min_y, $max_y, 0, 0, $y_ticks );

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>