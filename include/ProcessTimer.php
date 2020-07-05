<?php

function microtime_float() {
	$time = microtime ();
	return ( double ) substr ( $time, 11 ) + ( double ) substr ( $time, 0, 8 );
}

class ProcessTimer {

	function __construct() {
		$this->_stop = 0.0;
		$this->start();
	}

	function start() {
		$this->_start = microtime_float ();
	}

	function stop() {
		$this->_stop = microtime_float ();
		return $this->duration ();
	}

	function duration() {
		if ($this->_stop == 0) {
			return microtime_float () - $this->_start;
		}
		return ($this->_stop - $this->_start)/1000000;
	}
}
?>