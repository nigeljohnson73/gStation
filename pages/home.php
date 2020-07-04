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
		<div class="col-sm-1"></div>
		<div class="col-sm-5">
			<div data-ng-hide='env'><img src='/gfx/ajax-loader-bar.gif' alt='Waiting for environment to load'/></div>
			<div class="snapshot-container" data-ng-show="camshot.available"><a href='{{camshot.livestream_url}}' target='live_stream'><img  src='{{camshot.src}}' alt='Video capture snapshot' class='img-thumbnail' /></a></div>
			
			<div class='sensor-container' data-ng-show='env.sensors'>
				<div class='sensor-holder demand-holder light-{{env.demand.light}}' data-ng-show='env.demand'>
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
	
			<div class='trigger-container' data-ng-show='env.triggers'>
				<div  data-ng-repeat="trigger in env.triggers" class='trigger-holder state-{{trigger.state}}'>
					<div class='name'>{{trigger.label}}</div>
				</div>
			</div>
			
			<div class="info-container" data-ng-show="env.info">
				<div class="nextsun-container" data-ng-show="env.info.nextsun">{{env.info.nextsun}}</div>
				<div class="location-container" data-ng-show="env.location">
					<div class="location-detail" data-ng-show="env.location.maplink">Location: <a href="{{env.location.maplink}}" target="location_map">{{env.location.name}}</a></div>
					<div class="location-detail" data-ng-hide="env.location.maplink">Model: <strong>{{env.location.name}}</strong></div>
					<div class="build-container">Model built on {{env.location.build | date : 'yyyy-MM-dd'}} at {{env.location.build | date : 'HH:mm:ss'}}</div>
					<div class="updated-container">Environment updated on {{env.timestamp | date : 'yyyy-MM-dd'}} at {{env.timestamp | date : 'HH:mm:ss'}}</div>
				</div>
				
			</div>
		</div>
	
		<div class="col-sm-5 text-center">
		<div class="chart-container" style="position: relative"><canvas id="temperature-graph"></canvas></div>
		<div class="chart-container" style="position: relative"><canvas id="humidity-graph"></canvas></div>
		<hr />
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

		<div class="col-sm-1"></div>
	</div> <!-- ROW --> 

</div>
<?php include_once '_footer.php';?>