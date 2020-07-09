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
