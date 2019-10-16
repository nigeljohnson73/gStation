<?php

function setupTables() {
	global $mysql;

	$str = "
		CREATE TABLE IF NOT EXISTS history (
			id VARCHAR(8) NOT NULL PRIMARY KEY,
			data MEDIUMTEXT NOT NULL,
			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)";
	$mysql->query ( $str );

	$str = "
		CREATE TABLE IF NOT EXISTS model (
			id VARCHAR(8) NOT NULL PRIMARY KEY,
			data MEDIUMTEXT NOT NULL,
			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)";
	$mysql->query ( $str );

	$str = "
		CREATE TABLE IF NOT EXISTS config (
			id VARCHAR(64) NOT NULL PRIMARY KEY,
			data MEDIUMTEXT NOT NULL,
			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)";
	$mysql->query ( $str );

	$str = "
		CREATE TABLE IF NOT EXISTS temperature_logger (
			entered TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL PRIMARY KEY,
	 		demanded FLOAT NOT NULL,
	 		temperature FLOAT NOT NULL
		)";
	$mysql->query ( $str );
}

function clearSensorLogger() {
	global $mysql;
	setupTables ();
	$mysql->query ( "DELETE FROM temperature_logger where entered < '" . timestampFormat ( timestampAdd ( timestampNow (), numDays ( - 1 ) ), "Y-m-d H:i:s" ) . "'" );
	// $mysql->query ( "DELETE FROM temperature_gradient_logger where entered < '" . timestampFormat ( timestampAdd ( timestampNow (), numDays ( - 1 ) ), "Y-m-d H:i:s" ) . "'" );
}

function lastTemp() {
	$n = 2;
	global $mysql, $temperature_buffer;
	$rows = $mysql->query ( "SELECT * FROM temperature_logger ORDER BY entered DESC LIMIT " . $n );
	$ret = null;
	if (is_array ( $rows ) && count ( $rows ) > 0) {
		$ret ["demanded"] = $rows [0] ["demanded"];
		$temps = array ();
		// $times = array ();
		foreach ( $rows as $row ) {
			$row = ( object ) $row;
			$temps [] = $row->temperature;
			$times [] = timestamp2Time ( $row->entered );
		}
		// $temps_raw = $temps;
		$fall = $temps [0] - $temps [count ( $temps ) - 1];
		$crawl = $times [0] - $times [count ( $times ) - 1];
		$m = $fall / $crawl;

		$ret ["temperature"] = $temps [count ( $temps ) - 1];
		$ret ["direction"] = ($m == 0) ? (0) : (($m > 0) ? (1) : (0));

		$str = "lastTemp($n):";
		$str .= ", F:" . sprintf ( "%02.3f", $fall ) . "° ";
		$str .= ", C:" . sprintf ( "%02.3f", $crawl ) . "s ";
		$str .= ", M:" . sprintf ( "%02.3f", $m );
		$str .= ", T:" . sprintf ( "%02.3f", $ret ["temperature"] );
		$str .= ", DIR:" . $ret ["direction"];
		logger ( LL_INFO, $str );
	}
	return ( object ) $ret;
}

function getConfig($id, $default = false) {
	global $mysql;
	$ret = $mysql->query ( "SELECT data FROM config WHERE id = ?", "s", array (
			$id
	) );
	if (is_array ( $ret ) && count ( $ret ) > 0) {
		return $ret [0] ["data"];
	}
	return $default;
}

function setConfig($id, $value) {
	global $mysql;
	$ret = $mysql->query ( "REPLACE INTO config (id, data) VALUES (?, ?)", "ss", array (
			$id,
			$value
	) );
}

function setLight($val) {
	global $hl_light_pin, $hl_high_value;
	// $cmd = "sh " . realpath ( dirname ( __FILE__ ) . "/../sh/gpio.sh" ) . " " . $hl_light_pin . " " . $val . " 2>&1";
	$cmd = "echo " . $val . " > /sys/class/gpio/gpio" . $hl_light_pin . "/value";
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	ob_end_clean ();
	logger ( LL_DEBUG, "setLight(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $cmd );
	// logger ( LL_DEBUG, "setLight(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $last_line );
}

function setHeat($val) {
	global $hl_heat_pin, $hl_high_value;
	// $cmd = "sh " . realpath ( dirname ( __FILE__ ) . "/../sh/gpio.sh" ) . " " . $hl_heat_pin . " " . $val . " 2>&1";
	$cmd = "echo " . $val . " > /sys/class/gpio/gpio" . $hl_heat_pin . "/value";
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
		$demand_temperature = getConfig ( "temperature_demand", 999999 );

		$mysql->query ( "REPLACE INTO temperature_logger (temperature, demanded) VALUES (?, ?)", "dd", array (
				$temperature,
				$demand_temperature
		) );

		$msg = "tick(): Local temperature: " . $temperature . "C";
		logger ( LL_DEBUG, $msg );
		if (! $quiet) {
			echo $msg . "\n";
		}
	}
	// }
}

