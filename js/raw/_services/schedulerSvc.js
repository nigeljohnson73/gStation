/*
           _              _       _           _____          
          | |            | |     | |         / ____|         
  ___  ___| |__   ___  __| |_   _| | ___ _ _| (_____   _____ 
 / __|/ __| '_ \ / _ \/ _` | | | | |/ _ \ '__\___ \ \ / / __|
 \__ \ (__| | | |  __/ (_| | |_| | |  __/ |  ____) \ V / (__ 
 |___/\___|_| |_|\___|\__,_|\__,_|_|\___|_| |_____/ \_/ \___|

 */
app.service('schedulerSvc', [ "$interval", function($interval) {
	schedulerSvc = this; // cuz "this" changes later
	schedulerSvc._queue = []; // holds the call queue

} ]);
