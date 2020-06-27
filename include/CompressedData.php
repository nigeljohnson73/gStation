<?php

class RawData {

	function __construct($data) {
		$this->data = $data;
	}

	function tidy() {
		// in case we used a file
	}
}

class UncompressedData extends RawData {

	function __construct($data) {
		parent::__construct ( $data );
	}

	function gzip() {
		$ret = gzcompress ( $this->data, 9 );
		// if($ret === false) {
		// return "";
		// }
		return $ret;
	}

	function bzip2() {
		$ret = bzcompress ( $this->data, 9 );
		// if($ret + 0 == $ret) {
		// // an error number occured
		// return "";
		// }
		return $ret;
	}
}

class CompressedData extends RawData {

	function __construct($data) {
		parent::__construct ( $data );
	}

	function uncompress() {
		return $this->data;
	}
}

class GzipData extends CompressedData {

	function __construct($data) {
		parent::__construct ( $data );
	}

	function uncompress() {
		$ret = gzuncompress ( $this->data );
		// if($ret === false) {
		// return "";
		// }
		return $ret;
	}
}

class Bzip2Data extends CompressedData {

	function __construct($data) {
		parent::__construct ( $data );
	}

	function uncompress() {
		$ret = bzdecompress ( $this->data );
		// if($ret + 0 == $ret) {
		// // an error number occured
		// return "";
		// }
		return $ret;
	}
}
?>