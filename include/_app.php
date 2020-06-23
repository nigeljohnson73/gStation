<?php
function getSnapshotUrl() {
        $host = $_SERVER['HTTP_HOST'];
        return "http://".$host.":8081/?action=stream";
}

function getSnapshotFile() {
        $fn = "/logs/gcam_snapshot.jpg";
        if(file_exists($fn)){
                $ftime = filemtime($fn);
                $now = time();
                $live = 5*60;
                if(($now - $ftime) <= $live) {
                        return $fn;
                }
        }
        return false;
}


$tables_setup = false;

function setupTables() {
	global $mysql, $tables_setup;
	if ($tables_setup) {
		return;
	}
	$tables_setup = true;

	// Configuration data
	$str = "
		CREATE TABLE IF NOT EXISTS config (
			id VARCHAR(64) NOT NULL PRIMARY KEY,
			data MEDIUMTEXT NOT NULL,
			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)";
	$mysql->query ( $str );

	// Used for DarkSky API historic data
	$str = "
		CREATE TABLE IF NOT EXISTS history (
			id VARCHAR(8) NOT NULL PRIMARY KEY,
			data MEDIUMTEXT NOT NULL,
			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)";
	$mysql->query ( $str );

	// Used for the current data model
	$str = "
		CREATE TABLE IF NOT EXISTS model (
			id VARCHAR(8) NOT NULL PRIMARY KEY,
			data MEDIUMTEXT NOT NULL,
			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)";
	$mysql->query ( $str );

	// last 24 hours of sensor data
	$str = "
		CREATE TABLE IF NOT EXISTS sensors (
			event BIGINT UNSIGNED NOT NULL,
			name VARCHAR(255) NOT NULL,
			param VARCHAR(255) NOT NULL,
			value VARCHAR(255) NOT NULL,
			age SMALLINT NULL,
			KEY(event),
			KEY(name), 
			KEY(param, name)
		)";
	$mysql->query ( $str );

	$str = "
		CREATE TABLE IF NOT EXISTS triggers (
			event BIGINT UNSIGNED NOT NULL,
			param VARCHAR(255) NOT NULL,
			value VARCHAR(255) NOT NULL,
			KEY(event),
			KEY(param)
	)";
	$mysql->query ( $str );

	$str = "
		CREATE TABLE IF NOT EXISTS demands (
			event BIGINT UNSIGNED NOT NULL,
			param VARCHAR(255) NOT NULL,
			value VARCHAR(255) NOT NULL,
			KEY(event),
			KEY(param)
	)";
	$mysql->query ( $str );

	clearLogs ();
}

function clearLogs() {
	global $mysql, $logger;
	$tsnow = timestampNow ();
	$ts_delete = timestampAddDays ( $tsnow, - 1 );
	$mysql->query ( "DELETE FROM demands where event < " . $ts_delete );
	$mysql->query ( "DELETE FROM sensors where event < " . $ts_delete );
	$mysql->query ( "DELETE FROM triggers where event < " . $ts_delete );
	$logger->clearLogs ();
}

function nextSunChange() {
	$tsnow = timestampNow ();
	$midnight = timestamp2Time ( timestampFormat ( $tsnow, "Ymd" ) . "000000" );
	$nowoffset = timestamp2Time ( $tsnow ) - $midnight;
	$today = timestampFormat ( $tsnow, "Ymd" );
	$tomorrow = timestampFormat ( timestampAdd ( $tsnow, numDays ( 1 ) ), "Ymd" );

	$today = timestampFormat ( $today, "md" );
	if ($today == "0229") {
		$today = "0228";
	}
	$tomorrow = timestampFormat ( $tomorrow, "md" );
	if ($tomorrow == "0229") {
		$today = "0228";
	}
	// echo "Today: $today\n";
	// echo "Tomorrow: $tomorrow\n";

	$model = getModel ( array (
			$today,
			$tomorrow
	) );
	// print_r ( $model );

	$ret = "";
	if ($nowoffset < $model [$today]->sunriseOffset) {
		$secs = $model [$today]->sunriseOffset - $nowoffset;
		if ($secs > 59) {
			$ret = "Sunrise " . periodFormat ( $secs, true );
		} else {
			$ret = "Sunrise < 1m";
		}
	} elseif ($nowoffset < $model [$today]->sunsetOffset) {
		$secs = $model [$today]->sunsetOffset - $nowoffset;
		if ($secs > 59) {
			$ret = "Sunset " . periodFormat ( $secs, true );
		} else {
			$ret = "Sunset < 1m";
		}
	} else {
		$secs = $model [$tomorrow]->sunriseOffset - $nowoffset + (24 * 60 * 60);
		if ($secs > 59) {
			$ret = "Sunrise " . periodFormat ( $secs, true );
		} else {
			$ret = "Sunrise < 1m";
		}
	}
	return $ret;
}

