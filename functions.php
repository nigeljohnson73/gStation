<?php
ini_set ( 'max_execution_time', 300 );
ini_set ( 'memory_limit', '128M' );
ini_set ( 'post_max_size', '32M' );
ini_set ( 'upload_max_filesize', '16M' );
error_reporting ( E_ALL );
ini_set ( 'display_errors', 'on' );

// All calcuations are done in UTC
date_default_timezone_set ( "UTC" );

function getHost() {
	$retvar = 0;
	$output = "";
	$cmd = "hostname 2>/dev/null";
	exec ( $cmd, $output, $retvar );
	return $output [0];
}

function getPostArgs($args, $defs = null) {
	if (! is_array ( $args )) {
		$args = array (
				$args
		);
	}

	if ($defs && ! is_array ( $defs )) {
		$defs = array (
				$defs
		);
	}

	$ret = [ ];
	foreach ( $args as $k => $a ) {
		if (isset ( $_POST [$a] )) {
			$ret [$a] = $_POST [$a];
		} else if (isset ( $defs [$k] )) {
			$ret [$a] = $defs [$k];
		}
	}

	if (count ( $ret ) == 0) {
		return null;
	}
	if (count ( $ret ) == 1) {
		return $ret [$args [0]];
	}
	return $args;
}

function hex2rgb($hex) {
	(strlen ( $hex ) === 4) ? list ( $r, $g, $b ) = sscanf ( '#' . implode ( '', array_map ( 'str_repeat', str_split ( str_replace ( '#', '', $hex ) ), [ 
			2,
			2,
			2
	] ) ), "#%02x%02x%02x" ) : list ( $r, $g, $b ) = sscanf ( $hex, "#%2x%2x%2x" );
	return ( object ) [ 
			"r" => $r,
			"g" => $g,
			"b" => $b
	];
}

function inArrayByName($name, $arr) {
	foreach ( $arr as $a ) {
		if ($a->name == $name) {
			return true;
		}
	}
	return false;
}

function cleanFile($filename, $key) {
	if (! file_exists ( $filename )) {
		echo "File '" . $filename . "' cannot be accessed\n";
		return false;
	} else {
		$quiet = false;
		$output = "";

		$lines = file ( $filename );
		foreach ( $lines as $line ) {
			if (strpos ( $line, $key ) === 0) {
				$quiet = ! $quiet;
			} else {
				if (! $quiet) {
					$output .= $line;
				}
			}
		}

		// echo $output;
		$fn = "/tmp/cleansedata." . time () . ".txt";
		file_put_contents ( $fn, $output );

		$cmd = "sudo rm -f " . $filename . ".orig";
		// echo "Removing .orig file: $cmd\n";
		exec ( $cmd );

		$cmd = "sudo cp " . $filename . " " . $filename . ".orig";
		echo "Backing up original file to '" . $filename . ".orig'\n";
		exec ( $cmd );

		$cmd = "sudo cp " . $fn . " " . $filename;
		// echo "Overwriting original file: $cmd\n";
		exec ( $cmd );

		// echo "Removing temp file\n";
		@unlink ( $fn );
	}

	return true;
}

function installFile($src, $filename) {
	if (! file_exists ( $filename )) {
		echo "File '" . $filename . "' cannot be accessed\n";
		return false;
	} else {
		setupGpio ( true );
		global $led_pin;
		$repl = array ();
		$repl ["[[LED_PIN]]"] = $led_pin;

		$c = file_get_contents ( $src );
		foreach ( $repl as $k => $v ) {
			$c = str_replace ( $k, $v, $c );
		}

		// echo $c."\n";

		$fn = "/tmp/cleansedata." . time () . ".txt";
		file_put_contents ( $fn, $c );
		$cmd = "sudo rm -f " . $filename . ".orig";
		echo "Backing up original file to '" . $filename . ".orig'\n";
		exec ( $cmd );

		$cmd = "sudo cp " . $filename . " " . $filename . ".orig";
		// echo "Copying current file to .orig file: $cmd\n";
		exec ( $cmd );

		$cmd = "sudo cat " . $fn . " | sudo tee -a " . $filename;
		// echo "Adding update: $cmd\n";
		exec ( $cmd );

		// echo "Removing temp file\n";
		@unlink ( $fn );
	}

	return true;
}

function getAppId() {
	$app_id = "TBD";
	return $app_id;
}

function getAppVersion() {
	$app_version = "v0.1a";
	return $app_version;
}

