<?php
include_once("../functions.php");

$fn = getSnapshotFile();
if ($fn) {
	header('Content-Type: image/jpeg');
	readfile($fn);
	exit();
} else {
// render a new image
}
?>
