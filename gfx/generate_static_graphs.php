<?php
$quiet = true;
include_once (dirname ( __FILE__ ) . "/../functions.php");

ob_start();
$zone = "ZONE1";
include(dirname(__FILE__)."/graph_measured_humidity.php");
$c = ob_get_contents();
file_put_contents(dirname(__FILE__)."/static_graph_humidity_".$zone.".png", $c);
ob_end_clean();

ob_start();
$zone = "ZONE1";
include(dirname(__FILE__)."/graph_measured_temperature.php");
$c = ob_get_contents();
file_put_contents(dirname(__FILE__)."/static_graph_temperature_".$zone.".png", $c);
ob_end_clean();

?>