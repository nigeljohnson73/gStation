<?php
/************************************************************\
 * php-bulksms version 1.0, modified 28-Aug-05
 * By Liam Hatton
 * liam@hatton.name http://dl.liam.hatton.name/
 /************************************************************\
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software
 * Foundation; either version 2.1 of the  License, or (at your
 * option) any later version.
 *
 * This library is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 * PURPOSE.  See the GNU Lesser General Public License for
 * more details.
 *
 * You should have received a copy of the GNU Lesser General
 * Public License along with this library; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330,
 * Boston, MA  02111-1307 USA
 /************************************************************\
 * Please see readme.htm for more information and documentation.
 * If your copy did not come with this file, please download the
 * original at: http://dl.liam.hatton.name
 /************************************************************\
 * PLEASE CHANGE THE FOLLOWING BEFORE RUNNING THIS SCRIPT.
 \************************************************************/

// BulkSMS account details
// define('BULKSMS_USERNAME', 'sappsys');
// define('BULKSMS_PASSWORD', 'Aj1Mar05');

// You need to uncomment the relevant line for the country
// your BulkSMS account is registered in. If you select
// the wrong one, your username and password will not
// work.

// International (for all other countries):
// define('BULKSMS_HOST','bulksms.vsms.net');

// UK:
define ( 'BULKSMS_HOST', 'www.bulksms.co.uk' );

// USA:
// define('BULKSMS_HOST','usa.bulksms.com');

// South Africa:
// define('BULKSMS_HOST','bulksms.2way.co.za');

// Spain:
// define('BULKSMS_HOST','bulksms.com.es');

/**
 * **********************************************************\
 * Optional parameters, do not need to be changed.
 * \***********************************************************
 */

// Your country code (for number formatting functions), leave
// as 0 if you do not want to specify this.
define ( 'COUNTRY_CODE', '0' );

// Set this option to true if you want to send requests
// to the EAPI using port 80 instead of 5567/7512. You may
// find it necessary to set this if you are behind a firewall
// that blocks outgoing connections using non-standard ports.
// It is best to leave this alone unless you absolutely need
// this feature, because non-standard ports are used to avoid
// transparent proxies (which can cause lots of problems with
// the EAPI).
define ( 'USE_PORT_80', false );

/**
 * **********************************************************\
 * CODE STARTS HERE
 * NOTHING CAN BE EDITED BEYOND HERE.
 * \***********************************************************
 */
// Error code consonants
define ( 'SUCCESS', 1 );
define ( 'FATAL', - 1 );
define ( 'RETRY', - 2 );
define ( 'INPUT_ERR', - 3 );
define ( 'NO_MATCH', - 66 );

// Incoming message type code consonants
define ( 'AUTO', 0 );
define ( 'STATUS', 1 );
define ( 'INBOX', 2 );

// EAPI status codes
define ( 'EAPI_IN_PROGRESS', 0 );
define ( 'EAPI_SUCCESS', 0 );
define ( 'EAPI_SCHEDULED', 1 );
define ( 'EAPI_DELIVERED_UPSTREAM', 10 );
define ( 'EAPI_DELIVERED_TO_MOBILE', 11 );
define ( 'EAPI_UPSTREAM_UNACK', 12 );
define ( 'ERR_EAPI_FATAL', 22 );
define ( 'ERR_EAPI_AUTH_ERR', 23 );
define ( 'ERR_EAPI_INPUT_ERR', 24 );
define ( 'ERR_EAPI_NO_CREDITS', 25 );
define ( 'ERR_EAPI_NO_UP_CREDITS', 26 );
define ( 'ERR_EAPI_EXCEEDED_QUOTA', 27 );
define ( 'ERR_EAPI_EXCEEDED_UP_QUOTA', 28 );
define ( 'ERR_EAPI_SENDING_CANCELLED', 29 );
define ( 'ERR_EAPI_UNAVAIL', 40 );
define ( 'ERR_EAPI_DELIVERY_FAIL', 50 );
define ( 'ERR_EAPI_DELIVERY_PHONE_FAIL', 51 );
define ( 'ERR_EAPI_DELIVERY_NET_FAIL', 52 );
define ( 'ERR_EAPI_TRANSIENT_UP_FAIL', 60 );
define ( 'EAPI_UPSTREAM_STATUS_UPDATE', 61 );
define ( 'ERR_EAPI_UPSTREAM_STATUS_CANCEL', 62 );
define ( 'ERR_EAPI_MSG_EXPIRED', 70 );
define ( 'ERR_EAPI_UNKNOWN', 70 );
define ( 'NO_EAPI_STATUS_CODE', - 99 );

class BulkSms {
	var $_handler;
	var $_response;
	var $_eapi_status_code;
	var $_eapi_status_msg;
	var $_batch_id;
	var $_debug;
	var $_quotation;
	var $_num_list = array ();
	var $_queue = array ();
	var $_queue_id = 0;
	var $_incoming = array ();

