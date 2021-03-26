<?php

function raw_send_sms($tag, $message, $recip, $route) {
	$sms = new BulkSms ();
	$ret = true;

	$credit = 0;
	if ($sms->get_credits () == SUCCESS) {
		$credit = trim ( $sms->get_response () );
	}

	foreach ( $recip as $r ) {
		$vars = array (
				"message" => $message,
				"msisdn" => $r,
				"sender" => $tag,
				"repliable" => 0,
				"routing_group" => $route,
				"allow_concat_text_sms" => 1,
				"concat_text_sms_max_parts" => 5
		);
		if ($sms->send_sms ( $vars ) == SUCCESS) {
			logger ( LL_INFO, "Sent SMS to '$r' from '$tag'" );
		} else {
			$ret = false;
			logger ( LL_ERROR, "BulkSMS: There was an error sending text to '" . $r . "': " . trim ( $sms->get_status () ) );
		}
	}
	if ($sms->get_credits () == SUCCESS) {
		$o = $credit;
		$credit = trim ( $sms->get_response () );
		$cost = $o - $credit;
		if ($cost > 0) {
			$remain = floor ( $credit / $cost );
		} else {
			$remain = $credit;
		}
		logger ( LL_SYS, "BulkSMS: remaining credit: " . $credit . " (Transmit cost: " . $cost . ", remaining transmits: " . $remain . ")" );
	} else {
		logger ( LL_ERROR, "BulkSMS: An error occurred getting balance: " . trim ( $sms->get_status () ) );
	}
	return $ret;
}

function sendSmsTo($message, $recip, $route = 2) {
	global $bulksms_username, $loc;
	if ($bulksms_username == "") {
		logger ( LL_INFO, "BulkSMS: Not enabled" );
		return null;
	}

	if (! is_array ( $recip )) {
		$recip = array (
				$recip
		);
	}

	// global $bulksms_sender;
	// global $bulksms_notify;
	// // global $bulksms_low_warning;
	// $bulksms_tag = $bulksms_sender;
	// $owner_sms = $bulksms_notify;
	// // $low_warning = $bulksms_low_warning; // tParameters::get ( "BULK_SMS_LOW_WARNING" );
	// $ret = true;

	// $sms = new BulkSms ();

	// $credit = 0;
	// if ($sms->get_credits () == SUCCESS) {
	// $credit = trim ( $sms->get_response () );
	// } else {
	// logger ( LL_SYS, "BulkSMS: failed to get credits:\n" . $sms->_debug );
	// return false;
	// }

	// Not required since Bulksms will do the alerting based on profile
	// if ($credit <= $low_warning) {
	// logger ( LL_SYS, "BulkSMS: paging owner about low credit level" );
	// raw_send_sms ( $bulksms_tag, "BulkSMS credit low (" . $credit . ")", $owner_sms, $route );
	// if ($sms->get_credits () == SUCCESS) {
	// $credit = trim ( $sms->get_response () );
	// }
	// }

	// if ($credit > 5) {
	global $bulksms_sender;
	$ret = raw_send_sms ( $bulksms_sender, $loc . ": " . $message, $recip, $route );
	// } else {
	// logger ( LL_ERROR, "BulkSMS: There were insufficient credits to send an SMS message" );
	// return false;
	// }
	return $ret;
}
?>