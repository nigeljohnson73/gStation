<?php

function lastTemp() {
	$n = 7;
	$sig_temp_diff = 0.001; // anything is good for now
	global $mysql, $temperature_buffer;
	$rows = $mysql->query ( "SELECT * FROM temperature_logger ORDER BY entered DESC LIMIT " . $n );
	$ret = null;
	if (is_array ( $rows ) && count ( $rows ) > 0) {
		$dems = array ();
		$temps = array ();
		// $times = array ();
		foreach ( $rows as $row ) {
			$row = ( object ) $row;
			$temps [] = $row->temperature;
			$dems [] = $row->demanded;
			$times [] = timestamp2Time ( $row->entered );
		}
		// print_r($temps);
		// $temps_raw = $temps;
		$fall = $temps [0] - $temps [count ( $temps ) - 1];
		$crawl = $times [0] - $times [count ( $times ) - 1];
		$m = $fall / $crawl;

		$ret ["demanded"] = sprintf ( "%02.03f", $dems [count ( $dems ) - 1] );
		$ret ["temperature"] = sprintf ( "%02.03f", $temps [count ( $temps ) - 1] );
		$ret ["direction"] = (abs ( $m ) <= $sig_temp_diff) ? (0) : (($m > 0) ? (1) : (- 1));
		$ret ["dbg_fall"] = sprintf ( "%02.03f", $fall );
		$ret ["dbg_crawl"] = sprintf ( "%02.03f", $crawl );
		$ret ["dbg_grad"] = sprintf ( "%02.03f", $m );

		$str = "lastTemp($n):";
		$str .= ", Fall:" . sprintf ( "%0.3d", $fall ) . "° ";
		$str .= ", Crawl:" . sprintf ( "%0.1d", $crawl ) . "s ";
		$str .= ", Grad:" . sprintf ( "%0.3d", $m );
		$str .= ", Dem:" . sprintf ( "%0.2d", $ret ["demanded"] );
		$str .= ", Temp:" . sprintf ( "%0.2d", $ret ["temperature"] );
		$str .= ", Dir:" . $ret ["direction"];
		logger ( LL_DBG, $str );
	}
	return ( object ) $ret;
}

