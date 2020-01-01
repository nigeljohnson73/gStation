<?php

function getLocalMeasurements($what, $name) {
	global $mysql;
	$where = "name = '$name'";
	$bits = explode ( ",", $name );
	foreach ( $bits as $k => $v ) {
		$bits [$k] = trim ( $v );
	}
	if (count ( $bits ) > 1) {
		$where = "name in ('" . implode ( "','", $bits ) . "')";
	}
	// $res = $mysql->query ( "SELECT * FROM temperature_logger where temperature != 999999 and demanded != 999999 and entered >= DATE_SUB(NOW(), INTERVAL 12 HOUR)" );
	$sql = "SELECT event, name, value FROM sensors where param = '" . $what . "' " . $where . " union select event, 'DEMANDED' as 'name', value from demands where param = '" . $what . "'";
	// echo "SQL: \"" . $sql . "\"\n";
	// $sql = "SELECT event, name, value FROM sensors where name = 'ZONE1' and param = 'temperature' union select event, 'DEMANDED' as 'name', value from demands where param = 'temperature'";
	$res = $mysql->query ( $sql );
	// echo "Local temp count: ".count($res)."\n";
	// var_dump($res);
	if (is_array ( $res ) && count ( $res ) > 0) {
		$ret = array ();
		foreach ( $res as $r ) {
			// echo ob_print_r($r);
			// $dem [timestamp2Time ( $r ["entered"] )] = $r ["demanded"];
			$ret [$r ["name"]] [timestamp2Time ( $r ["event"] )] = $r ["value"];
		}
		// return array (
		// "temperature" => $act
		// // "demanded" => $dem
		// );
		return $ret;
	}
	return null;
}

function drawMeasuredGraph($what, $zone) {
	$legend_keys = [ ];
	$legend_key ["temperature"] = "C";
	$legend_key ["humidity"] = "%";

	$legend_key = $legend_key [$what];

	$dbg = false;
	$vals = getLocalMeasurements ( $what, $zone );
	// echo "<pre>".ob_print_r($temps)."</pre>";
	// Lets have some axes regardless of data
	$legend = "Not enough " . $what . " measurements have been gathered";
	$min_y = 0;
	$max_y = 5;
	$y_ticks = array ();

	foreach ( $vals as $k => $v ) {
		if ($v && count ( array_keys ( $v ) ) > 2) {
			$legend = "Measured " . $what . " (" . $zone . ")";
			$vc = count ( $v );

			$ll = LL_DEBUG;
			logger ( $ll, "graphLocalValues(" . $what . "): Got count: " . $vc );

			$vc_max = 400;
			if ($vc >= (2 * $vc_max)) {
				logger ( $ll, "graphLocalValues(" . $what . ", " . $k . "): calling deltaDecimateArray()" );
				$temps [$k] = deltaDecimateArray ( $v, 0.1, floor ( $vc / $vc_max ) );
			} else if ($vc >= ($vc_max)) {
				logger ( $ll, "graphLocalValues(" . $what . ", " . $k . "): calling smoothArray()" );
				$temps [$k] = smoothArray ( $v, 1, 1 );
			} else {
				logger ( $ll, "graphLocalValues(" . $what . ", " . $k . "): no need for point reduction" );
			}
			logger ( $ll, "graphLocalValues(" . $what . ", " . $k . "): Render count: " . $vc );
		}
	}

	$min_y = floor ( graphValMin ( $vals ) );
	$max_y = ceil ( graphValMax ( $vals ) );
	$y_ticks = array ();
	for($i = $min_y; $i <= $max_y; $i ++) {
		$y_ticks [$i] = $i . $legend_key;
	}

	$x_ticks = 12;
	$x_subticks = 1;
	return drawTimeGraph ( $vals, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks );
}

function _graphMinMax($arr, $compfunc, $blankval, $kvfunc) {
	// echo "<pre>_graphMinMax(\$arr, $compfunc, $blankval, $kvfunc): called\n" . ob_print_r ( $arr ) . "</pre>\n";
	$ret = $blankval;
	if ($arr && count ( $arr )) {
		foreach ( $arr as $legend => $vals ) {
			if (count ( $vals )) {
				// echo "&nbsp;&nbsp;&nbsp;&nbsp;_graphMinMax(\$arr, $compfunc, $kvfunc): '$legend': curr: $ret, new: " . $compfunc ( $kvfunc ( $vals ) ) . "</br>";
				$ret = $compfunc ( $ret, $compfunc ( $kvfunc ( $vals ) ) );
			}
		}
		$ret = ($ret == $blankval) ? (null) : ($ret);
	}
	// echo "&nbsp;&nbsp;&nbsp;&nbsp;_graphMinMax(\$arr, $compfunc, $kvfunc): return: " . tfn ( $ret ) . "</br>";
	return $ret;
}