function timeOfDay($offset) {
	$h = floor ( $offset / (60 * 60) );
	$offset -= $h * 60 * 60;
	$m = floor ( $offset / (60) );
	$offset -= $m * 60;
	$s = floor ( $offset );

	return sprintf ( "%02d:%02d:%02d", $h, $m, $s );
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
	// DarkSky does not differentiate a high and a low humidity :(
	$obj->humidityDay = firstOf ( $dso, "humidity" );
	$obj->humidityNight = firstOf ( $dso, "humidity" );
	$obj->lunation = firstOf ( $dso, "moonPhase" );
	$obj->pressure = firstOf ( $dso, "pressure" );
	$obj->sunriseOffset = $dso->sunriseTime - $dso->time - $utcOffset;
	$obj->sunsetOffset = $dso->sunsetTime - $dso->time - $utcOffset;
	$obj->temperatureDay = firstOf ( $dso, $temperature_high_labels );
	$obj->temperatureNight = firstOf ( $dso, $temperature_low_labels );
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

function rebuildModelFromDemands() {
	$msg = "rebuildDataModel(): Using environmental demands";
	echo "\n" . $msg . "\n";
	logger ( LL_INFO, $msg );

	$model = [ ]; // return this

	global $demand, $demand_solstice;

	$tssol = timestampFormat ( timestampNow (), "Y" ) . $demand_solstice . "000000";

	$dcount = 0;
	$dts = $tssol;
	$next_dts = $tssol;

	$dtemp_step = 0;
	$ntemp_step = 0;
	$dhumd_step = 0;
	$nhumd_step = 0;
	$suns_step = 0;
	$dlen_step = 0;

	$dtemp = $demand [0]->day_temperature;
	$ntemp = $demand [0]->night_temperature;
	$dhumd = $demand [0]->day_humidity;
	$nhumd = $demand [0]->night_humidity;
	$suns = $demand [0]->sunset;
	$dlen = $demand [0]->daylight_hours;

	// for($i = 0; $i < 7; $i ++) {
	for($i = 0; $i < 365; $i ++) {
		$obj = new StdClass ();
		$obj->temperatureDay = $dtemp;
		$obj->temperatureNight = $ntemp;
		$obj->humidityDay = $dhumd;
		$obj->humidityNight = $nhumd;
		$obj->sunsetOffset = $suns * (60 * 60);
		$obj->sunriseOffset = ($suns - $dlen) * (60 * 60);
		$obj->daylightHours = $dlen;

		// Stash the data
		$model [timestampFormat ( $dts, "md" )] = $obj;
		if (false) {
			if ($i < 3) {
				echo sprintf ( "Day: %03d", $i ) . " - " . timestampFormat ( $dts, "Ymd" ) . ", MD: " . timestampFormat ( $dts, "md" ) . ", Sunset: " . number_format ( $suns, 2 ) . ", Daylen: " . number_format ( $dlen, 2 ) . ", Model: " . ob_print_r ( $obj ) . "\n";
			} else {
				echo sprintf ( "Day: %03d", $i ) . " - " . timestampFormat ( $dts, "Ymd" ) . ", MD: " . timestampFormat ( $dts, "md" ) . ", Sunset: " . number_format ( $suns, 2 ) . ", Daylen: " . number_format ( $dlen, 2 ) . "\n";
			}
		}

		if ($dts >= $next_dts) {
			// Reset the deltas
			// echo "Next period available: " . ((count ( $demand ) > ($dcount + 1)) ? ("Yes") : ("No")) . "\n";
			$dtemp_step = (count ( $demand ) > ($dcount + 1)) ? (($demand [$dcount + 1]->day_temperature - $demand [$dcount]->day_temperature) / ($demand [$dcount]->period_length)) : (0);
			$ntemp_step = (count ( $demand ) > ($dcount + 1)) ? (($demand [$dcount + 1]->night_temperature - $demand [$dcount]->night_temperature) / ($demand [$dcount]->period_length)) : (0);
			$dhumd_step = (count ( $demand ) > ($dcount + 1)) ? (($demand [$dcount + 1]->day_humidity - $demand [$dcount]->day_humidity) / ($demand [$dcount]->period_length)) : (0);
			$nhumd_step = (count ( $demand ) > ($dcount + 1)) ? (($demand [$dcount + 1]->night_humidity - $demand [$dcount]->night_humidity) / ($demand [$dcount]->period_length)) : (0);
			$suns_step = (count ( $demand ) > ($dcount + 1)) ? (($demand [$dcount + 1]->sunset - $demand [$dcount]->sunset) / ($demand [$dcount]->period_length)) : (0);
			$dlen_step = (count ( $demand ) > ($dcount + 1)) ? (($demand [$dcount + 1]->daylight_hours - $demand [$dcount]->daylight_hours) / ($demand [$dcount]->period_length)) : (0);

			// echo "Next Period: " . timestampFormat ( $next_dts, "md" ) . "\n";
			// echo " day temp step: " . $dtemp_step . "\n";
			// echo " night temp step: " . $ntemp_step . "\n";
			// echo " day humidity step: " . $dhumd_step . "\n";
			// echo " night humidity step: " . $nhumd_step . "\n";
			// echo " sunset step: " . $suns_step . "\n";
			// echo " day length step: " . $dlen_step . "\n";

			// Increment the next time we gotta do this
			$next_dts = (count ( $demand ) > ($dcount + 1)) ? (timestampAdd ( $next_dts, numDays ( $demand [$dcount]->period_length ) )) : (timestampAdd ( $tssol, numDays ( 366 ) ));
			$dcount += 1;
		}

		// Increment the values
		$dtemp += $dtemp_step;
		$ntemp += $ntemp_step;
		$dhumd += $dhumd_step;
		$nhumd += $nhumd_step;
		$suns += $suns_step;
		$dlen += $dlen_step;

		// Increment the day
		$dts = timestampAdd ( $dts, numDays ( 1 ) );

		// Skip leap years
		if (timestampFormat ( $dts, "md" ) == "0229") {
			// $model [timestampFormat ( $dts, "md" )] = $obj;
			$dts = timestampAdd ( $dts, numDays ( 1 ) );
		}
	}

	return $model;
}

function rebuildModelFromDarkSky() {
	$msg = "rebuildDataModel(): Attempting DarkSky download";
	echo "\n" . $msg . "\n";
	logger ( LL_INFO, $msg );

	$model = [ ]; // return this

	global $darksky_key;

	if ($darksky_key == "") {
		// Not sure how we got here really
		logger ( LL_INFO, "rebuildDataModel(): DarkSky API not enabled" );
		$model = rebuildModelFromSimulation ();
		return $model;
	}

	global $force_api_history;

	echo "Retrieving historic data from Dark Sky\n";
	getDarkSkyApiData ( $force_api_history );
	echo "\n";

	// Get all the data we have. This will pop at some point. TODO: probably cap this to something!!
	$hist = getDarkSkyDataPoints ();

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

			// Turn humidity into a percentage
			$v->humidityDay *= 100;
			$v->humidityNight *= 100;

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
					// if($k[0]=="0" && $k[1]=="1" && $pk == "temperatureDay"){
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
		logger ( LL_INFO, "rebuildDataModel(): Not enough DarkSky data for valid environment" );
		$model = rebuildModelFromSimulation ();
	}

	return $model;
}

