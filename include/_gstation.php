<?php

function setupTables() {
	global $mysql;

	// Used for DarkSky API data
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
		CREATE TABLE IF NOT EXISTS sensors (
			event BIGINT UNSIGNED NOT NULL,
			name VARCHAR(255) NOT NULL,
			param VARCHAR(255) NOT NULL,
			value VARCHAR(255) NOT NULL,
			KEY(event)
		)";
	$mysql->query ( $str );

	$str = "
		CREATE TABLE IF NOT EXISTS triggers (
			event BIGINT UNSIGNED NOT NULL,
			param VARCHAR(255) NOT NULL,
			value VARCHAR(255) NOT NULL,
			KEY(event)
		)";
	$mysql->query ( $str );

	clearLogs ();
}

function clearLogs() {
	global $mysql, $logger;
	// TODO: fix this on sensors and
	// $mysql->query ( "DELETE FROM temperature_logger where entered < DATE_SUB(NOW(), INTERVAL 24 HOUR)" );
	$logger->clearLogs ();
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
		global $summer_solstice, $day_temperature_min, $day_temperature_max, $night_temperature_min, $night_temperature_max, $day_humidity_min, $day_humidity_max, $night_humidity_min, $night_humidity_max, $sunset_max, $sunset_min, $daylight_max, $daylight_min;

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

		$high_delta_humidity = ($day_humidity_max - $day_humidity_min) / 2;
		$high_mid_humidity = $day_humidity_min + $high_delta_humidity;

		$low_delta_humidity = ($night_humidity_max - $night_humidity_min) / 2;
		$low_mid_humidity = $night_humidity_min + $low_delta_humidity;

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

			// Humidity is higher in the winter when temps drop, so use sin to get 180 degrees off
			$obj->humidityHigh = $high_mid_humidity + $high_delta_humidity * sin ( deg2rad ( $i * $deg_step ) );
			$obj->humidityLow = $low_mid_humidity + $low_delta_humidity * sin ( deg2rad ( $i * $deg_step ) );

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

function setupTriggersScript() {
	$ofn = dirname ( __FILE__ ) . "/../sh/start_triggers.sh";
	$str = "";
	$str .= "#!/bin/sh\n\n";
	$str .= trim ( createTriggersSetupScript () );

	file_put_contents ( $ofn, $str );
}

function setupSensorsScript() {
	$ofn = dirname ( __FILE__ ) . "/../sh/start_sensors.sh";
	$str = "";
	$str .= "#!/bin/sh\n\n";
	$str .= createSensorsSetupScript ();

	file_put_contents ( $ofn, $str );
}

$triggers_enumerated = false;

function enumerateTriggers() {
	global $triggers_enumerated, $triggers;

	if ($triggers_enumerated) {
		return;
	}
	$triggers_enumerated = true;

	foreach ( $triggers as $pin => $t ) {
		$gpio_pin = "trigger_pin_" . ($pin + 1);
		global $$gpio_pin;
		$t->pin = $$gpio_pin;
	}
}

function enumerateSensors() {
	global $sensors_enumerated, $sensors;

	if ($sensors_enumerated) {
		return;
	}
	$sensors_enumerated = true;

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

function triggerPullUpDown($type) {
	$ret = "down";

	switch ($type) {
		case "iSSR" :
			$ret = "up";
			break;
	}

	return $ret;
}

function triggerDefaultState($type) {
	$ret = 0;

	switch ($type) {
		case "LED" :
		case "iSSR" :
			$ret = 1;
			break;
	}

	return $ret;
}

function isGpio($type) {
	$ret = true;

	switch ($type) {
		case "EMPTY" :
		case "MH-Z19B" :
			$ret = false;
			break;
	}

	return $ret;
}

function createTriggersSetupScript() {
	global $triggers;
	enumerateTriggers ();

	$ret = "";

	foreach ( $triggers as $t ) {
		if (isGpio ( $t->type )) {
			$ret .= "# " . $t->name . " (" . $t->type . ")\n";
			$ret .= "gpio -g mode " . $t->pin . " out\n";
			$ret .= "gpio -g mode " . $t->pin . " " . triggerPullUpDown ( $t->type ) . "\n";
			$ret .= "gpio -g write " . $t->pin . " " . triggerDefaultState ( $t->type ) . "\n";
			$ret .= "\n";
		}
	}

	return $ret;
}

function createSensorsSetupScript() {
	global $sensors;
	enumerateSensors ();

	$ret = "";

	foreach ( $sensors as $s ) {
		if (isGpio ( $s->type )) {
			$ret .= "dtoverlay ";
			$ret .= overlay ( $s->type );
			$ret .= " gpiopin=" . $s->pin . "\n";
		}
	}

	return $ret;
}

function readSensorRaw_DS18B20($sensor) {
	$ret = new StdClass ();

	foreach ( $sensor->enumeration as $e ) {
		$output = null;
		$retvar = 0;
		$cmd = "cat " . $e->file . " 2>&1";
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
		$cmd = "cat " . $e->file . " 2>&1";
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
			if (strlen ( $output [0] ) == 5) {
				echo ("Got correct character count '" . $output [0] . "'\n");
				if (is_numeric ( $output [0] )) {
					echo ("Integer conversion works\n");
					$val = (( double ) $output [0]) / 1000.00;
				} else {
					echo ("Integer conversion failed '" . $output [0] . "'\n");
				}
			} else {
				echo ("Got incorrect character count:\n" . ob_print_r ( $output ) . "\n");
			}
			// echo("length: ".strlen($output[0])."\n");
			// echo ("Got:\n" . ob_print_r ( $output ) . "\n");

			// if(is_array($output) && count($output) == 2) {
			// echo ("Got correct line count:\n".ob_print_r($output)."\n");
			// if(preg_match('/crc=[a-f0-9]{2} YES/', $output[0])) {
			// echo ("CRC passed\n");
			// list($dummy, $temp) = explode("t=", $output[1]);
			// echo ("Got temp: ".$temp."\n");
			// $val = ((double)$temp)/1000.0;
			// echo ("Set val: ".$val."\n");
			$output = null;
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

function checkSensors($type, $pin) {
	$output = null;
	$retvar = 0;
	$cmd = "sudo dtoverlay -l 2>&1";

	exec ( $cmd, $output, $retvar );
	$output = implode ( "\n", $output ) . "\n";
	print_r ( $output );
	if (preg_match ( '/' . overlay ( $type ) . '\s+gpiopin=' . $pin . '/', $output )) {
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
		echo "No sensor overlay setup for a " . $type . " on pin " . $pin . "\n";
		sleep ( 30 );
		echo "retrying...\n";
	}

	$func = "readSensorRaw_" . $sensor->type;
	while ( true ) {
		$ret = $func ( $sensor );
		if ($ret == null) {
			echo "Garbage sensor??\n";
		} else {
			$ret->name = $sensor->name;
			$jstr = json_encode ( $ret );
			file_put_contents ( $sensor->ofn, $jstr );
			echo "Writing to '" . $sensor->ofn . "'\n";
			print_r ( $ret );
		}
		sleep ( sensorCooloff ( $type ) );
	}
}

function gatherSensors() {
	$key = "/tmp/gs_sensor_";
	$files = directoryListing ( "/tmp", "*.json" );
	$ret = array ();
	foreach ( $files as $file ) {
		if (substr ( $file, 0, strlen ( $key ) ) == $key) {
			echo "Processing '$file'\n";
			$c = file_get_contents ( $file );
			$j = json_decode ( $c );
			$name = $j->name;
			unset ( $j->name );
			$j = ( array ) $j;
			foreach ( $j as $k => $v ) {
				$o = new StdClass ();
				$o->name = $name;
				$o->param = $k;
				$o->val = $v;
				$ret [] = $o;
			}
		}
	}
	return $ret;
}
?>