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
			if(isJson(data.data)) {
				// http response object returned, strip out the server response
				data = data.data;
			} else {
				logger("apiSvc.call(): malformed response, creating error data object", "wrn");
				ldata = {};
				// text in the console where you would expect some explanation
				ldata.console = data.data.split(/\r\n|\r|\n/); 
				ldata.success = false;
				ldata.status = "error";
				ldata.message = "";
				data = ldata;
				logger(data, "wrn");
			}

			if (typeof notify == "function") {
				logger("apiSvc.call(): calling notifier", "dbg");
				notify(data);
			}
		}, function(data) {
			logger("apiSvc.call(): failed", "err");
			//console.log(data);
			ldata = {};
			if (data.status != 200) {
				logger("apiSvc.call(): creating error data object", "wrn");
				// We probably got rubbish back, so create a pretified version
				ldata.success = false;
				ldata.status = "error";
				ldata.message = "The server failed to process the request (Err#" + data.status + ")";
				ldata.console = data.data; // allows you to see the error text in the console where you would expect some explanation
			} else {
				ldata = data.data;
			}

			if (typeof notify == "function") {
				notify(ldata);
			}
		});
	};
} ]);