function tick($quiet = false) {
	global $lat, $lng, $day, $mon, $bulksms_owner_sms, $bulksms_alert_sunrise, $bulksms_alert_sunset;
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
			sendSms ( $msg, $bulksms_owner_sms );
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

	$temperature = lastTemp ();
	if (! $quiet) {
		echo "Last temp: " . ob_print_r ( $temperature ) . "\n";
	}
	$direction_temperature = $temperature->direction;
	setConfig ( "temperature_direction", $direction_temperature );

	$temperature = $temperature->temperature;
	setConfig ( "temperature", $temperature );

	// Work out whether we need to switch the heater on
	$heat = ($demand_temperature - $temperature) > 0;
	setHeat ( ($heat) ? ($hl_high_value) : ($hl_low_value) );

	/**
	 * *************************************************************************************************************************************
	 * Send the sumary to the OLED display
	 */
	// $str = round ( $temperature, 2 ) . "° " . $status . " " . (($heat) ? ("(#)") : ("(_)"));
	$str = "" . sprintf ( "%02.1f", $temperature ) . "° ";
	$str .= ($direction_temperature == 0) ? ("-") : (($direction_temperature > 0) ? ("^") : ("v"));
	// $str .= ($direction_temperature == 0) ? ("--") : (($direction_temperature > 0) ? ("/\\") : ("\\/"));
	$str .= " " . sprintf ( "%02.1f", $demand_temperature ) . "°";
	$str .= "|";
	$str .= (($heat) ? ("H[*]") : ("H[ ]")) . " " . (($status == "DAY") ? ("[*]L") : ("[ ]L"));

	// $status . " " . (($heat) ? ("(#)") : ("(_)"));
	$log = timestampFormat ( timestampNow (), "H:i:s" ) . "; dem: " . round ( $demand_temperature, 2 ) . ", act: " . round ( $temperature, 2 ) . ", dir: $direction_temperature, OLED: '$str'";
	logger ( LL_INFO, $log );
	echo "$log\n";
	setOled ( $str );
}

function darkSkyObj($data, $id = null) {
	global $mysql;
	$obj = new StdClass ();

	$temperature_high_labels = array (
			"apparentTemperatureHigh",
			"temperatureHigh",
			"apparentTemperatureMax",
			"temperatureMax"
	);
	$temperature_low_labels = array (
			"apparentTemperatureLow",
			"temperatureLow",
			"apparentTemperatureMin",
			"temperatureMin"
	);

	$utcOffset = $data->offset * 3600;

	if (! isset ( $data->daily->data [0] )) {
		return null;
	}
	$dso = $data->daily->data [0];

	if (! isset ( $dso->time )) {
		echo "    Missing core data 'time'\n";
		return null;
	}
	if (! isset ( $dso->sunriseTime )) {
		echo "    Missing core data 'sunriseTime'\n";
		return null;
	}
	if (! isset ( $dso->sunsetTime )) {
		echo "    Missing core data 'sunriseTime'\n";
		return null;
	}

	$tm = $dso->time;
	$obj->cloudCover = firstOf ( $dso, "cloudCover" );
	$obj->humidity = firstOf ( $dso, "humidity" );
	$obj->lunation = firstOf ( $dso, "moonPhase" );
	$obj->pressure = firstOf ( $dso, "pressure" );
	$obj->sunriseOffset = $dso->sunriseTime - $dso->time - $utcOffset;
	$obj->sunsetOffset = $dso->sunsetTime - $dso->time - $utcOffset;
	$obj->temperatureHigh = firstOf ( $dso, $temperature_high_labels );
	$obj->temperatureLow = firstOf ( $dso, $temperature_low_labels );
	$obj->daylightHours = ($obj->sunsetOffset - $obj->sunriseOffset) / 3600;
	$obj->windSpeed = firstOf ( $dso, "windSpeed" );

	return $obj;
}

function getDarkSkyDataPoints($id_list = null, $raw = false) {
	global $mysql;

	$sql = "SELECT * FROM history";
	if (is_array ( $id_list )) {
		$inlist = "'" . implode ( "','", $id_list ) . "'";
		$sql .= " where id in (" . $inlist . ")";
	}
	// Get the historic data that exists in the database already...
	// echo "SQL: " . $sql . "\n";
	$rows = $mysql->query ( $sql );

	$ret = array ();
	foreach ( $rows as $r ) {
		if ($raw) {
			$ret [$r ["id"]] = $r ["data"];
		} else {
			// echo "" . print_r ( json_decode ( $r ["data"] ) ) . "\n";
			$obj = darkSkyObj ( json_decode ( $r ["data"] ), $r ["id"] );
			if ($obj) {
				$ret [$r ["id"]] = $obj;
			}
		}
	}

	// Send it back
	return $ret;
}

