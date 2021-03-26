<?php

function sendPushover_RAW($message) {
	global $loc, $app_title, $pushover_user_key, $pushover_api_token, $pushover_server_url, $pushover_server_title;
	echo timestampFormat ( timestampNow (), "Y-m-d\TH:i:s\Z" ) . ": Pushover send starting.\n";
	if (strlen ( $pushover_user_key ) && strlen ( $pushover_api_token )) {
		/*
		 * curl_setopt($c, CURLOPT_POSTFIELDS, array(
		 * //'token' => $this->getToken(),
		 * //'user' => $this->getUser(),
		 * //'title' => $this->getTitle(),
		 * //'message' => $this->getMessage(),
		 * 'html' => $this->getHtml(),
		 * 'device' => $this->getDevice(),
		 * 'priority' => $this->getPriority(),
		 * 'timestamp' => $this->getTimestamp(),
		 * 'expire' => $this->getExpire(),
		 * 'retry' => $this->getRetry(),
		 * 'callback' => $this->getCallback(),
		 * //'url' => $this->getUrl(),
		 * 'sound' => $this->getSound(),
		 * //'url_title' => $this->getUrlTitle()
		 * ));
		 */

		$post_fields = [ 
				"token" => $pushover_api_token,
				"user" => $pushover_user_key,
				"title" => $loc . " - " . $app_title,
				"message" => $message
		];

		if (strlen ( $pushover_server_url )) {
			$post_fields ["url"] = $pushover_server_url;
			if (strlen ( $pushover_server_title )) {
				$post_fields ["url_title"] = $pushover_server_title;
			}
		}

		$fn = getSnapshotFileName ();
		if (strlen ( $fn ) && file_exists ( $fn )) {
			// echo "Adding file attachment for '$fn' (" . number_format ( filesize ( $fn ) / 1000 ) . "kb)\n";
			$post_fields ["attachment"] = new CurlFile ( realpath ( $fn ), "image/jpeg", basename ( $fn ) );
		}
		// echo "Post_fields:" . ob_print_r ( $post_fields );
		curl_setopt_array ( $ch = curl_init (), array (
				CURLOPT_URL => "https://api.pushover.net/1/messages.json",
				CURLOPT_POSTFIELDS => $post_fields,
				CURLOPT_SAFE_UPLOAD => true,
				CURLOPT_RETURNTRANSFER => true
		) );
		// curl_exec ( $ch );
		echo "Curl Exec: " . tfn ( curl_exec ( $ch ) ) . "\n";
		print_r ( curl_getinfo ( $ch ) );
		curl_close ( $ch );
	}
	echo timestampFormat ( timestampNow (), "Y-m-d\TH:i:s\Z" ) . ": Pushover send complete.\n";
}

?>