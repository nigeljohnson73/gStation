
/*
  _   _	     _	                  __                  _   _				 
 | | | | ___| |_ __   ___ _ __   / _|_   _ _ __   ___| |_(_) ___  _ __  ___ 
 | |_| |/ _ \ | '_ \ / _ \ '__| | |_| | | | '_ \ / __| __| |/ _ \| '_ \/ __|
 |  _  |  __/ | |_) |  __/ |	|  _| |_| | | | | (__| |_| | (_) | | | \__ \
 |_| |_|\___|_| .__/ \___|_|	|_|  \__,_|_| |_|\___|\__|_|\___/|_| |_|___/
              |_|														   
 */

// Returns the data element of an object array keyed by the name parameter
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

var millisecondsToMidnight = function() {
	var now = new Date();
	var then = new Date(now);
	then.setHours(24, 0, 0, 0);
	return (then - now);
}

var lpad = function(n, width, z) {
	z = z || '0';
	n = n + '';
	return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
};

hours2Hm = function(num) {
	num = num * 60;
	var hours = Math.floor(num / 60);
	var minutes = num % 60;
	return lpad("" + hours, 2) + ":" + lpad("" + minutes, 2);
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

logger = function(l, err) {
	if (!err)
		err = "inf";

	//msg = moment().format("YYYY-MM-DD HH:mm:ss") + "| " + l;
	msg = moment().format("HH:mm:ss") + "| " + l;
	if (err == "dbg") {
		console.debug(msg);
	}
	if (err == "inf") {
		console.log(msg);
	}
	if (err == "wrn") {
		console.warn(msg);
	}
	if (err == "err") {
		console.error(msg);
	}
};

logObj = function(msg, err) {
	if (!err)
		err = "inf";

	if (err == "dbg") {
		console.debug(msg);
	}
	if (err == "inf") {
		console.log(msg);
	}
	if (err == "wrn") {
		console.warn(msg);
	}
	if (err == "err") {
		console.error(msg);
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
	$("#snackbar").html(text);

	if (!$("#snackbar").hasClass("show")) {
		$("#snackbar").addClass("show");
	}

	// After 3 seconds, remove the show class from DIV
	if (toastTimeout === null) {
		toastTimeout = setTimeout(function() {
			if (toastTimeout) {
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
// When the ticker comes back every 5 seconds wrap the adding of asensor
// reading with the timestamp from now.
var addSensorReading = function(arr, sensor, value) {
	return addSensorReadingWithDate(arr, sensor, value, new Date());
};

// Add a sensor reading in the scatter graph
var addSensorReadingWithDate = function(arr, sensor, value, dte) {
	found = false;
	if (value == undefined) {
		return false;
	}

	angular.forEach(arr, function(item, index) {
		if (item.name == sensor.name) {
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
	});

	if (!found) {
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

	return true;
};

// Creates the graphs used for the model that cover a year with each item being
// a day
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

// Used for graphs that are driven from the server tick
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
				logObj(ldata, "wrn");
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
				logObj(ldata, "wrn");
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
	$scope.title = "Home Control";
	$scope.loading = true;

	// Storage for the API queue
	$scope.api_calls = [];

	// Storage for graphing data
	$scope.sensor_temperature = [];
	$scope.sensor_humidity = [];
	$scope.server_temperature = [];
	$scope.server_cpu_load = [];
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

	var checkGraph = function(g, id, src, title, units) {
		ret = g;
		if (g) {
			g.update();
		} else {
			ret = createMinuteGraph(id, src, title, units);
		}
		return ret;
	};
	/***************************************************************************
	 * The heartbeat handler is called every 5 seconds and requires some
	 * significant processing to pull out all the data
	 */
	var getEnv = function() {
		apiSvc.call("getEnv", {}, function(data) {
			logger("HomeCtrl::handleGetEnv()", "dbg");
			logObj(data, "inf");
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
				processLoad($scope.history.server_temperature, $scope.server_temperature, obj, obj.temperature);
				processLoad($scope.history.server_cpu_load, $scope.server_cpu_load, obj, obj.cpu_load);
				processLoad($scope.history.server_mem_load, $scope.server_mem_load, obj, obj.mem_load);
				processLoad($scope.history.server_hdd_load, $scope.server_hdd_load, obj, obj.sd_load);

				// Reset the history data
				$scope.history = {};

				// Perform a tidy up so we don't have anything prior to 24 hours
				// ago
				tidyData($scope.sensor_temperature);
				tidyData($scope.sensor_humidity);
				tidyData($scope.server_temperature);
				tidyData($scope.server_cpu_load);
				tidyData($scope.server_mem_load);
				tidyData($scope.server_hdd_load);

				$scope.sensor_temperature_graph = checkGraph($scope.sensor_temperature_graph, '#sensor-temperature-graph', $scope.sensor_temperature, "Temperature", "°C");
				$scope.sensor_humidity_graph = checkGraph($scope.sensor_humidity_graph, '#sensor-humidity-graph', $scope.sensor_humidity, "Humidity", "%");
				$scope.server_temperature_graph = checkGraph($scope.server_temperature_graph, '#server-temperature-graph', $scope.server_temperature, "Temperature", "°C");
				$scope.server_cpu_load_graph = checkGraph($scope.server_cpu_load_graph, '#server-cpu_load-graph', $scope.server_cpu_load, "CPU Load", "%");
				$scope.server_mem_load_graph = checkGraph($scope.server_mem_load_graph, '#server-mem_load-graph', $scope.server_mem_load, "Memory Usage", "%");
				$scope.server_hdd_load_graph = checkGraph($scope.server_hdd_load_graph, '#server-hdd_load-graph', $scope.server_hdd_load, "Storage Usage", "%");

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

	/***************************************************************************
	 * Camera image updates every minutes so only refresh every 10 seconds
	 */
	var getSnapshotImage = function() {
		apiSvc.call("getSnapshotImage", {}, function(data) {
			logger("HomeCtrl::handleGetSnapshotImage()", "dbg");
			logObj(data, "dbg");
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

	/***************************************************************************
	 * API queue
	 */

	var getApiData = function(o) {
		var d = new Duration();
		apiSvc.call(o.api, {
			today : moment().format("MMDD")
		}, function(data) {
			logger(o.api + "(): Data transfer: " + d.prettyEnd());
			logObj(data, "dbg");
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
			// Reschedule adding this to the call stack at midnight
			if (o.requeue) {
				logger("Requeuing call to '" + o.api + "'", "dbg")
				$timeout(function() {
					$scope.api_calls.push(o);
				}, Math.max(millisecondsToMidnight(), 5 * 60 * 1000));
			} else {
				logger("Call to '" + o.api + "' singleshot - no requeue", "dbg")
			}

		}, true); // do post so response is not cached
	};

	var processApiCalls = function(ms) {
		logger("processApiCalls(): called", "dbg");
		if (ms == undefined) {
			ms = 2000;
		}
		// Chain the calls in the return from one, start the next
		data = $scope.api_calls.shift();
		if (data) {
			logger("Calling history retrieval with " + ms + "ms delay", "dbg");
			$scope.history_api_call = $timeout(getApiData, ms, true, data);
		} else {
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
		api : "history/getServerTemperature",
		requeue : false,
		success : function(data) {
			$scope.history.server_temperature = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getServerCpuload",
		requeue : false,
		success : function(data) {
			$scope.history.server_cpu_load = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getServerMemoryload",
		requeue : false,
		success : function(data) {
			$scope.history.server_mem_load = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getServerHddload",
		requeue : false,
		success : function(data) {
			$scope.history.server_hdd_load = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getSensorTemperature",
		requeue : false,
		success : function(data) {
			$scope.history.sensor_temperature = data.history;
		}
	});

	$scope.api_calls.push({
		api : "history/getSensorHumidity",
		requeue : false,
		success : function(data) {
			$scope.history.sensor_humidity = data.history;
		}
	});

	// Start the history data chain
	$scope.history_api_call = $timeout(processApiCalls, 1000, true, 100);
} ]);