function getDarkSkyApiData($force_recall) {
	global $darksky_key;
	global $mysql;
	global $yr_history, $dy_history, $lat, $lng, $api_call_cap;

	// Calculate how many days to go back
	$total_ndays = $yr_history * 365;

	// Start from tomorrow for consistency
	$ts = timestampFormat ( timestampNow (), "Ymd" );

	// Iterate through all the data points we need
	$force = array (); // calls we have to make regardless
	$dates = array (); // the rest of the days we think we need
	for($i = 0; $i < $total_ndays; $i ++) {
		$ts = timestampFormat ( $ts, "Ymd" );
		if ($i <= $force_recall) {
			$force [] = $ts;
		} else {
			$dates [$ts] = $ts;
		}
		$ts = timestampAdd ( $ts, numDays ( - 1 ) );
	}
	logger ( LL_DEBUG, "getDarkSkyApiData(): data points required: " . (count ( $force ) + count ( $dates )) );

	// Get any data points we already have
	$data_points = getDarkSkyDataPoints ( $dates, true );
	logger ( LL_DEBUG, "getDarkSkyApiData(): forced requests: " . count ( $force ) );
	logger ( LL_DEBUG, "getDarkSkyApiData(): data points available: " . count ( $data_points ) );
	foreach ( $data_points as $k => $v ) {
		if (isset ( $dates [$k] )) {
			unset ( $dates [$k] );
		}
	}
	// echo "Forced calls for :\n" . ob_print_r ( $force ) . "\n";
	// echo "Regular calls calls for :\n" . ob_print_r ( $dates ) . "\n";

	// generate our call list
	$calls = array_merge ( $force, array_values ( $dates ) );
	logger ( LL_INFO, "getDarkSkyApiData(): API calls required: " . (count ( $calls )) );

	// If we need too many, truncate the list
	if (count ( $calls ) > $api_call_cap) {
		$c = 0;
		foreach ( $calls as $k => $v ) {
			if ($c >= $api_call_cap) {
				unset ( $calls [$k] );
			}
			$c ++;
		}
		logger ( LL_INFO, "getDarkSkyApiData(): API calls capped at " . count ( $calls ) );
	}

	$c = 0;
	foreach ( $calls as $ts ) {
		$c += 1;

		$ts_api = timestampFormat ( $ts, "Y-m-d\TH:i:s" );
		$ts_ymd = timestampFormat ( $ts, "Ymd" );

		$call = "https://api.darksky.net/forecast/" . $darksky_key . "/" . $lat . "," . $lng . "," . $ts_api . "?units=si&exclude=currently,minutely,hourly,alerts";
		echo "    Getting " . $c . " of " . count ( $calls ) . ": " . timestampFormat ( $ts, "Y-m-d" );
		logger ( LL_DEBUG, "Calling API: " . $call );
		$data = @file_get_contents ( $call );
		if ($data) {
			// $chk = json_decode ( $data );
			// if (! isset ( $chk->daily )) {
			// echo " - FAILED - Missing daily data\n";
			// } else {
			echo " - OK\n";
			// }

			// Generate an object of all the values
			$values = array (
					"id" => $ts_ymd,
					"data" => $data
			);

			$mysql->query ( "REPLACE INTO history (id, data) VALUES(?, ?)", "ss", array_values ( $values ) );
			// }
		} else {
			echo " - FAILED - API rejection\n";
			// Log the failure
			logger ( LL_WARNING, "getDarkSkyData(): API failure for " . timestampFormat ( $ts, "Y-m-d" ) );
		}
	}
}

