<?php

/**

 DROP TABLE IF EXISTS weather;

 CREATE TABLE IF NOT EXISTS weather (
 id VARCHAR(64) NOT NULL PRIMARY KEY,
 lat FLOAT NOT NULL,
 lng FLOAT NOT NULL,
 day TINYINT NOT NULL,
 month TINYINT NOT NULL,
 year SMALLINT NOT NULL,
 data MEDIUMTEXT NOT NULL,
 last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX (lat, lng, day, month)
 );

 REPLACE INTO weather (id, lat, lng, day, month, year, data) VALUES(?, ?, ?, ?, ?, ?, ?);
 */
function setupTables() {
	global $mysql;

	$str = "
		CREATE TABLE IF NOT EXISTS weather (
			id VARCHAR(64) NOT NULL PRIMARY KEY,
	 		lat FLOAT NOT NULL,
			lng FLOAT NOT NULL,
			day TINYINT NOT NULL,
			month TINYINT NOT NULL,
			year SMALLINT NOT NULL,
			data MEDIUMTEXT NOT NULL,
			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	 		INDEX (lat, lng, day, month)
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

	$str = "
		CREATE TABLE IF NOT EXISTS humidity_logger (
			entered TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL PRIMARY KEY,
	 		demanded FLOAT NOT NULL,
	 		humidity FLOAT NOT NULL
		)";
	$mysql->query ( $str );
}

function clearSensorLogger() {
	global $mysql;
	$mysql->query ( "DELETE FROM temperature_logger where entered < '" . timestampFormat ( timestampAdd ( timestampNow (), numDays ( - 1 ) ), "Y-m-d H:i:s" ) . "'" );
	$mysql->query ( "DELETE FROM humidity_logger where entered < '" . timestampFormat ( timestampAdd ( timestampNow (), numDays ( - 1 ) ), "Y-m-d H:i:s" ) . "'" );
}

function lastTemp() {
	global $mysql;
	$ret = $mysql->query ( "SELECT * FROM temperature_logger ORDER BY entered DESC LIMIT 1" );
	if (is_array ( $ret ) && count ( $ret ) > 0) {
		return $ret [0];
	}
	return null;
}

function lastHumidity() {
	global $mysql;
	$ret = $mysql->query ( "SELECT * FROM humidity_logger where humidity != 999 ORDER BY entered DESC LIMIT 1" );
	if (is_array ( $ret ) && count ( $ret ) > 0) {
		return $ret [0];
	}
	return null;
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
	// var_dump($ret);
	// return $default;
}

function setLight($val) {
	global $hl_light_pin, $hl_high_value;
	// $cmd = "sh " . realpath ( dirname ( __FILE__ ) . "/../sh/gpio.sh" ) . " " . $hl_light_pin . " " . $val . " 2>&1";
	$cmd = "echo " . $val . " > /sys/class/gpio/gpio" . $hl_light_pin . "/value";
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	ob_end_clean ();
	logger ( LL_INFO, "setLight(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $cmd );
	// logger ( LL_DEBUG, "setLight(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $last_line );
}

function setHeat($val) {
	global $hl_heat_pin, $hl_high_value;
	// $cmd = "sh " . realpath ( dirname ( __FILE__ ) . "/../sh/gpio.sh" ) . " " . $hl_heat_pin . " " . $val . " 2>&1";
	$cmd = "echo " . $val . " > /sys/class/gpio/gpio" . $hl_heat_pin . "/value";
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	ob_end_clean ();
	logger ( LL_INFO, "setHeat(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $cmd );
	// logger ( LL_DEBUG, "setHeat(" . (($val == $hl_high_value) ? ("ON") : ("OFF")) . "): " . $last_line );
}

function setOled($text) {
	$cmd = "echo '" . $text . "' > /tmp/oled.txt";
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	ob_end_clean ();
	logger ( LL_INFO, "setOled(): " . $cmd );
	// logger ( LL_DEBUG, "setOled(): " . $last_line );
}

function readSensors($quiet = false) {
	global $use_dht, $mysql;

	// Tidy up the logger tables
	clearSensorLogger ();

	if ($use_dht) {
		$cmd = "python3 " . dirname ( __FILE__ ) . "/dht22.py 2>&1";
		ob_start ();
		$last_line = @system ( $cmd, $retval );
		// $last_line = "T:25.9|H:53.3";
		ob_end_clean ();

		if ($last_line [0] != 'T') {
			if (! quiet)
				echo "Unable to determine local temp/humidity\n\n";
		} else {
			@list ( $temperature, $humidity ) = @explode ( "|", $last_line );
			$temperature = @explode ( ":", $temperature ) [1] + 0;
			$humidity = @explode ( ":", $humidity ) [1] + 0;

			setConfig ( "temperature", $temperature );
			setConfig ( "humidity", $humidity );
			if (! $quiet)
				echo "Local temperature: " . $temperature . "C\n";
			if (! $quiet)
				echo "Local humidity: " . $humidity . "%\n";
			echo "\n";
		}
	} else {
		$cmd = "cat /sys/bus/w1/devices/28-*/w1_slave 2>&1";
		ob_start ();
		$last_line = @system ( $cmd, $retval );
		// $last_line = "67 01 4c 46 7f ff 0c 10 c4 t=22437";
		ob_end_clean ();

		@list ( $dummy, $temperature ) = explode ( " t=", $last_line );
		if ($temperature == "") {
			if (! $quiet)
				echo "Unable to determine local temp\n\n";
			ob_start ();
			@system ( "echo '999C " . getConfig ( "STATUS", "---" ) . "' > /tmp/oled.txt", $retval );
			ob_end_clean ();
		} else {
			$temperature = ($temperature + 0) / 1000;

			setConfig ( "temperature", $temperature );
			if (! $quiet)
				echo "Local temperature: " . $temperature . "C\n";
			if (! $quiet)
				echo "\n";
		}
	}
}

function tick($quiet = false) {
	global $lat, $lng, $day, $mon, $bulksms_owner_sms, $bulksms_alert_sunrise, $bulksms_alert_sunset;
	global $hl_high_value, $hl_low_value;
	global $mysql;
	$tsnow = timestampFormat ( timestampNow (), "His" );

	$last_status = getConfig ( "status", "NIGHT" );
	$last_temperature = getConfig ( "temperature" );
	$last_humidity = getConfig ( "humidity" );

	/**
	 * *************************************************************************************************************************************
	 * Get the weather data from the database
	 */
	$data = getData ( $lat, $lng, $day, $mon );

	/**
	 * *************************************************************************************************************************************
	 * Calculate the sunset/rise times.
	 * They should be remotely correct and based in local timezone hours
	 */
	$status = (( int ) ($tsnow) >= ( int ) ($data->sunrise) && ( int ) ($tsnow) <= ( int ) ($data->sunset)) ? ("DAY") : ("NIGHT");

	if (! $quiet)
		echo "Status: '" . $status . "'\n";
	if (! $quiet)
		echo ob_print_r ( $data ) . "\n";

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
	$demand_temperature = ($status == "DAY") ? ($data->high_hist) : ($data->low_hist);
	setConfig ( "temperature_demand", $demand_temperature );
	$temperature = getConfig ( "temperature" );

	// Work out whether we need to switch the heater on
	$heat = false;
	if ($temperature !== false) {
		$mysql->query ( "REPLACE INTO temperature_logger (temperature, demanded) VALUES (?, ?)", "dd", array (
				$temperature,
				$demand_temperature
		) );

		// $direction_temperature = ($temperature<$last_temperature)?("UP"):(($temperature==$last_temperature)?("--"):("DN"));
		// TODO: calulate whether we need to do anything with last temp, current and direction (is it suitably different)

		setConfig ( "temperature_last", $last_temperature );
		setConfig ( "temperature", $temperature );
		// setConfig ( "temperature_direction", $direction_temperature );

		// TODO: Calculate the
		$heat = $demand_temperature > $temperature;

		// Set the string for display
		$temperature = round ( $temperature, 1 );
	} else {
		$temperature = "---";
	}
	setHeat ( ($heat) ? ($hl_high_value) : ($hl_low_value) );

	/**
	 * *************************************************************************************************************************************
	 * Send the sumary to the OLED display
	 */
	$str = $temperature . "C " . $status . " " . (($heat) ? ("#") : ("."));
	setOled ( $str );
}

function getIdList($lat, $lng, $day, $mon) {
	// These should be global as well. Work on that
	global $yr_history;
	global $dy_history;
	global $dy_forecast;
	logger ( LL_DEBUG, "getIdList($lat, $lng, $day, $mon): yr_history: " . $yr_history . ", dy_history: " . $dy_history . ", dy_forecast: " . $dy_forecast );
	$yr = timestampFormat ( timestampNow (), "Y" );

	$ret = array ();
	// Go back through the year history
	for($y = 0; $y <= $yr_history; $y ++) {
		// Go forward and backwards in the day history/forecast
		for($d = - $dy_history; $d <= $dy_forecast; $d ++) {
			// Work out full timestamp of the requried day by adding days for safety
			$ts = timestampAddDays ( timestamp ( $day, $mon, $yr - $y ), $d );
			// The ID is only the day
			$ret [] = timestampFormat ( $ts, "Ymd" ) . "|" . $lat . "|" . $lng;
		}
	}

	rsort ( $ret );
	return $ret;
}

function getDataPoints($id_list) {
	global $mysql;

	// Make sure the database is ready
	setupTables ();

	// Generate the SQL list
	$inlist = "'" . implode ( "','", $id_list ) . "'";
	// Get the historic data that exists in the database already...
	$rows = $mysql->query ( "SELECT * FROM weather where id in (" . $inlist . ")" );
	// logger ( LL_DEBUG, "getDataPoints(): " . "SELECT * FROM weather where id in (" . $inlist . ")" );

	$ret = array ();
	// Iterate through each one so we have id based associative array
	foreach ( $rows as $r ) {
		$r = ( object ) $r;
		// logger ( LL_INFO, "getDataPoints(): got id '" . $r->id . "'" );
		// logger ( LL_INFO, "getDataPoints(): got object\n" . ob_print_r($r) );
		$ret [$r->id] = $r;
	}

	// Send it back
	return $ret;
}

function addObjParam(&$obj, $name, $row, $arr = null, $count = false) {
	if ($arr === null) {
		$arr = $name;
	}
	if (! is_array ( $arr )) {
		$arr = array (
				$arr
		);
	}
	$val = null;
	foreach ( $arr as $k ) {
		if ($val === null && isset ( $row->$k )) {
			$val = $row->$k;
			if ($count != null) {
				$name = $name . "_hist";
				$count = $name . "_count";
				// logger ( LL_INFO, "\$obj->$name incremented \$row->$k" );
				if (! isset ( $obj->$name ))
					$obj->$name = 0;
				if (! isset ( $obj->$count ))
					$obj->$count = 0;
				$obj->$name += $val;
				$obj->$count += 1;
			} else {
				// logger ( LL_INFO, "\$obj->$name set to \$row->$k" );
				$obj->$name = $val;
			}
		}
	}
}

function tidyObjParam(&$obj, $name) {
	$oname = $name;
	$name = $name . "_hist";
	$count = $name . "_count";

	if (! isset ( $obj->$oname )) {
		$obj->$oname = false;
	}

	if (isset ( $obj->$count ) && $obj->$count > 0) {
		$obj->$name /= $obj->$count;
	} else {
		$obj->$name = false;
	}

	if (isset ( $obj->$count )) {
		unset ( $obj->$count );
	}
}

function processData($day, $mon, $yr, $data) {
	// At this point we have all the data for all the days we need to calculate the average of
	$obj = new StdClass ();
	foreach ( $data as $row ) {
		$row = json_decode ( $row->data );
		$ns = "nearest-station";
		// var_dump($row->flags->$ns);
		if (isset ( $row->latitude )) {
			$obj->lat = $row->latitude;
		}
		if (isset ( $row->longitude )) {
			$obj->lng = $row->longitude;
		}
		if (isset ( $row->timezone )) {
			$obj->timezone = $row->timezone;
		}
		if (isset ( $row->offset )) {
			$obj->timezoneOffset = $row->offset * 60 * 60;
		}
		// $obj->units = $row->flags->units;
		if (isset ( $row->flags->$ns )) {
			$obj->nearestStation = $row->flags->$ns;
		}

		if (isset ( $row->daily ) && isset ( $row->daily->data [0] )) {
			$row = $row->daily->data [0];
			$high_labels = array (
					"apparentTemperatureHigh",
					"temperatureHigh",
					"apparentTemperatureMax",
					"temperatureMax"
			);
			$low_labels = array (
					"apparentTemperatureLow",
					"temperatureLow",
					"apparentTemperatureMin",
					"temperatureMin"
			);

			// echo "POST row: " . ob_print_r ( $row ) . "\n";
			// logger ( LL_DEBUG, "" . timestampFormat ( time2Timestamp ( $row->time ), "Y-m-d\TH:i:sT" ) . "), Sunset: " . timestampFormat ( time2Timestamp ( $row->sunsetTime ), "Y-m-d\TH:i:s" ) );
			if (timestampFormat ( time2Timestamp ( $row->sunriseTime ), "Ymd" ) == timestampFormat ( timestamp ( $day, $mon, $yr ), "Ymd" )) {
				// copy todays values
				// logger ( LL_DEBUG, " *** Setting todays values" );
				$obj->sunset = timestampFormat ( time2Timestamp ( $row->sunsetTime ), "His" );
				$obj->sunrise = timestampFormat ( time2Timestamp ( $row->sunriseTime ), "His" );
				$obj->daylight = ($row->sunsetTime - $row->sunriseTime) / 3600;
				$obj->midnightTime = $row->time; // timestampFormat ( time2Timestamp ( $row->time ), "c" );
				$obj->sunsetTime = $row->sunsetTime;
				$obj->sunriseTime = $row->sunriseTime;
				$obj->sunsetOffset = $row->sunsetTime - $row->time;
				$obj->sunriseOffset = $row->sunriseTime - $row->time;
				// $obj->sunsetOffset = $row->sunsetTime-$row->time;
				// $obj->sunriseOffset = $row->sunriseTime-$row->time;
				// logger(LL_SYS, "sunrise: ".timestampFormat ( time2Timestamp ( $row->sunriseTime ), "Y-m-d H:i:s" ));
				// logger(LL_SYS, "sunset: ".timestampFormat ( time2Timestamp ( $row->sunsetTime ), "Y-m-d H:i:s" ));
				// logger(LL_SYS, "diff: ".timestampDifference("20191010010000", "20191010020000"));
				// logger(LL_SYS, "diff: ".timestampDifference("20191010010000", "20191010020000"));
				$obj->lunation = $row->moonPhase; // https://en.wikipedia.org/wiki/New_moon#Lunation_Number

				addObjParam ( $obj, "high", $row, $high_labels );
				addObjParam ( $obj, "low", $row, $low_labels );
				addObjParam ( $obj, "humidity", $row );
				addObjParam ( $obj, "precipitation", $row, "precipAccumulation" );
				addObjParam ( $obj, "cloudCover", $row );
				addObjParam ( $obj, "pressure", $row );
				addObjParam ( $obj, "visibility", $row );
				addObjParam ( $obj, "windSpeed", $row );

				// addObjParam ( $obj, "dewPoint", $row, "dewPoint" );
			}

			addObjParam ( $obj, "high", $row, $high_labels, true );
			addObjParam ( $obj, "low", $row, $low_labels, true );
			addObjParam ( $obj, "humidity", $row, null, true );
			addObjParam ( $obj, "precipitation", $row, "precipAccumulation", true );
			addObjParam ( $obj, "cloudCover", $row, null, true );
			addObjParam ( $obj, "pressure", $row, null, true );
			addObjParam ( $obj, "visibility", $row, null, true );
			addObjParam ( $obj, "windSpeed", $row, null, true );
		}
	}

	tidyObjParam ( $obj, "high" );
	tidyObjParam ( $obj, "low" );
	tidyObjParam ( $obj, "humidity" );
	tidyObjParam ( $obj, "precipitation" );
	tidyObjParam ( $obj, "cloudCover" );
	tidyObjParam ( $obj, "pressure" );
	tidyObjParam ( $obj, "visibility" );
	tidyObjParam ( $obj, "windSpeed" );

	$arr = ( array ) $obj;
	ksort ( $arr );
	return ( object ) $arr;
}

function getData($lat, $lng, $day, $mon, $yr = null, $fill = false, $force = false) {
	global $darksky_key;
	global $mysql;
	global $dy_history, $dy_forecast;

	// calculate so we can do comparisons
	if ($yr === null) {
		$yr = timeStampFormat ( timestampNow (), "Y" );
	}

	// Get a list of ID's associated with this day
	$id_list = getIdList ( $lat, $lng, $day, $mon );
	logger ( LL_DEBUG, "getData(): data points required: " . count ( $id_list ) );
	logger ( LL_XDEBUG, "getData(): id list:\n" . ob_print_r ( $id_list ) );

	// Get the data points in the database if they exist
	$data_points = getDataPoints ( $id_list );
	logger ( LL_DEBUG, "getData(): data points available: " . count ( $data_points ) );
	logger ( LL_XDEBUG, ob_print_r ( $data_points ) );

	// Process the list to see what we have missing
	$ret = array ();
	$refresh = array ();
	foreach ( $id_list as $id ) {
		$bits = explode ( "|", $id );
		$ts_yr = timestampFormat ( $bits [0], "Y" );
		$diff = abs ( timestampDifference ( timestampDay ( timestampNow () ), $bits [0] ) );

		if (! isset ( $data_points [$id] ) || ($force && ($diff <= numDays ( max ( $dy_history, $dy_forecast ) )))) {
			if (($force && ($ts_yr == $yr))) {
				logger ( LL_EDEBUG, "Forced refresh for '$id'" );
			} else {
				logger ( LL_DEBUG, "Need data for '$id'" );
			}
			$refresh [] = $id;
		} else {
			$ret [$id] = $data_points [$id];
			logger ( LL_XDEBUG, "Got data for '$id'" );
		}
	}

	if ($fill) {
		logger ( LL_INFO, "getData(): API calls required: " . count ( $refresh ) );
		foreach ( $refresh as $id ) {
			$bits = explode ( "|", $id );
			$ts = timestampFormat ( $bits [0], "Y-m-d\TH:i:s" );
			$ts_day = timestampFormat ( $bits [0], "d" );
			$ts_mon = timestampFormat ( $bits [0], "m" );
			$ts_yr = timestampFormat ( $bits [0], "Y" );

			$call = "https://api.darksky.net/forecast/" . $darksky_key . "/" . $lat . "," . $lng . "," . $ts . "?units=si&exclude=currently,minutely,hourly,alerts";
			logger ( LL_DEBUG, "Calling API: " . $call );
			$data = @file_get_contents ( $call );
			if ($data) {
				// Generate an object of all the values
				$values = array (
						"id" => $id,
						"lat" => $lat,
						"lng" => $lng,
						"day" => $ts_day,
						"month" => $ts_mon,
						"year" => $ts_yr,
						"data" => $data
				);

				// store it
				$ret [$id] = ( object ) $values;
				$mysql->query ( "REPLACE INTO weather (id, lat, lng, day, month, year, data) VALUES(?, ?, ?, ?, ?, ?, ?)", "sddiiis", array_values ( $values ) );
			} else {
				logger ( LL_WARNING, "getData(): API failure for " . timestampFormat ( $ts, "Y-m-d" ) );
			}
		}
	}

	return processData ( $day, $mon, $yr, $ret );
	// logger ( LL_INFO, "Data:\n" . ob_print_r ( $ret ) );
}

function getHistoricData() {
	global $darksky_key;
	global $mysql;
	global $yr_history, $dy_history, $lat, $lng, $api_call_cap;

	// Calculate how many days to go back
	$total_ndays = $yr_history * 365;
	// Start from tomorrow for consistency
	$tsnow = timestampAdd ( timestampNow (), numDays ( 1 ) );

	// Iterate through all the data points we need
	$dates = array ();
	for($i = 1; $i <= $total_ndays; $i ++) {
		$ts = timestampAdd ( $tsnow, numDays ( - $i ) );
		$dates [timestampFormat ( $ts, "Ymd" ) . "|" . $lat . "|" . $lng] = $ts;
	}
	logger ( LL_DEBUG, "getHistoricData(): data points required: " . count ( $dates ) );

	// Get any data points we already have
	$data_points = getDataPoints ( array_keys ( $dates ) );
	logger ( LL_DEBUG, "getHistoricData(): data points available: " . count ( $data_points ) );
	foreach ( $data_points as $k => $v ) {
		if (isset ( $dates [$k] )) {
			unset ( $dates [$k] );
		}
	}

	// We need to retrieve the rest
	logger ( LL_INFO, "getHistoricData(): API calls required: " . count ( $dates ) );

	// If we need too many, truncate the list
	if (count ( $dates ) > $api_call_cap) {
		$c = 0;
		foreach ( $dates as $k => $v ) {
			if ($c >= $api_call_cap) {
				unset ( $dates [$k] );
			}
			$c ++;
		}
		logger ( LL_INFO, "getHistoricData(): API calls capped at " . count ( $dates ) );
	}

	foreach ( $dates as $id => $ts ) {
		$ts = timestampFormat ( $ts, "Y-m-d\TH:i:s" );
		$ts_day = timestampFormat ( $ts, "d" );
		$ts_mon = timestampFormat ( $ts, "m" );
		$ts_yr = timestampFormat ( $ts, "Y" );

		$call = "https://api.darksky.net/forecast/" . $darksky_key . "/" . $lat . "," . $lng . "," . $ts . "?units=si&exclude=currently,minutely,hourly,alerts";
		logger ( LL_DEBUG, "Calling API: " . $call );
		$data = @file_get_contents ( $call );
		if ($data) {
			// Generate an object of all the values
			$values = array (
					"id" => $id,
					"lat" => $lat,
					"lng" => $lng,
					"day" => $ts_day,
					"month" => $ts_mon,
					"year" => $ts_yr,
					"data" => $data
			);

			// store it
			$ret [$id] = ( object ) $values;
			$mysql->query ( "REPLACE INTO weather (id, lat, lng, day, month, year, data) VALUES(?, ?, ?, ?, ?, ?, ?)", "sddiiis", array_values ( $values ) );
		} else {
			// Log the failure
			logger ( LL_WARNING, "getHistoricData(): API failure for " . timestampFormat ( $ts, "Y-m-d" ) );
		}
	}
}

function getSunData($ndays = 10) {
	$data = getRawHistoricData ( $ndays );
	// return $data;

	$rise = array ();
	$set = array ();
	$day = array ();
	foreach ( $data as $ts => $d ) {
		$t = $d->midnightTime + $d->timezoneOffset + 1; // plus the offset to get to local time plus one so it's definately today
		                                                // $rise [$t] = $d -> sunriseOffset/3600;
		$rise [$t] = $d->sunriseOffset / 3600;
		$set [$t] = $d->sunsetOffset / 3600;
		$day [$t] = ($d->sunsetOffset - $d->sunriseOffset) / 3600;
	}

	return array (
			"daylight" => $day,
			"sunrise" => $rise,
			"sunset" => $set
	);
}

function getTempData($ndays = 10) {
	$data = getRawHistoricData ( $ndays );
	// return $data;

	$lo = array ();
	$hi = array ();
	foreach ( $data as $ts => $d ) {
		$t = $d->midnightTime + $d->timezoneOffset + 1; // plus the offset to get to local time plus one so it's definately today
		                                                // $rise [$t] = $d -> sunriseOffset/3600;
		$lo [$t] = $d->low_hist;
		$hi [$t] = $d->high_hist;
	}

	return array (
			"low" => $lo,
			"high" => $hi
	);
}

function getRawHistoricData($ndays = 10) {
	global $mysql, $lat, $lng;

	$ts = timestampNow ();
	for($i = 0; $i < $ndays; $i ++) {
		$yr = timestampFormat ( $ts, "Y" );
		$mn = timestampFormat ( $ts, "m" );
		$dy = timestampFormat ( $ts, "d" );

		$data [timestampFormat ( $ts, "Ymd" )] = getData ( $lat, $lng, $dy, $mn, $yr );

		$ts = timestampAddDays ( $ts, - 1 );
	}
	return $data;
}

?>