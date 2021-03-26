<?php
include_once (dirname ( __FILE__ ) . "/../../functions.php");
global $mysql;
$ret = startJsonRespose ();

if (isset ( $_POST ["data"] )) {
	//logger ( LL_INF, "data supplied: '" . $_POST ["data"] );
	$obj = json_decode ( $_POST ["data"] );
	// logger ( LL_INF, ob_print_r ( $json ) );
	// $hostname = $json ["name"];
	// $ip_addr = $json ["ip"];

	$mysql->query ( "DELETE FROM ports WHERE ip = ?", "s", array (
			$obj->ip
	) );
	foreach ( $obj->port as $port ) {
		$p = new StdClass ();
		$p->id = $port->name;
		// $p->hostname = $obj->name;
		$p->ip = $obj->ip;
		$p->type = $port->type;
		logger ( LL_INF, ob_print_r ( $p ) );
		// logger ( LL_INF, ob_print_r ( array_values ( ( array ) $p ) ) );
		$mysql->query ( "REPLACE INTO ports (id, ip, type, alarm) VALUES(?, ?, ?, 'NO')", "sss", array_values ( ( array ) $p ) );
	}

	// $mysql->query ( "REPLACE INTO model (id, data) VALUES(?, ?)", "ss", array_values ( $values ) );
} else {
	echo "No data supplied\n";
}

$ret->args = $_POST;
$ret->timestamp = timestampFormat ( timestampNow (), "Y-m-d\TH:i:s\Z" );

endJsonRespose ( $ret, true );
?>