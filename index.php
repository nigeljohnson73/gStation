<?php
include_once (dirname ( __FILE__ ) . "/functions.php");
setupTables ();
$fdate = newestFile ( dirname ( __FILE__ ) . "/." );
$jsdate = newestFile ( dirname ( __FILE__ ) . "/js" );
$cssdate = newestFile ( dirname ( __FILE__ ) . "/css" );
$apidate = newestFile ( dirname ( __FILE__ ) . "/api" );
?>
<!doctype html>
<html>
<head>
<base href="/">

<title><?php echo $loc - $app_title ?></title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

<link rel="icon" type="image/png" href="gfx/rhino.png?<?php echo $fdate[0] ?>" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato&display=swap" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css" />
<link rel="stylesheet" href="css/app.<?php stylesheetPayload() ?>.css?<?php echo $cssdate[0] ?>" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.8/angular.min.js"></script>
<script src='https://ajax.googleapis.com/ajax/libs/angularjs/1.7.8/angular-route.min.js'></script>
<script src='js/app.<?php javascriptPayload() ?>.js?<?php echo $jsdate[0] ?>'></script>

<script>
log_to_console = 2; // Info messages and worse
var app_id = '<?php echo getAppId() ?>';
var build_date = '<?php echo date("Y/m/d H:i:s", $fdate[0]) ?>';
var app_version = '<?php echo getAppVersion() ?>';
var api_build_date = '<?php echo date("Y/m/d H:i:s", $apidate[0]) ?>';
var api_build_date_raw = '<?php echo $apidate[0] ?>';
</script>
</head>
<body id="njp" data-ng-app="myApp">
	<div id="page-loading">
		<img src="/gfx/ajax-loader-bar.gif" alt="Page loading" />
		<p>Please wait while the page loads...</p>
	</div>
	<div id="page-loaded" data-ng-view></div>
	<div id="snackbar"></div>
</body>
</html>