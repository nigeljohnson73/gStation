<?php
include_once ("functions.php");

$ofn = dirname ( __FILE__ ) . "/sh/start_sensors.sh";
$str = "";
$str .= "#!/bin/sh\n";
$str .= createSensorSetupScript ();

//print_r($sensors);
file_put_contents ( $ofn, $str );

readSensor(1);
?>
