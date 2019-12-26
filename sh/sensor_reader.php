<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

if ("" . @$argv [1] == "") {
	echo "You need to supply a sensor number on the command line\n";
	exit ( 0 );
}

setupGpio ();
readSensor ( $argv [1] );

?>