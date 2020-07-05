<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");
$pt = new ProcessTimer ();
$ret = startJsonRespose ();
// $ret = (object)[];
$ret->history = ( object ) [ ];
$hl = 10 * 60;

global $mysql, $show_empty, $sensors;

$activity_prep = 0;
$activity_db = 0;
$activity_proc = 0;

$demand_exclude = [ ];
$sensor_exclude = [ 
		"PI"
];
echo "Averaging duration : " . $hl . "s\n";

// Hide any disabled sensors. This is only for temp and humidtiy history... no triggers or other stuff
if (! $show_empty) {
	foreach ( $sensors as $s ) {
		if ($s->type == "EMPTY" && isset ( $s->label )) {
			echo "Exluding EMPTY sensor '" . $s->label . "' (" . $s->name . ")\n";
			$sensor_exclude [] = $s->name;
		}
	}
}

$swhere = "";
if (count ( $sensor_exclude )) {
	$swhere = " WHERE name ";
	if (count ( $sensor_exclude ) == 1) {
		$swhere .= "!= '" . $sensor_exclude [0] . "'";
	} else {
		$swhere .= "NOT IN ('" . implode ( "', '", $sensor_exclude ) . "')";
	}
}

$dwhere = "";
if (count ( $demand_exclude )) {
	$dwhere = " WHERE param ";
	if (count ( $demand_exclude ) == 1) {
		$dwhere .= "!= '" . $demand_exclude [0] . "'";
	} else {
		$dwhere .= "NOT IN ('" . implode ( "', '", $demand_exclude ) . "')";
	}
}

$sql = "(SELECT param, name, event, value FROM sensors" . $swhere . ") UNION (SELECT param, 'DEMAND' as name, event, value FROM demands" . $dwhere . ")";
// echo "SQL\n".$sql."\n";
$activity_prep = $pt->duration ();

$pt = new ProcessTimer ();
$res = $mysql->query ( $sql );
$activity_db = $pt->duration ();

$pt = new ProcessTimer ();
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
				// if (strtoupper ( $param ) == "TEMPERATURE") {
				$t = timestampFormat ( time2Timestamp ( $tm ), "Y-m-d\TH:i:s\Z" );
				// echo "\t\t" . $param . "." . $name . ": got " . count ( $arr ) . " values for " . $t . "(" . $tm . ")\n";
				$avg = array_sum ( $arr ) / count ( $arr );
				$int [$param] [$name] [$t] = $avg;
				// $data [] = ( object ) [
				// "t" => $t,
				// "y" => $avg
				// ];
				// }
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
		// echo "Processing " . $param . "\n";
		$zones = [ ];
		foreach ( $names as $name => $values ) {
			// echo "\tProcessing " . $param . "." . $name . "\n";
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
		$param = strtolower ( $param );
		// echo "Adding ".count($zones)." to '".$param."'\n";
		$ret->history->$param = $zones;
		// print_r($ret);
	}
} else {
	echo "Got no data!!\n";
}
$activity_proc = $pt->duration ();

echo "Preparation activity took " . durationStamp ( $activity_prep, true ) . "\n";
echo "Database activity took " . durationStamp ( $activity_db ) . "\n";
echo "Processing activity took " . durationStamp ( $activity_proc ) . "\n";
echo "Total call duration: " . durationStamp ( $activity_prep + $activity_db + $activity_proc ) . "\n";

endJsonRespose ( $ret, true );
?>