function newestFile($p = ".") {
	$mtime = 0;
	$mfile = "";

	$files = directoryListing ( $p );
	foreach ( $files as $file ) {
		$fn = str_replace ( $p . "/", "", $file );
		$dot = strpos ( $fn, "." );
		if ($dot !== 0) {
			// echo "file: '".$file."' - ".date("Y/m/d H:i:s", $mt)."\n";
			$mt = filemtime ( $file );
			if (is_dir ( $file )) {
				$n = newestFile ( $file );
				if ($n [0] > $mtime) {
					$mtime = $n [0];
					$mfile = $n [1];
				}
			} else if ($mt > $mtime) {
				$mtime = $mt;
				$mfile = $file;
			}
		}
	}

	// echo "newest file in '".$p."': '".$mfile."' - ".date("Y/m/d H:i:s", $mtime)."\n";
	return array (
			$mtime,
			$mfile,
			date ( "Y/m/d H:i:s", $mtime )
	);
}

function runCmd($cmd) {
	// echo "runCmd(): '" . $cmd . ".\n";
	$retval = "";
	ob_start ();
	$last_line = @system ( $cmd, $retval );
	ob_end_clean ();
	return $last_line;
}

function bitCompare($str, $in, $now, $prev) {
	// $bp = 24; // bit padding
	$has_str = "YES";
	$now_str = "NOW";
	$no_str = "NEVER";
	$ret = $no_str;

	// $hi = str_pad ( decbin ( $in ), $bp, "0", STR_PAD_LEFT );
	// $hn = str_pad ( decbin ( $now ), $bp, "0", STR_PAD_LEFT );
	// $hp = str_pad ( decbin ( $prev ), $bp, "0", STR_PAD_LEFT );

	// echo $str . ": ";
	if ($in & $now) {
		$ret = $now_str;
	} elseif ($in & $prev) {
		$ret = $has_str;
	}
	// echo $ret;
	// echo "\n";

	// echo "In (in): " . $hi . " (" . dechex ( $in ) . ")\n";
	// echo "Now (hn): " . $hn . " (" . (($in & $now) ? ("set") : ("--")) . ")\n";
	// echo "Prev(hp): " . $hp . " (" . (($in & $prev) ? ("set") : ("--")) . ")\n";
	// echo "----\n";

	return $ret;
}

