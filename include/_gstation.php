<?php

function setupSensorScript() {
	$ofn = dirname ( __FILE__ ) . "/../sh/start_sensors.sh";
	$str = "";
	$str .= "#!/bin/sh\n";
	$str .= createSensorSetupScript ();

	file_put_contents ( $ofn, $str );
}

$sensors_enumerated = false;

function enumerateSensors() {
	global $sensors_enumerated, $sensors, $pin_enum;

	if ($sensors_enumerated) {
		return;
	}

	foreach ( $sensors as $pin => $s ) {
		$s->enumeration = enumeration ( $s->type );
		$gpio_pin = "sensor_pin_" . ($pin + 1);
		global $$gpio_pin;
		$s->pin = $$gpio_pin;
		$s->ofn = "/tmp/gs_sensor_" . ($pin + 1) . ".json";
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
			$obj->file = $bn;
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

// How long to give the sensor time to refresh
function sensorCooloff($type) {
	$ret = 2;

	switch ($type) {
		case "DHT11" :
		case "DHT22" :
			$ret = 3;
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
	$ret = new StdClass ();

	foreach ( $sensor->enumeration as $e ) {
		$output = null;
		$retvar = 0;
		$cmd = "cat " . $e->file;
		$val = null;

		$retry_count = 0;
		$retry_limit = 5;
		$retry = false;
		do {
			if ($retry) {
				echo ("Read $retry_count failed, pausing and retrying\n");
				sleep ( 1 );
			}
			$retry_count = $retry_count + 1;
			$retry = true;

			exec ( $cmd, $output, $retvar );
			if (is_array ( $output ) && count ( $output ) == 2) {
				echo ("Got correct line count:\n" . ob_print_r ( $output ) . "\n");
				if (preg_match ( '/crc=[a-f0-9]{2} YES/', $output [0] )) {
					echo ("CRC passed\n");
					list ( $dummy, $temp ) = explode ( "t=", $output [1] );
					echo ("Got temp: " . $temp . "\n");
					$val = (( double ) $temp) / 1000.0;
					echo ("Set val: " . $val . "\n");
					$output = null;
				} else {
					echo ("CRC check failed\n");
				}
			} else {
				echo ("Got incorrect line count:\n" . ob_print_r ( $output ) . "\n");
			}
		} while ( $val == null && $retry_count < $retry_limit );

		if ($val == null) {
			echo "Read sensor failed.\n";
			return null;
		}
		$param = $e->name;
		$ret->$param = $val;
	}
	return $ret;
}

function readSensorRaw_DHT11($sensor) {
	$ret = new StdClass ();

	foreach ( $sensor->enumeration as $e ) {
		$output = null;
		$retvar = 0;
		$cmd = "cat " . $e->file;
		$val = null;

		$retry_count = 0;
		$retry_limit = 10;
		$retry = false;
		do {
			if ($retry) {
				echo ("Read $retry_count failed, pausing and retrying\n");
				sleep ( 1 );
			}
			$retry_count = $retry_count + 1;
			$retry = true;

			exec ( $cmd, $output, $retvar );
			echo ("Got:\n" . ob_print_r ( $output ) . "\n");

			// if(is_array($output) && count($output) == 2) {
			// echo ("Got correct line count:\n".ob_print_r($output)."\n");
			// if(preg_match('/crc=[a-f0-9]{2} YES/', $output[0])) {
			// echo ("CRC passed\n");
			// list($dummy, $temp) = explode("t=", $output[1]);
			// echo ("Got temp: ".$temp."\n");
			// $val = ((double)$temp)/1000.0;
			// echo ("Set val: ".$val."\n");
			// $output=null;
			// } else {
			// echo ("CRC check failed\n");
			// }
			// } else {
			// echo ("Got incorrect line count:\n".ob_print_r($output)."\n");
			// }
		} while ( $val == null && $retry_count < $retry_limit );

		if ($val == null) {
			echo "Read sensor failed.\n";
			return null;
		}
		$param = $e->name;
		$ret->$param = $val;
	}
	return $ret;
}

function readSensorRaw_DHT22($sensor) {
	return readSensorRaw_DHT11 ( $sensor );
}

function checkSensors($type, $pin) {
	$output = null;
	$retvar = 0;
	$cmd = "sudo dtoverlay -l 2>&1";

	exec ( $cmd, $output, $retvar );
	$output = implode("\n", $output)."\n";
	print_r ( $output );
	if(preg_match('/'.overlay($type).'\s+gpiopin='.$pin.'/', $output)) {	
		return true;
	}
	return false;
}

function readSensor($i) {
	global $sensors;

	if (! isset ( $sensors [$i - 1] )) {
		echo ("No sensor defined for slot #" . $i . "\n");
		while ( true ) {
			// Endless loop
			sleep ( 30 );
		}
	}
	enumerateSensors ();

	$sensor = $sensors [$i - 1];
	$type = $sensor->type;
	$pin = $sensor->pin;

	while ( checkSensors ( $type, $pin ) == false ) {
		echo "No sensor overlay setup for a ".$type." on pin ".$pin."\n";
		sleep ( 30 );
		echo "retrying...\n";
	}

	$func = "readSensorRaw_" . $sensor->type;
	while ( true ) {
		$ret = $func ( $sensor );
		if ($ret == null) {
			echo "Garbage sensor??\n";
		} else {
			print_r ( $ret );
		}
		sleep ( sensorCooloff ( $type ) );
	}
}
?>
