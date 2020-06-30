<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");
$ret = startJsonRespose ();

$ret->camshot = new StdClass();
$ret->camshot->available = false;

$fn = getSnapshotFile();
if ($fn) {
	$ret->camshot->available = true;
	$im = file_get_contents($fn);
	$imdata = base64_encode($im);
	$ret->camshot->src = "data:image/jpeg;base64,".$imdata;
	$ret->camshot->livestream_url = getSnapshotUrl();
}

endJsonRespose ( $ret, true );
?>