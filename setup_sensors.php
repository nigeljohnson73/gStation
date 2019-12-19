<?php
include_once ("functions.php");

function overlay($type) {
	$ret = "w1-gpio";

	switch($type) {
		case "DHT11":
		case "DHT22":
			$ret = "dht11";
		break;
	}

	return $ret;
}

function setup() {
	global $zone;
	$ret = "";

	foreach($zone as $z) {
		$ret .= "dtoverlay ";
		$ret .= overlay($z->type);
		$ret .= " gpiopin=".$z->pin."\n";
	}

	return $ret;
}

$ofn = dirname ( __FILE__ ) . "/sh/start_sensors.sh";
$str = "";
$str .= "#!/bin/sh\n";
$str .= setup();

file_put_contents($ofn, $str);
?>
