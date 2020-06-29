app.controller('HomeCtrl', [ "$scope", "$interval", "apiSvc",
		function($scope, $interval, apiSvc) {
			$scope.loading = true;
			$scope.title = "Welcome Home";
			logger("Started HomeCtrl");

			// Turn this into a function and call it in the interval and before
			// so it starts immediately.
			// https://stackoverflow.com/a/21989838
			var getEnv = function() {
				apiSvc.call("getEnv", {}, function(data) {
					logger("HomeCtrl::handleGetEnv()");
					console.log(data);
					if (data.message.length) {
						toast(data.message);
					}
					$scope.loading = false;
				}, true); // do post so response is not cached
			};
			getEnv();
			// $scope.env_api_call = $interval(getEnv, 5000);

			// OR much tidier, but won't fire until the first inteval
			// $interval(function() {
			// apiSvc.call("getEnv", {}, function(data) {
			// logger("HomeCtrl::handleGetEnv()");
			// console.log(data);
			// if (data.message.length) {
			// toast(data.message);
			// }
			// $scope.loading = false;
			// }, true); // do post so response is not cached
			// }, 5000) // 5000 ms execution

		} ]);
