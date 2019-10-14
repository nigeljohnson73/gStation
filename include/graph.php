<?php

function _graphMinMax($arr, $compfunc, $blankval, $kvfunc) {
	// echo "<pre>_graphMinMax(\$arr, $compfunc, $blankval, $kvfunc): called\n" . ob_print_r ( $arr ) . "</pre>\n";
	$ret = $blankval;
	foreach ( $arr as $legend => $vals ) {
		if (count ( $vals )) {
			// echo "&nbsp;&nbsp;&nbsp;&nbsp;_graphMinMax(\$arr, $compfunc, $kvfunc): '$legend': curr: $ret, new: " . $compfunc ( $kvfunc ( $vals ) ) . "</br>";
			$ret = $compfunc ( $ret, $compfunc ( $kvfunc ( $vals ) ) );
		}
	}
	$ret = ($ret == $blankval) ? (null) : ($ret);
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
function drawTimeGraph($data, $legend, $nmajor_x, $nminor_x, $min_y, $max_y, $nmajor_y, $nminor_y, $y_ticks, $x_format = "Y-m-d H:i") {
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

	// Days along the bottom
	$min_x = graphKeyMin ( $data );
	$max_x = graphKeyMax ( $data );

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

	// Draw Axes
	imageline ( $im, $border, $y - $border, $x - $border, $y - $border, $fg );
	imageline ( $im, $border, $y - $border, $border, $border, $fg );

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

	// Overall legend
	imagestring ( $im, $font, $border, $border / 2, $legend, $fg );

	// range for values
	foreach ( $y_ticks as $v => $label ) {
		$yv = $y - ((scaleVal ( $v, $min_y, $max_y ) * ($y - 2 * $border)) + $border);
		imagestring ( $im, /*$font*/2, 10, $yv - 7, $label, $fg );
	}
	// range for timestamps
	imagestring ( $im, $font, $border, $y - $border + 5, timestampFormat ( time2Timestamp ( $min_x ), $x_format ), $fg );

	$rhs_label = timestampFormat ( time2Timestamp ( $max_x ), $x_format );
	//list ( $left, , $right ) = imageftbbox ( 12, 0, $font, $rhs_label );
	$width = imageFontWidth($font) * strlen($rhs_label);
	imagestring ( $im, $font, $x - $border - $width, $y - $border + 5, $rhs_label, $fg );

	return $im;
}

?>