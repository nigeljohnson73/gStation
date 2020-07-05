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
		ret = "";

		const ms2h = 1/(60*60*1000);
		const ms2m = 1/(60*1000);
		const ms2s = 1/(1000);
		
		var ms = this.end();
		var h = Math.floor( ms * ms2h );
		ms -= h / ms2h;
		var m = Math.floor( ms * ms2m );
		ms -= m / ms2m;
		var s = Math.floor( ms * ms2s );
		ms -= s / ms2s;
		
		ret += (h>0)?(h+"h"):("");
		ret += ((ret.length>0)?(" "):("")) + ((m>0 || ret.length)?(m+"m"):(""));
		ret += ((ret.length>0)?(" "):("")) + ((s>0 || ret.length)?(s+"m"):(""));
		ret += ((ret.length>0)?(" "):("")) + ms+"ms";
		return ret;
	};
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
	var n = !isFinite(+number) ? 0 : +number, prec = !isFinite(+decimals) ? 0
			: Math.abs(decimals), sep = (typeof thousands_sep === 'undefined') ? ','
			: thousands_sep, dec = (typeof dec_point === 'undefined') ? '.'
			: dec_point, s = '', toFixedFix = function(n, prec) {
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
	var uc = [ 'A', 'B', 'C', 'E', 'F', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'R',
			'T', 'W', 'Y', 'Z' ];
	var lc = [ 'a', 'b', 'd', 'e', 'g', 'h', 'k', 'n', 'p', 'q', 'r', 's', 't',
			'x', 'y', 'z' ];
	var nc = [ '2', '3', '4', '5', '6', '7', '8', '9' ];
	var sc = [ '=', '-', '.', '_', '@' ];
	var an = [].concat(uc).concat(lc).concat(nc);
	var ny = [].concat(sc).concat(an);
	return key.replace(/[xlunas]/g, function(c) {
		return (c === 'u' ? uc.random() : (c === 'l' ? lc.random()
				: (c === 'n' ? nc.random() : (c === 's' ? sc.random()
						: (c === 'a' ? an.random() : ny.random())))));
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
			output = output + this._keyStr.charAt(enc1)
					+ this._keyStr.charAt(enc2) + this._keyStr.charAt(enc3)
					+ this._keyStr.charAt(enc4);

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
				string += String.fromCharCode(((c & 15) << 12)
						| ((c2 & 63) << 6) | (c3 & 63));
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

app.config([ "$locationProvider", "$routeProvider",
		function($locationProvider, $routeProvider) {
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