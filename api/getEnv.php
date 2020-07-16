<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");
$ret = startJsonRespose ();
global $sensors;
global $triggers;
global $show_empty;
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

// Extract and process the system demands
$env->demand = envExtract ( "DEMAND", $dbenv );
if (! $control_temperature) {
	unset ( $env->demand->temperature );
}
if (! $control_humidity) {
	unset ( $env->demand->humidity );
}
$env->demand->light = str_replace ( "'", "", $env->demand->light );
$env->demand->name = $sensors [6]->name;
$env->demand->label = $sensors [6]->label;
$env->demand->colour = $sensors [6]->colour;

// $env->demand->light="sun";
unset ( $env->demand->alarm );

$env->pi = envExtract ( "PI", $dbenv );
$env->pi->name = "PI";
$env->pi->label = $loc; // gethostname();//"CPU";
$env->pi->colour = "#600";

// Extract and process sensor information
$env->sensors = [ ];
foreach ( $sensors as $s ) {
	if ($s->name != "DEMAND" && $s->name != "PI" && ! inArrayByName ( $s->name, $env->sensors )) {
		// echo "Processing sensor '".$s->name."'\n";
		// print_r ( $s );
		if ($show_empty || $s->type != "EMPTY") {
			$sensor = envExtract ( $s->name, $dbenv );
			$sensor->name = $s->name;
			$sensor->type = $s->type;
			$sensor->label = $s->label;
			$sensor->colour = ($s->colour);
			// print_r ( $sensor );
			$env->sensors [] = $sensor;
		}
	}
}
// Calculate the sensor state
foreach ( $env->sensors as $s ) {
	if ($s->type == strtoupper ( "EMPTY" )) {
		$s->state = "disabled";
	} else if (strtoupper ( $s->alarm ) == "YES") {
		$s->state = "alarm";
	} else {
		$s->state = "normal";
	}
}

// Extract and process trigger details
$env->triggers = [ ];
foreach ( $triggers as $t ) {
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
	if ($t->type == "EMPTY") {
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
