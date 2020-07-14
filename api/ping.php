<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");
$ret = startJsonRespose ();

if (isset ( $_POST ["sleep"] )) {
	echo "Sleep time supplied: " . $_POST ["sleep"] . "s\n";
	usleep ( $_POST ["sleep"] * 1000000 );
} else {
	echo "No sleep time supplied\n";
}

$ret->args = $_POST;
$ret->timestamp = timestampFormat ( timestampNow (), "Y-m-d\TH:i:s\Z" );

endJsonRespose ( $ret, true );
?>