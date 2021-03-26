<?php

function getLocalMeasurements_orig($what, $name) {
	global $mysql;
	$swhere = "name = '$name'";
	$twhere = "param = '$name'";
	$bits = explode ( ",", $name );
	foreach ( $bits as $k => $v ) {
		$bits [$k] = trim ( $v );
	}
	if (count ( $bits ) > 1) {
		$swhere = "name in ('" . implode ( "','", $bits ) . "')";
		$twhere = "param in ('" . implode ( "','", $bits ) . "')";
	}
	$sql = "SELECT event, name, value FROM sensors where param = '" . $what . "' and " . $swhere . " ";
	$sql .= "union select event, 'EXPECTED' as 'name', value from expects where param = '" . $what . "' ";
	$sql .= "union select event, 'TRIGGER' as 'name', value from triggers where " . $twhere;
	// echo "SQL: \"" . $sql . "\"\n";
	$res = $mysql->query ( $sql );
	// echo "Local temp count: ".count($res)."\n";
	// var_dump($res);
	if (is_array ( $res ) && count ( $res ) > 0) {
		$ret = array ();
		foreach ( $res as $r ) {
			// echo ob_print_r($r);
			// $dem [timestamp2Time ( $r ["entered"] )] = $r ["expected"];
			$ret [$r ["name"]] [timestamp2Time ( $r ["event"] )] = $r ["value"];
		}
		// return array (
		// "temperature" => $act
		// // "expected" => $dem
		// );
		return $ret;
	}
	return null;
}

function getLocalMeasurements($what, $name) {
	// echo "getLocalMeasurements('$what', '$name'): Started\n";
	$ret = null;
	global $mysql;
	// $where = "name = '$name'";
	$bits = explode ( ",", $name );
	$sqls = [ ];
	foreach ( $bits as $v ) {
		$obit = trim ( $v );
		$bit = trim ( $v );
		$arr = array ();
		preg_match ( '/(.*)(\((.*)\))/', $obit, $arr );
		if (count ( $arr )) {
			$bit = trim ( $arr [1] );
		}
		// echo "Looking for '".$bit."'\n";
		if (in_array ( strtolower ( $what ), array (
				"trigger",
				"triggers"
		) )) {
			$mult = ($bit [1] + 0) * (1 / (count ( $bits ) + 1)); // ($bit[1] + 0)*0.2;
			                                                      // $sqls [strtolower ( $obit )] = "SELECT event, param as 'name', (value*".$mult.") as value FROM triggers WHERE param = '" . $bit . "' AND value > 0.5";
			$sqls [strtolower ( $obit )] = "SELECT event, param as 'name', (value*" . $mult . ") as value FROM triggers WHERE param = '" . $bit . "'";
		} else if (strtolower ( $bit ) == "expect" || strtolower ( $bit ) == "expected") {
			$sqls [strtolower ( $obit )] = "SELECT event, 'EXPECTED' as 'name', value FROM expects WHERE param = '" . $what . "'";
		} else if (in_array ( strtolower ( $what ), array (
				"sensor_age",
				"sensor_ages"
		) )) {
			$sqls [strtolower ( $obit )] = "SELECT DISTINCT event, name, age as value FROM sensors WHERE name = '" . $bit . "' AND age IS NOT NULL";
		} else {
			$sqls [strtolower ( $obit )] = "SELECT event, name, value FROM sensors WHERE param = '" . $what . "' AND name = '" . $bit . "'";
		}
	}
	foreach ( $sqls as $sql ) {
		$res = $mysql->query ( $sql );
		echo "SQL: \"" . $sql . "\"\n";
		// echo " Count: ".count($res)."\n";
		// echo timestampFormat ( timestampNow (), "H:i:s" ) . ": getLocalMeasurements(): " . count ( $res ) . " rows from \"$sql\"\n";
		if (is_array ( $res ) && count ( $res ) > 0) {
			if ($ret == null) {
				$ret = array ();
			}
			foreach ( $res as $r ) {
				$ret [$r ["name"]] [timestamp2Time ( $r ["event"] )] = $r ["value"];
			}
		}
		sleep ( 1 );
	}
	return $ret;
}

if (! function_exists ( "getGraphColour" )) {

	function getGraphColour($name) {
		return null;
	}
}

// function drawMeasuredGraph($what, $zone) {
// echo timestampFormat ( timestampNow (), "H:i:s" ) . ": drawMeasuredGraph(): started\n";

// $legend_keys = [ ];
// $legend_key ["temperature"] = "C";
// $legend_key ["humidity"] = "%";
// $legend_key ["mem_load"] = "%";
// $legend_key ["cpu_load"] = "%";
// $legend_key ["cpu_wait"] = "%";
// $legend_key ["sd_load"] = "%";
// $legend_key ["trigger"] = "";
// $legend_key ["triggers"] = "";
// $legend_key ["sensor_age"] = "s";
// $legend_key ["sensor_ages"] = "s";

// $legend_key = $legend_key [$what];

