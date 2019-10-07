<?php

class SimpleEncrypter {
	var $scramble1; // 1st string of ASCII characters
	var $scramble2; // 2nd string of ASCII characters
	var $errors; // array of error messages
	var $adj; // 1st adjustment value (optional)
	var $mod;

	// 2nd adjustment value (optional)
	function __construct($salt = null, $saltlen = null) {
		if (strlen ( $salt ) == 0) {
			$salt = "S1mPl3EnCrypt10n";
		}
		$this->key = $salt;
		if ($saltlen === null) {
			$saltlen = strlen ( $salt );
		}
		$this->keylen = $saltlen;
		// Each of these two strings must contain the same characters, but in a different order.
		// Use only printable characters from the ASCII table.
		// Each character can only appear once in each string.
		$this->scramble1 = '! #�"$%�&()*+,-./012' . "'" . '3456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]' . "\\" . '^_`abcdefghijklmnopqrstuvwxyz{|}~';
		$this->scramble2 = 'f^jAE]okI�OzU[2�&q1{"3`h5w_794p@6s8?BgP>dFV=m D<TcS%' . "\\" . 'Ze|r:lGK/uCy.Jx)HiQ!#$~(;Lt-R}M' . "'" . 'a,NvW+Ynb*0X';

		if (strlen ( $this->scramble1 ) != strlen ( $this->scramble2 )) {
			trigger_error ( '** SCRAMBLE1 is not same length as SCRAMBLE2 **', E_USER_ERROR );
		}

		$this->adj = 1.75; // this value is added to the rolling fudgefactors
		$this->mod = 3; // if divisible by this the adjustment is made negative
	}

	function _convertKey($key) {
		if (empty ( $key )) {
			$this->errors [] = 'No value has been supplied for the encryption key';
			return;
		}

		$array [] = strlen ( $key );
		$tot = 0;
		for($i = 0; $i < strlen ( $key ); $i ++) {
			$char = substr ( $key, $i, 1 );
			$num = strpos ( $this->scramble1, $char );

			if ($num === false) {
				$this->errors [] = "Key contains an invalid character ($char)";
				return;
			}

			$array [] = $num;
			$tot = $tot + $num;
		}

		$array [] = $tot;

		return $array;
	}

	function _applyFudgeFactor(& $fudgefactor) {
		$fudge = array_shift ( $fudgefactor );
		$fudge = $fudge + $this->adj;
		$fudgefactor [] = $fudge;

		if (! empty ( $this->mod )) { // if modifier has been supplied
			if ($fudge % $this->mod == 0) { // if it is divisible by modifier
				$fudge = $fudge * - 1; // reverse then sign
			} // if
		}

		return $fudge;
	}

	function _checkRange($num) {
		$num = round ( $num );
		$limit = strlen ( $this->scramble1 );

		while ( $num >= $limit ) {
			$num = $num - $limit;
		}

		while ( $num < 0 ) {
			$num = $num + $limit;
		}

		return $num;
	}

	function _encrypt($key, $source, $sourcelen = 0) {
		$this->errors = array ();

		$fudgefactor = $this->_convertKey ( $key );
		if ($this->errors)
			return;
		if (empty ( $source )) {
			$this->errors [] = 'No value has been supplied for encryption';
			return;
		}

		while ( strlen ( $source ) < $sourcelen ) {
			$source .= ' ';
		}

		$target = NULL;
		$factor2 = 0;
		for($i = 0; $i < strlen ( $source ); $i ++) {
			$char1 = substr ( $source, $i, 1 );
			$num1 = strpos ( $this->scramble1, $char1 );
			if ($num1 === false) {
				$this->errors [] = "Source string contains an invalid character ($char1)";
				return;
			}

			$adj = $this->_applyFudgeFactor ( $fudgefactor );
			$factor1 = $factor2 + $adj; // accumulate in $factor1

			$num2 = round ( $factor1 ) + $num1; // generate offset for $scramble2
			$num2 = $this->_checkRange ( $num2 ); // check range
			$factor2 = $factor1 + $num2; // accumulate in $factor
			$char2 = substr ( $this->scramble2, $num2, 1 );
			$target .= $char2;
		}

		return $target;
	}

	function _decrypt($key, $source) {
		$this->errors = array ();

		$fudgefactor = $this->_convertKey ( $key );
		if ($this->errors) {
			return;
		}

		if (empty ( $source )) {
			$this->errors [] = 'No value has been supplied for decryption';
			return;
		}

		$target = NULL;
		$factor2 = 0;
		for($i = 0; $i < strlen ( $source ); $i ++) {
			$char2 = substr ( $source, $i, 1 );
			$num2 = strpos ( $this->scramble2, $char2 );

			if ($num2 === false) {
				$this->errors [] = "Source string contains an invalid character ($char2)";
				return;
			}

			$adj = $this->_applyFudgeFactor ( $fudgefactor );
			$factor1 = $factor2 + $adj;
			$num1 = $num2 - round ( $factor1 ); // generate offset for $scramble1
			$num1 = $this->_checkRange ( $num1 ); // check range
			$factor2 = $factor1 + $num2; // accumulate in $factor2
			$char1 = substr ( $this->scramble1, $num1, 1 );
			$target .= $char1;
		}

		return rtrim ( $target );
	}

	function encrypt($plain) {
		$enc = $plain;
		$enc = $this->_encrypt ( $this->key, $enc, $this->keylen );
		$enc = base64_encode ( $enc );
		return $enc;
	}

	function decrypt($enc) {
		$plain = $enc;
		$plain = base64_decode ( $plain );
		$plain = $this->_decrypt ( $this->key, $plain, $this->keylen );
		return $plain;
	}
}
?>