function rebuildModelFromSimulation() {
	$msg = "rebuildDataModel(): Using environmental simulation";
	echo "\n" . $msg . "\n";
	logger ( LL_INFO, $msg );

	$model = [ ]; // return this

	// global $darksky_key;
	// if ($darksky_key == "") {
	// logger ( LL_INFO, "rebuildDataModel(): Simulating data" );
	// } else {
	// logger ( LL_INFO, "rebuildDataModel(): Simulating data (not enough real data yet)" );
	// }

	global $summer_solstice, $day_temperature_winter, $day_temperature_summer, $night_temperature_winter, $night_temperature_summer, $day_humidity_summer, $day_humidity_winter, $night_humidity_summer, $night_humidity_winter, $sunset_summer, $sunset_winter, $daylight_summer, $daylight_winter;

	$tsnow = timestampNow ();
	$yr = timestampFormat ( $tsnow, "Y" );

	$high_delta_temperature = ($day_temperature_summer - $day_temperature_winter) / 2;
	$high_mid_temperature = $day_temperature_winter + $high_delta_temperature;

	$low_delta_temperature = ($night_temperature_summer - $night_temperature_winter) / 2;
	$low_mid_temperature = $night_temperature_winter + $low_delta_temperature;

	$high_delta_humidity = ($day_humidity_winter - $day_humidity_summer) / 2;
	$high_mid_humidity = $day_humidity_summer + $high_delta_humidity;

	$low_delta_humidity = ($night_humidity_winter - $night_humidity_summer) / 2;
	$low_mid_humidity = $night_humidity_summer + $low_delta_humidity;

	$sunset_delta_offset = ($sunset_summer - $sunset_winter) / 2;
	$sunset_mid_offset = $sunset_winter + $sunset_delta_offset;

	// echo "Sunset MIN: $sunset_winter. MAX: $sunset_summer\n";
	// echo "Sunset AVG: $sunset_mid_offset. delta: $sunset_delta_offset\n";

	$sunrise_min = $sunset_summer - $daylight_summer; // longest day
	$sunrise_max = $sunset_winter - $daylight_winter; // shortesst
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
			// $model [timestampFormat ( $tsnow, "md" )] = $model ["0228"];
			$tsnow = timestampAdd ( $tsnow, numDays ( 1 ) );
		}

		// Highest temps happen about 60 days after the solstice
		global $solstice_temp_delta_days;
		$obj->temperatureDay = round ( $high_mid_temperature + $high_delta_temperature * cos ( deg2rad ( ($i - $solstice_temp_delta_days) * $deg_step ) ), 3 );
		$obj->temperatureNight = round ( $low_mid_temperature + $low_delta_temperature * cos ( deg2rad ( ($i - $solstice_temp_delta_days) * $deg_step ) ), 3 );

		// Humidity is also offset from the solstice
		$obj->humidityDay = round ( $high_mid_humidity + $high_delta_humidity * cos ( deg2rad ( 180 + ($i - $solstice_temp_delta_days) * $deg_step ) ), 3 );
		$obj->humidityNight = round ( $low_mid_humidity + $low_delta_humidity * cos ( deg2rad ( 180 + ($i - $solstice_temp_delta_days) * $deg_step ) ), 3 );

		// Daylength is the only real thing that is bount to the solstices
		$obj->sunsetOffset = round ( ($sunset_mid_offset + $sunset_delta_offset * cos ( deg2rad ( $i * $deg_step ) )) * 3600, 3 );
		$obj->sunriseOffset = round ( ($sunrise_mid_offset + $sunrise_delta_offset * cos ( deg2rad ( 180 + $i * $deg_step ) )) * 3600, 3 );
		$obj->daylightHours = round ( ($obj->sunsetOffset - $obj->sunriseOffset) / 3600, 3 );

		$model [timestampFormat ( $tsnow, "md" )] = $obj;

		$tsnow = timestampAdd ( $tsnow, numDays ( 1 ) );
	}

	return $model;
}

function rebuildDataModel() {
	global $mysql, $darksky_key, $demand;
	$model = array ();

	if (count ( $demand ) > 0) {
		if ($darksky_key != "") {
        		echo "Retrieving historic data from Dark Sky\n";
        		global $force_api_history;
        		getDarkSkyApiData ( $force_api_history );
		}
		$model = rebuildModelFromDemands ();
	} else if ($darksky_key != "") {
		$model = rebuildModelFromDarkSky ();
	} else {
		$model = rebuildModelFromSimulation ();
	}

	// Update the model table
	if ($model && count ( $model )) {
		foreach ( $model as $k => $v ) {
			$values = array (
					"id" => $k,
					"data" => json_encode ( $v )
			);

			$mysql->query ( "REPLACE INTO model (id, data) VALUES(?, ?)", "ss", array_values ( $values ) );
		}
		logger ( LL_INFO, "rebuildDataModel(): Stored model to database" );
	} else {
		logger ( LL_INFO, "rebuildDataModel(): No model generated" );
	}
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
			if (strlen ( $t ) > 4) {
				$t = timestampFormat ( $t, "md" );
			}
			if ($t == "0229") {
				$t = "0228"; // No leap years
			}
			$sql .= $comma . "'" . $t . "'";
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
	// echo "enumerateTriggers(): called\n";
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
	// echo "enumerateSensors(): called\n";
	global $sensors_enumerated, $sensors;

	if ($sensors_enumerated) {
		return;
	}
	$sensors_enumerated = true;

	foreach ( $sensors as $k => $s ) {
		$s->enumeration = sensorEnumeration ( $s->type );
		if ($s->type == "PI") {
			$s->pin = 0;
		} else {
			$gpio_pin = "sensor_pin_" . ($k);
			global $$gpio_pin;
			$s->pin = $$gpio_pin;
		}
		$s->ofn = "/tmp/sensor_data_" . ($k) . ".json";
		// echo "sensor: ".$s->type.", k: ".$k.", pin: ".$gpio_pin.", value: ".($$gpio_pin)."\n";
	}
}

$w1_enum = 1;
$iio_enum = 0;

function sensorEnumeration($type) {
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

	// See https://www.raspberrypi.org/forums/viewtopic.php?t=80730#p1056163 for better way to handle LED
	switch ($type) {
		case "LED" :
			$ret = "pi3-act-led";
			break;
//		case "DHT11" :
//		case "DHT22" :
//			$ret = "dht11";
//			break;
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
		case "PI" :
		case "EMPTY" :
		case "DHT11" :
		case "DHT22" :
		case "MH-Z19B" :
			$ret = false;
			break;
	}

	// echo "isGpio($type): " . tfn ( $ret ) . "\n";
	return $ret;
}