function rebuildDataModel() {
	global $mysql;

	// Get all the data we have. This will pop at some point. TODO: probably cap this to something!!
	$hist = getDarkSkyDataPoints ();
	logger ( LL_INFO, "rebuildDataModel(): Processing " . count ( $hist ) . " data points" );

	global $season_adjust_days, $timeszone_adjust_hours, $smoothing_days, $smoothing_loops;

	// Temporary store for the day/month combo data
	$store = array ();
	foreach ( $hist as $k => $v ) {
		$nk = timestampAdd ( $k, numDays ( - $season_adjust_days ) );
		// echo "Season adjust: ".timestampFormat($k, "c"). " --> ".timestampFormat($nk, "c")."\n";
		$ts = timestampFormat ( $nk, "md" );
		if (! isset ( $store [$ts] )) {
			$store [$ts] = array ();
		}
		$store [$ts] [] = $v;
	}

	// Deleteing any leap data. Leap days are either the day after Feb 28, or the day before Mar 01
	if (isset ( $store ["0229"] )) {
		unset ( $store ["0229"] );
	}
	logger ( LL_INFO, "rebuildDataModel(): Processed into " . count ( $store ) . " day model" );

	// flatten the day data into day/month averages
	$model = array ();
	foreach ( $store as $k => $v ) {
		$v = averageObjectArray ( $v );

		// Alter the timeszone times
		$time_offset = array (
				"sunriseOffset",
				"sunsetOffset"
		);
		$day_secs = 24 * 60 * 60;
		foreach ( $time_offset as $o ) {
			$v->$o += $timeszone_adjust_hours * 60 * 60;
			// clamp to within day
			$v->$o = ($v->$o < 0) ? ($v->$o + $day_secs) : (($v->$o >= $day_secs) ? ($v->$o - $day_secs) : ($v->$o));
		}

		// We are done here
		$model [$k] = $v;
	}

	logger ( LL_INFO, "rebuildDataModel(): Flattened model by averages per day" );

	// Perform smoothing
	// $k is the month-day key
	// $v in the day object
	// $pk is the paramater key within the day object

	$smooth_exclude = array (
			"lunation"
	);
	$store = array ();

	// First collect all the paramaeter data. Store by field key to sort, then model key to put back
	foreach ( $model as $k => $v ) {
		// Convert to an array so we can field surf
		$v = ( array ) $v;
		foreach ( $v as $pk => $val ) {
			if (! in_array ( $pk, $smooth_exclude )) {
				if (! isset ( $store [$pk] )) {
					$store [$pk] = array ();
				}
				// if($k[0]=="0" && $k[1]=="1" && $pk == "temperatureHigh"){
				// echo "Storing ".$pk."(".$k."): ".$val."\n";
				// }
				$store [$pk] [$k] = $val;
			}
		}
	}
	logger ( LL_INFO, "rebuildDataModel(): Smoothing: calculated data keys" );

	// Smooth the parameters
	// echo "Going in '".array_keys($store)[0]."': ".ob_print_r($store[array_keys($store)[0]])."\n";
	foreach ( $store as $pk => $vals ) {
		// Sort so they are in the right order. Don't know how they get out of order but still
		ksort ( $vals );
		$store [$pk] = smoothValues ( $vals, $smoothing_days, $smoothing_loops );
	}
	logger ( LL_INFO, "rebuildDataModel(): Smoothing: performed smoothing" );
	// echo "Coming out '".array_keys($store)[0]."': ".ob_print_r($store[array_keys($store)[0]])."\n";

	// Now put everything back where it was
	foreach ( $store as $pk => $vals ) {
		foreach ( $vals as $k => $val ) {
			$model [$k]->$pk = $val;
		}
	}
	logger ( LL_INFO, "rebuildDataModel(): Smoothing: updated model" );

	// Update the model table
	foreach ( $model as $k => $v ) {
		$values = array (
				"id" => $k,
				"data" => json_encode ( $v )
		);

		$mysql->query ( "REPLACE INTO model (id, data) VALUES(?, ?)", "ss", array_values ( $values ) );
	}
	logger ( LL_INFO, "rebuildDataModel(): Stored model to database" );
}

function getModel($ts = null) {
	$sql = "SELECT * FROM model";
	if ($ts != null) {
		$sql .= " WHERE id = '" . timestampFormat ( $ts, "md" ) . "'";
	}
	global $mysql;
	$rows = $mysql->query ( $sql );
	$ret = array ();
	foreach ( $rows as $row ) {
		$k = $row ["id"];
		$v = json_decode ( $row ["data"] );
		$ret [$k] = $v;
	}
	if (count ( $ret ) == 1) {
		return $ret [array_keys ( $ret ) [0]];
	}
	return $ret;
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
	$raw = getDarkSkyDataPoints ( null, true );
	$valid = getDarkSkyDataPoints ( null, false );

	$ret->dataPointTotal = count ( $raw );
	$ret->dataPointValid = count ( $valid );
	$ret->dataPointInvalid = count ( $raw ) - count ( $valid );
	$ret->dataPointPerDay = floor ( count ( $raw ) / 365 );

	$rows = $mysql->query ( "select max(last_updated) as ud from model" );
	if ($rows && count ( $rows )) {
		$ret->lastModelRebuild = $rows [0] ["ud"];
	}
	return $ret;
}
?>