function graphValMin($arr) {
	return _graphMinMax ( $arr, "min", PHP_INT_MAX, "array_values" );
}

function graphValMax($arr) {
	return _graphMinMax ( $arr, "max", PHP_INT_MIN, "array_values" );
}

function graphKeyMin($arr) {
	return _graphMinMax ( $arr, "min", PHP_INT_MAX, "array_keys" );
}

function graphKeyMax($arr) {
	return _graphMinMax ( $arr, "max", PHP_INT_MIN, "array_keys" );
}

function graphPoint($im, $x, $y, $colour, $dist = 1) {
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

/**
 * **************************************
 * $data - a legend indexed array of keyed value arrays with the keys being unix timestamps
 *
 * $legend - The main title of the graph
 *
 * $nmajor_x - number of major tick lines on the x axis, should be calculated based on the range of days or whatever
 *
 * $nminor_x - number of minor ticks per major on the x axis
 *
 * $min_y - the lowest y value to display (should be lower than the lowest value)
 *
 * $max_y - the highest y value to display (should be higher than the highest value)
 *
 * $nmajor_y - number of major tick lines on the y axis, should be calculated based on the range (should be awhole number ideally)
 *
 * $nminor_y - number of minor ticks per major on the y axis
 *
 * $y_ticks - a value indexed array of labels to display
 */
function drawTimeGraph($data, $legend, $nmajor_x, $nminor_x, $min_y, $max_y, $nmajor_y, $nminor_y, $y_ticks = null, $x_format = null, $pinpoint = null) {
	if ($x_format === null) {
		$x_format = "Y-m-d H:i";
	}
	$x = 640;
	$y = 480;
	$border = 40;

	$im = imagecreatetruecolor ( $x, $y );
	// This doesn't work. needs a recompile on mac? dunno about raspian
	// $font = imageloadfont(dirname(__FILE__)."/../fonts/andalemo.ttf");
	$font = 4;

	$major = imagecolorallocate ( $im, 0xcc, 0xcc, 0xcc ); // major ticks
	$minor = imagecolorallocate ( $im, 0xdd, 0xdd, 0xdd ); // minor ticks
	$bg = imagecolorallocate ( $im, 0xee, 0xee, 0xee ); // bakground
	$fg = imagecolorallocate ( $im, 0x00, 0x00, 0x00 ); // axes and text

	$bp_dark_green = imagecolorallocate ( $im, 0x00, 0x99, 0x00 );
	$bp_lime_green = imagecolorallocate ( $im, 0x99, 0xcc, 0x00 );
	$bp_purple = imagecolorallocate ( $im, 0x66, 0x00, 0x99 );
	$yellow = imagecolorallocate ( $im, 0xff, 0xff, 0x00 );
	$orange = imagecolorallocate ( $im, 0xff, 0x99, 0xaa );
	$lorange = imagecolorallocate ( $im, 0xff, 0xaa, 0xcc );
	$red = imagecolorallocate ( $im, 0xff, 0x00, 0x00 );
	$light_blue = imagecolorallocate ( $im, 0x66, 0x66, 0xff );

	$graph_cols = array (
			$bp_dark_green,
			$bp_lime_green,
			$bp_purple,
			$light_blue,
			$orange,
			$yellow
	);

	// Start with background
	imagefill ( $im, 0, 0, $bg );

	// Draw the minor x ticks
	if (($nmajor_x > 0) && ($nminor_x > 0)) {
		$steps = $nmajor_x * ($nminor_x + 1);
		$x_step = ($x - 2 * $border) / ($steps);
		for($i = 1; $i <= $steps; $i ++) {
			imageline ( $im, $border + ($i * $x_step), $border, $border + ($i * $x_step), $y - $border, $minor );
		}
	}
	// Draw the minor y ticks
	if (($nmajor_y > 0) && ($nminor_y > 0)) {
		$steps = $nmajor_y * ($nminor_y + 1);
		$y_step = ($y - 2 * $border) / ($steps);
		for($i = 1; $i <= $steps; $i ++) {
			imageline ( $im, $border, $border + ($i * $y_step), $x - $border, $border + ($i * $y_step), $minor );
		}
	}

	// Draw the major x ticks
	if ($nmajor_x > 0) {
		$steps = $nmajor_x;
		$x_step = ($x - 2 * $border) / ($steps);
		for($i = 1; $i <= $steps; $i ++) {
			imageline ( $im, $border + ($i * $x_step), $border, $border + ($i * $x_step), $y - $border, $major );
		}
	}

	// Draw the minor y ticks
	if ($nmajor_y > 0) {
		$steps = $nmajor_y;
		$y_step = ($y - 2 * $border) / ($steps);
		for($i = 1; $i <= $steps; $i ++) {
			imageline ( $im, $border, $y - $border - ($i * $y_step), $x - $border, $y - $border - ($i * $y_step), $major );
		}
	}

	if ($data && count ( $data )) {
		// Days along the bottom
		$min_x = graphKeyMin ( $data );
		$max_x = graphKeyMax ( $data );

		// Draw the pin point
		if ($pinpoint) {
			if (! is_array ( $pinpoint )) {
				$pinpoint = array (
						$pinpoint
				);
			}

			$pinpoint [0]->colors = array (
					$red,
					$lorange
			);
			if (isset ( $pinpoint [1] )) {
				$pinpoint [1]->colors = array (
						$lorange,
						$yellow
				);
			}

			$pinpoint = array_reverse ( $pinpoint );
			// echo "<pre>".ob_print_r($pinpoint)."</pre>";
			foreach ( $pinpoint as $k => $p ) {
				$xv = (scaleVal ( $p->x, $min_x, $max_x ) * ($x - 2 * $border)) + $border;
				$yv = $y - ((scaleVal ( $p->y, $min_y, $max_y ) * ($y - 2 * $border)) + $border);
				$in_x = $xv >= $border && ($xv <= ($x - $border));
				$in_y = $yv >= $border && ($yv <= ($y - $border));
				if ($in_x && $in_y) {
					graphPoint ( $im, $xv, $yv, $p->colors [1], 5 );
				}
				if ($in_x) {
					imageline ( $im, $xv, $border, $xv, $y - $border, $p->colors [0] );
				}
				if ($in_y) {
					imageline ( $im, $border, $yv, $x - $border, $yv, $p->colors [0] );
				}
			}
		}
	}

	// Draw Axes
	imageline ( $im, $border, $y - $border, $x - $border, $y - $border, $fg );
	imageline ( $im, $border, $y - $border, $border, $border, $fg );

	if ($data && count ( $data )) {
		// Days along the bottom
		$min_x = graphKeyMin ( $data );
		$max_x = graphKeyMax ( $data );

		// Process in the data graphPoints
		$col_index = 0;
		foreach ( $data as $leg => $trace ) {
			foreach ( $trace as $k => $v ) {
				$xv = (scaleVal ( $k, $min_x, $max_x ) * ($x - 2 * $border)) + $border;
				$yv = $y - ((scaleVal ( $v, $min_y, $max_y ) * ($y - 2 * $border)) + $border);
				graphPoint ( $im, $xv, $yv, $graph_cols [$col_index] );
				// graphPoint ( $im, $xv, $yvt, $red );
			}
			$col_index += 1;
		}
		// echo "<pre>TICKS: ".ob_print_r(tfn($y_ticks))."</pre>";
		// range for values
		if ($y_ticks) {
			foreach ( $y_ticks as $v => $label ) {
				$yv = $y - ((scaleVal ( $v, $min_y, $max_y ) * ($y - 2 * $border)) + $border);
				imagestring ( $im, /*$font*/2, 10, $yv - 7, $label, $fg );
			}
		} else {
			// echo "BOLLOCKS";
			$yv = $y - ((scaleVal ( $min_y, $min_y, $max_y ) * ($y - 2 * $border)) + $border);
			imagestring ( $im, /*$font*/2, 10, $yv - 7, $min_y, $fg );
		}
		// range for timestamps
		imagestring ( $im, $font, $border, $y - $border + 5, timestampFormat ( time2Timestamp ( $min_x ), $x_format ), $fg );

		$rhs_label = timestampFormat ( time2Timestamp ( $max_x ), $x_format );
		// list ( $left, , $right ) = imageftbbox ( 12, 0, $font, $rhs_label );
		$width = imageFontWidth ( $font ) * strlen ( $rhs_label );
		imagestring ( $im, $font, $x - $border - $width, $y - $border + 5, $rhs_label, $fg );
	}

	// Overall legend
	imagestring ( $im, $font, $border, $border / 2, $legend, $fg );

	return $im;
}

?>