function setLight($val) {
	global $hl_light_pin, $hl_high_value;
	// $cmd = "sh " . realpath ( dirname ( __FILE__ ) . "/../sh/gpio.sh" ) . " " . $hl_light_pin . " " . $val . " 2>&1";
	// $cmd = "echo " . $val . " > /sys/class/gpio/gpio" . $hl_light_pin . "/value";
	$cmd = "gpio -g write " . $hl_light_pin . " " . $val;
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	ob_end_clean ();
	logger ( LL_DEBUG, "setLight(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $cmd );
	// logger ( LL_DEBUG, "setLight(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $last_line );
}

function setHeat($val) {
	global $hl_heat_pin, $hl_high_value;
	// $cmd = "sh " . realpath ( dirname ( __FILE__ ) . "/../sh/gpio.sh" ) . " " . $hl_heat_pin . " " . $val . " 2>&1";
	// $cmd = "echo " . $val . " > /sys/class/gpio/gpio" . $hl_heat_pin . "/value";
	$cmd = "gpio -g write " . $hl_heat_pin . " " . $val;
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	ob_end_clean ();
	logger ( LL_DEBUG, "setHeat(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $cmd );
	// logger ( LL_DEBUG, "setHeat(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $last_line );
}

function setOled($text) {
	$cmd = "echo '" . $text . "' > /tmp/oled.txt";
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	ob_end_clean ();
	logger ( LL_DEBUG, "setOled(): " . $cmd );
	// logger ( LL_DEBUG, "setOled(): " . $last_line );
}

function readSensors($quiet = false) {
	global $mysql;

	// if ($use_dht) {
	// $cmd = "python3 " . dirname ( __FILE__ ) . "/dht22.py 2>&1";
	// ob_start ();
	// $last_line = @system ( $cmd, $retval );
	// // $last_line = "T:25.9|H:53.3";
	// ob_end_clean ();

	// if ($last_line [0] != 'T') {
	// $msg = "tick(): Unable to determine local temperature/humidity";
	// logger ( LL_DEBUG, $msg );
	// if (! $quiet) {
	// echo $msg . "\n";
	// }
	// } else {
	// @list ( $temperature, $humidity ) = @explode ( "|", $last_line );
	// $temperature = @explode ( ":", $temperature ) [1] + 0;
	// $demand_temperature = getConfig ( "temperature_demand", 999999 );
	// $mysql->query ( "REPLACE INTO temperature_logger (temperature, demanded) VALUES (?, ?)", "dd", array (
	// $temperature,
	// $demand_temperature
	// ) );
	// $msg = "tick(): Local temperature: " . $temperature . "C";
	// logger ( LL_DEBUG, $msg );
	// if (! $quiet) {
	// echo $msg . "\n";
	// }

	// $humidity = @explode ( ":", $humidity ) [1] + 0;
	// $demand_humidity = getConfig ( "humidity_demand", 999999 );
	// $mysql->query ( "REPLACE INTO humidity_logger (humidity, demanded) VALUES (?, ?)", "dd", array (
	// $humidity,
	// $demand_temperature
	// ) );
	// $msg = "tick(): Local humidity: " . $humidity . "C";
	// logger ( LL_DEBUG, $msg );
	// if (! $quiet) {
	// echo $msg . "\n";
	// }
	// }
	// } else {
	$cmd = "cat /sys/bus/w1/devices/28-*/w1_slave 2>&1";
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	// $last_line = "67 01 4c 46 7f ff 0c 10 c4 t=22437";
	ob_end_clean ();

	@list ( $dummy, $temperature ) = explode ( " t=", $last_line );
	if ($temperature == "") {
		$msg = "tick(): Unable to determine local temperature";
		logger ( LL_DEBUG, $msg );
		if (! $quiet) {
			echo $msg . "\n";
		}
	} else {
		$temperature = ($temperature + 0) / 1000;
		$demand_temperature = getConfig ( "temperature_demand", null );

		if ($demand_temperature !== null) {
			$mysql->query ( "REPLACE INTO temperature_logger (temperature, demanded) VALUES (?, ?)", "dd", array (
					$temperature,
					$demand_temperature
			) );
		}

		$msg = "tick(): Local temperature: " . $temperature . "C";
		logger ( LL_DEBUG, $msg );
		if (! $quiet) {
			echo $msg . "\n";
		}
	}
	// }
}

function otick($quiet = false) {
	global $lat, $lng, $day, $mon, $bulksms_notify, $bulksms_alert_sunrise, $bulksms_alert_sunset;
	global $hl_high_value, $hl_low_value;
	global $mysql;
	global $temperature_buffer;

	$tsnow = timestampNow ();
	$nowOffset = timestampFormat ( $tsnow, "H" ) * 60 * 60 + timestampFormat ( $tsnow, "i" ) * 60 + timestampFormat ( $tsnow, "s" );

	$last_status = getConfig ( "status", "NIGHT" );

	/**
	 * *************************************************************************************************************************************
	 * Get the weather data from the database
	 */
	$data = getModel ( $tsnow );

	/**
	 * *************************************************************************************************************************************
	 * Calculate the sunset/rise times.
	 */
	$status = (( int ) ($nowOffset) >= ( int ) ($data->sunriseOffset) && ( int ) ($nowOffset) <= ( int ) ($data->sunsetOffset)) ? ("DAY") : ("NIGHT");

	$msg = "status is still '" . $last_status . "'";
	if ($last_status != $status) {
		$msg = "status changed from '" . $last_status . "' to '" . $status . "'";
		logger ( LL_INFO, "tick(): " . $msg );
		setConfig ( "status", $status );
		if (($status == "DAY" && $bulksms_alert_sunrise) || ($status == "NIGHT" && $bulksms_alert_sunset)) {
			sendSms ( $msg, $bulksms_notify );
		}
	} else {
		logger ( LL_DEBUG, "tick(): " . $msg );
	}
	setLight ( ($status == "DAY") ? ($hl_high_value) : ($hl_low_value) );

	/**
	 * *************************************************************************************************************************************
	 * Read the local environmental sensors
	 */
	readSensors ( $quiet );

	/**
	 * *************************************************************************************************************************************
	 * Work with the temperature
	 */
	$demand_temperature = ($status == "DAY") ? ($data->temperatureHigh) : ($data->temperatureLow);
	setConfig ( "temperature_demand", $demand_temperature );

	$lt = lastTemp ();
	if (! $quiet) {
		echo "Last temp: " . ob_print_r ( $lt ) . "\n";
	}
	$direction_temperature = $lt->direction;
	setConfig ( "temperature_direction", $direction_temperature );

	$temperature = $lt->temperature;
	setConfig ( "temperature", $temperature );

	// Work out whether we need to switch the heater on
	$heat = ($demand_temperature - $temperature) > 0;
	setHeat ( ($heat) ? ($hl_high_value) : ($hl_low_value) );

	/**
	 * *************************************************************************************************************************************
	 * Send the sumary to the OLED display
	 */
	// $str = round ( $temperature, 2 ) . "° " . $status . " " . (($heat) ? ("(#)") : ("(_)"));
	$ostr = "";
	$ostr .= "" . sprintf ( "%02.1f", $temperature ) . "°";
	// $ostr .= $direction_temperature. " ";
	$ostr .= " D" . (($direction_temperature == 0) ? ("-") : (($direction_temperature > 0) ? ("^") : ("v")));
	// $ostr .= " " . sprintf ( "%02.1f", $demand_temperature ) . "°";
	$ostr .= " H" . (($heat) ? ("X") : ("-"));
	$ostr .= " L" . (($status == "DAY") ? ("X") : ("-"));
	$ostr .= "|";
	$ostr .= nextSunChange ();
	// $ostr .= (($heat) ? ("H[#]") : ("H[ ]")) . " " . (($status == "DAY") ? ("[#]L") : ("[ ]L"));

	// $status . " " . (($heat) ? ("(#)") : ("(_)"));
	$lstr = "";
	$lstr .= "Temp: " . sprintf ( "%0.02f", $lt->temperature ) . "°";
	$lstr .= ", Dem: " . sprintf ( "%0.02f", $lt->demanded ) . "°";
	$lstr .= ", Dir: " . (($lt->direction == 0) ? ("-") : (($lt->direction > 0) ? ("U") : ("D")));
	$lstr .= " (Fall: " . sprintf ( "%0.03f", $lt->dbg_fall ) . "°";
	$lstr .= ", Crawl: " . sprintf ( "%0.01f", $lt->dbg_crawl ) . "s";
	$lstr .= ", Grad: " . sprintf ( "%0.03f", $lt->dbg_grad );
	$lstr .= ")";
	$lstr .= ", OLED: '" . $ostr . "'";
	// $log = timestampFormat ( timestampNow (), "H:i:s" ) . "; dem: " . round ( $demand_temperature, 2 ) . ", act: " . round ( $temperature, 2 ) . ", dir: $direction_temperature, OLED: '$str'";
	logger ( LL_INFO, $lstr );
	echo "$lstr\n";
	setOled ( $ostr );
}

function getModeledDataFields($arr) {
	$model = getModel ();
	$yr = timestampFormat ( timestampNow (), "Y" );
	$data = array ();
	foreach ( $model as $k => $v ) {
		$time = timestamp2Time ( $yr . $k );
		foreach ( $arr as $kk ) {
			if (! isset ( $data [$kk] )) {
				$data [$kk] = array ();
			}
			$data [$kk] [$time] = $v->$kk;
		}
	}
	return $data;
}

function modelStatus() {
	global $mysql;

	$ret = new StdClass ();

	global $darksky_key;
	if ($darksky_key !== "") {
		$ret->modelUsed = "DarkSky";
		$raw = getDarkSkyDataPoints ( null, true );
		$valid = getDarkSkyDataPoints ( null, false );
		$ret->dataPointTotal = count ( $raw );
		$ret->dataPointValid = count ( $valid );
		$ret->dataPointInvalid = count ( $raw ) - count ( $valid );
		$ret->dataPointPerDay = floor ( count ( $raw ) / 365 );
	} else {
		$ret->modelUsed = "Simulation";
	}
	$rows = $mysql->query ( "select max(last_updated) as ud from model" );
	if ($rows && count ( $rows )) {
		$ret->lastModelRebuild = $rows [0] ["ud"];
	}
	return $ret;
}

function nextSunChange() {
	$tsnow = timestampNow ();
	$midnight = timestamp2Time ( timestampFormat ( $tsnow, "Ymd" ) . "000000" );
	$nowoffset = timestamp2Time ( $tsnow ) - $midnight;
	$today = timestampFormat ( $tsnow, "Ymd" );
	$tomorrow = timestampFormat ( timestampAdd ( $tsnow, numDays ( 1 ) ), "Ymd" );
	echo "Today: $today\n";
	echo "Tomorrow: $tomorrow\n";

	$model = getModel ( array (
			$today,
			$tomorrow
	) );
	// print_r ( $model );

	$ret = "";
	if ($nowoffset < $model [timestampFormat ( $today, "md" )]->sunriseOffset) {
		$secs = $model [timestampFormat ( $today, "md" )]->sunriseOffset - $nowoffset;
		if ($secs > 59) {
			$ret = "Sunrise " . periodFormat ( $secs, true );
		} else {
			$ret = "Sunrise < 1m";
		}
	} elseif ($nowoffset < $model [timestampFormat ( $today, "md" )]->sunsetOffset) {
		$secs = $model [timestampFormat ( $today, "md" )]->sunsetOffset - $nowoffset;
		if ($secs > 59) {
			$ret = "Sunset " . periodFormat ( $secs, true );
		} else {
			$ret = "Sunset < 1m";
		}
	} else {
		$secs = $model [timestampFormat ( $tomorrow, "md" )]->sunriseOffset - $nowoffset + (24 * 60 * 60);
		if ($secs > 59) {
			$ret = "Sunrise " . periodFormat ( $secs, true );
		} else {
			$ret = "Sunrise < 1m";
		}
	}
	return $ret;
}

?>
