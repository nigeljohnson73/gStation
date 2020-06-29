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

		xxlogger("apiSvc.call('" + api + "', '" + method + "')");
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
			xxlogger("apiSvc.call(): success");
			//console.log(data);
			data = data.data; // http response object returned, strip out the server response

			if (typeof notify == "function") {
				xxlogger("apiSvc.call(): calling notifier");
				notify(data);
			}
		}, function(data) {
			xxlogger("apiSvc.call(): failed");
			//console.log(data);
			ldata = {};
			if (data.status != 200) {
				xxlogger("apiSvc.call(): creating error data object");
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
