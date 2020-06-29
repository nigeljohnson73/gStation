app.controller('HomeCtrl', [ "$scope", "apiSvc", function($scope, apiSvc) {
	$scope.loading = true;
	$scope.title="Welcome Home";
	logger("Started HomeCtrl");
	apiSvc.call("getEnv", {}, function(data) {
		logger("HomeCtrl::handleGetEnv()");
		console.log(data);
		if(data.message.length) {
			toast(data.message);
		}
		$scope.loading = false;
	}, true); // do post so response is not cached
} ]);
