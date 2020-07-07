
/*
  _   _	     _	                  __                  _   _				 
 | | | | ___| |_ __   ___ _ __   / _|_   _ _ __   ___| |_(_) ___  _ __  ___ 
 | |_| |/ _ \ | '_ \ / _ \ '__| | |_| | | | '_ \ / __| __| |/ _ \| '_ \/ __|
 |  _  |  __/ | |_) |  __/ |	|  _| |_| | | | | (__| |_| | (_) | | | \__ \
 |_| |_|\___|_| .__/ \___|_|	|_|  \__,_|_| |_|\___|\__|_|\___/|_| |_|___/
              |_|														   
 */

class Duration {
	constructor() {
		this.start = new Date().getTime();
	};

	end() {
		return new Date().getTime() - this.start;
	};

	prettyEnd() {
		var ret = "";

		const ms2h = 1 / (60 * 60 * 1000);
		const ms2m = 1 / (60 * 1000);
		const ms2s = 1 / (1000);

		var ms = this.end();
		var h = Math.floor(ms * ms2h);
		ms -= h / ms2h;
		var m = Math.floor(ms * ms2m);
		ms -= m / ms2m;
		var s = Math.floor(ms * ms2s);
		ms -= s / ms2s;

		ret += (h > 0) ? (h + "h") : ("");
		ret += ((ret.length > 0) ? (" ") : ("")) + ((m > 0 || ret.length) ? (m + "m") : (""));
		ret += ((ret.length > 0) ? (" ") : ("")) + ((s > 0 || ret.length) ? (s + "s") : (""));
		ret += ((ret.length > 0) ? (" ") : ("")) + ms + "ms";
		return ret;
	};
};

var lpad = function(n, width, z) {
	  z = z || '0';
	  n = n + '';
	  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
	};

hours2Hm = function(num) {
	num=num*60;
	var hours = Math.floor(num / 60);
	var minutes = num % 60;
	return lpad(""+hours, 2) + ":" + lpad(""+minutes, 2);
};

hexToRgb = function(hex) {
	// Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
	var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
	hex = hex.replace(shorthandRegex, function(m, r, g, b) {
		return r + r + g + g + b + b;
	});

	var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
	return result ? {
		r : parseInt(result[1], 16),
		g : parseInt(result[2], 16),
		b : parseInt(result[3], 16)
	} : null;
};

isJson = function(item) {
	item = typeof item !== "string" ? JSON.stringify(item) : item;

	try {
		item = JSON.parse(item);
	} catch (e) {
		return false;
	}

	if (typeof item === "object" && item !== null) {
		return true;
	}

	return false;
};

colorLuminance = function(hex, lum) {

	// validate hex string
	hex = String(hex).replace(/[^0-9a-f]/gi, "");
	if (hex.length < 6) {
		hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
	}
	lum = lum || 0;

	// convert to decimal and change luminosity
	var rgb = "#", c, i;
	for (i = 0; i < 3; i++) {
		c = parseInt(hex.substr(i * 2, 2), 16);
		c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
		rgb += ("00" + c).substr(c.length);
	}
	// console.log("colorLuminance(" + hex + ", " + lum + "): returning '" + rgb
	// + "'");

	return rgb;
};

// log_to_console = 2;
logger = function(l, err) {
	if (!err)
		err = "inf";

	// TODO: make this more resolute
	// if (err == "dbg" && log_to_console >= 3) {
	// console.debug(l);
	// }
	// if (err == "inf" && log_to_console >= 2) {
	// console.log(l);
	// }
	// if (err == "wrn" && log_to_console >= 1) {
	// console.warn(l);
	// }
	// if (err == "err" && log_to_console >= 0) {
	// console.error(l);
	// }
	if (err == "dbg") {
		console.debug(l);
	}
	if (err == "inf") {
		console.log(l);
	}
	if (err == "wrn") {
		console.warn(l);
	}
	if (err == "err") {
		console.error(l);
	}
};