function createTriggersSetupScript() {
	global $triggers, $led_pin, $button_pin;
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

	// Now handled with the heartbeat overlay
	// if($led_pin != 99) {
	// $ret .= "# LED\n";
	// $ret .= "gpio -g mode " . $led_pin . " out\n";
	// $ret .= "gpio -g mode " . $led_pin . " down\n";
	// $ret .= "gpio -g write " . $led_pin . " 1\n";
	// $ret .= "\n";
	// }

	// Handled in the Python library
	// if($button_pin != 99) {
	// $ret .= "# BUTTON\n";
	// $ret .= "gpio -g mode " . $button_pin . " in\n";
	// $ret .= "gpio -g mode " . $button_pin . " up\n";
	// //$ret .= "gpio -g write " . $button_pin . " 1\n";
	// $ret .= "\n";
	// }

	return $ret;
}

function createSensorsSetupScript() {
	global $sensors;
	enumerateSensors ();

	$ret = "";

	foreach ( $sensors as $s ) {
		// echo ob_print_r($s);
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
	global $outlier_temperature_min, $outlier_temperature_max, $outlier_humidity_min, $outlier_humidity_max;

		$output = null;
		$retvar = 0;
		$cmd = "/webroot/gStation/sh/DHTXXD -g".$sensor->pin." 2>&1";
		$val = null;

		$retry_count = 0;
		$retry_limit = 5;
		$retry = false;
		do {
			if ($retry) {
				//$sleep_ms = mt_rand(2250,5000);
				$sleep_ms = 500;
				echo ("Read $retry_count failed, pausing for ".$sleep_ms."ms and retrying\n");
				usleep($sleep_ms * 1000);
			}
			$retry_count = $retry_count + 1;
			$retry = true;

			exec ( $cmd, $output, $retvar );
			@list($err, $temp, $humidity) = explode(" ", $output[0]);
			if($err == 0) {
				echo "Got zero error status. T: ".$temp.", H: ".$humidity."\n";
				$ret->temperature = $temp;
				$ret->humidity = $humidity;
				$val = true;
			} else {
				echo "Got a non-zero error code: ".$err."\n";
			}
/*
			if (strlen ( $output [0] ) == 5) {
				echo ("Got correct character count '" . $output [0] . "'\n");
				if (is_numeric ( $output [0] )) {
					echo ("Integer conversion works\n");
					$val = (( double ) $output [0]) / 1000.00;
					$olmax = "outlier_" . ($e->name) . "_max";
					$olmin = "outlier_" . ($e->name) . "_min";
					if ($$olmin != "" && $val < $olmin) {
						$msg = ($e - name . " reading of " . $val . " is out of tolerance (< " . ($$olmin) . ")\n");
						echo $msg;
						logger ( LL_WARNING, $msg );
						$val = null;
					} elseif ($$olmax != "" && $val < $olmax) {
						$msg = ($e - name . " reading of " . $val . " is out of tolerance (> " . ($$olmax) . ")\n");
						echo $msg;
						logger ( LL_WARNING, $msg );
						$val = null;
					}
				} else {
					echo ("Integer conversion failed '" . $output [0] . "'\n");
				}
			} else {
				echo ("Got incorrect character count:\n" . ob_print_r ( $output ) . "\n");
			}
*/
			$output = null;
		} while ( $val == null && $retry_count < $retry_limit );

		if ($val == null) {
			echo "Read sensor failed.\n";
			return null;
		}

	return $ret;
}

function readSensorRaw_DHT11_orig($sensor) {
	$ret = new StdClass ();
	global $outlier_temperature_min, $outlier_temperature_max, $outlier_humidity_min, $outlier_humidity_max;

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
				$sleep_ms = mt_rand(2250,5000);
				echo ("Read $retry_count failed, pausing for ".$sleep_ms."ms and retrying\n");
				usleep($sleep_ms * 1000);
				//usleep(2250000); // 2.25 seconds
				//sleep(5);
			}
			$retry_count = $retry_count + 1;
			$retry = true;

			exec ( $cmd, $output, $retvar );
			if (strlen ( $output [0] ) == 5) {
				echo ("Got correct character count '" . $output [0] . "'\n");
				if (is_numeric ( $output [0] )) {
					echo ("Integer conversion works\n");
					$val = (( double ) $output [0]) / 1000.00;
					$olmax = "outlier_" . ($e->name) . "_max";
					$olmin = "outlier_" . ($e->name) . "_min";
					if ($$olmin != "" && $val < $olmin) {
						$msg = ($e - name . " reading of " . $val . " is out of tolerance (< " . ($$olmin) . ")\n");
						echo $msg;
						logger ( LL_WARNING, $msg );
						$val = null;
					} elseif ($$olmax != "" && $val < $olmax) {
						$msg = ($e - name . " reading of " . $val . " is out of tolerance (> " . ($$olmax) . ")\n");
						echo $msg;
						logger ( LL_WARNING, $msg );
						$val = null;
					}
				} else {
					echo ("Integer conversion failed '" . $output [0] . "'\n");
				}
			} else {
				echo ("Got incorrect character count:\n" . ob_print_r ( $output ) . "\n");
			}
			$output = null;
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

function readSensorRaw_MH_Z19B($sensor) {
	// Coming soon
	echo "readSensorRaw_MH_Z19B(): Coming soon\n";
	return null;
	/*
	 * Enalbe UART:
	 * http://www.circuits.dk/setup-raspberry-pi-3-gpio-uart/
	 * Python: https://www.circuits.dk/testing-mh-z19-ndir-co2-sensor-module/
	 *
	 * // Let's start the class
	 * $serial = new PhpSerial;
	 *
	 * // First we must specify the device. This works on both linux and windows (if
	 * // your linux serial device is /dev/ttyS0 for COM1, etc)
	 * $serial->deviceSet("COM1");
	 *
	 * // We can change the baud rate, parity, length, stop bits, flow control
	 * $serial->confBaudRate(2400);
	 * $serial->confParity("none");
	 * $serial->confCharacterLength(8);
	 * $serial->confStopBits(1);
	 * $serial->confFlowControl("none");
	 *
	 * // Then we need to open it
	 * $serial->deviceOpen();
	 *
	 * // To write into
	 * $serial->sendMessage("Hello !");
	 *
	 * // Or to read from
	 * $read = $serial->readPort();
	 *
	 * // If you want to change the configuration, the device must be closed
	 * $serial->deviceClose();
	 *
	 * // We can change the baud rate
	 * $serial->confBaudRate(2400);
	 *
	 * // etc...
	 * //
	 * //
	 * Notes from Jim :
	 * > Also, one last thing that would be good to document, maybe in example.php:
	 * > The actual device to be opened caused me a lot of confusion, I was
	 * > attempting to open a tty.* device on my system and was having no luck at
	 * > all, until I found that I should actually be opening a cu.* device instead!
	 * > The following link was very helpful in figuring this out, my USB/Serial
	 * > adapter (as most probably do) lacked DTR, so trying to use the tty.* device
	 * > just caused the code to hang and never return, it took a lot of googling to
	 * > realize what was going wrong and how to fix it.
	 * >
	 * > http://lists.apple.com/archives/darwin-dev/2009/Nov/msg00099.html
	 *
	 * Riz comment : I've definately had a device that didn't work well when using cu., but worked fine with tty. Either way, a good thing to note and keep for reference when debugging.
	 */
}

function getVmStats() {
	$hdd = exec ( "df -k | grep '^\/dev\/root'" );
	// echo "free: '$free'\n";
	$bits = explode ( " ", preg_replace ( '/\s+/', " ", trim ( $hdd ) ) );
	// echo "bits: " . ob_print_r ( $bits ) . "\n";

	$keys = [ ];
	$keys [] = "fs";
	$keys [] = "blocks";
	$keys [] = "used";
	$keys [] = "available";
	$keys [] = "use";
	$keys [] = "mount";

	$hdd = new StdClass ();
	foreach ( $bits as $k => $v ) {
		$key = $keys [$k];
		$hdd->$key = $v;
	}
	// echo "HDD: ".ob_print_r($hdd)."\n";

	$free = exec ( "free | grep '^Mem:'" );
	// echo "free: '$free'\n";
	$bits = explode ( " ", preg_replace ( '/\s+/', " ", trim ( $free ) ) );
	// echo "bits: " . ob_print_r ( $bits ) . "\n";

	$keys = [ ];
	$keys [] = "dummy";
	$keys [] = "total";
	$keys [] = "used";
	$keys [] = "free";
	$keys [] = "shared";
	$keys [] = "cache";
	$keys [] = "available";

	$free = new StdClass ();
	foreach ( $bits as $k => $v ) {
		$key = $keys [$k];
		$free->$key = $v;
	}

	$vmstat = exec ( "vmstat 1 2" );
	// echo "vmstat: '$vmstat'\n";
	$bits = explode ( " ", preg_replace ( '/\s+/', " ", trim ( $vmstat ) ) );
	// echo "bits: " . ob_print_r ( $bits ) . "\n";

	$keys = [ ];
	$keys [] = "procs_r";
	$keys [] = "procs_b";
	$keys [] = "mem_swapd";
	$keys [] = "mem_free";
	$keys [] = "mem_buff";
	$keys [] = "mem_cache";
	$keys [] = "swap_si";
	$keys [] = "swap_so";
	$keys [] = "io_bi";
	$keys [] = "io_bo";
	$keys [] = "sys_in";
	$keys [] = "sys_cs";
	$keys [] = "cpu_us";
	$keys [] = "cpu_sy";
	$keys [] = "cpu_id";
	$keys [] = "cpu_wa";
	$keys [] = "cpu_st";

	$vmstat = new StdClass ();
	foreach ( $bits as $k => $v ) {
		$key = $keys [$k];
		$vmstat->$key = $v;
		// echo "(".$k.") '".$key."' - '" .$v."'\n";
	}

	$throt = exec ( "vcgencmd get_throttled" );
	$throt = explode ( "0x", $throt ) [1];
	$throt = hexdec ( $throt );

	$temp = exec ( "vcgencmd measure_temp" );
	$temp = explode ( "=", $temp ) [1];
	$temp = explode ( "'", $temp ) [0];

	$ret = new StdClass ();
	$ret->sd_total = $hdd->blocks;
	$ret->sd_avail = $hdd->available;
	$ret->sd_load = round ( 100 * $hdd->used / $hdd->blocks, 3 );
	$ret->cpu_wait = $vmstat->cpu_wa;
	$ret->cpu_load = 100 - $vmstat->cpu_id;
	$ret->mem_total = $free->total;
	$ret->mem_avail = $free->available;
	$ret->mem_load = round ( 100 * ($ret->mem_total - $ret->mem_avail) / $ret->mem_total, 3 );
	$ret->temperature = $temp;
	$ret->under_voltage = bitCompare ( "UNDERVOLT", $throt, (1 << 0), (1 << 16) );
	$ret->frequency_capped = bitCompare ( "FREQCAP", $throt, (1 << 1), (1 << 17) );
	$ret->throttled = bitCompare ( "THROTTLED", $throt, (1 << 2), (1 << 18) );
	$ret->soft_temperature_limited = bitCompare ( "TEMPLIMIT", $throt, (1 << 3), (1 << 19) );

	return $ret;
}

function readSensorRaw_PI($sensor) {
	echo "readSensorRaw_PI():" . ob_print_r ( $sensor ) . "\n";
	return getVmStats ();
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

function highValue($type) {
	$ret = 1;

	switch ($type) {
		case "iSSR" :
			$ret = 0;
			break;
	}

	return $ret;
}

function lowValue($type) {
	$ret = 0;

	switch ($type) {
		case "iSSR" :
			$ret = 1;
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
	echo "readSensor($i): started\n";

	if (! isset ( $sensors [$i] )) {
		echo ("No sensor defined for slot #" . $i . " - endless looping required\n");
		while ( true ) {
			// Endless loop
			sleep ( 30 );
		}
	}
	enumerateSensors ();

	$sensor = $sensors [$i];
	echo "readSensor($i): sensor: " . ob_print_r ( $sensor ) . "\n";
	$type = $sensor->type;
	$pin = @$sensor->pin;
	$func = "readSensorRaw_" . str_replace ( "-", "_", $sensor->type );

	if ($type == "EMPTY" || $pin == 99) {
		echo ("Sensor slot #" . $i . " is not configured - endless looping required\n");
		while ( true ) {
			// Endless loop
			sleep ( 30 );
		}
	}

	if (isGpio ( $type )) {
		while ( checkSensors ( $type, $pin ) == false ) {
			echo "No sensor overlay setup for a " . $type . " on pin " . $pin . " - will retry in 30 seconds\n";
			sleep ( 30 );
			// echo "retrying...\n";
		}
	} else if (! function_exists ( $func )) {
		echo ("Sensor slot #" . $i . " is not readable - endless looping required\n");
		while ( true ) {
			// Endless loop
			sleep ( 30 );
		}
	}

	$lfn = "/tmp/sensor_reader_" . $i . ".log";
	while ( true ) {
		ob_start ();
		$ret = $func ( $sensor );
		if ($ret == null) {
			echo "Garbage sensor??\n";
		} else {
			$ret->event = time ();
			$ret->name = $sensor->name;
			$jstr = json_encode ( $ret );
			file_put_contents ( $sensor->ofn, $jstr );
			touch ( $sensor->ofn );
			echo "Writing to '" . $sensor->ofn . "'\n";
			print_r ( $ret );
		}
		sleep ( sensorCooloff ( $type ) );
		$c = ob_get_contents ();
		file_put_contents ( $lfn, $c );
		ob_end_clean ();
	}
}

function gatherSensors() {
	global $sensor_age;
	$key = "/tmp/sensor_data_";
	$files = directoryListing ( "/tmp", "*.json" );
	$ret = array ();
	foreach ( $files as $file ) {
		if (substr ( $file, 0, strlen ( $key ) ) == $key) {
			$c = file_get_contents ( $file );
			$j = json_decode ( $c );
			$t = 0;
			if (isset ( $j->event )) {
				$t = $j->event;
				unset ( $j->event );
			}
			$age = time () - $t;
			$age_str = ($age < 0) ? (durationFormat ( - $age ) . " in the future") : (durationFormat ( $age ) . " old");
			// echo "time now: ".time()." (".timestampFormat(time2Timestamp(time())).")\n";
			// echo "time file: ".$t." (".timestampFormat(time2Timestamp($t)).")\n";
			// echo "age: ".$age." (".$age_str.")\n";
			if ($age >= $sensor_age) {
				echo "Skipping '$file' - data too old (age: " . $age_str . ")\n";
			} else {
				echo "Processing '$file' (age: " . $age_str . ")\n";
				$name = $j->name;
				unset ( $j->name );
				$j = ( array ) $j;
				foreach ( $j as $k => $v ) {
					$o = new StdClass ();
					$o->name = $name;
					$o->param = $k;
					$o->value = $v;
					$o->age = $age;
					$ret [] = $o;
				}
			}
		}
	}
	return $ret;
}

function setupGpio($quiet = false) {
	// echo "setupGpio(): called\n";
	$runtime_version = @file_get_contents ( dirname ( __FILE__ ) . "/../board.txt" );

	global $sensor_pin_1, $sensor_pin_2, $sensor_pin_3, $sensor_pin_4, $trigger_pin_1, $trigger_pin_2, $trigger_pin_3, $trigger_pin_4, $trigger_pin_5, $trigger_pin_6, $led_pin, $button_pin;

	if (in_array ( $runtime_version, [ 
			"2.1f",
			"2.1g",
			"2.0b",
			"2.0c"
	] )) {
		$sensor_pin_1 = 4;
		$sensor_pin_2 = 17;
		$sensor_pin_3 = 7;

		$trigger_pin_1 = 18;
		$trigger_pin_2 = 23;
		$trigger_pin_3 = 24;
		$trigger_pin_4 = 25;

		// The 2.1 boards have more sensors
		if (in_array ( $runtime_version, [ 
				"2.1g",
				"2.1f"
		] )) {
			$sensor_pin_4 = 22;
			$trigger_pin_5 = 8;
			$trigger_pin_6 = 11;
		}

		// In these versions of the board, the sensors and triggers are all the same, but the button pin moved, and an LED was added in later versions.
		if (in_array ( $runtime_version, [ 
				"2.1g"
		] )) {
			$button_pin = 10;
			$led_pin = 9;
		}
		if (in_array ( $runtime_version, [ 
				"2.0c"
		] )) {
			$led_pin = 10;
		}
		if (in_array ( $runtime_version, [ 
				"2.1f",
				"2.0c",
				"2.0b"
		] )) {
			$button_pin = 9;
		}
	}
	if ($runtime_version == "2.0") {
		// THe first PCB
		$sensor_pin_1 = 4;

		$trigger_pin_1 = 17;
		$trigger_pin_2 = 18;

		$button_pin = 14;
	}

	if (! $quiet) {
		echo "setupGpio(): runtime_version = " . $runtime_version . "\n";
		if ($sensor_pin_1 != 99) {
			echo "setupGpio(): sensor_pin_1 = " . $sensor_pin_1 . "\n";
		}
		if ($sensor_pin_2 != 99) {
			echo "setupGpio(): sensor_pin_2 = " . $sensor_pin_2 . "\n";
		}
		if ($sensor_pin_3 != 99) {
			echo "setupGpio(): sensor_pin_3 = " . $sensor_pin_3 . "\n";
		}
		if ($sensor_pin_4 != 99) {
			echo "setupGpio(): sensor_pin_4 = " . $sensor_pin_4 . "\n";
		}
		if ($trigger_pin_1 != 99) {
			echo "setupGpio(): trigger_pin_1 = " . $trigger_pin_1 . "\n";
		}
		if ($trigger_pin_2 != 99) {
			echo "setupGpio(): trigger_pin_2 = " . $trigger_pin_2 . "\n";
		}
		if ($trigger_pin_3 != 99) {
			echo "setupGpio(): trigger_pin_3 = " . $trigger_pin_3 . "\n";
		}
		if ($trigger_pin_4 != 99) {
			echo "setupGpio(): trigger_pin_4 = " . $trigger_pin_4 . "\n";
		}
		if ($trigger_pin_5 != 99) {
			echo "setupGpio(): trigger_pin_5 = " . $trigger_pin_5 . "\n";
		}
		if ($trigger_pin_6 != 99) {
			echo "setupGpio(): trigger_pin_6 = " . $trigger_pin_6 . "\n";
		}
		if ($button_pin != 99) {
			echo "setupGpio(): button_pin = " . $button_pin . "\n";
		}
		if ($led_pin != 99) {
			echo "setupGpio(): led_pin = " . $led_pin . "\n";
		}
	}
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
	global $mysql, $demand, $darksky_key;

	$ret = new StdClass ();

	global $darksky_key;
	if ($demand && count ( $demand )) {
		$ret->modelUsed = "Demand Ramp";
	} elseif ($darksky_key !== "") {
		$ret->modelUsed = "DarkSky";
		$raw = getDarkSkyDataPoints ( null, true );
		$valid = getDarkSkyDataPoints ( null, false );
		$ret->dataPointTotal = count ( $raw );
		$ret->dataPointValid = count ( $valid );
		$ret->dataPointInvalid = count ( $raw ) - count ( $valid );
		$ret->dataPointPerDay = floor ( count ( $raw ) / 365 );
	} else {
		$ret->modelUsed = "Environment Simulation";
	}
	$rows = $mysql->query ( "select max(last_updated) as ud from model" );
	if ($rows && count ( $rows )) {
		$ret->lastModelRebuild = $rows [0] ["ud"];
	}
	return $ret;
}

function tick() {
	echo "************************************************************************************************************************************\n";
	global $mysql, $bulksms_notify, $bulksms_alert_sunset, $bulksms_alert_sunrise, $bulksms_alert_tod, $triggers, $conditions;

	// Set the parameters for the tick
	$tsnow = timestampNow ();
	$nowOffset = timestampFormat ( $tsnow, "H" ) * 60 * 60 + timestampFormat ( $tsnow, "i" ) * 60 + timestampFormat ( $tsnow, "s" );

	$model = getModel ( $tsnow );
	echo "\nModel data:\n";
	print_r ( $model );

	// Prepare the storage for later
	$data = array (); // Whre we will store sensors and trigger data

	/**
	 * *************************************************************************************************************************************
	 * Calculate the sunset/rise times.
	 */
	$last_status = getConfig ( "status", "NIGHT" );
	$last_tod = getConfig ( "tod", "NIGHT" );
	$tod = "NIGHT";
	$status = (( int ) ($nowOffset) >= ( int ) ($model->sunriseOffset) && ( int ) ($nowOffset) <= ( int ) ($model->sunsetOffset)) ? ("DAY") : ("NIGHT");
	if ($status == "DAY") {
		$dayLength = $model->sunsetOffset - $model->sunriseOffset;
		$stepOffset = $dayLength / 5;
		echo "LIGHT.MOON, TOD.NIGHT    : 00:00:00 -> " . timeOfDay ( $model->sunriseOffset );
		if ($nowOffset < ($model->sunriseOffset)) {
			echo " <-- ***NOW***\n";
		} else {
			echo "\n";
		}
		// echo "SUNRISE = " . timeOfDay($model->sunriseOffset) . "\n";
		// echo "SUNSET = " . timeOfDay($model->sunsetOffset) . "\n";
		echo "LIGHT.SUN,  TOD.EARLY    : " . timeOfDay ( $model->sunriseOffset + 0 * $stepOffset ) . " -> " . timeOfDay ( $model->sunriseOffset + 1 * $stepOffset );
		if ($nowOffset >= ($model->sunriseOffset + 0 * $stepOffset) && $nowOffset < ($model->sunriseOffset + 1 * $stepOffset)) {
			echo " <-- ***NOW***\n";
		} else {
			echo "\n";
		}
		echo "LIGHT.SUN,  TOD.EARLYMID : " . timeOfDay ( $model->sunriseOffset + 1 * $stepOffset ) . " -> " . timeOfDay ( $model->sunriseOffset + 2 * $stepOffset );
		if ($nowOffset >= ($model->sunriseOffset + 1 * $stepOffset) && $nowOffset < ($model->sunriseOffset + 2 * $stepOffset)) {
			echo " <-- ***NOW***\n";
		} else {
			echo "\n";
		}
		echo "LIGHT.SUN,  TOD.MID      : " . timeOfDay ( $model->sunriseOffset + 2 * $stepOffset ) . " -> " . timeOfDay ( $model->sunriseOffset + 3 * $stepOffset );
		if ($nowOffset >= ($model->sunriseOffset + 2 * $stepOffset) && $nowOffset < ($model->sunriseOffset + 3 * $stepOffset)) {
			echo " <-- ***NOW***\n";
		} else {
			echo "\n";
		}
		echo "LIGHT.SUN,  TOD.MIDLATE  : " . timeOfDay ( $model->sunriseOffset + 3 * $stepOffset ) . " -> " . timeOfDay ( $model->sunriseOffset + 4 * $stepOffset );
		if ($nowOffset >= ($model->sunriseOffset + 3 * $stepOffset) && $nowOffset < ($model->sunriseOffset + 4 * $stepOffset)) {
			echo " <-- ***NOW***\n";
		} else {
			echo "\n";
		}
		echo "LIGHT.SUN,  TOD.LATE     : " . timeOfDay ( $model->sunriseOffset + 4 * $stepOffset ) . " -> " . timeOfDay ( $model->sunriseOffset + 5 * $stepOffset );
		if ($nowOffset >= ($model->sunriseOffset + 4 * $stepOffset) && $nowOffset < ($model->sunriseOffset + 5 * $stepOffset)) {
			echo " <-- ***NOW***\n";
		} else {
			echo "\n";
		}
		echo "LIGHT.MOON, TOD.NIGHT    : " . timeOfDay ( $model->sunsetOffset ) . " -> 23:59:59";
		if ($nowOffset >= ($model->sunsetOffset)) {
			echo " <-- ***NOW***\n";
		} else {
			echo "\n";
		}

		// echo "day step offset = " . ($stepOffset / 60) . " minutes\n";
		if ($nowOffset < ($model->sunriseOffset + 1 * $stepOffset)) {
			$tod = "EARLY";
		} else if ($nowOffset < ($model->sunriseOffset + 2 * $stepOffset)) {
			$tod = "EARLYMID";
		} else if ($nowOffset < ($model->sunriseOffset + 3 * $stepOffset)) {
			$tod = "MID";
		} else if ($nowOffset < ($model->sunriseOffset + 4 * $stepOffset)) {
			$tod = "MIDLATE";
		} else {
			$tod = "LATE";
		}
	}
	if ($last_tod != $tod) {
		setConfig ( "tod", $tod );
	}

	$msg = "Status is still '" . $last_tod . "'";
	$ll = LL_DEBUG;
	if ($last_status != $status) {
		$msg = "Status changed from '" . $last_status . "' to '" . $status . "'";
		$ll = LL_INFO;
		setConfig ( "status", $status );
		if (($status == "DAY" && $bulksms_alert_sunrise) || ($status == "NIGHT" && $bulksms_alert_sunset)) {
			echo "############################### SMS ##### $msg\n";
			sendSms ( $msg, $bulksms_notify );
		}
	} else {
		if ($last_tod != $tod) {
			$msg = "Time of day changed from '" . $last_tod . "' to '" . $tod . "'";
			if ($bulksms_alert_tod) {
				echo "############################### SMS ##### $msg\n";
				sendSms ( $msg, $bulksms_notify );
			}
		}
	}
	logger ( $ll, "tick(): " . $msg );

	// $status = "DAY";
	$hl = ($status == "DAY") ? ("Day") : ("Night");
	$temp = "temperature" . $hl;
	$humd = "humidity" . $hl;
	$data ["DEMAND.LIGHT"] = "'" . (($status == 'DAY') ? ("SUN") : ("MOON")) . "'";
	$data ["DEMAND.TEMPERATURE"] = round ( $model->$temp, 3 );
	//echo "Humidity in model: ". $model->$humd."\n";
	$data ["DEMAND.HUMIDITY"] = round ( $model->$humd, 3 );
	$data ["DATA.HOUR"] = round ( $nowOffset / (60 * 60), 3 );
	$data ["DATA.HR"] = floor ( $nowOffset / (60 * 60) );
	// $data ["DATA.HR"] = timestampFormat ( timestampNow (), "H" );
	$data ["DATA.MN"] = timestampFormat ( timestampNow (), "i" );
	$data ["DATA.TOD"] = "'" . $tod . "'";

	// setConfig ( "temperature_demand", $model->$temp );
	// setConfig ( "humidity_demand", $model->$humd );

	echo "\nGathering sensor data\n";
	$sensors = gatherSensors ();
	$ages = array();
	foreach ( $sensors as $s ) {
		$name = $s->name;
		$param = $s->param;
		$val = $s->value;
		$age = $s->age;
		$data [strtoupper ( $name . "." . $param )] = $val;
		$ages [strtoupper ( $name )] = $age;
	}

	//echo "\nEnvironmental data:\n";
	//print_r ( $data );

	// Now we get the triggers and see what we need to set

	echo "\nProcessing trigger conditions\n";
	$fires = array ();
	enumerateTriggers ();
	foreach ( $triggers as $t ) {
		if (isGpio ( $t->type )) {
			$t->demand = 0;
			$fires [$t->name] = $t;
		}
	}

	foreach ( $conditions as $c ) {
		$oc = $c;
		foreach ( $data as $k => $v ) {
			$c = str_replace ( "[[" . $k . "]]", $v, $c );
		}

		$matches = array ();
		if (preg_match ( "/\[\[(.*?)\]\]/", $c, $matches ) == 0) {
			list ( $k, $expr ) = explode ( " IF ", $c );
			if (isset ( $fires [$k] )) {
				$eval = '$fire = (' . $expr . ')?(highValue($fires[$k]->type)):(lowValue($fires[$k]->type));';
				eval ( $eval );
				$fires[$k]->demand = ($fire)?($fire):($fires[$k]->demand);
				echo (($fire)?("FIRED"):("-----")).": ". $k . " = (" . $expr . ")?(1):(0); // ".$oc."\n";
			} else {
				echo "Skipping damaged condition - Trigger '" . $k . "' not found (" . $oc . ")\n";
			}
		} else {
			$missing = [ ];
			$cap = false;
			foreach ( $matches as $m ) {
				if ($cap) {
					$missing [] = $m;
				}
				$cap = ! $cap;
			}
			echo "Skipping damaged condition - 'Sensor '" . implode ( "', '", $missing ) . "' not found (" . $oc . ")\n";
		}
	}
	// setLight ( ($status == "DAY") ? ($hl_high_value) : ($hl_low_value) );

	// print_r ( $fires );

	echo "\nExecuting triggers\n";
	foreach ( $fires as $f ) {
		$data ["TRIGGER." . $f->name] = ($f->demand == highValue ( $f->type )) ? (1) : (0);
		$cmd = "gpio -g write " . $f->pin . " " . $f->demand;
		echo "Executing (" . $f->name . ") '" . $cmd . "'\n";
		system ( $cmd . " > /dev/null 2>&1" );
	}

	// Stash data to database tables
	foreach ( $data as $k => $v ) {
		list ( $what, $k ) = explode ( ".", $k );
		if ($what == "DEMAND") {
			$mysql->query ( "REPLACE INTO demands (event, param, value) VALUES (?, ?, ?)", "iss", array (
					$tsnow,
					$k,
					$v
			) );
		} elseif ($what == "TRIGGER") {
			$mysql->query ( "REPLACE INTO triggers (event, param, value) VALUES (?, ?, ?)", "iss", array (
					$tsnow,
					$k,
					$v
			) );
		} elseif ($what == "DATA") {
			// We know about it, but we can ignore it
		} elseif ($what == "INFO") {
			// We know about it, but we can ignore it
		} else {
			$age = isset($ages[$what])?($ages[$what]):(null);
			//echo "writing ".$what." to database k: ".$k.", v: ".$v.", age: ".tfn($age)."\n";
			// Must be a zone name
			$mysql->query ( "REPLACE INTO sensors (event, name, param, value, age) VALUES (?, ?, ?, ?, ?)", "isssi", array (
					$tsnow,
					$what,
					$k,
					$v,
					$age
			) );
		}
	}

	// TODO: Get OLED Working correctly
	$retvar = 0;
	$output = "";
	$cmd = "hostname 2>/dev/null";
	exec ( $cmd, $output, $retvar );
	// echo "Ran: '".$cmd."' : ".ob_print_r($output)."\n";
	$hostname = $output[0];

	$retvar = 0;
	$output = "";
	$cmd = "hostname -I 2>/dev/null";
	exec ( $cmd, $output, $retvar );
	// echo "Ran: '".$cmd."' : ".ob_print_r($output)."\n";
	@list ( $ipaddress, $dummy ) = explode ( " ", $output [0] );

	$next_sun = nextSunChange ();

	// Update the info parameters
	$data ["INFO.IPADDR"] = $ipaddress;
	$data ["INFO.HOSTNAME"] = $hostname;
	$data ["INFO.NEXTSUN"] = $next_sun;

	// Set the display message
	$ostr = "";
	$ostr .= $ipaddress;
	$ostr .= "|";
	$ostr .= $next_sun;
	file_put_contents ( "/tmp/oled.txt", $ostr );

	ksort($data);
	echo "\nEnvironmental data:\n";
	print_r ( $data );
	setConfig ( "env", json_encode ( $data ) );
}

?>
