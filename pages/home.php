<?php include_once '_header.php';

function getSensorData($env) {
	global $sensors;
	
	$units = array("LIGHT" => "",
			"TEMPERATURE" => "°C",
			"HUMIDITY" => "%RH",
			"CPU_LOAD" => "%CPU",
			"MEM_LOAD" => "%MEM",
			"SD_LOAD" => "%SD");
	
	$ret = array();
	$env=(array)$env;
	foreach($sensors as $s) {
		// 		print_r($s);
		$n = $s->name;
		$l = isset($s->label)?$s->label:$s->name;
		if(isset($s->label)) { // This is a git because sensor5 uses zone 2 again :(
			foreach($units as $k => $v) {
				$ek = $n.".".$k;
				if(isset($env[$ek])) {
					$value = $env[$ek];
					if(is_numeric($value)) {
						$value = number_format($value, 2);
					}
					//echo "$n.$k is '".$value."\n";
					$ret[$l][$v]= $value.$v;
					
				} else {
					//echo "'$ek' is not set\n";
					$ret[$l][$v]= "&nbsp";
				}
			}
		}
	}
	
	//print_r($ret);
	return $ret;
}
?>
<div class="container-fluid text-center">
	<div class="row">
		<div class="col-sm-5">
		<div class="snapshot-container" data-ng-show="camshot.available"><a href='{{camshot.livestream_url}}' target='live_stream'><img  src='{{camshot.src}}' alt='Video capture snapshot' class='img-thumbnail' /></a></div>
		
		<div class='sensor-container'>
			<div class='sensor-holder demand-holder light-{{env.demand.light}}'>
				<div class='name'>DEMAND</div>
				<div class='value'>{{env.demand.temperature}}°C</div>
				<div class='value'>{{env.demand.humidity}}%RH</div>
			</div>
			<div  data-ng-repeat="sensor in env.sensors" class='sensor-holder state-{{sensor.state}}'>
				<div class='name'>{{sensor.label}}</div>
				<div class='value' data-ng-show='sensor.temperature'>{{sensor.temperature}}°C</div>
				<div class='value' data-ng-hide='sensor.temperature'>&nbsp;</div>
				<div class='value' data-ng-show='sensor.humidity'>{{sensor.humidity}}%RH</div>
				<div class='value' data-ng-hide='sensor.humidity'>&nbsp;</div>
			</div>
		</div>
		
		<div class="info-container">
			<div class="nextsun-container">{{env.info.nextsun}}</div>
			<div class="location-container" data-ng-show="env.location.maplink">Location: <a href="{{env.location.maplink}}" target="location_map">{{env.location.name}}</a></div>
			<div class="location-container" data-ng-hide="env.location.maplink">Model: {{env.location.name}}</div>
			<div class="build-container">Model built on {{env.location.build | date : 'yyyy-MM-dd'}} at {{env.location.build | date : 'HH:mm:ss'}}</div>
			<div class="updated-container">Environment updated on {{env.timestamp | date : 'yyyy-MM-dd'}} at {{env.timestamp | date : 'HH:mm:ss'}}</div>
			
		</div>
	</div>
	
		<!-- 
		<hr />
<?php
			$fn = getSnapshotFile();
			if($fn) echo "			<a href='".getSnapshotUrl()."' target='live_stream'><img  src='/gfx/snapshot.php' alt='Video capture snapshot' class='img-thumbnail' style='margin-bottom:8px; margin-right:5px;' /></a>\n";

			$env = json_decode(getConfig("env"));
			// echo "<pre>";
			$sd = getSensorData($env);
			// echo "</pre>";
			
			echo "<div class='sensor-container'>\n";
			foreach($sd as $k => $v) {
				echo "				<div class='sensor-holder'><div class='label'>".$k."</div>";
				foreach($v as $l => $s) {
					echo "<div class='sensor'><div class='value'>$s</div></div>";
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
				echo "				<div class='trigger-holder'><div class='label'>".$t->label."</div><div class='trigger' style='background-color:$col'>&nbsp;</div></div>\n";
			}
			
			echo "</div>\n";
			?><!-- 
			<pre><?php echo "Environment status at " . timestampFormat ( timestampNow (), "Y-m-d\TH:i:s T" ) . "\n"; ?>

Location: <?php print_r(json_decode(getConfig("location"))) ?>

Current model: <?php print_r(getModel(timestampNow())) ?>

Current environment: <?php print_r(json_decode(getConfig("env"))) ?></pre> -->



		<div class="col-sm-7 text-center">
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
</div>
<?php include_once '_footer.php';?>