	/**
	 * **********************************************************\
	 * SEND_SMS: Send a short text message via BulkSMS's eapi.
	 * \***********************************************************
	 */
	function send_sms($vars, $quote = false) {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$this->_push_debug_msg ( "called send_sms" );
		$host = BULKSMS_HOST . ":5567";
		if ($quote == true) {
			$this->_push_debug_msg ( "instructed to quote_sms" );
			$uri = "/eapi/submission/quote_sms/2/2.0";
		} else
			$uri = "/eapi/submission/send_sms/2/2.0";

		global $bulksms_username;
		global $bulksms_password;

		unset ( $vars ["username"] );
		unset ( $vars ["password"] );
		$vars2 = array (
				"username" => $bulksms_username,
				"password" => $bulksms_password
		);
		$vars = array_merge ( $vars2, $vars );
		unset ( $vars2 );
		if ($vars ["message"] == NULL || ($vars ["msisdn"] == NULL && $vars ["dest_group_id"] == NULL)) {
			$this->_push_debug_msg ( "missing required fields" );
			$this->_handler = INPUT_ERR;
			return $this->get_status ();
		}
		$response = $this->_post_eapi ( $host, $uri, $vars );
		if (! $response)
			return $this->get_status ();
		$this->_push_debug_msg ( "sent to _post_eapi without errors" );
		$this->_parse_eapi_status ( $response );
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * GET_CREDITS: Get number of credits remaining in your
	 * account.
	 * \***********************************************************
	 */
	function get_credits() {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$host = BULKSMS_HOST . ":7512";
		$uri = "/eapi/1.0/get_credits.mc";
		$this->_push_debug_msg ( "called get_credits" );

		global $bulksms_username;
		global $bulksms_password;

		$vars = array (
				"username" => $bulksms_username,
				"password" => $bulksms_password
		);
		logger ( LL_DEBUG, "get_credits():\n" . ob_print_r ( $vars ) );

		$response = $this->_post_eapi ( $host, $uri, $vars );
		if (! $response)
			return $this->get_status ();
		$this->_push_debug_msg ( "sent to _post_eapi without errors" );
		if (count ( $response ) == 1) {
			$this->_push_debug_msg ( "we got a response: " . $response [0] );
			$this->_response = $response [0];
			$this->_handler = SUCCESS;
		} else {
			$this->_push_debug_msg ( "we got something else, send to _parse_eapi_status" );
			$this->_parse_eapi_status ( $response );
		}
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * GET_STATUS_REPORT: Get status information for a particular
	 * message from BulkSMS.
	 * \***********************************************************
	 */
	function get_status_report($vars) {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$host = BULKSMS_HOST . ":5567";
		$uri = "/eapi/status_reports/get_report/2/2.0";
		$this->_push_debug_msg ( "called get_status_report" );

		global $bulksms_username;
		global $bulksms_password;

		unset ( $vars ["username"] );
		unset ( $vars ["password"] );
		$vars2 = array (
				"username" => $bulksms_username,
				"password" => $bulksms_password
		);

		$vars = array_merge ( $vars2, $vars );
		unset ( $vars2 );
		$retr = $this->_post_eapi ( $host, $uri, $vars );
		if (count ( $retr ) == 0)
			return $this->get_status ();
		if (! $retr [0] [0] == 0) {
			$this->_push_debug_msg ( "we got an error, send to _parse_eapi_status" );
			$this->_parse_eapi_status ( $retr );
			return $this->get_status ();
		}
		;
		$this->_parse_eapi_status ( $retr [0] );
		unset ( $retr [0] );
		$this->_response = $retr;
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * GET_INBOX: Get your BulkSMS inbox.
	 * \***********************************************************
	 */
	function get_inbox($vars = array (
			"last_retrieved_id" => 0
	)) {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$host = BULKSMS_HOST . ":5567";
		$uri = "/eapi/reception/get_inbox/1/1.0";
		$this->_push_debug_msg ( "called get_inbox" );
		unset ( $vars ["username"] );

		global $bulksms_username;
		global $bulksms_password;

		unset ( $vars ["username"] );
		unset ( $vars ["password"] );
		$vars2 = array (
				"username" => $bulksms_username,
				"password" => $bulksms_password
		);

		$vars = array_merge ( $vars2, $vars );
		unset ( $vars2 );
		$retr = $this->_post_eapi ( $host, $uri, $vars );
		if (count ( $retr ) == 0)
			return $this->get_status ();
		if ((! $retr [0] [0] == 0)) {
			$this->_push_debug_msg ( "we got an error, send to _parse_eapi_status" );
			$this->_parse_eapi_status ( $retr );
			return $this->get_status ();
		}
		;
		$this->_parse_eapi_status ( $retr [0] );
		if ($retr [0] [2] == "0") {
			$this->_push_debug_msg ( "we got no records" );
		} else {
			unset ( $retr [0] );
			$this->_response = $retr;
		}
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * GET_NUMBER_LIST: Returns your phone number list in an
	 * array.
	 * \***********************************************************
	 */
	function get_number_list() {
		$this->_push_debug_msg ( "called get_number_list" );
		return $this->_num_list;
	}

	/**
	 * **********************************************************\
	 * ADD_NUMBER_TO_LIST: Add number to the list.
	 * \***********************************************************
	 */
	function add_number_to_list($num) {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$this->_push_debug_msg ( "called add_number_to_list with num: " . $num );
		array_push ( $this->_num_list, $num );
		$this->_handler = SUCCESS;
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * DEL_NUMBER_FROM_LIST: Delete number from the list.
	 * \***********************************************************
	 */
	function del_number_from_list($num) {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$this->_push_debug_msg ( "called del_number_from_list with num: " . $num );
		$key = array_search ( $num, $this->_num_list );
		if ($key === false) {
			$this->_push_debug_msg ( "this number is not in the list" );
			$this->_handler = INPUT_ERR;
			return $this->get_status ();
		}
		unset ( $this->_num_list [$key] );
		$this->_handler = SUCCESS;
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * CLEAR_LIST: Clears the list.
	 * \***********************************************************
	 */
	function clear_list() {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$this->_push_debug_msg ( "called clear_list" );
		unset ( $this->_num_list );
		$this->_num_list = array ();
		$this->_handler = SUCCESS;
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * SEND_TO_LIST: Send an SMS to the list.
	 * \***********************************************************
	 */
	function send_to_list($vars, $quote = false, $remove_dups = false, $split = NULL) {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$this->_push_debug_msg ( "called send_to_list" );
		unset ( $vars ["username"] );
		unset ( $vars ["password"] );
		if ($remove_dups == true) {
			$this->_push_debug_msg ( "removing duplicate numbers" );
			$this->_num_list = array_unique ( $this->_num_list );
		}
		if (count ( $this->_num_list ) > 5000) {
			$this->_push_debug_msg ( "list is too big (above 5000), so we need to split it" );
			$split = 5000;
		}
		if (! $split == NULL) {
			$this->_push_debug_msg ( "splitting arrays into chunks of " . $split );
			$number_lists = array_chunk ( $this->_num_list, $split );
			$status = array ();
			$eapi_status_code = array ();
			$eapi_status_msg = array ();
			$quotation = array ();
			$batch_id = array ();
			$this->_push_debug_msg ( "processing number of chunks: " . count ( $number_lists ) );
			foreach ( $number_lists as $number_list ) {
				$var = $vars;
				$var ["msisdn"] = implode ( ',', $number_list );
				$this->send_sms ( $var, $quote );
				array_push ( $status, $this->_handler );
				array_push ( $eapi_status_code, $this->_eapi_status_code );
				array_push ( $eapi_status_msg, $this->_eapi_status_msg );
				array_push ( $quotation, $this->_quotation );
				array_push ( $batch_id, $this->_batch_id );
			}
			$this->_handler = $status;
			$this->_eapi_status_code = $eapi_status_code;
			$this->_eapi_status_msg = $eapi_status_msg;
			$this->_quotation = $quotation;
			$this->_batch_id = $batch_id;
			return $this->get_status ();
		} else {
			$this->_push_debug_msg ( "processing number list of: " . count ( $this->_num_list ) );
			$vars ["msisdn"] = implode ( ',', $this->_num_list );
			return $this->send_sms ( $vars, $quote );
		}
	}

	/**
	 * **********************************************************\
	 * ADD_TO_PUBLIC_GROUP: Adds a phone number to a public group.
	 * \***********************************************************
	 */
	function add_to_public_group($group_id, $msisdn, $firstname = NULL, $lastname = NULL, $fixnumber = true) {
		$this->_push_debug_msg ( "called add_to_public_group" );
		$hack_success_url = "http://success-add-to-group.localhost/";
		$hack_failure_url = "http://failure-add-to-group.localhost/";
		$host = BULKSMS_HOST . ":5567";
		$uri = "/eapi/1.0/phonebook/public_add_member";
		if ($group_id == "" || $msisdn == "") {
			$this->_push_debug_msg ( "missing required fields, returning input_err" );
			return INPUT_ERR;
		}
		if ($fixnumber == true) {
			$this->_push_debug_msg ( "fixnumber set to true" );
			$msisdn = $this->fix_number ( $msisdn );
		} else {
			$this->_push_debug_msg ( "fixnumber set to false" );
			if ($this->check_number ( $msisdn ) == false) {
				$this->_push_debug_msg ( "number invalid, returning input_err" );
				return INPUT_ERR;
			} else
				$this->_push_debug_msg ( "number okay" );
		}
		$vars = array (
				"group_id" => $group_id,
				"msisdn" => $msisdn,
				"given_name" => $firstname,
				"surname" => $lastname,
				"success_url" => $hack_success_url,
				"fail_url" => $hack_failure_url
		);
		$this->_post_public_group ( $host, $uri, $vars );
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * DEL_FROM_PUBLIC_GROUP: Removes a phone number from a public
	 * group.
	 * \***********************************************************
	 */
	function del_from_public_group($group_id, $msisdn, $fixnumber = true) {
		$this->_push_debug_msg ( "called del_from_public_group" );
		$hack_success_url = "http://success-add-to-group.localhost/";
		$hack_failure_url = "http://failure-add-to-group.localhost/";
		$host = BULKSMS_HOST . ":5567";
		$uri = "/eapi/1.0/phonebook/public_remove_member";
		if ($group_id == "" || $msisdn == "") {
			$this->_push_debug_msg ( "missing required fields, returning input_err" );
			return INPUT_ERR;
		}
		if ($fixnumber == true) {
			$this->_push_debug_msg ( "fixnumber set to true" );
			$msisdn = $this->fix_number ( $msisdn );
		} else {
			$this->_push_debug_msg ( "fixnumber set to false" );
			if ($this->check_number ( $msisdn ) == false) {
				$this->_push_debug_msg ( "number invalid, returning input_err" );
				return INPUT_ERR;
			} else
				$this->_push_debug_msg ( "number okay" );
		}
		$vars = array (
				"group_id" => $group_id,
				"msisdn" => $msisdn,
				"success_url" => $hack_success_url,
				"fail_url" => $hack_failure_url
		);
		$this->_post_public_group ( $host, $uri, $vars );
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * LIST2GROUP: Exports number list to a public group.
	 * \***********************************************************
	 */
	function list2group($group_id) {
		$this->_push_debug_msg ( "called list2group with: " . $group_id );
		if (count ( $this->_num_list ) == 0) {
			$this->_push_debug_msg ( "empty list, returning INPUT_ERR" );
			return INPUT_ERR;
		}
		$list = $this->_num_list;
		$this->_push_debug_msg ( "removing duplicate numbers" );
		$this->_num_list = array_unique ( $list );
		$status = array ();
		foreach ( $list as $i ) {
			$this->_push_debug_msg ( "running iteration of add_to_public_group for: " . $i );
			$this->add_to_public_group ( $group_id, $i, NULL, NULL, false );
			array_push ( $status, $this->_handler );
		}
		$this->_handler = $status;
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * PUSH_SMS_TO_QUEUE: Add a message to the queue.
	 * Returns a
	 * unique ID.
	 * \***********************************************************
	 */
	function push_sms_to_queue($vars) {
		$this->_push_debug_msg ( "called push_sms_to_queue" );
		unset ( $vars ["username"] );
		unset ( $vars ["password"] );
		$this->_queue_id ++;
		$vars ["id"] = $this->_queue_id;
		array_push ( $this->_queue, $vars );
		$this->_handler = SUCCESS;
		$this->_push_debug_msg ( "returning id: " . $vars ["id"] );
		return $vars ["id"];
	}

	/**
	 * **********************************************************\
	 * DEL_SMS_FROM_QUEUE: Deletes a message from the queue,
	 * defined by the ID returned by push_sms_to_queue.
	 * \***********************************************************
	 */
	function del_sms_from_queue($id) {
		$this->_push_debug_msg ( "called del_sms_from_queue with id: " . $id );
		for($i = 0; $i < count ( $this->_queue ); $i ++) {
			if ($this->_queue [$i] ["id"] == $id) {
				$key = $i;
				break;
			}
		}
		if (isset ( $key ) == false) {
			$this->_push_debug_msg ( "this id is not in the list" );
			$this->_handler = INPUT_ERR;
			return $this->get_status ();
		}
		unset ( $this->_queue [$key] );
		$this->_handler = SUCCESS;
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * CLEAR_QUEUE: Erases SMS queue.
	 * \***********************************************************
	 */
	function clear_queue() {
		$this->_push_debug_msg ( "called clear_queue" );
		$this->_queue = array ();
		$this->_handler = SUCCESS;
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * PROCESS_QUEUE: Processes SMS queue.
	 * \***********************************************************
	 */
	function process_queue($vars = NULL) {
		$this->_push_debug_msg ( "called process_queue" );
		$csv = $this->get_queue ();
		$csv = $this->_build_queue_csv ( $csv );
		if (strlen ( $csv ) == 0) {
			$this->_push_debug_msg ( "too short, perhaps no messages in queue" );
			$this->_handler = INPUT_ERR;
			return $this->get_status ();
		}
		$this->_push_debug_msg ( "generated csv: " . $csv );
		if ($vars != NULL) {
			$this->_push_debug_msg ( "extra vars: " . count ( $vars ) );
		}
		return $this->_process_csv_batch_sms ( $csv, $vars );
	}

	/**
	 * **********************************************************\
	 * GET_QUEUE: Returns SMS queue.
	 * \***********************************************************
	 */
	function get_queue() {
		$this->_push_debug_msg ( "called get_queue" );
		$this->_handler = SUCCESS;
		return $this->_queue;
	}

	/**
	 * **********************************************************\
	 * LOAD_INCOMING_VARS: Load an incoming object pushed to your
	 * server by BulkSMS.
	 * When you are using this in a script,
	 * you should not output anything.
	 * \***********************************************************
	 */
	function load_incoming_vars($password, $type = AUTO) {
		$this->_push_debug_msg ( "called load_incoming_vars" );

		// --- Found at http://au.php.net/header ---
		// Date in the past
		header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );

		// always modified
		header ( "Last-Modified: " . gmdate ( "D, d M Y H:i:s" ) . " GMT" );

		// HTTP/1.1
		header ( "Cache-Control: no-store, no-cache, must-revalidate" );
		header ( "Cache-Control: post-check=0, pre-check=0", false );

		// HTTP/1.0
		header ( "Pragma: no-cache" );
		// ------

		if (count ( $_GET ) == 0) {
			$this->_push_debug_msg ( "there are no HTTP get vars" );
			$this->_handler = FATAL;
			echo 0;
			return $this->get_status ();
		}
		if ($password != $_GET ['pass']) {
			$this->_push_debug_msg ( "the password does not match" );
			$this->_handler = FATAL;
			echo 0;
			return $this->get_status ();
		}
		if ($type == AUTO) {
			$this->_push_debug_msg ( "auto type guessing" );
			if ($_GET ['status'] == "") {
				if (! $_GET ['msisdn'] == "") {
					$this->_push_debug_msg ( "appears to be inbox: " . INBOX );
					$type = INBOX;
				} else {
					$this->_push_debug_msg ( "cannot recognise this message type" );
					$this->_handler = FATAL;
					echo 0;
					return $this->get_status ();
				}
			} else {
				$this->_push_debug_msg ( "appears to be status msg: " . STATUS );
				$type = STATUS;
			}
		} else
			$this->_push_debug_msg ( "status provided as code: " . $type );
		$arr = $_GET;
		unset ( $arr ['pass'] );
		switch ($type) {
			case INBOX :
				$arr ['incoming_type'] = INBOX;
				break;
			case STATUS :
				$arr ['incoming_type'] = STATUS;
				break;
		}
		$this->_incoming = $arr;
		$this->_handler = SUCCESS;
		echo 1;
		return $this->get_status ();
	}

	/**
	 * **********************************************************\
	 * GET_INCOMING_OBJ: Get incoming push object as array.
	 * \***********************************************************
	 */
	function get_incoming_obj() {
		$this->_push_debug_msg ( "called get_incoming_obj" );
		return $this->_incoming;
	}

	/**
	 * **********************************************************\
	 * GET_STATUS: Get script status information for an operation.
	 * \***********************************************************
	 */
	function get_status() {
		$this->_push_debug_msg ( "called get_status, returned: " . $this->_handler );
		return $this->_handler;
	}

	/**
	 * **********************************************************\
	 * GET_EAPI_STATUS_CODE: Get EAPI status code.
	 * \***********************************************************
	 */
	function get_eapi_status_code() {
		$this->_push_debug_msg ( "called get_eapi_status_code, returned: " . $this->_eapi_status_code );
		return $this->_eapi_status_code;
	}

	/**
	 * **********************************************************\
	 * GET_EAPI_STATUS_MSG: Get EAPI status message.
	 * \***********************************************************
	 */
	function get_eapi_status_msg() {
		$this->_push_debug_msg ( "called get_eapi_status_msg, returned: " . $this->_eapi_status_msg );
		return $this->_eapi_status_msg;
	}

	/**
	 * **********************************************************\
	 * GET_BATCH_ID: Get batch id for a message (sent using
	 * BulkSMS).
	 * \***********************************************************
	 */
	function get_batch_id() {
		$this->_push_debug_msg ( "called get_batch_id, returned: " . $this->_batch_id );
		return $this->_batch_id;
	}

	/**
	 * **********************************************************\
	 * GET_RESPONSE: Get response from BulkSMS.
	 * \***********************************************************
	 */
	function get_response() {
		$this->_push_debug_msg ( "called get_response, returned: " . $this->_response );
		return $this->_response;
	}

	/**
	 * **********************************************************\
	 * GET_QUOTATION: Get a quotation (price check) for sending
	 * an SMS (must use SendSMS with $quote = true first).
	 * \***********************************************************
	 */
	function get_quotation() {
		$this->_push_debug_msg ( "called get_quote, returned: " . $this->_quotation );
		return $this->_quotation;
	}

	/**
	 * **********************************************************\
	 * FIX_NUMBER: Formats a mobile phone number so it is in a
	 * suitable format, although it is best to require the user
	 * to provide the number in the correct format.
	 * \***********************************************************
	 */
	function fix_number($num, $tr_letters = true) {
		$this->_push_debug_msg ( "called fix_number with: " . $num );
		// List of common IDD codes used worldwide
		$idd = array (
				'0011',
				'011',
				'00'
		);
		// Start parsing now:
		// Strip out anything that isn't a letter or number
		$num = preg_replace ( '/[^\w]*/', '', $num );
		if ($tr_letters == true) {
			// Set to translate letters to numbers (default setting)
			$this->_push_debug_msg ( "translating letters to numbers" );
			// List of numbers associated to characters on a telephone keypad, we fix these for completeness.
			$letters = array (
					'A',
					'B',
					'C',
					'D',
					'E',
					'F',
					'G',
					'H',
					'I',
					'J',
					'K',
					'L',
					'M',
					'N',
					'O',
					'P',
					'Q',
					'R',
					'S',
					'T',
					'U',
					'V',
					'W',
					'X',
					'Y',
					'Z',
					'a',
					'b',
					'c',
					'd',
					'e',
					'f',
					'g',
					'h',
					'i',
					'j',
					'k',
					'l',
					'm',
					'n',
					'o',
					'p',
					'q',
					'r',
					's',
					't',
					'u',
					'v',
					'w',
					'x',
					'y',
					'z'
			);
			$new_letters = array (
					'2',
					'2',
					'2',
					'3',
					'3',
					'3',
					'4',
					'4',
					'4',
					'5',
					'5',
					'5',
					'6',
					'6',
					'6',
					'7',
					'7',
					'7',
					'7',
					'8',
					'8',
					'8',
					'9',
					'9',
					'9',
					'9',
					'2',
					'2',
					'2',
					'3',
					'3',
					'3',
					'4',
					'4',
					'4',
					'5',
					'5',
					'5',
					'6',
					'6',
					'6',
					'7',
					'7',
					'7',
					'7',
					'8',
					'8',
					'8',
					'9',
					'9',
					'9',
					'9'
			);
			// Replace letters with number equivalents
			$num = str_replace ( $letters, $new_letters, $num );
		} else {
			// Not set to translate letters to numbers
			if (preg_match ( '/\D/', $num )) {
				$this->_push_debug_msg ( "found a letter in the input, returning false" );
				return false;
			} else {
				$this->_push_debug_msg ( "no letters - looks okay" );
			}
		}
		// Strip out all IDD access codes
		foreach ( $idd as $code )
			$num = preg_replace ( '/\A' . $code . '/', '', $num );
		// fix leading 0
		if (! COUNTRY_CODE == 0)
			$num = preg_replace ( '/\A0/', COUNTRY_CODE, $num );
		// Return result
		$this->_push_debug_msg ( "fix_number returned: " . $num );
		return $num;
	}

	/**
	 * **********************************************************\
	 * CHECK_NUMBER: Check a number to see if it is in the correct
	 * format.
	 * \***********************************************************
	 */
	function check_number($num) {
		$this->_push_debug_msg ( "called check_number with: " . $num );
		if (preg_match ( '/\D/', $num ))
			return false;
		if (preg_match ( '/\A0/', $num ))
			return false;
		if (strlen ( $num ) < 8)
			return false;
		$this->_push_debug_msg ( "number is in correct format, returning true" );
		return true;
	}

	/**
	 * **********************************************************\
	 * INTERNAL FUNCTIONS, NOT TO BE ACCESSED DIRECTLY.
	 * \***********************************************************
	 */
	function _post_eapi($host, $uri, $vars) {
		$this->_push_debug_msg ( "called _post_eapi" );
		if ($host == NULL || $uri == NULL || $vars == NULL || BULKSMS_HOST == "BULKSMS_HOST") {
			if (! BULKSMS_HOST == "BULKSMS_HOST") {
				$this->_push_debug_msg ( "empty vars sent" );
			} else {
				$this->_push_debug_msg ( "please define country host at the top of this script" );
			}
			$this->_handler = FATAL;
			return NULL;
		}
		// include_once ('http.inc');
		$this->_push_debug_msg ( "posting to http" );
		$http = new http ();
		preg_match ( "/(.*):(\d*)/", $host, $u );
		$http->host = $u [1];
		if (USE_PORT_80 == true) {
			$http->port = 80;
		} else {
			$http->port = $u [2];
		}
		$this->_push_debug_msg ( "host: " . $http->host );
		$this->_push_debug_msg ( "port: " . $http->port );
		$status = $http->post ( $uri, $vars );
		if ($status == HTTP_STATUS_OK) {
			$response = $http->get_response_body ();
			$http->disconnect ();
			unset ( $http );
			$this->_push_debug_msg ( "received from bulksms: " . $response );
			if (preg_match ( '/^0\|Results to follow\n\n/', $response ) || preg_match ( '/^0\|records to follow\|\d*\n\n/', $response )) {
				$eapi1 = array ();
				$eapi2 = array ();
				preg_match ( '/^(.*)\n\n/', $response, $r );
				$eapi1 [0] = explode ( '|', $r [1] );
				$response = preg_replace ( "/^.*\n\n/", "", $response );
				$eapi2 = explode ( '\n', $response );
				foreach ( $eapi2 as $e ) {
					array_push ( $eapi1, explode ( '|', $e ) );
				}
				unset ( $eapi2 );
				$this->_push_debug_msg ( "sent to first eapi status parse handler" );
				return $eapi1;
			} else {
				$this->_push_debug_msg ( "sent to second eapi status parse handler" );
				return explode ( '|', $response );
			}
		} else {
			$http->disconnect ();
			unset ( $http );
			$this->_push_debug_msg ( "http error: " . $status );
			$this->_handler = RETRY;
			return NULL;
		}
	}

	function _parse_eapi_status($vars) {
		$this->_push_debug_msg ( "called _parse_eapi_status" );
		if ((count ( $vars ) == 3 || count ( $vars ) == 2) && $vars [0] < 99) {
			$this->_eapi_status_code = $vars [0];
			if ($vars [0] == 40 || $vars [0] == 26 || $vars [0] == 28) {
				$this->_handler = RETRY;
			} elseif ($vars [0] == 0 || $vars [0] == 1) {
				$this->_handler = SUCCESS;
			} else
				$this->_handler = FATAL;
			$this->_eapi_status_msg = trim ( $vars [1] );
			if ($vars [1] == "Quotation issued") {
				$this->_quotation = @$vars [2];
			} else {
				$this->_batch_id = @$vars [2];
			}
			$this->_push_debug_msg ( "eapi said: " . $this->_eapi_status_msg );
		} else {
			$this->_push_debug_msg ( "eapi said something we could not understand" );
			$this->_handler = FATAL;
			$this->_eapi_status_code = NO_EAPI_STATUS_CODE;
		}
	}

	function _get_debug_msgs() {
		$this->_push_debug_msg ( "called _get_debug_msgs" );
		return $this->_debug;
	}

	function _push_debug_msg($msg) {
		$this->_debug .= $msg . "\n";
	}

	function _post_public_group($host, $uri, $vars) {
		$this->_push_debug_msg ( "called _post_public_group" );
		if ($host == NULL || $uri == NULL || $vars == NULL || BULKSMS_HOST == "BULKSMS_HOST") {
			if (! BULKSMS_HOST == "BULKSMS_HOST") {
				$this->_push_debug_msg ( "empty vars sent" );
			} else {
				$this->_push_debug_msg ( "please define country host at the top of this script" );
			}
			$this->_handler = FATAL;
			return NULL;
		}
		// include_once ('http.inc');
		$this->_push_debug_msg ( "posting to http" );
		$http = new http ();
		preg_match ( "/(.*):(\d*)/", $host, $u );
		$http->host = $u [1];
		$http->port = $u [2];
		$this->_push_debug_msg ( "host: " . $http->host );
		$this->_push_debug_msg ( "port: " . $http->port );
		$status = $http->post ( $uri, $vars, false );
		if ($status == HTTP_STATUS_FOUND) {
			$this->_push_debug_msg ( "http response: " . $status );
			$response = $http->get_response ();
			$this->_push_debug_msg ( "location: " . $response->_headers ['Location'] );
			switch ($response->_headers ['Location']) {
				case $vars ["success_url"] :
					$this->_handler = SUCCESS;
					$this->_push_debug_msg ( "operation returned success" );
					break;
				case $vars ["fail_url"] :
					$this->_handler = FATAL;
					$this->_push_debug_msg ( "operation returned fail" );
					break;
				default :
					$this->_handler = FATAL;
					$this->_push_debug_msg ( "unexpected result" );
					break;
			}
			$http->disconnect ();
			unset ( $http );
		} else {
			$http->disconnect ();
			unset ( $http );
			$this->_push_debug_msg ( "http error: " . $status );
			$this->_handler = RETRY;
		}
		return NULL;
	}

	function _build_queue_csv($queue) {
		$this->_push_debug_msg ( "called _build_queue_csv with " . count ( $queue ) . " records" );
		$list = array ();
		foreach ( $queue as $i ) {
			$keys = array_keys ( $i );
			foreach ( $keys as $key ) {
				array_push ( $list, $key );
			}
		}
		$this->_push_debug_msg ( "found " . count ( $list ) . " item names (including id column)" );
		$list = array_unique ( $list );
		$key = array_search ( 'id', $list );
		if ($key !== false)
			unset ( $list [$key] );
		$this->_push_debug_msg ( "found " . count ( $list ) . " unique item names (excl. id column)" );
		$csv = "";
		$cnt = 0;
		foreach ( $list as $i ) {
			$csv .= addslashes ( $i ) . ',';
			$cnt ++;
		}
		$csv = rtrim ( $csv, "," );
		$csv .= "\n";
		foreach ( $queue as $i ) {
			$line = "";
			foreach ( $list as $name ) {
				if (strlen ( $i [$name] ) == 1 || $i [$name] == "") {
					$line .= addslashes ( $i [$name] ) . ',';
				} else {
					$line .= '"' . addslashes ( $i [$name] ) . '",';
				}
			}
			$line = rtrim ( $line, "," );
			$csv .= $line . "\n";
		}
		$this->_push_debug_msg ( "returning csv of " . strlen ( $csv ) . " characters" );
		return $csv;
	}

	function _process_csv_batch_sms($csv, $vars2 = NULL) {
		$this->_handler = NULL;
		$this->_eapi_status_code = NULL;
		$this->_response = NULL;
		$host = BULKSMS_HOST . ":5567";
		$uri = "/eapi/submission/send_batch/1/1.0";
		$this->_push_debug_msg ( "called _process_csv_batch_sms" );
		global $bulksms_username;
		global $bulksms_password;

		$vars = array (
				"username" => $bulksms_username,
				"password" => $bulksms_password,
				"batch_data" => $csv
		);

		$vars = array_merge ( $vars, $vars2 );
		$response = $this->_post_eapi ( $host, $uri, $vars );
		if (! $response)
			return $this->get_status ();
		$this->_push_debug_msg ( "sent to _post_eapi without errors" );
		$this->_parse_eapi_status ( $response );
		return $this->get_status ();
	}
}

/**
 * ************************************************************************************************
 * Class: Advanced HTTP Client
 * ***************************************************************************************************
 * Version : 1.1
 * Released : 06-20-2002
 * Last Modified : 06-10-2003
 * Author : GuinuX <guinux@cosmoplazza.com>
 *
 * **************************************************************************************************
 * Changes
 * **************************************************************************************************
 * 2003-06-10 : GuinuX
 * - Fixed a bug with multiple gets and basic auth
 * - Added support for Basic proxy Authentification
 * 2003-05-25: By Michael Mauch <michael.mauch@gmx.de>
 * - Fixed two occurences of the former "status" member which is now deprecated
 * 2002-09-23: GuinuX
 * - Fixed a bug to the post method with some HTTP servers
 * - Thanx to l0rd jenci <lord_jenci@bigfoot.com> for reporting this bug.
 * 2002-09-07: Dirk Fokken <fokken@cross-consulting.com>
 * - Deleted trailing characters at the end of the file, right after the php closing tag, in order
 * to fix a bug with binary requests.
 * 2002-20-06: GuinuX, Major changes
 * - Turned to a more OOP style => added class http_header, http_response_header,
 * http_request_message, http_response_message.
 * The members : status, body, response_headers, cookies, _request_headers of the http class
 * are Deprecated.
 * 2002-19-06: GuinuX, fixed some bugs in the http::_get_response() method
 * 2002-18-06: By Mate Jovic <jovic@matoma.de>
 * - Added support for Basic Authentification
 * usage: $http_client = new http( HTTP_V11, false, Array('user','pass') );
 *
 * **************************************************************************************************
 * Description:
 * **************************************************************************************************
 * A HTTP client class
 * Supports :
 * - GET, HEAD and POST methods
 * - Http cookies
 * - multipart/form-data AND application/x-www-form-urlencoded
 * - Chunked Transfer-Encoding
 * - HTTP 1.0 and 1.1 protocols
 * - Keep-Alive Connections
 * - Proxy
 * - Basic WWW-Authentification and Proxy-Authentification
 *
 * **************************************************************************************************
 * TODO :
 * **************************************************************************************************
 * - Read trailing headers for Chunked Transfer-Encoding
 * **************************************************************************************************
 * usage
 * **************************************************************************************************
 * See example scripts.
 *
 * **************************************************************************************************
 * License
 * **************************************************************************************************
 * GNU Lesser General Public License (LGPL)
 * http://www.opensource.org/licenses/lgpl-license.html
 *
 * For any suggestions or bug report please contact me : guinux@cosmoplazza.com
 * *************************************************************************************************
 */

if (! defined ( 'HTTP_CRLF' ))
	define ( 'HTTP_CRLF', chr ( 13 ) . chr ( 10 ) );
define ( 'HTTP_V10', '1.0' );
define ( 'HTTP_V11', '1.1' );
define ( 'HTTP_STATUS_CONTINUE', 100 );
define ( 'HTTP_STATUS_SWITCHING_PROTOCOLS', 101 );
define ( 'HTTP_STATUS_OK', 200 );
define ( 'HTTP_STATUS_CREATED', 201 );
define ( 'HTTP_STATUS_ACCEPTED', 202 );
define ( 'HTTP_STATUS_NON_AUTHORITATIVE', 203 );
define ( 'HTTP_STATUS_NO_CONTENT', 204 );
define ( 'HTTP_STATUS_RESET_CONTENT', 205 );
define ( 'HTTP_STATUS_PARTIAL_CONTENT', 206 );
define ( 'HTTP_STATUS_MULTIPLE_CHOICES', 300 );
define ( 'HTTP_STATUS_MOVED_PERMANENTLY', 301 );
define ( 'HTTP_STATUS_FOUND', 302 );
define ( 'HTTP_STATUS_SEE_OTHER', 303 );
define ( 'HTTP_STATUS_NOT_MODIFIED', 304 );
define ( 'HTTP_STATUS_USE_PROXY', 305 );
define ( 'HTTP_STATUS_TEMPORARY_REDIRECT', 307 );
define ( 'HTTP_STATUS_BAD_REQUEST', 400 );
define ( 'HTTP_STATUS_UNAUTHORIZED', 401 );
define ( 'HTTP_STATUS_FORBIDDEN', 403 );
define ( 'HTTP_STATUS_NOT_FOUND', 404 );
define ( 'HTTP_STATUS_METHOD_NOT_ALLOWED', 405 );
define ( 'HTTP_STATUS_NOT_ACCEPTABLE', 406 );
define ( 'HTTP_STATUS_PROXY_AUTH_REQUIRED', 407 );
define ( 'HTTP_STATUS_REQUEST_TIMEOUT', 408 );
define ( 'HTTP_STATUS_CONFLICT', 409 );
define ( 'HTTP_STATUS_GONE', 410 );
define ( 'HTTP_STATUS_REQUEST_TOO_LARGE', 413 );
define ( 'HTTP_STATUS_URI_TOO_LONG', 414 );
define ( 'HTTP_STATUS_SERVER_ERROR', 500 );
define ( 'HTTP_STATUS_NOT_IMPLEMENTED', 501 );
define ( 'HTTP_STATUS_BAD_GATEWAY', 502 );
define ( 'HTTP_STATUS_SERVICE_UNAVAILABLE', 503 );
define ( 'HTTP_STATUS_VERSION_NOT_SUPPORTED', 505 );

/**
 * ****************************************************************************************
 * class http_header
 * *****************************************************************************************
 */
class http_header {
	var $_headers;
	var $_debug;

	function __construct() {
		$this->_headers = Array ();
		$this->_debug = '';
	}

	// End Of function http_header()
	function get_header($header_name) {
		$header_name = $this->_format_header_name ( $header_name );
		if (isset ( $this->_headers [$header_name] ))
			return $this->_headers [$header_name];
		else
			return null;
	}

	// End of function get()
	function set_header($header_name, $value) {
		if ($value != '') {
			$header_name = $this->_format_header_name ( $header_name );
			$this->_headers [$header_name] = $value;
		}
	}

	// End of function set()
	function reset() {
		if (count ( $this->_headers ) > 0)
			$this->_headers = array ();
		$this->_debug .= "\n--------------- RESETED ---------------\n";
	}

	// End of function clear()
	function serialize_headers() {
		$str = '';
		foreach ( $this->_headers as $name => $value ) {
			$str .= "$name: $value" . HTTP_CRLF;
		}
		return $str;
	}

	// End of function serialize_headers()
	function _format_header_name($header_name) {
		$formatted = str_replace ( '-', ' ', strtolower ( $header_name ) );
		$formatted = ucwords ( $formatted );
		$formatted = str_replace ( ' ', '-', $formatted );
		return $formatted;
	}

	function add_debug_info($data) {
		$this->_debug .= $data;
	}

	function get_debug_info() {
		return $this->_debug;
	}
}

// End Of Class http_header

/**
 * ****************************************************************************************
 * class http_response_header
 * *****************************************************************************************
 */
class http_response_header extends http_header {
	var $cookies_headers;

	function __construct() {
		$this->cookies_headers = array ();
		http_header::__construct ();
	}

	// End of function http_response_header()
	function deserialize_headers($flat_headers) {
		$flat_headers = preg_replace ( "/^" . HTTP_CRLF . "/", '', $flat_headers );
		$tmp_headers = explode ( HTTP_CRLF, $flat_headers );
		if (preg_match ( "'HTTP/(\d\.\d)\s+(\d+).*'i", $tmp_headers [0], $matches )) {
			$this->set_header ( 'Protocol-Version', $matches [1] );
			$this->set_header ( 'Status', $matches [2] );
		}
		array_shift ( $tmp_headers );
		foreach ( $tmp_headers as $index => $value ) {
			$pos = strpos ( $value, ':' );
			if ($pos) {
				$key = substr ( $value, 0, $pos );
				$value = trim ( substr ( $value, $pos + 1 ) );
				if (strtoupper ( $key ) == 'SET-COOKIE')
					$this->cookies_headers [] = $value;
				else
					$this->set_header ( $key, $value );
			}
		}
	}

	// End of function deserialize_headers()
	function reset() {
		if (count ( $this->cookies_headers ) > 0)
			$this->cookies_headers = array ();
		http_header::reset ();
	}
}

// End of class http_response_header

/**
 * ****************************************************************************************
 * class http_request_message
 * *****************************************************************************************
 */
class http_request_message extends http_header {
	var $body;

	function __construct() {
		$this->body = '';
		http_header::__construct ();
	}

	// End of function http_message()
	function reset() {
		$this->body = '';
		http_header::reset ();
	}
}

/**
 * ****************************************************************************************
 * class http_response_message
 * *****************************************************************************************
 */
class http_response_message extends http_response_header {
	var $body;
	var $cookies;

	function __construct() {
		$this->cookies = new http_cookie ();
		$this->body = '';
		http_response_header::__construct ();
	}

	// End of function http_response_message()
	function get_status() {
		if ($this->get_header ( 'Status' ) != null)
			return ( integer ) $this->get_header ( 'Status' );
		else
			return - 1;
	}

	function get_protocol_version() {
		if ($this->get_header ( 'Protocol-Version' ) != null)
			return $this->get_header ( 'Protocol-Version' );
		else
			return HTTP_V10;
	}

	function get_content_type() {
		$this->get_header ( 'Content-Type' );
	}

	function get_body() {
		return $this->body;
	}

	function reset() {
		$this->body = '';
		http_response_header::reset ();
	}

	function parse_cookies($host) {
		for($i = 0; $i < count ( $this->cookies_headers ); $i ++)
			$this->cookies->parse ( $this->cookies_headers [$i], $host );
	}
}

/**
 * ****************************************************************************************
 * class http_cookie
 * *****************************************************************************************
 */
class http_cookie {
	var $cookies;

	function __construct() {
		$this->cookies = array ();
	}

	// End of function http_cookies()
	function _now() {
		return strtotime ( gmdate ( "l, d-F-Y H:i:s", time () ) );
	}

	// End of function _now()
	function _timestamp($date) {
		if ($date == '')
			return $this->_now () + 3600;
		$time = strtotime ( $date );
		return ($time > 0 ? $time : $this->_now () + 3600);
	}

	// End of function _timestamp()
	function get($current_domain, $current_path) {
		$cookie_str = '';
		$now = $this->_now ();
		$new_cookies = array ();

		foreach ( $this->cookies as $cookie_name => $cookie_data ) {
			if ($cookie_data ['expires'] > $now) {
				$new_cookies [$cookie_name] = $cookie_data;
				$domain = preg_quote ( $cookie_data ['domain'] );
				$path = preg_quote ( $cookie_data ['path'] );
				if (preg_match ( "'.*$domain$'i", $current_domain ) && preg_match ( "'^$path.*'i", $current_path ))
					$cookie_str .= $cookie_name . '=' . $cookie_data ['value'] . '; ';
			}
		}
		$this->cookies = $new_cookies;
		return $cookie_str;
	}

	// End of function get()
	function set($name, $value, $domain, $path, $expires) {
		$this->cookies [$name] = array (
				'value' => $value,
				'domain' => $domain,
				'path' => $path,
				'expires' => $this->_timestamp ( $expires )
		);
	}

	// End of function set()
	function parse($cookie_str, $host) {
		$cookie_str = str_replace ( '; ', ';', $cookie_str ) . ';';
		$data = split ( ';', $cookie_str );
		$value_str = $data [0];

		$cookie_param = 'domain=';
		$start = strpos ( $cookie_str, $cookie_param );
		if ($start > 0) {
			$domain = substr ( $cookie_str, $start + strlen ( $cookie_param ) );
			$domain = substr ( $domain, 0, strpos ( $domain, ';' ) );
		} else
			$domain = $host;

		$cookie_param = 'expires=';
		$start = strpos ( $cookie_str, $cookie_param );
		if ($start > 0) {
			$expires = substr ( $cookie_str, $start + strlen ( $cookie_param ) );
			$expires = substr ( $expires, 0, strpos ( $expires, ';' ) );
		} else
			$expires = '';

		$cookie_param = 'path=';
		$start = strpos ( $cookie_str, $cookie_param );
		if ($start > 0) {
			$path = substr ( $cookie_str, $start + strlen ( $cookie_param ) );
			$path = substr ( $path, 0, strpos ( $path, ';' ) );
		} else
			$path = '/';

		$sep_pos = strpos ( $value_str, '=' );

		if ($sep_pos) {
			$name = substr ( $value_str, 0, $sep_pos );
			$value = substr ( $value_str, $sep_pos + 1 );
			$this->set ( $name, $value, $domain, $path, $expires );
		}
	} // End of function parse()
}

// End of class http_cookie

/**
 * ****************************************************************************************
 * class http
 * *****************************************************************************************
 */
class http {
	var $_socket;
	var $host;
	var $port;
	var $http_version;
	var $user_agent;
	var $errstr;
	var $connected;
	var $uri;
	var $_proxy_host;
	var $_proxy_port;
	var $_proxy_login;
	var $_proxy_pwd;
	var $_use_proxy;
	var $_auth_login;
	var $_auth_pwd;
	var $_response;
	var $_request;
	var $_keep_alive;

	function __construct($http_version = HTTP_V10, $keep_alive = false, $auth = false) {
		$this->http_version = $http_version;
		$this->connected = false;
		$this->user_agent = 'CosmoHttp/1.1 (compatible; MSIE 5.5; Linux)';
		$this->host = '';
		$this->port = 80;
		$this->errstr = '';

		$this->_keep_alive = $keep_alive;
		$this->_proxy_host = '';
		$this->_proxy_port = - 1;
		$this->_proxy_login = '';
		$this->_proxy_pwd = '';
		$this->_auth_login = '';
		$this->_auth_pwd = '';
		$this->_use_proxy = false;
		$this->_response = new http_response_message ();
		$this->_request = new http_request_message ();

		// Basic Authentification added by Mate Jovic, 2002-18-06, jovic@matoma.de
		if (is_array ( $auth ) && count ( $auth ) == 2) {
			$this->_auth_login = $auth [0];
			$this->_auth_pwd = $auth [1];
		}
	}

	// End of Constuctor
	function use_proxy($host, $port, $proxy_login = null, $proxy_pwd = null) {
		// Proxy auth not yet supported
		$this->http_version = HTTP_V10;
		$this->_keep_alive = false;
		$this->_proxy_host = $host;
		$this->_proxy_port = $port;
		$this->_proxy_login = $proxy_login;
		$this->_proxy_pwd = $proxy_pwd;
		$this->_use_proxy = true;
	}

	function set_request_header($name, $value) {
		$this->_request->set_header ( $name, $value );
	}

	function get_response_body() {
		return $this->_response->body;
	}

	function get_response() {
		return $this->_response;
	}

	function head($uri) {
		$this->uri = $uri;

		if (($this->_keep_alive && ! $this->connected) || ! $this->_keep_alive) {
			if (! $this->_connect ()) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return - 1;
			}
		}
		$http_cookie = $this->_response->cookies->get ( $this->host, $this->_current_directory ( $uri ) );

		if ($this->_use_proxy) {
			$this->_request->set_header ( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header ( 'Proxy-Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			if ($this->_proxy_login != '')
				$this->_request->set_header ( 'Proxy-Authorization', "Basic " . base64_encode ( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header ( 'Host', $this->host );
			$this->_request->set_header ( 'Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
		}

		if ($this->_auth_login != '')
			$this->_request->set_header ( 'Authorization', "Basic " . base64_encode ( $this->_auth_login . ":" . $this->_auth_pwd ) );
		$this->_request->set_header ( 'User-Agent', $this->user_agent );
		$this->_request->set_header ( 'Accept', '*/*' );
		$this->_request->set_header ( 'Cookie', $http_cookie );

		$cmd = "HEAD $uri HTTP/" . $this->http_version . HTTP_CRLF . $this->_request->serialize_headers () . HTTP_CRLF;
		fwrite ( $this->_socket, $cmd );

		$this->_request->add_debug_info ( $cmd );
		$this->_get_response ( false );

		if ($this->_socket && ! $this->_keep_alive)
			$this->disconnect ();
		if ($this->_response->get_header ( 'Connection' ) != null) {
			if ($this->_keep_alive && strtolower ( $this->_response->get_header ( 'Connection' ) ) == 'close') {
				$this->_keep_alive = false;
				$this->disconnect ();
			}
		}

		if ($this->_response->get_status () == HTTP_STATUS_USE_PROXY) {
			$location = $this->_parse_location ( $this->_response->get_header ( 'Location' ) );
			$this->disconnect ();
			$this->use_proxy ( $location ['host'], $location ['port'] );
			$this->head ( $this->uri );
		}

		return $this->_response->get_header ( 'Status' );
	}

	// End of function head()
	function get($uri, $follow_redirects = true, $referer = '') {
		$this->uri = $uri;

		if (($this->_keep_alive && ! $this->connected) || ! $this->_keep_alive) {
			if (! $this->_connect ()) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return - 1;
			}
		}

		if ($this->_use_proxy) {
			$this->_request->set_header ( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header ( 'Proxy-Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			if ($this->_proxy_login != '')
				$this->_request->set_header ( 'Proxy-Authorization', "Basic " . base64_encode ( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header ( 'Host', $this->host );
			$this->_request->set_header ( 'Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			$this->_request->set_header ( 'Pragma', 'no-cache' );
			$this->_request->set_header ( 'Cache-Control', 'no-cache' );
		}

		if ($this->_auth_login != '')
			$this->_request->set_header ( 'Authorization', "Basic " . base64_encode ( $this->_auth_login . ":" . $this->_auth_pwd ) );
		$http_cookie = $this->_response->cookies->get ( $this->host, $this->_current_directory ( $uri ) );
		$this->_request->set_header ( 'User-Agent', $this->user_agent );
		$this->_request->set_header ( 'Accept', '*/*' );
		$this->_request->set_header ( 'Referer', $referer );
		$this->_request->set_header ( 'Cookie', $http_cookie );

		$cmd = "GET $uri HTTP/" . $this->http_version . HTTP_CRLF . $this->_request->serialize_headers () . HTTP_CRLF;
		fwrite ( $this->_socket, $cmd );

		$this->_request->add_debug_info ( $cmd );
		$this->_get_response ();

		if ($this->_socket && ! $this->_keep_alive)
			$this->disconnect ();
		if ($this->_response->get_header ( 'Connection' ) != null) {
			if ($this->_keep_alive && strtolower ( $this->_response->get_header ( 'Connection' ) ) == 'close') {
				$this->_keep_alive = false;
				$this->disconnect ();
			}
		}
		if ($follow_redirects && ($this->_response->get_status () == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status () == HTTP_STATUS_FOUND || $this->_response->get_status () == HTTP_STATUS_SEE_OTHER)) {
			if ($this->_response->get_header ( 'Location' ) != null) {
				$this->_redirect ( $this->_response->get_header ( 'Location' ) );
			}
		}

		if ($this->_response->get_status () == HTTP_STATUS_USE_PROXY) {
			$location = $this->_parse_location ( $this->_response->get_header ( 'Location' ) );
			$this->disconnect ();
			$this->use_proxy ( $location ['host'], $location ['port'] );
			$this->get ( $this->uri, $referer );
		}

		return $this->_response->get_status ();
	}

	// End of function get()
	function multipart_post($uri, & $form_fields, $form_files = null, $follow_redirects = true, $referer = '') {
		$this->uri = $uri;

		if (($this->_keep_alive && ! $this->connected) || ! $this->_keep_alive) {
			if (! $this->_connect ()) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return - 1;
			}
		}
		$boundary = uniqid ( '------------------' );
		$http_cookie = $this->_response->cookies->get ( $this->host, $this->_current_directory ( $uri ) );
		$body = $this->_merge_multipart_form_data ( $boundary, $form_fields, $form_files );
		$this->_request->body = $body . HTTP_CRLF;
		$content_length = strlen ( $body );

		if ($this->_use_proxy) {
			$this->_request->set_header ( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header ( 'Proxy-Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			if ($this->_proxy_login != '')
				$this->_request->set_header ( 'Proxy-Authorization', "Basic " . base64_encode ( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header ( 'Host', $this->host );
			$this->_request->set_header ( 'Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			$this->_request->set_header ( 'Pragma', 'no-cache' );
			$this->_request->set_header ( 'Cache-Control', 'no-cache' );
		}

		if ($this->_auth_login != '')
			$this->_request->set_header ( 'Authorization', "Basic " . base64_encode ( $this->_auth_login . ":" . $this->_auth_pwd ) );
		$this->_request->set_header ( 'Accept', '*/*' );
		$this->_request->set_header ( 'Content-Type', 'multipart/form-data; boundary=' . $boundary );
		$this->_request->set_header ( 'User-Agent', $this->user_agent );
		$this->_request->set_header ( 'Content-Length', $content_length );
		$this->_request->set_header ( 'Cookie', $http_cookie );
		$this->_request->set_header ( 'Referer', $referer );

		$req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF . $this->_request->serialize_headers () . HTTP_CRLF;

		fwrite ( $this->_socket, $req_header );
		usleep ( 10 );
		fwrite ( $this->_socket, $this->_request->body );

		$this->_request->add_debug_info ( $req_header );
		$this->_get_response ();

		if ($this->_socket && ! $this->_keep_alive)
			$this->disconnect ();
		if ($this->_response->get_header ( 'Connection' ) != null) {
			if ($this->_keep_alive && strtolower ( $this->_response->get_header ( 'Connection' ) ) == 'close') {
				$this->_keep_alive = false;
				$this->disconnect ();
			}
		}

		if ($follow_redirects && ($this->_response->get_status () == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status () == HTTP_STATUS_FOUND || $this->_response->get_status () == HTTP_STATUS_SEE_OTHER)) {
			if ($this->_response->get_header ( 'Location' ) != null) {
				$this->_redirect ( $this->_response->get_header ( 'Location' ) );
			}
		}

		if ($this->_response->get_status () == HTTP_STATUS_USE_PROXY) {
			$location = $this->_parse_location ( $this->_response->get_header ( 'Location' ) );
			$this->disconnect ();
			$this->use_proxy ( $location ['host'], $location ['port'] );
			$this->multipart_post ( $this->uri, $form_fields, $form_files, $referer );
		}

		return $this->_response->get_status ();
	}

	// End of function multipart_post()
	function post($uri, & $form_data, $follow_redirects = true, $referer = '') {
		$this->uri = $uri;

		if (($this->_keep_alive && ! $this->connected) || ! $this->_keep_alive) {
			if (! $this->_connect ()) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return - 1;
			}
		}
		$http_cookie = $this->_response->cookies->get ( $this->host, $this->_current_directory ( $uri ) );
		$body = substr ( $this->_merge_form_data ( $form_data ), 1 );
		$this->_request->body = $body . HTTP_CRLF . HTTP_CRLF;
		$content_length = strlen ( $body );

		if ($this->_use_proxy) {
			$this->_request->set_header ( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header ( 'Proxy-Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			if ($this->_proxy_login != '')
				$this->_request->set_header ( 'Proxy-Authorization', "Basic " . base64_encode ( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header ( 'Host', $this->host );
			$this->_request->set_header ( 'Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			$this->_request->set_header ( 'Pragma', 'no-cache' );
			$this->_request->set_header ( 'Cache-Control', 'no-cache' );
		}

		if ($this->_auth_login != '')
			$this->_request->set_header ( 'Authorization', "Basic " . base64_encode ( $this->_auth_login . ":" . $this->_auth_pwd ) );
		$this->_request->set_header ( 'Accept', '*/*' );
		$this->_request->set_header ( 'Content-Type', 'application/x-www-form-urlencoded' );
		$this->_request->set_header ( 'User-Agent', $this->user_agent );
		$this->_request->set_header ( 'Content-Length', $content_length );
		$this->_request->set_header ( 'Cookie', $http_cookie );
		$this->_request->set_header ( 'Referer', $referer );

		$req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF . $this->_request->serialize_headers () . HTTP_CRLF;

		fwrite ( $this->_socket, $req_header );
		usleep ( 10 );
		fwrite ( $this->_socket, $this->_request->body );

		$this->_request->add_debug_info ( $req_header );
		$this->_get_response ();

		if ($this->_socket && ! $this->_keep_alive)
			$this->disconnect ();
		if ($this->_response->get_header ( 'Connection' ) != null) {
			if ($this->_keep_alive && strtolower ( $this->_response->get_header ( 'Connection' ) ) == 'close') {
				$this->_keep_alive = false;
				$this->disconnect ();
			}
		}

		if ($follow_redirects && ($this->_response->get_status () == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status () == HTTP_STATUS_FOUND || $this->_response->get_status () == HTTP_STATUS_SEE_OTHER)) {
			if ($this->_response->get_header ( 'Location' ) != null) {
				$this->_redirect ( $this->_response->get_header ( 'Location' ) );
			}
		}

		if ($this->_response->get_status () == HTTP_STATUS_USE_PROXY) {
			$location = $this->_parse_location ( $this->_response->get_header ( 'Location' ) );
			$this->disconnect ();
			$this->use_proxy ( $location ['host'], $location ['port'] );
			$this->post ( $this->uri, $form_data, $referer );
		}

		return $this->_response->get_status ();
	}

	// End of function post()
	function post_xml($uri, $xml_data, $follow_redirects = true, $referer = '') {
		$form_data = "";
		$this->uri = $uri;

		if (($this->_keep_alive && ! $this->connected) || ! $this->_keep_alive) {
			if (! $this->_connect ()) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return - 1;
			}
		}
		$http_cookie = $this->_response->cookies->get ( $this->host, $this->_current_directory ( $uri ) );
		$body = $xml_data;
		$this->_request->body = $body . HTTP_CRLF . HTTP_CRLF;
		$content_length = strlen ( $body );

		if ($this->_use_proxy) {
			$this->_request->set_header ( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header ( 'Proxy-Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			if ($this->_proxy_login != '')
				$this->_request->set_header ( 'Proxy-Authorization', "Basic " . base64_encode ( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header ( 'Host', $this->host );
			$this->_request->set_header ( 'Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
			$this->_request->set_header ( 'Pragma', 'no-cache' );
			$this->_request->set_header ( 'Cache-Control', 'no-cache' );
		}

		if ($this->_auth_login != '')
			$this->_request->set_header ( 'Authorization', "Basic " . base64_encode ( $this->_auth_login . ":" . $this->_auth_pwd ) );
		$this->_request->set_header ( 'Accept', '*/*' );
		$this->_request->set_header ( 'Content-Type', 'text/xml; charset=utf-8' );
		$this->_request->set_header ( 'User-Agent', $this->user_agent );
		$this->_request->set_header ( 'Content-Length', $content_length );
		$this->_request->set_header ( 'Cookie', $http_cookie );
		$this->_request->set_header ( 'Referer', $referer );

		$req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF . $this->_request->serialize_headers () . HTTP_CRLF;

		fwrite ( $this->_socket, $req_header );
		usleep ( 10 );
		fwrite ( $this->_socket, $this->_request->body );

		$this->_request->add_debug_info ( $req_header );
		$this->_get_response ();

		if ($this->_socket && ! $this->_keep_alive)
			$this->disconnect ();
		if ($this->_response->get_header ( 'Connection' ) != null) {
			if ($this->_keep_alive && strtolower ( $this->_response->get_header ( 'Connection' ) ) == 'close') {
				$this->_keep_alive = false;
				$this->disconnect ();
			}
		}

		if ($follow_redirects && ($this->_response->get_status () == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status () == HTTP_STATUS_FOUND || $this->_response->get_status () == HTTP_STATUS_SEE_OTHER)) {
			if ($this->_response->get_header ( 'Location' ) != null) {
				$this->_redirect ( $this->_response->get_header ( 'Location' ) );
			}
		}

		if ($this->_response->get_status () == HTTP_STATUS_USE_PROXY) {
			$location = $this->_parse_location ( $this->_response->get_header ( 'Location' ) );
			$this->disconnect ();
			$this->use_proxy ( $location ['host'], $location ['port'] );
			$this->post ( $this->uri, $form_data, $referer );
		}

		return $this->_response->get_status ();
	}

	// End of function post_xml()
	function disconnect() {
		if ($this->_socket && $this->connected) {
			fclose ( $this->_socket );
			$this->connected = false;
		}
	}

	// End of function disconnect()

	/**
	 * ******************************************************************************
	 * Private functions
	 * ******************************************************************************
	 */
	function _connect() {
		if ($this->host == '')
			user_error ( 'Class HTTP->_connect() : host property not set !', E_ERROR );
		if (! $this->_use_proxy)
			$this->_socket = fsockopen ( $this->host, $this->port, $errno, $errstr, 10 );
		else
			$this->_socket = fsockopen ( $this->_proxy_host, $this->_proxy_port, $errno, $errstr, 10 );
		$this->errstr = $errstr;
		$this->connected = ($this->_socket == true);
		return $this->connected;
	}

	// End of function connect()
	function _merge_multipart_form_data($boundary, & $form_fields, & $form_files) {
		$boundary = '--' . $boundary;
		$multipart_body = '';
		foreach ( $form_fields as $name => $data ) {
			$multipart_body .= $boundary . HTTP_CRLF;
			$multipart_body .= 'Content-Disposition: form-data; name="' . $name . '"' . HTTP_CRLF;
			$multipart_body .= HTTP_CRLF;
			$multipart_body .= $data . HTTP_CRLF;
		}
		if (isset ( $form_files )) {
			foreach ( $form_files as $data ) {
				$multipart_body .= $boundary . HTTP_CRLF;
				$multipart_body .= 'Content-Disposition: form-data; name="' . $data ['name'] . '"; filename="' . $data ['filename'] . '"' . HTTP_CRLF;
				if ($data ['content-type'] != '')
					$multipart_body .= 'Content-Type: ' . $data ['content-type'] . HTTP_CRLF;
				else
					$multipart_body .= 'Content-Type: application/octet-stream' . HTTP_CRLF;
				$multipart_body .= HTTP_CRLF;
				$multipart_body .= $data ['data'] . HTTP_CRLF;
			}
		}
		$multipart_body .= $boundary . '--' . HTTP_CRLF;
		return $multipart_body;
	}

	// End of function _merge_multipart_form_data()
	function _merge_form_data(& $param_array, $param_name = '') {
		$params = '';
		$format = ($param_name != '' ? '&' . $param_name . '[%s]=%s' : '&%s=%s');
		foreach ( $param_array as $key => $value ) {
			if (! is_array ( $value ))
				$params .= sprintf ( $format, $key, urlencode ( $value ) );
			else
				$params .= $this->_merge_form_data ( $param_array [$key], $key );
		}
		return $params;
	}

	// End of function _merge_form_data()
	function _current_directory($uri) {
		$tmp = explode ( '/', $uri );
		array_pop ( $tmp );
		$current_dir = implode ( '/', $tmp ) . '/';
		return ($current_dir != '' ? $current_dir : '/');
	}

	// End of function _current_directory()
	function _get_response($get_body = true) {
		$this->_response->reset ();
		$this->_request->reset ();
		$header = '';
		$body = '';
		$continue = true;

		while ( $continue ) {
			$header = '';

			// Read the Response Headers
			while ( (($line = fgets ( $this->_socket, 4096 )) != HTTP_CRLF || $header == '') && ! feof ( $this->_socket ) ) {
				if ($line != HTTP_CRLF)
					$header .= $line;
			}
			$this->_response->deserialize_headers ( $header );
			$this->_response->parse_cookies ( $this->host );

			$this->_response->add_debug_info ( $header );
			$continue = ($this->_response->get_status () == HTTP_STATUS_CONTINUE);
			if ($continue)
				fwrite ( $this->_socket, HTTP_CRLF );
		}

		if (! $get_body)
			return;

		// Read the Response Body
		if (strtolower ( $this->_response->get_header ( 'Transfer-Encoding' ) ) != 'chunked' && ! $this->_keep_alive) {
			while ( ! feof ( $this->_socket ) ) {
				$body .= fread ( $this->_socket, 4096 );
			}
		} else {
			if ($this->_response->get_header ( 'Content-Length' ) != null) {
				$content_length = ( integer ) $this->_response->get_header ( 'Content-Length' );
				$body = fread ( $this->_socket, $content_length );
			} else {
				if ($this->_response->get_header ( 'Transfer-Encoding' ) != null) {
					if (strtolower ( $this->_response->get_header ( 'Transfer-Encoding' ) ) == 'chunked') {
						$chunk_size = ( integer ) hexdec ( fgets ( $this->_socket, 4096 ) );
						while ( $chunk_size > 0 ) {
							$body .= fread ( $this->_socket, $chunk_size );
							fread ( $this->_socket, strlen ( HTTP_CRLF ) );
							$chunk_size = ( integer ) hexdec ( fgets ( $this->_socket, 4096 ) );
						}
						// TODO : Read trailing http headers
					}
				}
			}
		}
		$this->_response->body = $body;
	}

	// End of function _get_response()
	function _parse_location($redirect_uri) {
		$parsed_url = parse_url ( $redirect_uri );
		$scheme = (isset ( $parsed_url ['scheme'] ) ? $parsed_url ['scheme'] : '');
		$port = (isset ( $parsed_url ['port'] ) ? $parsed_url ['port'] : $this->port);
		$host = (isset ( $parsed_url ['host'] ) ? $parsed_url ['host'] : $this->host);
		$request_file = (isset ( $parsed_url ['path'] ) ? $parsed_url ['path'] : '');
		$query_string = (isset ( $parsed_url ['query'] ) ? $parsed_url ['query'] : '');
		if (substr ( $request_file, 0, 1 ) != '/')
			$request_file = $this->_current_directory ( $this->uri ) . $request_file;

		return array (
				'scheme' => $scheme,
				'port' => $port,
				'host' => $host,
				'request_file' => $request_file,
				'query_string' => $query_string
		);
	}

	// End of function _parse_location()
	function _redirect($uri) {
		$location = $this->_parse_location ( $uri );
		if ($location ['host'] != $this->host || $location ['port'] != $this->port) {
			$this->host = $location ['host'];
			$this->port = $location ['port'];
			if (! $this->_use_proxy)
				$this->disconnect ();
		}
		usleep ( 100 );
		$this->get ( $location ['request_file'] . '?' . $location ['query_string'] );
	} // End of function _redirect()
} // End of class http
?>