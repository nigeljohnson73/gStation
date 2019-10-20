<?php
include_once ("functions.php");
?>
<!doctype html>
<html ng-app>
<head>
<title><?php echo $app_title ?> - DEV local temperatures</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="icon" href="gfx/rhino.png">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
<link rel="stylesheet" href="css/app.<?php stylesheetPayload() ?>.css">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.8/angular.min.js"></script>
<script src='js/app.<?php javascriptPayload() ?>.js'></script>
</head>
<body>
	<pre><?php
	
	$res = $mysql->query ( "SELECT * FROM temperature_logger where temperature != 999999 and demanded != 999999" );
	echo "# Rows retrieved: ".count($res)."\n";
	echo "DELETE FROM temperature_logger;\n";
	// echo "Local temp count: ".count($res)."\n";
	// var_dump($res);
	if (is_array ( $res ) && count ( $res ) > 0) {
		foreach ( $res as $r ) {
			echo "INSERT INTO temperature_logger (entered, demanded, temperature) VALUES ('".$r["entered"]."',".$r["demanded"].",".$r["temperature"].");\n";
		}
	}
	
	echo "SELECT count(*) AS LOADED FROM temperature_logger;\n\n";
	
	?></pre>
</body>
</html>