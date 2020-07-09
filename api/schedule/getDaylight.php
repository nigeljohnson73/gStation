<?php
include_once (dirname ( __FILE__ ) . "/../../functions.php");
$today = getPostArgs ( "today", timestampNow () );
$ret = startJsonRespose ();
$ret->data = [ ];

$m = getModel ( $today );
$dataset = [ ];
$dataset [] = ( object ) [ 
		't' => timestampFormat ( timestampNow (), "Y-m-d" ) . "T11:59:59+00:00",
		'y' => $m->daylightHours - 0.25
];
$dataset [] = ( object ) [ 
		't' => timestampFormat ( timestampNow (), "Y-m-d" ) . "T12:00:01+00:00",
		'y' => $m->daylightHours + 0.25
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
$ret->data [] = getModelParamDataset ( "daylightHours", "Daylight", "#090", true );

$ret->xlabels = [ ];
$ret->model = getModel ();
foreach ( $ret->model as $day => $data ) {
	$ret->xlabels [] = timestampFormat ( timestampFormat ( timestampNow (), "Y" ) . $day, "Y-m-d\TH:i:s\Z" );
}

endJsonRespose ( $ret, true );
?>
