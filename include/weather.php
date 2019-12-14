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
	global $mysql, $logger;
	setupTables ();
	$mysql->query ( "DELETE FROM temperature_logger where entered < DATE_SUB(NOW(), INTERVAL 24 HOUR)" );
	$logger->clearLogs ();
}

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

function tick($quiet = false) {
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
	if ($darksky_key == "") {
		logger ( LL_INFO, "getDarkSkyApiData(): No API key" );
		return;
	}

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
	// echo "getDarkSkyApiData(): data points required: " . (count ( $force ) + count ( $dates ))."\n";

	// Get any data points we already have
	$data_points = getDarkSkyDataPoints ( $dates, true );
	logger ( LL_DEBUG, "getDarkSkyApiData(): forced requests: " . count ( $force ) );
	logger ( LL_DEBUG, "getDarkSkyApiData(): data points available: " . count ( $data_points ) );
	// echo "getDarkSkyApiData(): forced requests: " . count ( $force ) ."\n";
	// echo "getDarkSkyApiData(): data points available: " . count ( $data_points )."\n";
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
	// echo "getDarkSkyApiData(): API calls required: " . (count ( $calls ))."\n";
	// return;

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
	global $mysql, $darksky_key;
	$model = array ();

	$hist = null;
	// IF we have signed up for DarkSky and have a key, check how much data we have
	if ($darksky_key != "") {
		// Get all the data we have. This will pop at some point. TODO: probably cap this to something!!
		$hist = getDarkSkyDataPoints ();
	}

	// IF we have data, make sure we have enough to process
	if ($hist && count ( $hist ) >= 365) {
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
			$store [$pk] = smoothArray ( $vals, $smoothing_days, $smoothing_loops );
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
	} else {
		global $summer_solstice, $day_temperature_min, $day_temperature_max, $night_temperature_min, $night_temperature_max, $sunset_max, $sunset_min, $daylight_max, $daylight_min;

		if ($darksky_key == "") {
			logger ( LL_INFO, "rebuildDataModel(): Simulating data" );
		} else {
			logger ( LL_INFO, "rebuildDataModel(): Simulating data (not enough real data yet)" );
		}
		$tsnow = timestampNow ();
		$yr = timestampFormat ( $tsnow, "Y" );

		$high_delta_temperature = ($day_temperature_max - $day_temperature_min) / 2;
		$high_mid_temperature = $day_temperature_min + $high_delta_temperature;

		$low_delta_temperature = ($night_temperature_max - $night_temperature_min) / 2;
		$low_mid_temperature = $night_temperature_min + $low_delta_temperature;

		$sunset_delta_offset = ($sunset_max - $sunset_min) / 2;
		$sunset_mid_offset = $sunset_min + $sunset_delta_offset;

		// echo "Sunset MIN: $sunset_min. MAX: $sunset_max\n";
		// echo "Sunset AVG: $sunset_mid_offset. delta: $sunset_delta_offset\n";

		$sunrise_min = $sunset_max - $daylight_max; // longest day
		$sunrise_max = $sunset_min - $daylight_min; // shortesst
		$sunrise_delta_offset = ($sunrise_max - $sunrise_min) / 2;
		$sunrise_mid_offset = $sunrise_min + $sunrise_delta_offset;

		// echo "Sunrise MIN: $sunrise_min. MAX: $sunrise_max\n";
		// echo "Sunrise AVG: $sunrise_mid_offset. delta: $sunrise_delta_offset\n";

		$deg_step = 360 / 365;
		$tsnow = $yr . $summer_solstice . "000000";
		$model = array ();
		for($i = 0; $i < 365; $i ++) {
			$obj = new StdClass ();

			if (timestampFormat ( $tsnow, "md" ) == "0229") {
				// Skip leap years
				$tsnow = timestampAdd ( $tsnow, numDays ( 1 ) );
			}

			$obj->temperatureHigh = $high_mid_temperature + $high_delta_temperature * cos ( deg2rad ( $i * $deg_step ) );
			$obj->temperatureLow = $low_mid_temperature + $low_delta_temperature * cos ( deg2rad ( $i * $deg_step ) );

			$obj->sunsetOffset = ($sunset_mid_offset + $sunset_delta_offset * cos ( deg2rad ( $i * $deg_step ) )) * 3600;
			$obj->sunriseOffset = ($sunrise_mid_offset + $sunrise_delta_offset * cos ( deg2rad ( 180 + $i * $deg_step ) )) * 3600;
			$obj->daylightHours = ($obj->sunsetOffset - $obj->sunriseOffset) / 3600;

			$model [timestampFormat ( $tsnow, "md" )] = $obj;

			$tsnow = timestampAdd ( $tsnow, numDays ( 1 ) );
		}
	}

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
	if ($ts != null && ! is_array ( $ts )) {
		$ts = array (
				$ts
		);
	}
	$expected = 365;
	$sql = "SELECT * FROM model";

	if ($ts != null) {
		$esql = "";
		if (count ( $ts ) == 1) {
			$sql .= " WHERE id = ";
		} else {
			$sql .= " WHERE id IN (";
			$esql = ")";
		}
		$expected = count ( $ts );
		$comma = "";
		foreach ( $ts as $t ) {
			if ($t == "0229") {
				$t = "0228"; // No leap years
			}
			$sql .= $comma . "'" . timestampFormat ( $t, "md" ) . "'";
			$comma = ", ";
		}
		$sql .= $esql;
	}

	global $mysql;
	$rows = $mysql->query ( $sql );

	if (! $rows || count ( $rows ) != $expected) {
		// if we got no data, just make sure we have performed the initialisation
		logger ( LL_INFO, "getModel(): no model stored" );
		setupTables ();
		rebuildDataModel ();
		$rows = $mysql->query ( $sql );
	}

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
