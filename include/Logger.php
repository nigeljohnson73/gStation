<?php
/**************************************************************************************
 * This work is licensed. Please see license.txt for more information on terms.
 * (C) 2007 Nigel Johnson
 */
if (! defined ( "LL_NONE" )) {
	define ( "LL_NONE", 0 );
	define ( "LL_SYSTEM", 0 );
	define ( "LL_SYS", 0 );
	define ( "LL_ERROR", 1 );
	define ( "LL_ERR", 1 );
	define ( "LL_WRN", 2 );
	define ( "LL_WARN", 2 );
	define ( "LL_WARNING", 2 );
	define ( "LL_INF", 3 );
	define ( "LL_INFO", 3 );
	define ( "LL_DBG", 4 );
	define ( "LL_DEBUG", 4 );
	define ( "LL_EDEBUG", 5 );
	define ( "LL_XDEBUG", 6 );
}

$log_literals = array ();
$log_literals [LL_SYS] = "LL_SYS";
$log_literals [LL_ERROR] = "LL_ERROR";
$log_literals [LL_WARNING] = "LL_WARNING";
$log_literals [LL_INFO] = "LL_INFO";
$log_literals [LL_DEBUG] = "LL_DEBUG";
$log_literals [LL_EDEBUG] = "LL_EDEBUG";
$log_literals [LL_XDEBUG] = "LL_XDEBUG";

$log_to_literal = array ();
foreach ( $log_literals as $level => $literal ) {
	$log_to_literal [$literal] = $level;
}

if (! function_exists ( "mkpath" )) {

	function mkpath($path) {
		$dirs = array ();
		$path = preg_replace ( '/(\/){2,}|(\\\){1,}/', '/', $path ); // only forward-slash
		$dirs = explode ( "/", $path );
		$path = "";
		foreach ( $dirs as $element ) {
			$path .= $element . "/";
			if (! is_dir ( $path )) {
				if (! @mkdir ( $path )) {
					echo "mkpath(): something went wrong at : " . $path . "\n";
					return false;
				}
			}
		}
		// echo("<B>".$path."</B> successfully created");
		return true;
	}
}

class Logger {

	function __construct($path = "/logs", $app_name = "") {
		$this->setLevel ( LL_WARNING );
		$this->strings = array ();
		$this->strings [LL_SYS] = "SYS";
		$this->strings [LL_ERROR] = "ERR";
		$this->strings [LL_WARNING] = "WRN";
		$this->strings [LL_INFO] = "INF";
		$this->strings [LL_DEBUG] = "DBG";
		$this->strings [LL_EDEBUG] = "EDBG";
		$this->strings [LL_XDEBUG] = "XDBG";
		$this->log2String ( true );
		$this->_fp = null;

		if ($path !== null && $app_name !== null) {
			if (strlen ( $app_name )) {
				$path .= "/" . $app_name;
			}
			// echo "Logger starting at $path\n";

			$this->_path = $path;
			mkpath ( $this->_path );
			@ chmod ( $this->_path, 0777 );
			$this->_fn = date ( "Ymd" ) . ".txt";
			$logfile = $path . "/" . $this->_fn;
			@ touch ( $logfile );
			@ chmod ( $logfile, 0666 );
			$this->_fp = @fopen ( $path . "/" . $this->_fn, "a" );
		}

		if (! $this->_fp) {
			echo "<!-- Logger unable to write to file '" . $path . "/" . $this->_fn . "' -->\n";
		}
	}

	function tidy() {
	}

	function setLevel($level) {
		$this->_level = $level;
		// echo $this->_fn."- logging level: ".$this->_level."<br />";
	}

	function log($level, $str) {
		if ($level > $this->_level) {
			// echo $this->_fn."- logging request too low level<br />";
			return false;
		}

		$lev = $this->strings [$level];
		$ts = date ( "H:i:s" );
		$un = "";
		// if (class_exists("tUsers")) {
		// $un = tUsers :: getLoggedInUser();
		// }
		// if (strlen($un) == 0) {
		// $un = "_NONE_";
		// }

		$str = str_replace ( "\n", "\r\n", $str );
		// $str = $ts . " ; " . $lev . " ; " . $un . " ; " . $str . "\r\n";
		$str = $ts . " ; " . $lev . " ; " . $str . "\r\n";
		if ($this->_fp) {
			fwrite ( $this->_fp, $str );
		}

		if ($this->log_to_string) {
			$this->log_string .= $str;
		}
	}

	function log2String($enable = true) {
		$this->log_to_string = $enable;
		if ($enable) {
			$this->log_string = "";
		}
	}

	function getString() {
		return $this->log_string;
	}
}

if (strlen ( @$log_dir ) == 0) {
	$log_dir = "/logs";
}
$logger = new Logger ( $log_dir, @ $app_title );
$logger->setLevel ( @ $log_level );

function logger($level, $str) {
	global $logger;
	// foreach ($logger as $ilog) {
	$logger->log ( $level, $str );
	// }
}
?>
