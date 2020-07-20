<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $argv;

if ("" . @$argv [1] == "") {
	echo "You need to supply a sensor number on the command line\n";
	exit ( 0 );
}

setupGpio ();
readSensor ( $argv [1] );

?>
