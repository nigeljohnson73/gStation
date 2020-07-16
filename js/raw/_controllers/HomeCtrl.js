app.controller('HomeCtrl', [ "$scope", "$timeout", "$interval", "apiSvc", function($scope, $timeout, $interval, apiSvc) {
	$scope.title = "Home Control";
	$scope.loading = true;

	// Storage for the API queue
	$scope.api_calls = [];

	// Storage for graphing data
	$scope.sensor_temperature = [];
	$scope.sensor_humidity = [];
	$scope.server_cpu_load = [];
	$scope.server_cpu_wait = [];
	$scope.server_temperature = [];
	$scope.server_mem_load = [];
	$scope.server_hdd_load = [];

	// Storage for the data queues
	$scope.history = {};

	logger("Started HomeCtrl");

	// Called when one of the graph tabs is clicked, don't refresh the page
	$scope.preventRefresh = function(ev) {
		ev.preventDefault();
		return false;
	};

	// This is called to load the data into the correct array, but it also
	// checks to see if any history needs moving as well
	var processLoad = function(history, dst, obj, val) {
		if (history) {
			if (r = objectDataByName(history, obj.name)) {
				logger("Processing " + r.length + " historic values into '" + obj.name + "'", "dbg");
				angular.forEach(r, function(item, index) {
					addSensorReadingWithDate(dst, obj, item.y, new Date(item.t));
				});
			}
		}
		addSensorReading(dst, obj, val);

	};

	// Called to remove any data from the array that is out of scope, i.e. older
	// than 24 hours
	var tidyData = function(dst) {
		remove = new Date();
		remove.setDate(remove.getDate() - 1);
		angular.forEach(dst, function(item, index) {
			while (item.date, item.data.length > 0 && item.data[0].t <= remove) {
				// logger("Removing old '" + item.name + "' from 'TEMERATURE'
				// data points", "dbg");
				item.data.shift();
			}
		});
	};

	// Called to handle the update or create for the minute graphs required by
	// the heartbeat call. This just stops the significant creation animation
	var updateMinuteGraph = function(g, id, src, title, units) {
		ret = g;
		if (g) {
			g.update();
		} else {
			ret = createMinuteGraph(id, src, title, units);
		}
		return ret;
	};

	// Ensure the console is cleared every so often or it seems to back up a
	// shed load and cause the browser to die
	var clearLog = function() {
		console.clear();
		logger("Console log cleared");
	};
	$scope.console_clear_call = $interval(clearLog, 5 * 60 * 1000);

	/***************************************************************************
	 * The heartbeat handler is called every 5 seconds and requires some
	 * significant processing to pull out all the data
	 */
	var getEnv = function() {
		apiSvc.call("getEnv", {}, function(data) {
			logger("HomeCtrl::handleGetEnv()", "dbg");
			logger(data, "dbg");
			if (data.success) {
				// Save the env data first so it can be used everywhere else
				$scope.env = data.env;

				// Check for any historic 'DEMAND' data in the queue first
				var obj = {};
				Object.assign(obj, $scope.env.demand);
				processLoad($scope.history.sensor_temperature, $scope.sensor_temperature, obj, obj.temperature);
				processLoad($scope.history.sensor_humidity, $scope.sensor_humidity, obj, obj.humidity);

				// Check for any queue data for any of the know sensors
				angular.forEach($scope.env.sensors, function(item, index) {
					processLoad($scope.history.sensor_temperature, $scope.sensor_temperature, item, item.temperature);
					processLoad($scope.history.sensor_humidity, $scope.sensor_humidity, item, item.humidity);
				});

				var obj = {};
				Object.assign(obj, $scope.env.pi);
				processLoad($scope.history.server_cpu_load, $scope.server_cpu_load, obj, obj.cpu_load);
				processLoad($scope.history.server_cpu_wait, $scope.server_cpu_wait, obj, obj.cpu_wait);
				processLoad($scope.history.server_temperature, $scope.server_temperature, obj, obj.temperature);
				processLoad($scope.history.server_mem_load, $scope.server_mem_load, obj, obj.mem_load);
				processLoad($scope.history.server_hdd_load, $scope.server_hdd_load, obj, obj.sd_load);

				// Reset the history data
				$scope.history = {};

				// Perform a tidy up so we don't have anything prior to 24 hours
				// ago
				tidyData($scope.sensor_temperature);
				tidyData($scope.sensor_humidity);
				tidyData($scope.server_cpu_load);
				tidyData($scope.server_cpu_wait);
				tidyData($scope.server_temperature);
				tidyData($scope.server_mem_load);
				tidyData($scope.server_hdd_load);

				$scope.sensor_temperature_graph = updateMinuteGraph($scope.sensor_temperature_graph, '#sensor-temperature-graph', $scope.sensor_temperature, "Temperature", "°C");
				$scope.sensor_humidity_graph = updateMinuteGraph($scope.sensor_humidity_graph, '#sensor-humidity-graph', $scope.sensor_humidity, "Humidity", "%");
				$scope.server_cpu_load_graph = updateMinuteGraph($scope.server_cpu_load_graph, '#server-cpu_load-graph', $scope.server_cpu_load, "CPU Load", "%");
				$scope.server_cpu_wait_graph = updateMinuteGraph($scope.server_cpu_wait_graph, '#server-cpu_wait-graph', $scope.server_cpu_wait, "CPU Wait", "%");
				$scope.server_temperature_graph = updateMinuteGraph($scope.server_temperature_graph, '#server-temperature-graph', $scope.server_temperature, "Temperature", "°C");
				$scope.server_mem_load_graph = updateMinuteGraph($scope.server_mem_load_graph, '#server-mem_load-graph', $scope.server_mem_load, "Memory Usage", "%");
				$scope.server_hdd_load_graph = updateMinuteGraph($scope.server_hdd_load_graph, '#server-hdd_load-graph', $scope.server_hdd_load, "Storage Usage", "%");

			} else {
				$scope.env = null;
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;
		});
	};
	getEnv();
	$scope.env_api_call = $interval(getEnv, 5000);

	/***************************************************************************
	 * Camera image updates every minutes so only refresh every 10 seconds
	 */
	var getSnapshotImage = function() {
		apiSvc.queue("getSnapshotImage", {}, function(data) {
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
		});
	};
	getSnapshotImage();
	$scope.snapshot_image_api_call = $interval(getSnapshotImage, 10000);

	/***************************************************************************
	 * API queue
	 */

	var getApiData = function(o) {
		var d = new Duration();
		apiSvc.queue(o.api, function() {
			return {
				today : moment().format("MMDD")
			};
		}, function(data) {
			plogger(o.api + "(): Data transfer: " + d.prettyEnd());
			logger(data, "dbg");
			if (data.success) {
				o.success(data);
			} else {
				// Not sure what to do??
				// Redo?
			}
			if (data.message.length) {
				toast(data.message);
			}
			$scope.loading = false;

			// Chain the calls in the return from one, start the next
			processApiCalls();

			if (o.requeue) {
				// Set up a timer to add this back into the queue at midnight
				ms = Math.max(millisecondsToMidnight(), 5 * 60 * 1000);
				plogger(o.api + "(): requeue in " + prettyDuration(ms) + " (" + ms + "ms)");
				$timeout(function() {
					$scope.api_calls.push(o);
				}, ms);
			} else {
				logger("Call to '" + o.api + "' singleshot - no requeue", "dbg")
			}

		});
	};

	var processApiCalls = function(ms) {
		logger("processApiCalls(): called", "dbg");
		data = $scope.api_calls.shift();
		if (data) {
			getApiData(data);
		} else {
			// Set up a timer to restart the checking at 1 minute past midnight
			logger("API queue complete");
			var stm = millisecondsToMidnight();
			logger("Milliseconds of sleep: " + stm, "dbg");
			$scope.history_api_call = $timeout(processApiCalls, Math.max(millisecondsToMidnight() + 60 * 1000, 5 * 60 * 1000));
		}
	};

	/***************************************************************************
	 * API call data
	 */

	$scope.api_calls.push({
		api : "schedule/getTemperature",
		requeue : true,
		success : function(data) {
			$scope.schedule_temperature_graph = createDayGraph('#schedule-temperature-graph', data.data, "Temperature", "°C", data.xlabels);
		}
	});

	$scope.api_calls.push({
		api : "schedule/getHumidity",
		requeue : true,
		success : function(data) {
			$scope.schedule_humidity_graph = createDayGraph('#schedule-humidity-graph', data.data, "Humidity", "%", data.xlabels);
		}
	});

	$scope.api_calls.push({
		api : "schedule/getSun",
		requeue : true,
		success : function(data) {
			angular.forEach(data.data, function(item, index) {
				angular.forEach(item.data, function(item, index) {
					ts = moment(item.t);
					ts.local();
					h = parseFloat(ts.format("H")) + (parseFloat(ts.format("m")) / 60.0);
					item.t = ts.format();
					item.y = h;
				});
			});
			$scope.schedule_sun_graph = createDayGraph('#schedule-sun-graph', data.data, "Sun rise and set", "", data.xlabels, true);
		}
	});

	$scope.api_calls.push({
		api : "schedule/getDaylight",
		requeue : true,
		success : function(data) {
			$scope.schedule_daylight_graph = createDayGraph('#schedule-daylight-graph', data.data, "Daylight hours", "", data.xlabels);
		}
	});

	$scope.api_calls.push({
		api : "history/getServerCpuLoad",
		success : function(data) {
			$scope.history.server_cpu_load = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getServerCpuWait",
		success : function(data) {
			$scope.history.server_cpu_wait = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getServerTemperature",
		success : function(data) {
			$scope.history.server_temperature = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getServerMemoryLoad",
		success : function(data) {
			$scope.history.server_mem_load = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getServerHddLoad",
		success : function(data) {
			$scope.history.server_hdd_load = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getSensorTemperature",
		success : function(data) {
			$scope.history.sensor_temperature = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getSensorHumidity",
		success : function(data) {
			$scope.history.sensor_humidity = data.history;
		}
	});

	// Start the history data chain
	$scope.history_api_call = $timeout(processApiCalls, 100);
} ]);
