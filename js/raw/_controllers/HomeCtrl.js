app.controller('HomeCtrl', [ "$scope", "$interval", "apiSvc", function($scope, $interval, apiSvc) {
	$scope.temps = [];
	$scope.humds = [];

	var addSensorReading = function(arr, sensor, value) {
		return addSensorReadingWithDate(arr, sensor, value, new Date());
	};

	var addSensorReadingWithDate = function(arr, sensor, value, dte) {
		while (arr.length >= (24 * 60 * 60 / 5)) {
			// TODO: Fix this to drop out the values oder than this time ago
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
				// console.log("item.name: '" + item.name + "', sensor.name: '"
				// + sensor.name + "'");
				found = true;
				item.data.push({
					t : dte,
					y : value
				});

				item.data.sort(function(a, b) {
					if (a.t < b.t) {
						return -1;
					}
					if (a.t > b.t) {
						return 1;
					}
					return 0;
				});
			}
			// console.log("Adding " + value + " to " + item.name);
		});

		if (!found) {
			// console.log("Creating " + sensor.name + " and adding " + value);
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

	var updateGraph = function(id, arr, title, ind) {
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
					yAxes : [ {
						ticks : {
							// Include a dollar sign in the ticks
							callback : function(value, index, values) {
								return value + ind;
							}
						}
					} ],
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

	var objectDataByName = function(arr, name) {
		ret = null;
		angular.forEach(arr, function(item, index) {
			// logger("Searching for '" + name + "' found '" + item.name + "'");
			if (item.name == name) {
				logger("Found " + item.data.length + " data points for '" + item.name + "'", "dbg");
				ret = item.data;
				return;
			}
		});
		return ret;
	};

	$scope.loading = true;
	$scope.history = {};
	$scope.title = "Home Control";
	logger("Started HomeCtrl");

	// Get the environmental data every 5 seconds
	var getEnv = function() {
		apiSvc.call("getEnv", {}, function(data) {
			logger("HomeCtrl::handleGetEnv()", "dbg");
			logger(data, "dbg");
			if (data.success) {
				$scope.env = data.env;

				// Lets load the history demands first so they are on top of the
				// stack
				var obj = {};
				Object.assign(obj, $scope.env.demand);
				if (r = objectDataByName($scope.history.temperature, obj.name)) {
					logger("Processing " + r.length + " historic temperatures into '" + obj.name + "'", "dbg");
					angular.forEach(r, function(sitem, index) {
						addSensorReadingWithDate($scope.temps, obj, sitem.y, new Date(sitem.t));
					});
				}
				if (r = objectDataByName($scope.history.humidity, obj.name)) {
					logger("Processing " + r.length + " historic humidities into '" + obj.name + "'", "dbg");
					angular.forEach(r, function(sitem, index) {
						addSensorReadingWithDate($scope.humds, obj, sitem.y, new Date(sitem.t));
					});
				}
				// Now do the lastest values in
				addSensorReading($scope.temps, obj, obj.temperature);
				addSensorReading($scope.humds, obj, obj.humidity);

				// See if we have a queue to add to the existing non demand
				// stuff
				angular.forEach($scope.env.sensors, function(item, index) {
					if ($scope.history && $scope.history.temperature) {
						if (r = objectDataByName($scope.history.temperature, item.name)) {
							logger("Processing " + r.length + " historic temperatures into '" + item.name + "'", "dbg");
							angular.forEach(r, function(sitem, index) {
								addSensorReadingWithDate($scope.temps, item, sitem.y, new Date(sitem.t));
							});
						}
					}
					addSensorReading($scope.temps, item, item.temperature);
					if ($scope.history && $scope.history.humidity) {
						if (r = objectDataByName($scope.history.humidity, item.name)) {
							logger("Processing " + r.length + " historic humidities being loaded to '" + item.name + "'", "dbg");
							angular.forEach(r, function(sitem, index) {
								addSensorReadingWithDate($scope.humds, item, sitem.y, new Date(sitem.t));
							});
						}
					}
					addSensorReading($scope.humds, item, item.humidity);
				});

				// Reset so we don't do this again
				$scope.history.temperature = null;
				$scope.history.humidity = null;

				if ($scope.temp_graph) {
					$scope.temp_graph.update();
				} else {
					$scope.temp_graph = updateGraph('#temperature-graph', $scope.temps, "Temperature Readings", "°C");
				}
				;

				if ($scope.humd_graph) {
					$scope.humd_graph.update();
				} else {
					$scope.humd_graph = updateGraph('#humidity-graph', $scope.humds, "Humidity Readings", "%");
				}
				;
			} else {
				$scope.env = null;
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
		}, true); // do post so response is not cached
	};
	getEnv();
	$scope.env_api_call = $interval(getEnv, 5000);

	// Get the environmental data every 5 seconds
	var getGraphHistory = function() {
		apiSvc.call("getGraphHistory", {}, function(data) {
			logger("HomeCtrl::handleGetGraphHistory()", "dbg");
			logger(data, "dbg");
			if (data.success) {
				// logger(data.history, "dbg");
				$scope.history = data.history;
			} else {
				// $scope.env = null;
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
		}, true); // do post so response is not cached
	};
	getGraphHistory();

	// Get the snalshot image, it's only generated every minute, so no rush
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
		}, true); // do post so response is not cached
	};
	getSnapshotImage();
	$scope.snapshot_image_api_call = $interval(getSnapshotImage, 10000);

} ]);
