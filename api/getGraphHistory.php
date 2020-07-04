<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");
$ret = startJsonRespose ();
//$ret = (object)[];
$ret->history = ( object ) [ ];
$hl = 10 * 60;

global $mysql;

$sql = "(SELECT param, name, event, value FROM sensors WHERE name != 'PI') UNION (SELECT param, 'DEMAND' as name, event, value FROM demands where param != 'LIGHT')";
$res = $mysql->query ( $sql );

echo "Averaging shots: " . $hl . "\n";
$tmp = [ ];
$int = [ ];
if ($res && count ( $res )) {
	foreach ( $res as $r ) {
		$tmp [$r ["param"]] [$r ["name"]] [timestamp2Time ( $r ["event"] ) - (timestamp2Time ( $r ["event"] ) % $hl)] [] = $r ["value"];
	}
	// print_r($tmp);

	// $params = [ ];
	foreach ( $tmp as $param => $zones ) {
		echo "Got " . count ( $zones ) . " zones for '" . $param . "'\n";

		// $data_points = [ ];
		foreach ( $zones as $name => $values ) {
			echo "\t" . $param . ": got " . count ( $values ) . " values for '" . $name . "'\n";
			// $data = [ ];
			foreach ( $values as $tm => $arr ) {
			//	if (strtoupper ( $param ) == "TEMPERATURE") {
					$t = timestampFormat ( time2Timestamp ( $tm ), "Y-m-d\TH:i:s\Z" );
					// echo "\t\t" . $param . "." . $name . ": got " . count ( $arr ) . " values for " . $t . "(" . $tm . ")\n";
					$avg = array_sum ( $arr ) / count ( $arr );
					$int [$param] [$name] [$t] = $avg;
					// $data [] = ( object ) [
					// "t" => $t,
					// "y" => $avg
					// ];
			//	}
				// $data_points [] = ( object ) [
				// "name" => $name,
				// "values" => $data
				// ];
			}
		}
		// $params [] = ( object ) [
		// "name" => $param,
		// "values" => $data_points
		// ];
	}
	$tmp = $int;
	$int = [ ];
	// var_dump($params);

	foreach ( $tmp as $param => $names ) {
		echo "Processing ".$param."\n";
		$zones = [ ];
		foreach ( $names as $name => $values ) {
			echo "Processing ".$param.".".$name."\n";
			$data = [ ];
			foreach ( $values as $ts => $v ) {
				$data [] = ( object ) [ 
						"t" => $ts,
						"y" => $v
				];
			}
			$zones [] = ( object ) [ 
					"name" => $name,
					"data" => $data
			];
		}
		$param = strtolower($param);
		//echo "Adding ".count($zones)." to '".$param."'\n";
		$ret->history->$param = $zones;
		//print_r($ret);
	}
} else {
	echo "Got no data!!\n";
}

endJsonRespose ( $ret, true );
?>
