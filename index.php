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

setupTables();
$day = timestampFormat ( timestampNow (), "d" ); // 4;
$mon = timestampFormat ( timestampNow (), "m" ); // 10;

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
<title><?php echo $loc." - ".$app_title ?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="icon" href="gfx/rhino.png">
  <link href="https://fonts.googleapis.com/css?family=Architects+Daughter|Lato&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/app.<?php stylesheetPayload() ?>.css?t=<?php echo time() ?>">

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.8/angular.min.js"></script>
  <script src='js/app.<?php javascriptPayload() ?>.js?t=<?php echo time() ?>'></script>
</head>
<body>
	<div class="container-fluid">
	&nbsp;
		<div class="row">
			<div class="col-sm-4">
<?php
$fn = getSnapshotFile();
if($fn) {
echo "<a href='".getSnapshotUrl()."' target='liveStream'><img  src='/gfx/snapshot.php' alt='Video capture snapshot' class='img-thumbnail' style='margin-bottom:8px; margin-right:5px;' /></a>\n";
}

function getSensorData($env) {
	global $sensors;
	$labels = array("TEMPERATURE" => "Temp", "HUMIDITY" => "RH", "CPU_LOAD" => "CPU", "MEM_LOAD"=>"MEM", "SD_LOAD"=>"SD");
	$units = array("TEMPERATURE" => "C", "HUMIDITY" => "%", "CPU_LOAD" => "%", "MEM_LOAD"=>"%", "SD_LOAD"=>"%");
// TODO: Think of a better way of makking thises madatory - use the seosnr type fields??
// 	$labels = array("TEMPERATURE" => "Temp", "HUMIDITY" => "RH");
// 	$units = array("TEMPERATURE" => "°", "HUMIDITY" => "%");
	
	$ret = array();
	$env=(array)$env;
	foreach($sensors as $s) {
		$n = $s->name;
		foreach($labels as $k => $v) {
			$ek = $n.".".$k;
			if(isset($env[$ek])) {
				$value = $env[$ek];
				if(is_numeric($value)) {
					$value = number_format($value, 2);
				}
				//echo "$n.$k is '".$value."\n";
				$ret[$n][$v]= $value.$units[$k];
				
			} else {
				//echo "'$ek' is not set\n";
				$ret[$n][$v]= "--";
			}
		}
	}

	//print_r($ret);
	return $ret;
}

$env = json_decode(getConfig("env"));
// echo "<pre>";
$sd = getSensorData($env);
// echo "</pre>";

echo "<div class='trigger-container'>\n";
foreach($sd as $k => $v) {
	echo "				<div class='sensor-holder'><div class='label'>".$k."</div>";
	foreach($v as $l => $s) {
		echo "<div class='sensor'><div class='label'>$l:</div><div class='value'>$s</div></div>";
	}
	echo "</div>\n";
}
echo "</div>\n";

echo "<div class='trigger-container'>\n";
foreach($triggers as $t) {
	if(isset(((array)$env)["TRIGGER.".$t->name])) {
		$val = ((array)$env)["TRIGGER.".$t->name];
		$col = $val ? "#0f0" : "#030";
	} else {
		$col = "#ccc";
	}
	echo "				<div class='trigger-holder'><div class='label'>".$t->name."</div><div class='trigger' style='background-color:$col'>&nbsp;</div></div>\n";
}

echo "</div>\n";
?>
				<pre><?php
					echo "Processing environment at " . timestampFormat ( timestampNow (), "Y-m-d\TH:i:s T" ) . "\n";
 					if($darksky_key) {
 						echo "Location: " . $loc . " (" . latToDms ( $lat ) . ", " . lngToDms ( $lng ) . ")\n";
 					} else {
 						echo "Location: SIMULATED ENVIRONMENT\n";
 					}
 					echo "Current status: '" . getConfig ( "STATUS", "---" ) . "'\n";
?>
Model status: <?php print_r(modelStatus()) ?>

Current model: <?php print_r(getModel(timestampNow())) ?>

Current environment: <?php print_r(json_decode(getConfig("env"))) ?></pre>
		</div>
	
		<div class="col-sm-8 text-center">
			<div class='colour-container'>
<?php
				$cols = getAllGraphColours();
				echo "<!--\n".ob_print_r($cols)."-->\n";
				foreach($cols as $k=>$v) {
					echo "				<div class='colour-holder'><div class='label'>$k</div><div class='colour' style='background-color:$v'>&nbsp;</div></div>\n";
				}

?>
			</div>

			<?php
			foreach($graphs as $g) {
				list($what, $zone) = explode(".", $g);
				$ofn = "gfx/static_graph_".$what."_".$zone.".png";
				echo '				<img src="'.$ofn.'?'.randomQuery().'" alt="Measured '.$what.' graph for the last 24 hours" class="img-thumbnail" style="margin-bottom:8px; margin-right:5px;" />'."\n";
			}
			?>
				<img src="gfx/static_graph_temperature_scheduled.png?<?php echo randomQuery() ?>" alt="Temperature schedule graph" class="img-thumbnail" style="margin-bottom:8px; margin-right:5px;" />
				<img src="gfx/static_graph_humidity_scheduled.png?<?php echo randomQuery() ?>" alt="Humidity schedule graph" class="img-thumbnail" style="margin-bottom:8px; margin-right:5px;" />
				<img src="gfx/static_graph_sun_scheduled.png?<?php echo randomQuery() ?>" alt="Sunrise and sunset schedule graph" class="img-thumbnail" style="margin-bottom:8px; margin-right:5px;" />
				<img src="gfx/static_graph_daylight_scheduled.png?<?php echo randomQuery() ?>" alt="Scheduled day length graph" class="img-thumbnail" style="margin-bottom:8px; margin-right:5px;" />
		</div>
	</div> <!-- ROW --> 

	&nbsp;
	<div class="row">
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
		</div>
	</div>

	<div class="row">
		<div class="container-fluid text-center">
<?php if ($darksky_key !== ""): ?>
		<a target="DarkSky" href="https://darksky.net/poweredby/"><img src="https://darksky.net/dev/img/attribution/poweredby.png" alt="Powered by Dark Sky" style="width:150px;" /></a>
<?php else: ?>
		<a target="TribalRhino" href="https://tribalrhino.com/"><img src="gfx/poweredby.png" alt="Powered by Tribal Rhino" style="width:150px;" /></a>
		<!-- <img src="gfx/angrysnail.php" alt="andy snail" style="width:50px; margin-bottom:8px; margin-right:5px;" /> -->
<?php endif ?>
		</div>
	</div>
</body>
</html>
