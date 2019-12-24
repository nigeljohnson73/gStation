<?php 
$sensors_enumerated = false;

function enumerateSensors() {
	global $sensors_enumerated, $sensors, $pin_enum;
	
	if ($sensors_enumerated) {
		return;
	}
	
	foreach ( $sensors as $pin=>$s ) {
		$s->enumeration = enumeration ( $s->type );
		$gpio_pin = "sensor_pin_" . ($pin+1);
		global $$gpio_pin;
		$s->pin = $$gpio_pin;
		$s->ofn = "/tmp/gs_sensor_".($pin+1).".json";
	}
}

$w1_enum = 1;
$iio_enum = 0;

function enumeration($type) {
	$ret = null;
	global $w1_enum;
	global $iio_enum;
	
	switch ($type) {
		case "DS18B20" :
			$bn = "/sys/bus/w1/devices/w1_bus_master" . $w1_enum . "/28-*/w1_slave";
			$w1_enum = $w1_enum + 1;
			$ret = array ();
			$obj = new stdClass ();
			$obj->name = "temperature";
			$obj->file = $bn . "/in_temp_input";
			$ret [] = $obj;
			break;
			
		case "DHT11" :
		case "DHT22" :
			$bn = "/sys/bus/iio/devices/iio:device" . $iio_enum;
			$iio_enum = $iio_enum + 1;
			$ret = array ();
			$obj = new StdClass ();
			$obj->name = "temperature";
			$obj->file = $bn . "/in_temp_input";
			$ret [] = $obj;
			$obj = new StdClass ();
			$obj->name = "humidity";
			$obj->file = $bn . "/in_humidityrelative_input";
			$ret [] = $obj;
			break;
	}
	
	return $ret;
}

function overlay($type) {
	$ret = "w1-gpio";
	
	switch ($type) {
		case "DHT11" :
		case "DHT22" :
			$ret = "dht11";
			break;
	}
	
	return $ret;
}

function createSensorSetupScript() {
	global $sensors;
	enumerateSensors ();
	
	$ret = "";
	
	foreach ( $sensors as $s ) {
		$ret .= "dtoverlay ";
		$ret .= overlay ( $s->type );
		$ret .= " gpiopin=" . $s->pin . "\n";
	}
	
	return $ret;
}

function readSensorRaw_DS18B20($sensor) {
	$ret = new StdClass();
	foreach($sensor->enumeration as $e) {
		$c = file_get_contents($e->file);
//		echo $c;
		$param = $e->$name;
		$ret->$param = $c;
	}
	return $ret;
}
function readSensorRaw_DHT11($sensor) {
	$ret = new StdClass();
	foreach($sensor->enumeration as $e) {
		$c = file_get_contents($e->file);
		$param = $e->$name;
		$ret->$param = $c;
	}
	return $ret;
}
function readSensorRaw_DHT22($sensor) {
	return readSensorRaw_DHT11($sensor);
}
function readSensor($i) {
	global $sensors;

	// $i will be 1 based (1, 2, 3 etc) so make sure it's -1'd
	$sensor = $sensors[$i-1];
	$func = "readSensorRaw_".$sensor->type;
	$ret = $func($sensor);
	print_r($ret);
}
?>