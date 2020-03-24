<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

$w = 1000;
$h = 1000;
$sc = 1; // scale
$sw = 1;

// strokewidth
function startSvg() {
	global $w, $h, $sc;
	echo "<svg xmlns='http://www.w3.org/2000/svg' width='" . ($w * $sc) . "' height='" . ($h * $sc) . "' >\n";
}

function ep($x, $y) {
	// Points are defined it seems from 0-500 LR and TB, so need to turn these into percentage from the center
	global $w, $h, $sc;
	return array (
			($x - ($w / 2)),
			($y - ($h / 2))
	);
}

function dp($p, $mirror = false) {
	global $w, $h, $sc;

	$xm = $mirror ? - 1 : 1;
	return array (
			($w / 2 + ($xm * $sc * $p [0])),
			($h / 2 + ($sc * $p [1]))
	);
}

function dps($p, $mirror = false) {
	$p = dp ( $p, $mirror );
	$str = "";
	$str .= $p [0];
	$str .= ",";
	$str .= $p [1];

	return $str;
}

function _line($sp, $ep, $sw = 1, $mirror = false) {
	$str = "";
	$str .= "<line ";
	$str .= "x1='" . dp ( $sp, $mirror ) [0] . "' y1='" . dp ( $sp, $mirror ) [1] . "' ";
	$str .= "x2='" . dp ( $ep, $mirror ) [0] . "' y2='" . dp ( $ep, $mirror ) [1] . "' ";
	$str .= "stroke = 'black' ";
	$str .= "stroke-width='" . $sw . "' ";
	$str .= "stroke-linecap='round' ";
	$str .= "/>\n";
	return $str;
}

function line($sp, $ep, $sw = 1, $mirror = false) {
	$str = "";
	$str .= _line ( $sp, $ep, $sw, false );
	if ($mirror) {
		$str .= _line ( $sp, $ep, $sw, true );
	}
	echo $str;
}

function _curve($sp, $bp1, $bp2, $ep, $sw = 1, $mirror = false, $f = "none") {
	$str = "";
	$str .= "<path d='M";
	$str .= dps ( $sp, $mirror ) . " C";
	$str .= dps ( $bp1, $mirror ) . " ";
	$str .= dps ( $bp2, $mirror ) . " ";
	$str .= dps ( $ep, $mirror ) . "' ";
	$str .= "fill='" . $f . "' ";
	$str .= "stroke = 'black' ";
	$str .= "stroke-width='" . $sw . "' ";
	$str .= "stroke-linecap='round' ";
	$str .= "/>\n";
	return $str;
}

function curve($sp, $bp1, $bp2, $ep, $sw = 1, $mirror = false, $f = "none") {
	$str = "";
	$str .= _curve ( $sp, $bp1, $bp2, $ep, $sw, false, $f );
	if ($mirror) {
		$str .= _curve ( $sp, $bp1, $bp2, $ep, $sw, true, $f );
	}
	echo $str;
}

function _elipse($sp, $x, $y, $sw = 1, $r = 0, $mirror = false, $f = "none") {
	$fill = $f ? 'white' : 'none';
	$r = $mirror ? - $r : $r;

	// <ellipse transform="translate(580, 380) rotate(20,0,0)" rx="80" ry="110" fill="white" stroke-width="10" stroke="red" />

	$str = "";
	$str .= "<ellipse ";
	$str .= "transform='translate(" . dps ( $sp, $mirror ) . ") ";
	$str .= "rotate(" . $r . ",0,0)' ";
	$str .= "rx='" . $x . "' ";
	$str .= "ry='" . $y . "' ";
	$str .= "fill='" . $f . "' ";
	$str .= "stroke = 'black' ";
	$str .= "stroke-width='" . $sw . "' ";
	$str .= "/>\n";
	return $str;
}