function GUID() {
	if (function_exists ( 'com_create_guid' ) === true) {
		return trim ( com_create_guid (), '{}' );
	}

	return sprintf ( '%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand ( 0, 65535 ), mt_rand ( 0, 65535 ), mt_rand ( 0, 65535 ), mt_rand ( 16384, 20479 ), mt_rand ( 32768, 49151 ), mt_rand ( 0, 65535 ), mt_rand ( 0, 65535 ), mt_rand ( 0, 65535 ) );
}

// decimates up to the limit if the values are within the delta tolerance
function deltaDecimateArray($arr, $delta, $same_lim) {
	$debug = false;
	if ($debug) {
		echo "Start count: " . count ( $arr ) . "<br>";
	}
	$kk = null;
	$vv = null;
	$ret = array ();
	$same = 0;
	foreach ( $arr as $k => $v ) {
		if ($kk == null || abs ( $vv - $v ) >= $delta || $same > $same_lim) {
			$kk = $k;
			$vv = $v;
			$ret [$k] = $v;
			$same = 0;
		} else {
			$same ++;
		}
	}
	if ($debug) {
		echo "Ret count: " . count ( $ret ) . "<br>";
	}
	return $ret;
}

// removes valused from the array and averages the repalcements (keys are the first value)
function decimateArray($arr, $n = 2) {
	$i = 0;
	$k = null;
	$v = array ();

	$ret = array ();
	foreach ( $arr as $ak => $av ) {
		if ($i == $n) {
			// echo "process data for $k: $i, $n\n";
			// echo "Process data: i: $i, k: $k\n";
			$ret [$k] = array_sum ( $v ) / count ( $v );
			$i = 0;
			$k = null;
			$v = array ();
		}
		$i += 1;

		if ($k === null) {
			// echo "Stash key: i: $i, ak: $ak\n";
			$k = $ak;
		}

		// echo "Adding value '$av'\n";
		$v [] = $av;
	}
	if (count ( $v ) > 0) {
		// echo "processing left over\n";
		$ret [$k] = array_sum ( $v ) / count ( $v );
	}
	return $ret;
}

function smoothArray($arr, $n = 1, $l = 1) {
	for($i = 0; $i < $l; $i ++) {
		$arr = _smoothArray ( $arr, $n );
	}
	return $arr;
}

function _smoothArray($arr, $n = 1) {
	$keys = array_keys ( $arr );
	$ret = array ();
	foreach ( $keys as $i => $key ) {
		// echo "Begin key '".$key."'\n";
		$v = 0;
		// $comma = "";
		// echo " gathering: ";
		for($j = - $n; $j <= $n; $j ++) {
			$k = (($i + $j) < 0) ? (($i + $j) + count ( $arr )) : (($i + $j) >= count ( $arr ) ? (($i + $j) - count ( $arr )) : (($i + $j)));
			// echo $comma.$keys[$k];//." (".$j.", ".$k.")";
			$v += $arr [$keys [$k]];
			// $comma = ", ";
		}
		// echo "\n";
		$ret [$key] = $v / (2 * $n + 1);
	}
	return $ret;
}

// Flattens an aray of objects and averages any repeated fields
function averageObjectArray($arr) {
	// A place for all the field data and the values
	$store = array ();

	// Iterate through each object in the array
	foreach ( $arr as $i ) {
		$keys = array_keys ( ( array ) $i );
		// iterate through each field in the object
		foreach ( $keys as $k ) {
			$val = $i->$k;
			// If the field has a value (not a null) store it
			if ($val != null) {
				// Create the storage if it doesn't exist yet
				if (! isset ( $store [$k] )) {
					$store [$k] = array ();
				}
				$store [$k] [] = $val;
			}
		}
	}

	// start as an array so I can sort the keys later
	$ret = array ();
	// Now iterate through each field that was collected
	foreach ( $store as $k => $vals ) {
		// calculate the average of those that exist
		$v = array_sum ( $vals ) / count ( $vals );
		$ret [$k] = $v;
	}

	// Now sort the array based on key names
	ksort ( $ret );

	// return a new object from the array
	return ( object ) $ret;
}

// returns the first field from the object
function firstOf($obj, $keys) {
	if (! is_array ( $keys )) {
		$keys = array (
				$keys
		);
	}
	foreach ( $keys as $k ) {
		if (isset ( $obj->$k )) {
			return $obj->$k;
		}
	}
	// echo " Failed to find '".implode("', '", $keys)."'\n";
	// echo "".ob_print_r($obj)."\n";
	return null;
}

function randomQuery() {
	mt_srand ( time () );
	return mt_rand ();
}

// Returned a value between 0 and 1 as a percenteage of where $v is in the range $min -> $max
function scaleVal($v, $min, $max) {
	if (abs ( $max - $min ) < 0.01) {
		return 0;
	}
	return ($v - $min) / ($max - $min);
}

function latToDms($decimal) {
	$d = 0;
	$m = 0;
	$s = 0;
	$dir = "X";

	decimalToDmsRaw ( $decimal, $d, $m, $s, $dir, true );
	return dmsFormat ( $d, $m, $s, $dir );
}

function lngToDms($decimal) {
	$d = 0;
	$m = 0;
	$s = 0;
	$dir = "X";

	decimalToDmsRaw ( $decimal, $d, $m, $s, $dir, false );
	return dmsFormat ( $d, $m, $s, $dir );
}

function dmsFormat($d, $m, $s, $dir) {
	return "" . $d . "° " . $m . "' " . $s . "\" " . $dir;
}

function decimalToDmsRaw($decimal, &$degrees, &$minutes, &$seconds, &$direction, $latitude = true) {
	// set default values for variables passed by reference
	$degrees = 0;
	$minutes = 0;
	$seconds = 0;
	$direction = 'X';
	// decimal must be integer or float no larger than 180;
	// type must be Boolean
	if (! is_numeric ( $decimal ) || abs ( $decimal ) > 180 || ! is_bool ( $latitude )) {
		return false;
	}

	// inputs OK, proceed
	// type is latitude when true, longitude when false

	// set direction; north assumed
	if ($latitude && $decimal < 0) {
		$direction = 'S';
	} elseif (! $latitude && $decimal < 0) {
		$direction = 'W';
	} elseif (! $latitude) {
		$direction = 'E';
	} else {
		$direction = 'N';
	}

	// get absolute value of decimal
	$d = abs ( $decimal );

	// get degrees
	$degrees = floor ( $d );

	// get seconds
	$seconds = ($d - $degrees) * 3600;

	// get minutes
	$minutes = floor ( $seconds / 60 );

	// reset seconds
	$seconds = floor ( $seconds - ($minutes * 60) );
}

function numDays($d) {
	return $d * 24 * 60 * 60;
}

function timestamp($day, $mon, $year, $hour = 0, $minute = 0, $second = 0) {
	$day = str_pad ( (( int ) $day) + 0, 2, "0", STR_PAD_LEFT );
	$mon = str_pad ( (( int ) $mon) + 0, 2, "0", STR_PAD_LEFT );
	$hour = str_pad ( (( int ) $hour) + 0, 2, "0", STR_PAD_LEFT );
	$minute = str_pad ( (( int ) $minute) + 0, 2, "0", STR_PAD_LEFT );
	$second = str_pad ( (( int ) $second) + 0, 2, "0", STR_PAD_LEFT );
	return $year . $mon . $day . $hour . $minute . $second;
}

function timestampNow() {
	global $date_overide;
	if (isset ( $date_overide )) {
		return $date_overide;
	}
	return adodb_date ( "YmdHis" );
}

function time2Timestamp($tm) {
	return adodb_date ( "YmdHis", $tm );
}

function timestamp2Time($ts) {
	// echo "timestamp2Time($ts): Got: '$ts'\n";
	$ts = str_replace ( " ", "", $ts );
	$ts = str_replace ( ":", "", $ts );
	$ts = str_replace ( "/", "", $ts );
	$ts = str_replace ( "-", "", $ts );
	$ts = str_replace ( ".", "", $ts );
	$ts = preg_replace ( "/[A-Z]*/", "", strtoupper ( $ts ) );
	$ts .= "000000"; // just in case I only suply a date

	// echo "timestamp2Time($ts): New ts: '$ts'\n";

	$year = substr ( $ts, 0, 4 );
	$month = substr ( $ts, 4, 2 );
	$day = substr ( $ts, 6, 2 );
	$hour = substr ( $ts, 8, 2 );
	$minute = substr ( $ts, 10, 2 );
	$second = substr ( $ts, 12, 2 );
	// echo "adodb_mktime($hour, $minute, $second, $month, $day, $year)\n";
	return adodb_mktime ( $hour, $minute, $second, $month, $day, $year );
}

function timestampDay($ts) {
	return timestampFormat ( $ts, "Ymd" ) . "000000";
}

function periodFormat($secs, $short = false) {
	$h = $secs / 3600;
	$hflag = ($short) ? ("h") : (" hour");
	$mflag = ($short) ? ("m") : (" min");
	// this takes a duration in seconds and outputs in hours and
	// minutes to the nearest minute - is use is for kind of "about" times.
	$estr = "";
	$hours = floor ( $h );
	if ($hours) {
		$hours = number_format ( $hours, 0 );
		$estr .= $hours . $hflag;
		if (! $short) {
			$pl = "s";
			if ($hours == 1) {
				$pl = "";
			}
			$estr .= $pl;
		}
	}

	$mins = $h - $hours;
	$mins *= 60;
	if ($mins) {
		$mins = ceil ( $mins );
		if (strlen ( $estr )) {
			$estr .= " ";
		}
		$estr .= $mins . $mflag;
		if (! $short) {
			$pl = "s";
			if ($mins == 1) {
				$pl = "";
			}
			$estr .= $pl;
		}
	}
	return $estr;
}

function durationFormat($secs, $use_nearest_sec = false) {
	if ($use_nearest_sec) {
		$secs = nearest ( $secs, 1 );
	}
	$sec_min = 60;
	$sec_hour = $sec_min * 60;
	$sec_day = $sec_hour * 24;

	$days = floor ( $secs / $sec_day );
	$secs -= $days * $sec_day;

	$hours = floor ( $secs / $sec_hour );
	$secs -= $hours * $sec_hour;

	$mins = floor ( $secs / $sec_min );
	$secs -= $mins * $sec_min;

	$ret = "";
	if ($days > 0) {
		$ret .= " " . $days . "d";
	}
	if ($hours > 0) {
		$ret .= " " . $hours . "h";
	}
	if ($mins > 0) {
		$ret .= " " . $mins . "m";
	}
	if ($use_nearest_sec) {
		$ret .= " " . $secs . "s";
	} else {
		$ret .= " " . number_format ( $secs, 3 ) . "s";
	}

	return trim ( $ret );
}

function durationStamp($secs, $use_us = false) {
	// echo "durationStamp(): started with $secs seconds\n";
	$sec_min = 60;
	$sec_hour = $sec_min * 60;
	$sec_day = $sec_hour * 24;

	$days = floor ( $secs / $sec_day );
	$secs -= $days * $sec_day;
	// echo "durationStamp(): days: $days\n";

	$hours = floor ( $secs / $sec_hour );
	$secs -= $hours * $sec_hour;
	// echo "durationStamp(): hours: $hours\n";

	$mins = floor ( $secs / $sec_min );
	$secs -= $mins * $sec_min;
	// echo "durationStamp(): mins: $mins\n";

	$ms = ($secs - (floor ( $secs ))) * 1000;
	$secs = floor ( $secs );

	$us = round ( ($ms - (floor ( $ms ))) * 1000 );
	$ms = floor ( $ms );

	// echo "durationStamp(): secs: $secs\n";
	// echo "durationStamp(): ms: $ms\n";
	// echo "durationStamp(): us: $us\n";

	$ret = "";
	if ($days > 0) {
		$ret .= (strlen ( $ret ) ? (" ") : ("")) . $days . "d";
	}
	if (strlen ( $ret ) || $hours > 0) {
		$ret .= (strlen ( $ret ) ? (" ") : ("")) . $hours . "h";
	}
	if (strlen ( $ret ) || $mins > 0) {
		$ret .= (strlen ( $ret ) ? (" ") : ("")) . $mins . "m";
	}
	if (strlen ( $ret ) || $secs > 0) {
		$ret .= (strlen ( $ret ) ? (" ") : ("")) . $secs . "s";
	}
	if (! $use_us || strlen ( $ret ) || $ms > 0) {
		$ret .= (strlen ( $ret ) ? (" ") : ("")) . $ms . "ms";
	}
	if ($use_us) {
		$ret .= (strlen ( $ret ) ? (" ") : ("")) . $us . "us";
	}

	return trim ( $ret );
}

function timestampFormat($ts, $format = null) {
	if ($format == null) {
		$format = "d/m/Y H:i:s";
	}
	$tm = timestamp2Time ( $ts );
	return adodb_date ( $format, $tm );
}

function timestampAdd($ts, $sec) {
	// default is add seconds
	$tm = timestamp2Time ( $ts );
	return time2Timestamp ( $tm + $sec );
}

function timestampAddDays($ts, $day) {
	return timestampAdd ( $ts, numDays ( $day ) );
}

function timestampDifference($tthen, $tnow) {
	$tn = timestamp2Time ( timestampFormat ( $tnow, "YmdHis" ) );
	$tt = timestamp2Time ( timestampFormat ( $tthen, "YmdHis" ) );
	return $tn - $tt;
}

function timestampDelta($lower, $upper) {
	$seconds_diff = timestampDifference ( $lower, $upper );

	$pre = "";
	$day = 24 * 3600;
	$week = 7 * $day;
	$twenty_weeks = 20 * $week;
	$year = 365 * $day;

	if ($seconds_diff < $week) {
		// $days = round($seconds_diff / $day);
		$days = floor ( $seconds_diff / $day );
		$pl = "s";
		if ($days == 1) {
			$pl = "";
		}
		$d = $pre . $days . " day" . $pl;
	} elseif ($seconds_diff < $twenty_weeks) {
		$yrs = floor ( $seconds_diff / $week );
		$wks = round ( $seconds_diff / $week );
		$bwks = floor ( $seconds_diff / $week );
		if ($bwks != $wks) {
			$pre = "nearly ";
		}
		$pl = "s";
		if ($wks == 1) {
			$pl = "";
		}
		$d = $pre . $wks . " week" . $pl;
	} else {
		$yrs = floor ( $seconds_diff / $year );
		$seconds_diff -= $yrs * $year;
		$mnths = round ( $seconds_diff / ($year / 12) );
		$bmnths = floor ( $seconds_diff / ($year / 12) );
		if ($bmnths != $mnths) {
			$pre = "nearly ";
		}
		$y = "";
		if ($yrs > 0) {
			$pl = "s";
			if ($yrs == 1) {
				$pl = "";
			}
			$y = $yrs . " year" . $pl;
		}
		$m = "";
		if ($mnths > 0) {
			$pl = "s";
			if ($mnths == 1) {
				$pl = "";
			}
			$m = $mnths . " month" . $pl;
		}
		$sep = " and ";
		if ($mnths == 0 || $yrs == 0) {
			$sep = "";
		}
		$d = $pre . $y . $sep . $m;
	}
	return trim ( $d );
}

function obfuscateString($email) {
	$ret = '';
	for($i = 0; $i < strlen ( $email ); ++ $i) {
		$r = rand ( 0, 1 );
		if ($r) {
			$ret .= '&#x' . sprintf ( "%X", ord ( $email {$i} ) ) . ';';
		} else {
			$ret .= '&#' . ord ( $email {$i} ) . ';';
		}
	}
	return $ret;
}

function obfuscateAllEmailAddresses($text) {
	$text = ' ' . $text . ' ';
	$text = preg_replace ( "#(([A-Za-z0-9\-_\.]+?)@([^\s,{}\(\)\[\]]+\.[^\s.,{}\(\)\[\]]+))#iesU", "obfuscateString(\"$1\")", $text );

	return substr ( $text, 1, strlen ( $text ) - 2 );
}

function nearest($number, $nearest, $updown = null) {
	$bits = floor ( $number / $nearest );
	$result = $bits * $nearest;

	if ($result != $number) {
		switch ($updown) {
			case 'down' :
				break;

			case 'up' :
				$result += $nearest;
				break;
			default :
				$pcnt = ($number - $nearest) / $nearest;
				if ($pcnt >= 0.5) {
					$result += $nearest;
				}
				break;
		}
	}
	return $result;
}

function tfn($x) {
	if ($x === null) {
		return "(null)";
	}
	if ($x === true) {
		return "(true)";
	}
	if ($x === false) {
		return "(false)";
	}
	if (is_string ( $x )) {
		if ($x == "") {
			return "(empty string)";
		}
		return "\"$x\"";
	}
	return $x;
}

function latLongRadiusMinMax($latitude, $longitude, $km) {
	// http://blog.fedecarg.com/2009/02/08/geo-proximity-search-with-php-python-and-sql/
	$radius = $km / 1.609344; // has to be in standard miles
	$lng_min = $longitude - $radius / abs ( cos ( deg2rad ( $latitude ) ) * 69 );
	$lng_max = $longitude + $radius / abs ( cos ( deg2rad ( $latitude ) ) * 69 );
	$lat_min = $latitude - ($radius / 69);
	$lat_max = $latitude + ($radius / 69);

	$arr = array ();
	$arr ["lat_min"] = $lat_min;
	$arr ["lat_max"] = $lat_max;
	$arr ["long_min"] = $lng_min;
	$arr ["long_max"] = $lng_max;

	return $arr;
}

function latLongDistance($lat1, $lon1, $lat2, $lon2) {
	$theta = $lon1 - $lon2;
	$dist = sin ( deg2rad ( $lat1 ) ) * sin ( deg2rad ( $lat2 ) ) + cos ( deg2rad ( $lat1 ) ) * cos ( deg2rad ( $lat2 ) ) * cos ( deg2rad ( $theta ) );
	$dist = acos ( $dist );
	$dist = rad2deg ( $dist );
	$miles = $dist * 60 * 1.1515;
	return ($miles * 1.609344); // back in KM.
}

function getLocalJavascriptPayload() {
	$str = "";

	if (strtolower ( @ $_SERVER ['SERVER_NAME'] ) == "localhost") {
		$str = "\tlogging_enabled = true;\n" . $str;
	} else {
	}

	if (isset ( $_GET ["packed"] ) || strtolower ( @ $_SERVER ['SERVER_NAME'] ) != "localhost") {
		$packer = new JavaScriptPacker ( $str, 95, false, false );
		$str = utf8_encode ( $packer->pack () );
	}

	return $str;
}

function localJavascriptPayload() {
	echo getLocalJavascriptPayload ();
}

function javascriptPayload() {
	// if (isset ( $_GET ["packed"] ) || strtolower ( @ $_SERVER ['SERVER_NAME'] ) != "localhost") {
	if (isset ( $_GET ["packed"] )) { // TODO: Fix this!!!
		echo "packed";
	} elseif (isset ( $_GET ["nocr"] )) {
		echo "nocr";
	} else {
		echo "nopack";
	}
}

function stylesheetPayload() {
	// if (isset ( $_GET ["packed"] ) || strtolower ( @ $_SERVER ['SERVER_NAME'] ) != "localhost") {
	if (isset ( $_GET ["packed"] )) { // TODO: Fix this!!!
		echo "packed";
	} elseif (isset ( $_GET ["nocr"] )) {
		echo "nocr";
	} else {
		echo "nopack";
	}
}

function ob_print_r($what) {
	ob_start ();
	print_r ( $what );
	$c = ob_get_contents ();
	ob_end_clean ();
	return $c;
}

function directoryListing($dirname, $extensoes = null) {
	if ($extensoes === null) {
		$extensoes = array (
				".*"
		);
	} else if (! is_array ( $extensoes )) {
		$extensoes = explode ( ",", $extensoes );
	}

	$files = array ();
	$dir = @ opendir ( $dirname );
	while ( $dir && false !== ($file = readdir ( $dir )) ) {
		// $matches = array ();
		if ($file != "." && $file != ".." && $file != ".svn") {
			for($i = 0; $i < count ( $extensoes ); $i ++) {
				if ($extensoes [$i] [0] == "*") {
					$extensoes [$i] = "." . $extensoes [$i];
				}
				if (preg_match ( "/" . $extensoes [$i] . "$/i", $file )) {
					$files [] = $dirname . "/" . $file;
				}
				// }
			}
		}
	}

	@ closedir ( $dirname );
	sort ( $files );
	return $files;
}

function includeDirectory($d, $ext = "php") {
	$ret = array ();
	$files = directoryListing ( $d, $ext );
	foreach ( $files as $file ) {
		// echo "loading $file<br />";
		if (! preg_match ( '/index.php$/', $file )) {
			$ret [] = $file;
		}
	}
	return $ret;
}

function compressJavascript($force = false, $debug = true) {
	// return false;
	// if (@ strtolower ( $_SERVER ['SERVER_NAME'] ) == "localhost") {
	if (1) { // TODO: Fix this!!!
	         // Only perform this action if we are on the development server
	         // Production payload files will be updloaded from the dev sever

		// Set up the target filenames for the raw and packed versions
		$targetfn = dirname ( __FILE__ ) . "/js/app.nopack.js";
		$ptargetfn = dirname ( __FILE__ ) . "/js/app.packed.js";
		$ctargetfn = dirname ( __FILE__ ) . "/js/app.nocr.js";

		// Get the current mod time in case any sub files are newer
		$tt = @ filemtime ( $targetfn );
		// $tt = 0;

		// An indicator to determine whenther the file needs updating
		$save = $force;

		if ($debug) {
			// echo "<!-- compression check: $targetfn -->\n";
			// echo "<!-- mtime: $tt -->\n";
		}

		// Get a list of javascript files for the bundle
		$files = array ();
		$files = array_merge ( $files, directoryListing ( dirname ( __FILE__ ) . "/js/raw", ".js" ) );
		$files = array_merge ( $files, directoryListing ( dirname ( __FILE__ ) . "/js/raw/_directives", ".js" ) );
		$files = array_merge ( $files, directoryListing ( dirname ( __FILE__ ) . "/js/raw/_services", ".js" ) );
		$files = array_merge ( $files, directoryListing ( dirname ( __FILE__ ) . "/js/raw/_controllers", ".js" ) );
		$files = array_merge ( $files, directoryListing ( dirname ( __FILE__ ) . "/js/raw/cam", ".js" ) );

		$js = "";
		foreach ( $files as $file ) {
			// Add the sub file to the main file
			$js .= "\n" . trim ( file_get_contents ( $file ) );

			// Check if the sub-file is newer and make for saving
			if (filemtime ( $file ) > $tt) {
				if ($debug) {
					echo "<!-- '$file' - out of date -->\n";
				}
				$save = true;
			} else {
				if ($debug) {
					// echo "<!-- '$file' - ok -->\n";
				}
			}
		}

		// If we should save the file, and there is something to save
		if ($save && strlen ( $js )) {
			if ($debug)
				echo "<!-- Saving $targetfn (" . strlen ( $js ) . " bytes) -->\n";

			// Write the uncompressed version for testing etc
			file_put_contents ( $targetfn, $js );

			// Pack and write the compressed version
			$packer = new JavaScriptPacker ( $js, 95, false, false );
			$pjs = utf8_encode ( $packer->pack () );
			if ($debug)
				echo "<!-- Saving $ptargetfn (" . strlen ( $pjs ) . " bytes) -->\n";
			file_put_contents ( $ptargetfn, $pjs );

			$js = preg_replace ( '!/\*.*?\*/!s', '', $js ); // Remove block comments
			$js = preg_replace ( '#[^:\'"]//[^\,].*#', '', $js ); // Remove non url single line comments - the comma also allows for javascript regex
			$js = preg_replace ( '#^//.*#', '', $js ); // remove single line comments that start a line
			$js = preg_replace ( '/\n/', "", $js ); // remove carriage returns
			$js = preg_replace ( '/\s+/', " ", $js ); // remove any weird white space
			if ($debug)
				echo "<!-- Saving $ctargetfn (" . strlen ( $js ) . " bytes) -->\n";
			file_put_contents ( $ctargetfn, $js );
		}
	}
}

function compressStylesheet($force = false, $debug = true) {
	// echo "<!-- ".strtolower ( $_SERVER ['SERVER_NAME'])."-->\n";
	// return false;
	// if (@ strtolower ( $_SERVER ['SERVER_NAME'] ) == "localhost") {
	if (1) { // TODO: Fix this!!!
	         // Only perform this action if we are on the development server
	         // Production payload files will be updloaded from the dev sever

		// Set up the target filenames for the raw and packed versions
		$targetfn = dirname ( __FILE__ ) . "/css/app.nopack.css";
		$ptargetfn = dirname ( __FILE__ ) . "/css/app.packed.css";
		$ctargetfn = dirname ( __FILE__ ) . "/css/app.nocr.css";

		// Get the current mod time in case any sub files are newer
		$tt = @ filemtime ( $targetfn );
		// $tt = 0;

		// An indicator to determine whenther the file needs updating
		$save = $force;

		if ($debug) {
			// echo "<!-- compression check: $targetfn -->\n";
			// echo "<!-- mtime: $tt -->\n";
		}

		// Get a list of stylesheet files for the bundle
		$files = directoryListing ( dirname ( __FILE__ ) . "/css/raw", ".css" );

		$css = "";
		foreach ( $files as $file ) {
			// Add the sub file to the main file
			// echo "<!-- Adding CSS '$file' -->\n";
			$css .= "\n" . trim ( file_get_contents ( $file ) );

			// Check if the sub-file is newer and make for saving
			if (filemtime ( $file ) > $tt) {
				if ($debug) {
					echo "<!-- '$file' - out of date -->\n";
				}
				$save = true;
			} else {
				if ($debug) {
					// echo "<!-- '$file' - ok -->\n";
				}
			}
		}

		// If we should save the file, and there is something to save
		if ($save && strlen ( $css )) {
			if ($debug)
				echo "<!-- Saving $targetfn (" . strlen ( $css ) . " bytes) -->\n";

			// Write the uncompressed version for testing etc
			file_put_contents ( $targetfn, $css );

			// Pack and write the compressed version
			$pcss = CssMin::minify ( $css );
			if ($debug)
				echo "<!-- Saving $ptargetfn (" . strlen ( $pcss ) . " bytes) -->\n";
			file_put_contents ( $ptargetfn, $pcss );

			$css = preg_replace ( '!/\*.*?\*/!s', '', $css ); // Remove block comments
			$css = preg_replace ( '#[^:\'"]//.*#', '', $css ); // Remove non url single line comments
			$css = preg_replace ( '#^//.*#', '', $css ); // remove single line comments that start a line
			$css = preg_replace ( '/\n/', "", $css ); // remove carriage returns
			$css = preg_replace ( '/\s+/', " ", $css ); // remove any weird white space
			if ($debug)
				echo "<!-- Saving $ctargetfn (" . strlen ( $css ) . " bytes) -->\n";
			file_put_contents ( $ctargetfn, $css );
		}
	}
}

function getBackTrace() {
	$bt = debug_backtrace ();
	unset ( $bt [0] );
	// var_dump($bt);
	$ret = array ();
	$k = 0;
	foreach ( $bt as $t ) {
		$str = "";
		$str .= "#" . $k . " " . $t ["file"] . "(" . $t ["line"] . "): " . $t ["function"] . "()";
		$ret [] = $str;
		$k ++;
	}

	return $ret;
}

/**
 * **********************************************************
 */
// echo "<!--\n";
$inc = array ();
$inc [] = dirname ( __FILE__ ) . "/config.php";
$inc [] = dirname ( __FILE__ ) . "/config_override.php";
$inc = array_merge ( $inc, includeDirectory ( dirname ( __FILE__ ) . "/include" ) );
foreach ( $inc as $file ) {
	if (file_exists ( $file ) && ! is_dir ( $file )) {
		// echo "loading $file\n";
		include_once ($file);
	}
}
// echo "-->\n";

compressStylesheet ();
compressJavascript ();

?>
