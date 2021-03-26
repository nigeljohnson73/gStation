<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");
$ret = startJsonRespose ();
// global $sensors;
// global $triggers;
// global $show_empty;
global $loc;
global $control_temperature, $control_humidity;

function envExtract($what, $where, $override = null) {
	$ret = new StdClass ();
	foreach ( $where as $k => $v ) {
		// echo "Checking '$what' against '$k'\n";
		if (strpos ( $k, $what ) !== false) {
			list ( $n, $p ) = explode ( ".", $k );
			$n = $n;
			if ($override) {
				$p = $override;
			} else {
				$p = strtolower ( $p );
			}
			// echo "Processing '$n'.'$p' = '$v'\n";
			$ret->$p = strtolower ( $v );
		}
	}
	// print_r ( $ret );
	return $ret;
}

// $ret->message = "Loaded Environment - still to make it work";
$dbenv = ( array ) json_decode ( getConfig ( "env", new StdClass () ) );
$ret->env_dbg = $dbenv;
if (isset ( $dbenv ["INFO.LASTCHECK"] )) {
	// echo "Last Checked: ".timestampFormat($dbenv["INFO.LASTCHECK"], "Y-m-d\TH:i:s\Z")."\n";
} else {
	echo "ALARM STATUS CHECK IS INVALID!\n";
}

// New returnable object
$env = new StdClass ();

// Extract and process the location details
$env->location = json_decode ( getConfig ( "location", new StdClass () ) );
if (isset ( $env->location->lat )) {
	$env->location->lat_dms = latToDms ( $env->location->lat );
}
if (isset ( $env->location->lon )) {
	$env->location->lon_dms = lngToDms ( $env->location->lon );
}
if (isset ( $env->location->lat ) && isset ( $env->location->lon )) {
	$env->location->maplink = "https://www.google.com/maps/place/" . $env->location->lat . "," . $env->location->lon;
}

// Extract raw info
$env->info = envExtract ( "INFO", $dbenv );
$env->control = new StdClass ();
$env->control->temperature = $control_temperature;
$env->control->humidity = $control_humidity;

// Extract and process the server data block
$env->data = envExtract ( "DATA", $dbenv );
$env->data->tod = str_replace ( "'", "", $env->data->tod );

// Extract and process the system expects
if ($use_expect)
	$env->expect = envExtract ( "EXPECT", $dbenv );
if (! $control_temperature) {
	unset ( $env->expect->temperature );
}
if (! $control_humidity) {
	unset ( $env->expect->humidity );
}
$env->expect->light = str_replace ( "'", "", $env->expect->light );
$env->expect->name = "EXPECT"; // $sensors [6]->name;
$env->expect->label = "EXPECT"; // $sensors [6]->label;
$env->expect->colour = "#fac"; // $sensors [6]->colour;

// $env->expect->light="sun";
unset ( $env->expect->alarm );

$env->pi = envExtract ( "PI", $dbenv );
$env->pi->name = "PI";
$env->pi->label = $loc; // gethostname();//"CPU";
$env->pi->colour = "#090";

// Extract and process sensor information
$env->sensors = [ ];

function getPorts() {
	$ret = new StdClass ();
	$ret->sensors = array ();
	$ret->triggers = array ();
	global $mysql;
	$res = $mysql->query ( "SELECT id, type FROM ports" );
	if (is_array ( $res ) && count ( $res ) > 0) {
		foreach ( $res as $row ) {
			$r = new StdClass ();
			$r->name = $row ["id"];
			$r->label = $r->name;
			$r->type = $row ["type"];
			$r->colour = getColour ( $r->name );
			if ($r->type == "TRIGGER") {
				$ret->triggers [] = $r;
			} else {
				$ret->sensors [] = $r;
			}
		}
	}
	return $ret;
}

$ports = getPorts ();

foreach ( $ports->sensors as $s ) {
	if ($s->name != "EXPECT" && $s->name != "PI" && ! inArrayByName ( $s->name, $env->sensors )) {
		// echo "Processing sensor '".$s->name."'\n";
		// print_r ( $s );
		// if ($show_empty || $s->type != "EMPTY") {
		$sensor = envExtract ( $s->name, $dbenv );
		$sensor->name = $s->name;
		$sensor->type = $s->type;
		$sensor->label = $s->label;
		$sensor->colour = ($s->colour);
		// print_r ( $sensor );
		$env->sensors [] = $sensor;
		// }
	}
}
// Calculate the sensor state
foreach ( $env->sensors as $s ) {
	// if ($s->type == strtoupper ( "EMPTY" )) {
	// $s->state = "disabled";
	// } else
	if (isset ( $s->alarm ) && strtoupper ( $s->alarm ) == "YES") {
		$s->state = "alarm";
	} else {
		$s->state = "normal";
	}
}

// Extract and process trigger details
$env->triggers = [ ];
foreach ( $ports->triggers as $t ) {
	if (! inArrayByName ( $t->name, $env->triggers )) {
		// echo "Processing sensor '".$s->name."'\n";
		// print_r ( $s );
		if ($show_empty || $t->type != "EMPTY") {
			$trigger = envExtract ( $t->name, $dbenv, "state" );
			$trigger->name = $t->name;
			$trigger->type = $t->type;
			$trigger->label = $t->label;
			$trigger->colour = strtolower ( $t->colour );
			// print_r ( $sensor );
			$env->triggers [] = $trigger;
		}
	}
}
// Calculate the trigger state
foreach ( $env->triggers as $t ) {
	if (isset ( $s->alarm ) && strtoupper ( $s->alarm ) == "YES") {
		$t->state = "alarm";
	} else if ($t->type == "EMPTY") {
		$t->state = "disabled";
	} else if ($t->state == 0) {
		$t->state = "off";
	} else {
		$t->state = "on";
	}
}

$env->timestamp = timestampFormat ( timestampNow (), "Y-m-d\TH:i:s\Z" );
$ret->env = $env;

endJsonRespose ( $ret, true );
?>
