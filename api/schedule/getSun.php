<?php
include_once (dirname ( __FILE__ ) . "/../../functions.php");
$pt = new ProcessTimer ();
$ret = startJsonRespose ();

$ret->data = [ ];

$m = getModel ( timestampNow () );
$dataset = [ ];
$dataset [] = ( object ) [ 
		//'t' => timetampFormat(time2Timestamp(timestamp2Time(timestampFormat ( timestampNow (), "Y-m-d" ) . "T00:00:00Z") + $m->sunriseOffset), "Y-m-d\TH:i:s\Z"),
		't' => timestampFormat ( timestampNow (), "Y-m-d" ) . "T00:00:00Z",
		//'y' => 0.5
		'y' => $m->sunriseOffset // / (60 * 60)
];
$dataset [] = ( object ) [ 
		//'t' => timetampFormat(time2Timestamp(timestamp2Time(timestampFormat ( timestampNow (), "Y-m-d" ) . "T00:00:00Z") + $m->sunsetOffset), "Y-m-d\TH:i:s\Z"),
		't' => timestampFormat ( timestampNow (), "Y-m-d" ) . "T00:00:00Z",
		//'y' => 23.5
		'y' => $m->sunsetOffset // / (60 * 60)
];
$rgb = hex2rgb ( "#c00" );
$today = ( object ) [ 
		"name" => strtoupper ( "TODAY" ),
		"label" => ucwords ( "Today" ),
		"backgroundColor" => "rgba(" . $rgb->r . ", " . $rgb->g . ", " . $rgb->b . ", 0.2)",
		"borderColor" => "rgba(" . $rgb->r . ", " . $rgb->g . ", " . $rgb->b . ", 1.0)",
		"borderWidth" => 1,
		"fill" => false,
		"data" => $dataset
];

$ret->data [] = $today;
$ret->data [] = getModelParamDataset ( "sunriseOffset", "Sunrise", "#090", true /*, 1 / (60 * 60)*/ );
$ret->data [] = getModelParamDataset ( "sunsetOffset", "Sunset", "#609", true/*, 1 / (60 * 60)*/ );

// REset all of these to event times
foreach($ret->data as $series) {
	foreach($series->data as $point) {
		$point->t = timestampFormat(time2Timestamp(timestamp2Time($point->t) + $point->y), "Y-m-d\TH:i:s")."Z";
		unset($point->y);
	}
}

$ret->labels = [ ];
$ret->model = getModel ();
foreach ( $ret->model as $day => $data ) {
	$ret->xlabels [] = timestampFormat ( timestampFormat ( timestampNow (), "Y" ) . $day, "Y-m-d\TH:i:s\Z" );
}

endJsonRespose ( $ret, true );
?>
