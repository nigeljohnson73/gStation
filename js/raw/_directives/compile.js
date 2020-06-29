/*
                            _ _            _ _               _   _
   ___ ___  _ __ ___  _ __ (_) | ___    __| (_)_ __ ___  ___| |_(_)_   _____
  / __/ _ \| '_ ` _ \| '_ \| | |/ _ \  / _` | | '__/ _ \/ __| __| \ \ / / _ \
 | (_| (_) | | | | | | |_) | | |  __/ | (_| | | | |  __/ (__| |_| |\ V /  __/
  \___\___/|_| |_| |_| .__/|_|_|\___|  \__,_|_|_|  \___|\___|\__|_| \_/ \___|
                     |_|
*/
app.directive('compile', [ '$compile', function($compile) {
	return function(scope, element, attrs) {
		scope.$watch(function(scope) {
			// watch the 'compile' expression for changes
			return scope.$eval(attrs.compile);
		}, function(value) {
			// when the 'compile' expression changes assign it into the current DOM
			element.html(value);

			// compile the new DOM and link it to the current scope.
			// NOTE: we only compile .childNodes so that we don't get into infinite loop compiling ourselves
			$compile(element.contents())(scope);
		});
	};
} ]);
