<?php

class JsonResponse {

	function __construct() {
		$this->message = "";
		$this->console = "";
	}
}

function startJsonRespose() {
	// global $database_domain;
	ob_start ();
	$ret = new JsonResponse ();
	return $ret;
}

function endJsonRespose($ret, $success = true) {
	$ret->console = trim ( ob_get_contents () );
	if (strlen ( $ret->console ) && strpos ( $ret->console, "\n" ) !== FALSE) {
		$ret->console = explode ( "\n", $ret->console );
	}
	ob_end_clean ();

	$ret->success = $success;
	if (! isset ( $ret->status )) {
		$ret->status = ($success ? "ok" : "error");
	}
	$json = json_encode ( $ret );

	if (json_last_error () !== JSON_ERROR_NONE) {
		$json = json_encode ( array (
				"success" => false,
				"status" => "error",
				"message" => json_last_error_msg (),
				"console" => explode ( "\n", utf8_encode ( ob_print_r ( $ret ) ) )
		) );
	}
	header ( 'Content-type: application/json; charset=UTF-8' );
	echo $json;
	die ();
}
?>