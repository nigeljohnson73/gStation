<?php
include_once ("functions.php");

// Home made NEST thermostat:
// https://www.stuff.tv/features/how-build-homemade-nest-thermostat
//
// Succulent reference: http://www.llifle.com/Encyclopedia/SUCCULENTS/Family/Aizoaceae/16149/Conophytum_obcordellum
// Get seeds from SA: http://silverhillseeds.co.za/entirecat.asp
// Also check: https://www.worldwondersgardens.co.uk/
// and: https://succulentplants.uk/
// buy: https://succulentplants.uk/product/graptopetalum-mendozae-succulent-leaf-cutting/
// buy: https://succulentplants.uk/product/fenestraria-baby-toes-living-stone-rooted-plant/#comment-2356
// Create connection

setupTables ();
$day = timestampFormat ( timestampNow (), "d" ); // 4;
$mon = timestampFormat ( timestampNow (), "m" ); // 10;
                                                 // $mon = 10;

// $call = "https://api.darksky.net/forecast/" . $darksky_key . "/" . $dark_sky_lat . "," . $dark_sky_lng . "," . $time . "T00:00:00?units=si&exclude=currently,minutely,hourly,alerts,flags";
// $json = file_get_contents ( $call );
// $ret = json_decode ( $json );
// date_default_timezone_set ( $ret->timezone );
// $obj = json_decode ( $json )->daily->data [0];

// $last_temp = lastTemp();
// if($last_temp == null) {
// $last_temp = "NO DATA";
// } else {
// $last_temp = $last_temp["temperature"] . "C at " . $last_temp["entered"];
// }

?>
<!doctype html>
<html ng-app>
<head>
<title><?php echo $app_title." - ".$loc ?></title>
<link rel="icon" href="gfx/rhino.png">
<script
	src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.8/angular.min.js"></script>
<link rel="stylesheet"
	href="https://netdna.bootstrapcdn.com/twitter-bootstrap/2.0.4/css/bootstrap-combined.min.css">
<link rel="stylesheet" href="css/app.<?php stylesheetPayload() ?>.css">
<script src='js/app.<?php javascriptPayload() ?>.js'></script>
</head>
<body>
	<div class="container-fluid">
		<pre><?php
		// tick();
		// $status = getConfig("STATUS", "NIGHT");
		echo "Processing weather at " . timestampFormat ( timestampNow (), "Y-m-d\TH:i:s T" ) . "\n";
		echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n";
		echo "Current status: '" . getConfig ( "STATUS", "---" ) . "'\n";
		// print_r ( getData ( $lat, $lng, $day, $mon, null, false, false ) );

		?>
Last temp: <?php print_r(tfn(lastTemp())) ?></pre>
	</div>

	<div class="container-fluid debug">

		<pre><?php
		$str = $logger->getString ();
		if (strlen ( trim ( $str ) ) == 0) {
			$str = "*** NO LOG OUTPUT ***";
		} else {
			$str = trim ( $str ) . "\n*** END OF LOG ***";
		}
		echo "Log output:\n";
		echo $str . "\n";
		?></pre>

		<img
			src="gfx/graph_local_temperatures.php?<?php echo randomQuery() ?>"
			alt="Local Temperature Graph" /> <img
			src="gfx/graph_remote_temperatures.php"
			alt="Remote Average Temperature Graph" />
		<!-- <?php //phpInfo() ?> -->
	</div>
</body>
</html>