function elipse($sp, $x, $y, $sw = 1, $r = 0, $mirror = false, $f = "none") {
	$str = "";
	$str .= _elipse ( $sp, $x, $y, $sw, $r, false, $f );
	if ($mirror) {
		$str .= _elipse ( $sp, $x, $y, $sw, $r, true, $f );
	}
	echo $str;
}

function endSvg() {
	echo "</svg>\n";
}

$sw = 30;
ob_start ();
startSvg ();
curve ( ep ( 830, 560 ), ep ( 830, 660 ), ep ( 895, 660 ), ep ( 895, 560 ), $sw, true ); // Spiral point 0 to point 1
curve ( ep ( 830, 560 ), ep ( 830, 420 ), ep ( 960, 420 ), ep ( 960, 560 ), $sw, true ); // Spiral point 1 to point 2
curve ( ep ( 960, 560 ), ep ( 960, 650 ), ep ( 920, 745 ), ep ( 778, 820 ), $sw, true ); // Spiral point 2 to point 3
curve ( ep ( 720, 465 ), ep ( 700, 280 ), ep ( 955, 200 ), ep ( 925, 465 ), $sw, true ); // Shell layer 2 (middle)
curve ( ep ( 500, 070 ), ep ( 720, 070 ), ep ( 855, 180 ), ep ( 860, 297 ), $sw, true ); // Shell layer 3 (top)

curve ( ep ( 500, 390 ), ep ( 700, 390 ), ep ( 790, 490 ), ep ( 790, 700 ), $sw, true ); // Body top
curve ( ep ( 680, 870 ), ep ( 750, 870 ), ep ( 790, 880 ), ep ( 790, 700 ), $sw, true ); // Body bottom
curve ( ep ( 765, 850 ), ep ( 860, 900 ), ep ( 810, 950 ), ep ( 740, 940 ), $sw, true ); // foot point 1 to point 2
curve ( ep ( 740, 940 ), ep ( 650, 920 ), ep ( 630, 940 ), ep ( 500, 940 ), $sw, true ); // foot point 2 to point 3
curve ( ep ( 801, 875 ), ep ( 860, 865 ), ep ( 860, 825 ), ep ( 830, 790 ), $sw, true ); // Heel

curve ( ep ( 500, 640 ), ep ( 565, 640 ), ep ( 590, 620 ), ep ( 625, 580 ), $sw, true ); // Mouth 1
curve ( ep ( 625, 580 ), ep ( 640, 560 ), ep ( 640, 560 ), ep ( 650, 540 ), $sw, true ); // Mouth 2
curve ( ep ( 650, 540 ), ep ( 670, 500 ), ep ( 720, 520 ), ep ( 700, 590 ), $sw, true ); // Mouth 3
curve ( ep ( 625, 580 ), ep ( 680, 620 ), ep ( 690, 620 ), ep ( 700, 590 ), $sw, true ); // Mouth 4

curve ( ep ( 530, 390 ), ep ( 540, 200 ), ep ( 620, 170 ), ep ( 680, 160 ), $sw, true ); // Antenna 1
curve ( ep ( 680, 160 ), ep ( 740, 150 ), ep ( 730, 200 ), ep ( 710, 210 ), $sw, true ); // Antenna 2
curve ( ep ( 710, 210 ), ep ( 630, 190 ), ep ( 570, 270 ), ep ( 580, 396 ), $sw, true ); // Antenna 3

elipse ( ep ( 581.5, 400 ), 80, 110, $sw, 10, true, "white" ); // Eye outer
elipse ( ep ( 581.5, 425 ), 10 + $sw / 2, 20 + $sw / 2, 1, 10, true, "black" ); // Pupli
line ( ep ( 506, 450 ), ep ( 685, 342 ), $sw, true ); // Elye lid

endSvg ();
$r = ob_get_contents ();
ob_end_clean ();
header ( 'Content-type: image/svg+xml' );
echo $r;
// file_put_contents("/tmp/angrysnail.svg", $r);
?>