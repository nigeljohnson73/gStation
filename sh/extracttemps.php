<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

echo "DELETE FROM temperature_logger;\n";
$ret = $mysql->query ( "SELECT * FROM th_logger WHERE temperature != 999" );
// $ret = $mysql->query ( "SELECT * FROM th_logger WHERE temperature != 999 ORDER BY entered ASC LIMIT 5" );
if ($ret) {
	foreach ( $ret as $row ) {
		echo "REPLACE INTO temperature_logger (entered, temperature, demanded) values ('" . $row ["entered"] . "', " . $row ["temperature"] . ", " . $row ["temperature"] . ");\n";
	}
}
?>