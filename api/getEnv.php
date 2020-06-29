<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");
$ret = startJsonRespose ();

$ret->message = "Loaded Environment - still to make it work";
$ret->env = json_decode(getConfig("env", new StdClass()));

endJsonRespose ( $ret, true );
?>