// //$dbg = false;
// $vals = getLocalMeasurements ( $what, $zone );
// // echo "<pre>".ob_print_r($temps)."</pre>";
// // Lets have some axes regardless of data
// $legend = "Not enough " . $what . " measurements have been gathered";
// $min_y = 0;
// $max_y = 5;
// $y_ticks = array ();

// // $temps = [];
// echo timestampFormat ( timestampNow (), "H:i:s" ) . ": drawMeasuredGraph(): Processing data points\n";
// if ($vals && count ( $vals )) {
// foreach ( $vals as $k => $v ) {
// if ($v && count ( array_keys ( $v ) ) > 2) {
// //$legend = "Measured " . $what . " (" . $zone . ")";
// $legend = ucwords($what) . " - " . $zone;
// $vc = count ( $v );

// $ll = LL_DEBUG;
// logger ( $ll, "graphLocalValues(" . $what . "): Got count: " . $vc );

// $vc_max = 400;
// if ($vc >= (2 * $vc_max)) {
// logger ( $ll, "graphLocalValues(" . $what . ", " . $k . "): calling deltaDecimateArray()" );
// // $temps [$k] = deltaDecimateArray ( $v, 0.1, floor ( $vc / $vc_max ) );
// } else if ($vc >= ($vc_max)) {
// logger ( $ll, "graphLocalValues(" . $what . ", " . $k . "): calling smoothArray()" );
// // $temps [$k] = smoothArray ( $v, 1, 1 );
// } else {
// logger ( $ll, "graphLocalValues(" . $what . ", " . $k . "): no need for point reduction" );
// }
// logger ( $ll, "graphLocalValues(" . $what . ", " . $k . "): Render count: " . $vc );
// }
// }

// $min_y = floor ( graphValMin ( $vals ) );
// $max_y = ceil ( graphValMax ( $vals ) );
// $c_y = $max_y - $min_y;
// $c_step = ($c_y < 20) ? (1) : (($c_y < 40) ? (2) : (($c_y < 60) ? (3) : (($c_y < 80) ? (4) : (($c_y < 100) ? (5) : (($c_y < 200) ? (10) : ((($c_y < 300) ? (20) : ((($c_y < 400) ? (30) : ((($c_y < 500) ? (40) : (50))))))))))));
// $max_y = $min_y + ceil ( ($max_y - $min_y) / $c_step ) * $c_step;

// $y_ticks = array ();
// for($i = $min_y; $i <= $max_y; $i += $c_step) {
// $y_ticks [$i] = $i . $legend_key;
// }
// }

// $x_ticks = 12;
// $x_subticks = 1;
// echo timestampFormat ( timestampNow (), "H:i:s" ) . ": drawMeasuredGraph(): Generating graph\n";
// // return drawTimeGraph ( $vals, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks );
// return drawTimeGraph ( $vals, $legend, $x_ticks, $x_subticks, $min_y, $max_y, count ( $y_ticks ), 1, $y_ticks );
// }
function _graphMinMax($arr, $compfunc, $blankval, $kvfunc) {
	// echo "<pre>_graphMinMax(\$arr, $compfunc, $blankval, $kvfunc): called\n" . ob_print_r ( $arr ) . "</pre>\n";
	$ret = $blankval;
	if ($arr && count ( $arr )) {
		foreach ( $arr as $legend => $vals ) {
			$legend = $legend;
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
	$lorange = imagecolorallocate ( $im, 0xff, 0xaa, 0x66 );
	$red = imagecolorallocate ( $im, 0xff, 0x00, 0x00 );
	$light_blue = imagecolorallocate ( $im, 0x66, 0x66, 0xff );

	// $bp_dark_green = "#090";
	// $bp_lime_green = "#9c0";
	// $bp_purple = "#609";
	// $yellow = "#ff0";
	// $orange = #f9a";
	// $lorange = "f9a";
	// $red = "#f00";
	// $light_blue = "#66f";

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
			$hex = getGraphColour ( $leg );
			if ($hex == null) {
				$rgb = $graph_cols [$col_index];
			} else {
				(strlen ( $hex ) === 4) ? list ( $r, $g, $b ) = sscanf ( '#' . implode ( '', array_map ( 'str_repeat', str_split ( str_replace ( '#', '', $hex ) ), [ 
						2,
						2,
						2
				] ) ), "#%02x%02x%02x" ) : list ( $r, $g, $b ) = sscanf ( $hex, "#%2x%2x%2x" );

				$rgb = imagecolorallocate ( $im, $r, $g, $b );
			}

			// echo "Leg: '$leg', col: '$hex', r: $r, g: $g, b: $b\n";
			foreach ( $trace as $k => $v ) {
				$xv = (scaleVal ( $k, $min_x, $max_x ) * ($x - 2 * $border)) + $border;
				$yv = $y - ((scaleVal ( $v, $min_y, $max_y ) * ($y - 2 * $border)) + $border);
				// graphPoint ( $im, $xv, $yv, $graph_cols [$col_index] );
				graphPoint ( $im, $xv, $yv, $rgb );
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