function number_format(number, decimals, dec_point, thousands_sep) {
	// Strip all characters but numerical ones.
	number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	var n = !isFinite(+number) ? 0 : +number, prec = !isFinite(+decimals) ? 0 : Math.abs(decimals), sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep, dec = (typeof dec_point === 'undefined') ? '.' : dec_point, s = '', toFixedFix = function(n, prec) {
		var k = Math.pow(10, prec);
		return '' + Math.round(n * k) / k;
	};
	// Fix for IE parseFloat(0.55).toFixed(0) = 0;
	s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
	if (s[0].length > 3) {
		s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	}
	if ((s[1] || '').length < prec) {
		s[1] = s[1] || '';
		s[1] += new Array(prec - s[1].length + 1).join('0');
	}
	return s.join(dec);
}

Array.prototype.random = function() {
	return this[Math.round((Math.random() * (this.length - 1)))];
};

/*******************************************************************************
 * genKey()
 * 
 * Generates an arbitary key that consists of the alpha-numeric and special
 * character sets, but with confusing characters like';1', 'i', and 'l' removed
 * 
 * Key is a charater string that defines the charaters and can consist of: u -
 * Upper case l - lower case n - number s - special character x - any of the
 * above
 * 
 * for example genKey('unlllaaa') would produce 'E5ncyCgt'
 */
function genKey(key) {
	var uc = [ 'A', 'B', 'C', 'E', 'F', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'R', 'T', 'W', 'Y', 'Z' ];
	var lc = [ 'a', 'b', 'd', 'e', 'g', 'h', 'k', 'n', 'p', 'q', 'r', 's', 't', 'x', 'y', 'z' ];
	var nc = [ '2', '3', '4', '5', '6', '7', '8', '9' ];
	var sc = [ '=', '-', '.', '_', '@' ];
	var an = [].concat(uc).concat(lc).concat(nc);
	var ny = [].concat(sc).concat(an);
	return key.replace(/[xlunas]/g, function(c) {
		return (c === 'u' ? uc.random() : (c === 'l' ? lc.random() : (c === 'n' ? nc.random() : (c === 's' ? sc.random() : (c === 'a' ? an.random() : ny.random())))));
	});
}

