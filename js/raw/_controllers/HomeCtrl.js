app.controller('HomeCtrl', [ "$scope", "$interval", "apiSvc",
		function($scope, $interval, apiSvc) {
			$scope.loading = true;
			$scope.title = "Home Control";
			logger("Started HomeCtrl");

			// Turn this into a function and call it in the interval and before
			// so it starts immediately.
			// https://stackoverflow.com/a/21989838
			var getEnv = function() {
				apiSvc.call("getEnv", {}, function(data) {
					logger("HomeCtrl::handleGetEnv()", "dbg");
					logger(data, "dbg");
					if (data.success) {
						$scope.env = data.env;
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
