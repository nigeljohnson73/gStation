app.controller('FooterCtrl', [ "$scope", function($scope) {
	// This is only used to update the copyright year to "this year". Massive overkill.
	$scope.nowDate = Date.now();
} ]);
