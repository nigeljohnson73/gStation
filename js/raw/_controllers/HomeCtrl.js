app.controller('HomeCtrl', [ "$scope", "$timeout", "$interval", "apiSvc", function($scope, $timeout, $interval, apiSvc) {
	// Storage for graphing data
	$scope.temps = [];
	$scope.humds = [];
	$scope.history_calls = [];

	var addSensorReading = function(arr, sensor, value) {
		return addSensorReadingWithDate(arr, sensor, value, new Date());
	};

	var addSensorReadingWithDate = function(arr, sensor, value, dte) {
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
	var createDayGraph = function(id, arr, title, ind, xlabels, yhours) {
		var ctx = $(id);
		var myChart = new Chart(ctx, {
			type : 'line',
			fill : false,
			data : {
				datasets : arr,
				labels : xlabels
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
							// max : ((yhours) ? (24) : (undefined)),
							stepSize : ((yhours) ? (1) : (undefined)),
							callback : function(value, index, values) {
								return yhours ? (hours2Hm(value)) : (value + ind);
							}
						}
					} ],
					xAxes : [ {
						type : 'time',
						ticks : {
							callback : function(value, index, values) {
								d = new Date(value);
								if (d.getDate() == 1 || (d.getDate() == 31 && d.getMonth() == 11)) {
									return value;
								}
								return undefined; // don't show this label

							},
							source : 'labels'
						},
						time : {
							unit : 'day',
							displayFormats : {
								day : 'MMM DD'
							}
						},
					} ]
				},
				tooltips : {
					callbacks : {
						title : function(tooltipItem, data) {
							return moment(tooltipItem[0].label).format("MMMM D");
						},
						label : function(tooltipItem, data) {
							var label = data.datasets[tooltipItem.datasetIndex].label || '';

							if (label) {
								label += ': ';
							}
							if (yhours) {
								h = tooltipItem.value;
								m = (tooltipItem.value - Math.floor(tooltipItem.value)) * 60;
								hh = lpad("" + Math.floor(h), 2, "0");
								mm = lpad("" + Math.floor(m), 2, "0");
								label += hh + ":" + mm;
							} else {
								label += (Math.round(tooltipItem.value * 100) / 100) + ind;
							}
							return label;
						}
					}
				}
			}
		});
		return myChart;
	};

	var createMinuteGraph = function(id, arr, title, ind) {
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
				},
				tooltips : {
					callbacks : {
						title : function(tooltipItem, data) {
							label = data.datasets[tooltipItem[0].datasetIndex].data[tooltipItem[0].index].t;
							return moment(label).format("MMMM Do, HH:mm:ss");
						},
						label : function(tooltipItem, data) {
							var label = data.datasets[tooltipItem.datasetIndex].label || '';

							if (label) {
								label += ': ';
							}
							label += (Math.round(tooltipItem.value * 100) / 100) + ind;
							return label;
						}
					}
				
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
				// Save the env dat afirst so it can be used everywher else
				$scope.env = data.env;

				// Load demands first
				var obj = {};
				Object.assign(obj, $scope.env.demand);
				if ($scope.history && $scope.history.temperature) {
					if (r = objectDataByName($scope.history.temperature, obj.name)) {
						logger("Processing " + r.length + " historic temperature values into '" + obj.name + "'");
						angular.forEach(r, function(item, index) {
							addSensorReadingWithDate($scope.temps, obj, item.y, new Date(item.t));
						});
					}
				}
				if ($scope.history && $scope.history.humidity) {
					if (r = objectDataByName($scope.history.humidity, obj.name)) {
						logger("Processing " + r.length + " historic humidity values into '" + obj.name + "'");
						angular.forEach(r, function(item, index) {
							addSensorReadingWithDate($scope.humds, obj, item.y, new Date(item.t));
						});
					}
				}

				// Now do the lastest 'DEMAND' values in
				addSensorReading($scope.temps, obj, obj.temperature);
				addSensorReading($scope.humds, obj, obj.humidity);

				// See if we have a queue to add to the existing non demand
				// stuff
				// Check for data for all known sensors
				angular.forEach($scope.env.sensors, function(item, index) {
					// Check for historic temperature data
					if ($scope.history && $scope.history.temperature) {
						if (r = objectDataByName($scope.history.temperature, item.name)) {
							logger("Processing " + r.length + " historic temperature values into '" + item.name + "'");
							angular.forEach(r, function(sitem, index) {
								addSensorReadingWithDate($scope.temps, item, sitem.y, new Date(sitem.t));
							});
						}
					}
					// Now load the one we just got
					addSensorReading($scope.temps, item, item.temperature);

					// Check for historic humidity data
					if ($scope.history && $scope.history.humidity) {
						if (r = objectDataByName($scope.history.humidity, item.name)) {
							logger("Processing " + r.length + " historic humidity values into '" + item.name + "'");
							angular.forEach(r, function(sitem, index) {
								addSensorReadingWithDate($scope.humds, item, sitem.y, new Date(sitem.t));
							});
						}
					}
					// Now load the one we just got
					addSensorReading($scope.humds, item, item.humidity);
				});

				// Reset the history data
				$scope.history.temperature = null;
				$scope.history.humidity = null;

				// Perform a tidy up so we don't have anything prior to 24 hours
				// ago
				remove = new Date();
				remove.setDate(remove.getDate() - 1);
				angular.forEach($scope.temps, function(item, index) {
					while (item.date, item.data.length > 0 && item.data[0].t <= remove) {
						logger("Removing old '" + item.name + "' from 'TEMERATURE' data points", "dbg");
						item.data.shift();
					}
				});
				angular.forEach($scope.humds, function(item, index) {
					while (item.date, item.data.length > 0 && item.data[0].t <= remove) {
						logger("Removing old '" + item.name + "' from 'HUMIDITY' data points", "dbg");
						item.data.shift();
					}
				});

				if ($scope.temp_graph) {
					$scope.temp_graph.update();
				} else {
					$scope.temp_graph = createMinuteGraph('#temperature-graph', $scope.temps, "Temperature Readings", "°C");
				}

				if ($scope.humd_graph) {
					$scope.humd_graph.update();
				} else {
					$scope.humd_graph = createMinuteGraph('#humidity-graph', $scope.humds, "Humidity Readings", "%");
				}
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

	var getScheduleTemperature = function() {
		var d = new Duration();
		apiSvc.call("schedule/getTemperature", {}, function(data) {
			logger("HomeCtrl::handleScheduleTemperature()");
			logger("getScheduleTemperature(): Data transferred: " + d.prettyEnd());
			logger(data);
			if (data.success) {
				// logger(data.data, "inf");
				$scope.schedule_temperature_graph = createDayGraph('#schedule-temperature-graph', data.data, "Temperature Schedule", "°C", data.xlabels);
			} else {
				// Not sure what to do??
				// Redo?
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
			// Chain the calls in the return from one, start the next
			getHistory();
		}, true); // do post so response is not cached
	};

	var getScheduleHumidity = function() {
		var d = new Duration();
		apiSvc.call("schedule/getHumidity", {}, function(data) {
			logger("HomeCtrl::handleScheduleHumidity()");
			logger("getScheduleHumidity(): Data transferred: " + d.prettyEnd());
			logger(data);
			if (data.success) {
				// logger(data.data, "inf");
				$scope.schedule_humidity_graph = createDayGraph('#schedule-humidity-graph', data.data, "Humidity Schedule", "%", data.xlabels);
			} else {
				// Not sure what to do??
				// Redo?
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
			// Chain the calls in the return from one, start the next
			getHistory();
		}, true); // do post so response is not cached
	};

	var getScheduleSun = function() {
		var d = new Duration();
		apiSvc.call("schedule/getSun", {}, function(data) {
			logger("HomeCtrl::handleScheduleSun()");
			logger("getScheduleSun(): Data transferred: " + d.prettyEnd());
			logger(data);
			if (data.success) {
				// logger(data.data, "inf");
				angular.forEach(data.data, function(item, index) {
					// if(item.name == "TODAY") {
					// logger(item, "inf");
					angular.forEach(item.data, function(item, index) {
						ts = moment(item.t);
						ts.local();
						h = parseFloat(ts.format("H")) + (parseFloat(ts.format("m")) / 60.0);
						item.t = ts.format();
						item.y = h;
						// logger(ts.format() + ", h: " + h);
					});
					// }
				});
				$scope.schedule_temperature_graph = createDayGraph('#schedule-sun-graph', data.data, "Sun Schedule", "", data.xlabels, true);
			} else {
				// Not sure what to do??
				// Redo?
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
			// Chain the calls in the return from one, start the next
			getHistory();
		}, true); // do post so response is not cached
	};

	var getScheduleDaylight = function() {
		var d = new Duration();
		apiSvc.call("schedule/getDaylight", {}, function(data) {
			logger("HomeCtrl::handleScheduleDaylight()");
			logger("getScheduleDaylight(): Data transferred: " + d.prettyEnd());
			logger(data);
			if (data.success) {
				// logger(data.data, "inf");
				$scope.schedule_temperature_graph = createDayGraph('#schedule-daylight-graph', data.data, "Daylight Schedule", "H", data.xlabels);
			} else {
				// Not sure what to do??
				// Redo?
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
			// Chain the calls in the return from one, start the next
			getHistory();
		}, true); // do post so response is not cached
	};

	var getHistoryTemperature = function() {
		var d = new Duration();
		apiSvc.call("history/getTemperature", {}, function(data) {
			logger("HomeCtrl::handleGetHistoryTemperature()");
			logger("getHistoryTemperature(): Data transferred: " + d.prettyEnd());
			logger(data);
			if (data.success) {
				// logger(data.history, "dbg");
				$scope.history.temperature = data.history;
			} else {
				// Not sure what to do??
				// Redo?
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
			// Chain the calls in the return from one, start the next
			getHistory();
		}, true); // do post so response is not cached
	};

	var getHistoryHumidity = function() {
		var d = new Duration();
		apiSvc.call("history/getHumidity", {}, function(data) {
			logger("HomeCtrl::handleGetHistoryHumidity()");
			logger("getHistoryHumidity(): Data transferred: " + d.prettyEnd());
			logger(data);
			if (data.success) {
				// logger(data.history, "dbg");
				$scope.history.humidity = data.history;
			} else {
				// Not sure what to do??
				// Redo?
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
			// Chain the calls in the return from one, start the next
			getHistory();
		}, true); // do post so response is not cached
	};

	var getHistory = function(ms) {
		if (ms == undefined) {
			ms = 2000;
		}
		// Chain the calls in the return from one, start the next
		call = $scope.history_calls.shift();
		if (call) {
			logger("Calling history retrieval with " + ms + "ms delay");
			$scope.history_api_call = $timeout(call, ms);
		} else {
			logger("All history up to date");
			$scope.history_api_call = null;
		}
	};

	$scope.history_calls.push(getScheduleTemperature);
	$scope.history_calls.push(getScheduleHumidity);
	$scope.history_calls.push(getScheduleSun);
	$scope.history_calls.push(getScheduleDaylight);
	$scope.history_calls.push(getHistoryTemperature);
	$scope.history_calls.push(getHistoryHumidity);

	// Start the history data chain
	$scope.history_api_call = $timeout(getHistory, 1000, true, 100);
} ]);
