<?php include_once '_header.php';?>
<div class="container-fluid text-center">
	<!-- <h2>About this application</h2> -->
	<p>I'm still working out what this app is doing. It is intended for display purposes only and any percieved working ability is purely coincidental.</p>
	<div class="row">
		<div class="col-xs-1">&nbsp;</div>
		<div class="col-xs-5 about-label text-right">App ID:</div>
		<div class="col-xs-5 about-value text-left">{{app_version}}-dot-{{app_id}}</div>
		<div class="col-xs-1">&nbsp;</div>
	</div>
	<div class="row">
		<div class="col-xs-1">&nbsp;</div>
		<div class="col-xs-5 about-label text-right">App Build:</div>
		<div class="col-xs-5 about-value text-left">{{build_date}}</div>
		<div class="col-xs-1">&nbsp;</div>
	</div>
	<div class="row">
		<div class="col-xs-1">&nbsp;</div>
		<div class="col-xs-5 about-label text-right">API Build:</div>
		<div class="col-xs-5 about-value text-left">{{api_build_date}}</div>
		<div class="col-xs-1">&nbsp;</div>
	</div>
</div>
<?php include_once '_footer.php';?>