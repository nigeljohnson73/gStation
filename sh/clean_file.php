<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

global $argv;
if ("" . @$argv [2] == "") {
	echo $argv [0] . " <file> <key>\n";
	exit ( 0 );
}

cleanFile ( $argv [1], $argv [2] );

?>
