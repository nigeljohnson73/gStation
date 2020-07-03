app.controller('HomeCtrl', [ "$scope", "$interval", "apiSvc", function($scope, $interval, apiSvc) {
	$scope.temps = [];
	$scope.humds = [];

	var addSensorReading = function(arr, sensor, value) {
		while (arr.length >= (24*60*60/5)) {
			// Only keep the last hour and a half or so.
			arr.shift();
		}

		found = false;
		if (value == undefined) {
			// console.log("Skipping bad value for " +
			// sensor.name);
			return false;
		}

		angular.forEach(arr, function(item, index) {
			if (item.name == sensor.name) {
				// console.log("item.name: '" +
				// item.name + "', sensor.name: '" +
				// sensor.name + "'");
				found = true;
				item.data.push({
					t : new Date(),
					y : value
				});
			}
			// console.log("Adding " + value + " to " +
			// item.name);
		});

		if (!found) {
			// console.log("Creating " + sensor.name
			// + " and adding " + value);
			rgb = hexToRgb(sensor.colour);
			arr.push({
				name : sensor.name,
				label : sensor.label,
				backgroundColor : "rgba(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ", 0.2)",
				borderColor : "rgba(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ", 1)",
				borderWidth : 1,
				fill : false,
				data : [ {
					t : new Date(),
					y : value
				} ]
			});
		}

		// console.log(arr);
		return true;
	};

	var updateGraph = function(id, arr, title) {
		var ctx = $(id);
		var myChart = new Chart(ctx, {
			type : 'line',
			fill : false,
			data : {
				datasets : arr,
			},
			options : {
				responsive : true,
				aspectRatio : 2,
				legend : {
					display : true
				},
				title : {
					display : true,
					fontSize : 18,
					text : title
				},
				scales : {
					xAxes : [ {
						type : 'time',
						distribution : 'linear',
						time : {
							unit : 'minute',
							displayFormats : {
								minute : "HH:mm"
							}
						},
					} ]
				}
			}
		});
		return myChart;
	};

	$scope.loading = true;
	$scope.title = "Home Control";
	logger("Started HomeCtrl");

	// Get the environmental data every 5 seconds
	var getEnv = function() {
		apiSvc.call("getEnv", {}, function(data) {
			logger("HomeCtrl::handleGetEnv()", "dbg");
			logger(data, "dbg");
			if (data.success) {
				$scope.env = data.env;
				// console.log($scope.env.demand);
				var obj = {};
				Object.assign(obj, $scope.env.demand);
				angular.forEach($scope.env.sensors, function(item, index) {
					addSensorReading($scope.temps, item, item.temperature);
					addSensorReading($scope.humds, item, item.humidity);
				});
				// Add these after so they appear on top
				addSensorReading($scope.temps, obj, obj.temperature);
				addSensorReading($scope.humds, obj, obj.humidity);

				if ($scope.temp_graph) {
					$scope.temp_graph.update();
				} else {
					$scope.temp_graph = updateGraph('#temperature-graph', $scope.temps, "Temperature Readings");
				}
				;

				if ($scope.humd_graph) {
					$scope.humd_graph.update();
				} else {
					$scope.humd_graph = updateGraph('#humidity-graph', $scope.humds, "Humidity Readings");
				}
				;
			} else {
				$scope.env = null;
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
		}, true); // do post so
		// response is not
		// cached
	};
	getEnv();
	$scope.env_api_call = $interval(getEnv, 5000);

	// Get the snalshot image, it's only gnerated every
	// minute, so no rush
	var getSnapshotImage = function() {
		apiSvc.call("getSnapshotImage", {}, function(data) {
			logger("HomeCtrl::handleGetSnapshotImage()", "dbg");
			logger(data, "dbg");
			if (data.success) {
				$scope.camshot = data.camshot;
			} else {
				$scope.camshot = null;
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
		}, true); // do post so
		// response is not
		// cached
	};
	getSnapshotImage();
	$scope.snapshot_image_api_call = $interval(getSnapshotImage, 10000);

} ]);
