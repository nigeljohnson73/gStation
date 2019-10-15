<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $loc, $local_timezone;
// $legend = "$loc Sunrise Time";
// $ndays = 365;
// $data = getSunData ( $ndays );
// unset ( $data ["daylight"] );
// unset ( $data ["sunset"] );

$legend = "$loc Modelled Daily Sunrise/Sunset Time (".$local_timezone.")";

$arr = array (
		"sunsetOffset",
		"sunriseOffset"
);
$midnight = time() - (time() % 86400);
$yr = timestampFormat(timestampNow(), "Y");

$data = getModeledDataFields ( $arr );
foreach ( $data as $k => $v ) {
	foreach($v as $kk => $vv) {
		$ts = time2Timestamp($kk + $vv);//$yr.$k.$hr.$mn.$sc;
// 		if($k == "sunsetOffset"){
// 			echo "".$ts." sunsetOffset</br>\n";
// 		}

		$dt = new DateTime('@'.round($kk + $vv,0));
		$dt->setTimeZone(new DateTimeZone($local_timezone));
		$nhr=$dt->format('H');
		$nmn=$dt->format('i');
		$nsc=$dt->format('d');
		$noff = ($nhr+0) + (($nmn+0)/60) + ($nsc+0)/3600;
// 		if($k == "sunsetOffset"){
// 			echo "&nbsp;&nbsp;&nbsp;&nbsp;".$dt->format('F j, Y, g:i a')." sunsetOffset: ".$noff."</br>\n";
// 		}
		
		$data [$k] [$kk] = $noff;
	}
}

$min_y = floor ( graphValMin ( $data ) );
$max_y = ceil ( graphValMax ( $data ) );

$y_ticks = array ();
for($i = $min_y; $i <= $max_y; $i ++) {
	$y_ticks [$i] = sprintf ( "%02d", $i ) . ":00";
}

$x_ticks = 12;
$x_subticks = 0;

$im = drawTimeGraph ( $data, $legend, $x_ticks, $x_subticks, $min_y, $max_y, $max_y - $min_y, 1, $y_ticks, "M d" );

header ( 'Content-type: image/png' );
imagepng ( $im );
imagedestroy ( $im );

?>