var Base64 = {
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

	encode : function(input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;

		input = Base64._utf8_encode(input);
		while (i < input.length) {
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);

			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;

			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}
			output = output + this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) + this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

		}
		return output;
	},

	decode : function(input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;

		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
		while (i < input.length) {

			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));

			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;

			output = output + String.fromCharCode(chr1);

			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}

		}
		output = Base64._utf8_decode(output);
		return output;
	},

	_utf8_encode : function(string) {
		string = string.replace(/\r\n/g, "\n");
		var utftext = "";

		for (var n = 0; n < string.length; n++) {
			var c = string.charCodeAt(n);

			if (c < 128) {
				utftext += String.fromCharCode(c);
			} else if ((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			} else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
		}
		return utftext;
	},

	_utf8_decode : function(utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;

		while (i < utftext.length) {
			c = utftext.charCodeAt(i);

			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			} else if ((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i + 1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			} else {
				c2 = utftext.charCodeAt(i + 1);
				c3 = utftext.charCodeAt(i + 2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
		}
		return string;
	}
};

var toastTimeout = null;
function toast(text) {
	xxlogger("updating toast text");
	$("#snackbar").html(text);

	if (!$("#snackbar").hasClass("show")) {
		xxlogger("showing toast");
		$("#snackbar").addClass("show");
	}

	// After 3 seconds, remove the show class from DIV
	if (toastTimeout === null) {
		toastTimeout = setTimeout(function() {
			if (toastTimeout) {
				xxlogger("Clearing toast");
				$("#snackbar").removeClass("show");
				toastTimeout = null;
			}
		}, 2990);
	}
};

$(document).ready(function() {
	// Switch main page into view
	$("#page-loading").hide();
	$("#page-loaded").show(1000);
	logger("Application Loaded");
});

var app = angular.module("myApp", [ 'ngRoute' ]);

app.config([ "$locationProvider", "$routeProvider", function($locationProvider, $routeProvider) {
	$locationProvider.html5Mode(true);

	$routeProvider.when('/', {
		templateUrl : '/pages/home.php',
		controller : 'HomeCtrl'
	}).when('/about', {
		templateUrl : '/pages/about.php',
		controller : 'AboutCtrl'
	}).when('/config', {
		templateUrl : '/pages/config.php',
		controller : 'ConfigCtrl'
	}).otherwise({
		templateUrl : '/pages/404.php'
	});
} ]);

logger("Hello There!");
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
app.directive('imageonload', [ function() {
	return {
	restrict : 'A',
	link : function(scope, element, attrs) {
		element.bind('load', function() {
			//call the function that was passed
			scope.$apply(attrs.imageonload);
		});
	}
	};
} ]);
/*
             _ ____
  __ _ _ __ (_) ___|_   _____
 / _` | '_ \| \___ \ \ / / __|
| (_| | |_) | |___) \ V / (__
 \__,_| .__/|_|____/ \_/ \___|
      |_|
*/
app.service('apiSvc', [ "$http", function($http, netSvc) {
//	app.service('apiSvc', [ "$http", "netSvc", function($http, netSvc) {
	apiSvc = this; // cuz "this" changes later

	apiSvc.online = false;

//	/***
//	*
//	*/
//	netSvc.addStateListener(function(tf) {
//		apiSvc.online = tf;
//	});
//
//	apiSvc.isOnline = function() {
//		return apiSvc.online;
//	};

	/***
	* CAll an API
	* pass a data object, the callback to notify and whether to use POST method or not
	* Use post=true for sending data so that it is not cached.
	* If using GET, the nocache set to true will force a new version *if* the API has been updated recently
	*/
	apiSvc.call = function(api, data, notify, post, nocache) {

		txdata = {};
		logtxdata = {};

		// now move the user data in
		for ( var attrname in data) {
			txdata[attrname] = data[attrname];
			logtxdata[attrname] = data[attrname];
		}

		var method = "GET"; // BAD BAD BAD, but I can't cache for offline otherwise
		var qs = "";
		if (post) {
			method = "POST";
		} else {
			if (!nocache) {
				txdata["cached"] = api_build_date_raw;
			}
			qs = "?" + $.param(txdata);
		}

		logger("apiSvc.call('" + api + "', '" + method + "')", "dbg");
		//console.log(logtxdata);

		// Send it all over to the server
		$http({
		method : method,
		url : '/api/' + api + ".php" + qs,
		data : $.param(txdata),
		headers : {
			// set the headers so angular passing info as form data (not request payload)
			'Content-Type' : 'application/x-www-form-urlencoded'
		}
		}).then(function(data) {
			logger("apiSvc.call(): success", "dbg");
			//console.log(data);
			ldata = {};
			if(isJson(data.data)) {
				// http response object returned, strip out the server response
				ldata = data.data;
			} else {
				logger("apiSvc.call(): malformed response", "wrn");
				// Any returned text in the console where you would expect some explanation
				ldata.console = data.data.trim().split(/\r\n|\r|\n/); 
				ldata.success = false;
				ldata.status = "error";
				ldata.message = "";
				logger(ldata, "wrn");
			}

			if (typeof notify == "function") {
				logger("apiSvc.call(): calling notifier", "dbg");
				notify(ldata);
			}
		}, function(data) {
			//logger("apiSvc.call(): failed", "err");
			//console.log(data);
			ldata = {};
			if (data.status == 200) {
				// Probbably should never be here ,since a 200 would be a success???
				logger("apiSvc.call(): failed at the remote end", "err");
				ldata = data.data;
				ldata.console = (data.data+"").trim().split(/\r\n|\r|\n/); 
				} else {
				logger("apiSvc.call(): HTTP failed with status code " + data.status, "err");
				// Any returned text in the console where you would expect some explanation
				if(data.data == null) {
					data.data = "";
				}
				ldata.console = (data.data+"").trim().split(/\r\n|\r|\n/); 
				ldata.success = false;
				ldata.status = "error";
				ldata.message = "";
				logger(ldata, "wrn");
			}

			if (typeof notify == "function") {
				logger("apiSvc.call(): calling notifier", "dbg");
				notify(ldata);
			}
		});
	};
} ]);
app.controller('AboutCtrl', [ "$scope", function($scope) {
	$scope.app_id = app_id;
	$scope.build_date = build_date;
	$scope.api_build_date = api_build_date;
	$scope.app_version = app_version;
} ]);
app.controller('ComingSoonCtrl', [ "$scope", function($scope) {
	$scope.title="Coming soon";
} ]);
app.controller('ConfigCtrl', [ "$scope", function($scope) {
	$scope.title="Cofiguration";
} ]);
app.controller('FooterCtrl', [ "$scope", function($scope) {
	// This is only used to update the copyright year to "this year". Massive overkill.
	$scope.nowDate = Date.now();
} ]);
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