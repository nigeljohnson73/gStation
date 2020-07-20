<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $argv;

if ("" . @$argv [1] == "") {
	echo $argv [0] . " <message>\n";
	exit ( 0 );
}

sendPushover_RAW ( $argv [1] );
?>
