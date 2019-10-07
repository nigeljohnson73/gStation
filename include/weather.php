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

function tick() {
	global $lat, $lng, $day, $mon, $bulksms_owner_sms;
	$data = getData ( $lat, $lng, $day, $mon );

	$last_status = getConfig ( "status", "NIGHT" );
	$tsnow = timestampFormat ( timestampNow (), "His" );
	$status = (( int ) ($tsnow) >= ( int ) ($data->sunrise) && ( int ) ($tsnow) <= ( int ) ($data->sunset)) ? ("DAY") : ("NIGHT");
	// logger ( LL_INFO, "Tick control: \n" . ob_print_r ( $data ) );
	echo "Status: '" . $status . "'\n";
	echo ob_print_r ( $data ) . "\n";

	$msg = "status is still '" . $last_status . "'";
	if ($last_status != $status) {
		$msg = "status changed from '" . $last_status . "' to '" . $status . "'";
		logger ( LL_INFO, "tick(): " . $msg );
		setConfig ( "status", $status );
		sendSms ( $msg, $bulksms_owner_sms );
	} else {
		logger ( LL_DEBUG, "tick(): " . $msg );
	}
	// sendSms($msg, "447517528741");
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
		if (isset ( $row->timezone )) {
			$obj->timezone = $row->timezone;
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

function getData($lat, $lng, $day, $mon, $fill = false, $force = false) {
	global $darksky_key;
	global $mysql;
	global $dy_history, $dy_forecast;

	// calculate so we can do comparisons
	$yr = timeStampFormat ( timestampNow (), "Y" );

	// Get a list of ID's associated with this day
	$id_list = getIdList ( $lat, $lng, $day, $mon );
	logger ( LL_DEBUG, "getData(): expecting " . count ( $id_list ) . " data elements" );
	// logger ( LL_INFO, "getData(): id list:\n" . ob_print_r ( $id_list ) );

	// Get the data points in the database if they exist
	$data_points = getDataPoints ( $id_list );
	logger ( LL_DEBUG, "getData(): found " . count ( $data_points ) . " database elements" );
	// logger ( LL_INFO, ob_print_r ( $data_points ) );

	// Process the list to see what we have missing
	$ret = array ();
	$refresh = array ();
	foreach ( $id_list as $id ) {
		$bits = explode ( "|", $id );
		$ts_yr = timestampFormat ( $bits [0], "Y" );
		$diff = abs ( timestampDifference ( timestampDay ( timestampNow () ), $bits [0] ) );
		// logger(LL_INFO, "Diff: ".$diff. ", numDays($dy_history): ".numDays($dy_history));
		// if($diff <= numDays($dy_history)) {
		// logger(LL_INFO, "Diff: ".$diff. ", numDays($dy_history): ".numDays($dy_history).", ADDED API CALL");
		// } else {
		// logger(LL_INFO, "Diff: ".$diff. ", numDays($dy_history): ".numDays($dy_history).", SKIPPED API CALL");
		// }
		if (! isset ( $data_points [$id] ) || ($force && ($diff <= numDays ( max ( $dy_history, $dy_forecast ) )))) {
			if (($force && ($ts_yr == $yr))) {
				// logger ( LL_INFO, "Forced refresh for '$id'" );
			} else {
				// logger ( LL_INFO, "Need data for '$id'" );
			}
			$refresh [] = $id;
		} else {
			$ret [$id] = $data_points [$id];
			// logger ( LL_INFO, "Got data for '$id'" );
		}
	}

	if ($fill) {
		logger ( LL_INFO, "getData(): need " . count ( $refresh ) . " website calls" );
		foreach ( $refresh as $id ) {
			$bits = explode ( "|", $id );
			$ts = timestampFormat ( $bits [0], "Y-m-d\TH:i:s" );
			$ts_day = timestampFormat ( $bits [0], "d" );
			$ts_mon = timestampFormat ( $bits [0], "m" );
			$ts_yr = timestampFormat ( $bits [0], "Y" );

			$call = "https://api.darksky.net/forecast/" . $darksky_key . "/" . $lat . "," . $lng . "," . $ts . "?units=si&exclude=currently,minutely,hourly,alerts";
			logger ( LL_DEBUG, "Calling API: " . $call );
			$data = file_get_contents ( $call );

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
		}
	}

	return processData ( $day, $mon, $yr, $ret );
	// logger ( LL_INFO, "Data:\n" . ob_print_r ( $ret ) );
}

?>