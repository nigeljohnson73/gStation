<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

if ("" . @$argv [2] == "") {
	echo $argv [0] . " <src file> <original file>\n";
	exit ( 0 );
}

installFile ( $argv [1], $argv [2] );

?>
