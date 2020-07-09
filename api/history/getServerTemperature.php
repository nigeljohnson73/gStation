<?php
include_once (dirname ( __FILE__ ) . "/../../functions.php");
$pt = new ProcessTimer ();
$ret = startJsonRespose ();
// $ret = (object)[];
$ret->history = ( object ) [ ];
$hl = 10 * 60;

global $mysql, $show_empty, $sensors;

$activity_prep = 0;
$activity_db = 0;
$activity_proc = 0;

echo "Averaging duration : " . $hl . "s\n";

$sql = "SELECT param, name, event, value FROM sensors WHERE name = 'PI' AND param = 'TEMPERATURE'";
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
		$tmp [$r ["name"]] [timestamp2Time ( $r ["event"] ) - (timestamp2Time ( $r ["event"] ) % $hl)] [] = $r ["value"];
	}
	foreach ( $tmp as $name => $values ) {
			echo "got " . count ( $values ) . " values for '" . $name . "'\n";
			foreach ( $values as $tm => $arr ) {
				$t = timestampFormat ( time2Timestamp ( $tm ), "Y-m-d\TH:i:s\Z" );
				$avg = array_sum ( $arr ) / count ( $arr );
				$int [$name] [$t] = $avg;
			}
	}
	$tmp = $int;
	$int = [ ];

	foreach ( $tmp as $name => $values ) {
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
		// echo "Adding ".count($zones)." to '".$param."'\n";
		$ret->history = $zones;
		// print_r($ret);
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
