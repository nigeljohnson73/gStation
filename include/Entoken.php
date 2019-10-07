<?php

class Entoken {
	var $use_rev = false;
	/**
	 * ************************************************************************
	 * The base character set that will be used to derive the lookup tables.
	 */
	var $baseChars = "";

	/**
	 * ************************************************************************
	 * The number of characters to pad to if not specified in the string.
	 */
	var $pad2chars = 9;

	/**
	 * ************************************************************************
	 * The character to use to pad the chunks.
	 */
	var $padchar = "-";

	/**
	 * ************************************************************************
	 * The base we are working in is the number of characters in the base set.
	 */
	var $base = 0;

	/**
	 * ************************************************************************
	 * the lookup character translation set.
	 * This determines how a character
	 * in the ascii set maps to our character set, for example 1iIjJlL are all
	 * synonmous and so map to the same character.
	 */
	var $baseCharLookup = array ();

	/**
	 * ************************************************************************
	 * The base map will be used to rotate the string so that zero can map to a
	 * pseudo random character depending on its position in the number.
	 */
	var $baseMap = array ();

	/**
	 * ************************************************************************
	 * The translator array will get populated so as to be able to decode
	 * incoming strings.
	 */
	var $translator = array ();

	/**
	 * ************************************************************************
	 * The constructor sets up the defaults for the class
	 */
	function __construct() {
		// This is the default character set with the ambiguous characters
		// removed, they will be mapped later.
		// $this->baseChars = "abdeghikmnopstuwyz347";

		// The default set has ben randomised so the customer 1 will get a
		// random looking string. the ninth character is special if you need
		// to ever need 9 character codes (giving 1.8trillion combos) then
		// anyone with the 8 digit one will automatically get this character
		// added to their code... if you are specialising stuff, you probably
		// want to take this into account.
		$this->baseChars = "nd7zipgabksy4ro3wthemu"; // randomised

		// Set the base we are working in.
		$this->base = strlen ( $this->baseChars );

		// build the translator array with the key being the destination
		// and the value being anything that will map into it.
		$this->translator ['o'] = "0q";
		$this->translator ['i'] = "lj1f";
		$this->translator ['z'] = "2";
		$this->translator ['s'] = "5";
		$this->translator ['g'] = "69";
		$this->translator ['b'] = "8";
		$this->translator ['e'] = "c";
		$this->translator ['u'] = "v";
		$this->translator ['x'] = "y";

		// turn the translator array into the lookup array
		foreach ( $this->translator as $bchar => $bstr ) {
			for($i = 0; $i < strlen ( $bstr ); $i ++) {
				$this->baseCharLookup [$bstr [$i]] = $bchar;
				$this->baseCharLookup [strtoupper ( $bstr [$i] )] = $bchar;
			}
		}

		// now map the characters we know about
		for($i = 0; $i < strlen ( $this->baseChars ); $i ++) {
			$this->baseCharLookup [$this->baseChars [$i]] = $this->baseChars [$i];
			$this->baseCharLookup [strtoupper ( $this->baseChars [$i] )] = $this->baseChars [$i];
		}

		// now generate the lookup tables by cycling the original string
		$clist = $this->baseChars;
		for($i = 0; $i < $this->base; $i ++) {
			$this->baseMap [$i] = $clist;
			$c = $clist [0];
			$r = substr ( $clist, 1 );
			$clist = $r . $c;
		}
	}

	/**
	 * ************************************************************************
	 * The lookup function is used to rationalise an encoded value string.
	 * It
	 * translates the values into the correct character set and filters out
	 * values that should not be there. This is potentially dangerous, but if
	 * punctuation is used it can be filtered out.
	 */
	protected function _lookupChar($c) {
		if (! isset ( $this->baseCharLookup [$c] )) {
			// Filter out uncknown characters.
			return "";
		}
		return $this->baseCharLookup [$c];
	}

	protected function _rationaliseString($str, $do_trans = true) {
		$nstr = "";
		for($i = 0; $i < strlen ( $str ); $i ++) {
			$x = $this->_lookupChar ( $str [$i] );
			if ($do_trans) {
				$nstr .= $x;
			} elseif (strlen ( $x )) {
				$nstr .= $str [$i];
			}
		}
		return $nstr;
	}

	function layout($str) {
		$str = $this->_rationaliseString ( $str, false );
		return substr ( $str, 0, 3 ) . $this->padchar . substr ( $str, 3, 3 ) . $this->padchar . substr ( $str, 6 );
	}

	function enc($val, $chars = null) {
		if ($chars === null) {
			$chars = $this->pad2chars;
		}

		$remainder = 0;
		$newval = "";

		$lut = 0;
		while ( $val > 0 ) {
			$remainder = bcmod ( $val, $this->base );
			$val = (($val - $remainder) / $this->base);
			$newval .= $this->baseMap [$lut ++] [$remainder];
		}

		// now pad with zeros to the length required
		while ( $lut < $chars ) {
			$zero = $this->baseMap [$lut ++] [0];
			$newval .= $zero;
		}
		if ($this->use_rev) {
			$newval = strrev ( $newval );
		}
		return $this->layout ( $newval );
	}

	function dec($str) {
		$val = 0;

		if ($this->use_rev) {
			$str = strrev ( $str );
		}
		$str = $this->_rationaliseString ( $str );

		for($i = 0; $i < strlen ( $str ); $i ++) {
			$x = strpos ( $this->baseMap [$i], $str [$i] );
			if ($x === false) {
				return - 1;
			}
			$val += $x * pow ( $this->base, ($i) );
		}
		return $val;
	}
}

/*
 * function getNumChars($value) {
 * global $ed;
 *
 * $remainder = 0;
 * $n = 0;
 *
 * while ($value > 0) {
 * $remainder = bcmod($value, $ed->base);
 * $value = (($value - $remainder) / $ed->base);
 * $n++;
 * }
 *
 * return $n;
 * }
 *
 * function checkNum($nchars) {
 * global $ed;
 * $number = pow($ed->base, $nchars) - 1;
 * echo "The string for " . number_format($number) . " in base $ed->base is ";
 * echo $ed->enc($number);
 * echo ", requiring " . getNumChars($number) . " characters ";
 * echo "<br />";
 * }
 *
 * function doStr($str) {
 * global $ed;
 * $l = $ed->layout($str);
 * $d = $ed->dec($str);
 * $e = $ed->enc($d);
 * echo $str . "(" . $l . "): " . number_format($d) . " (" . $e . ")<br />";
 * }
 *
 * function isUkPostcode($postcode) {
 * $postcode = strtoupper(str_replace(chr(32), '', $postcode));
 * if (ereg("^(GIR0AA)|(TDCU1ZZ)|((([A-PR-UWYZ][0-9][0-9]?)|" .
 * "(([A-PR-UWYZ][A-HK-Y][0-9][0-9]?)|" .
 * "(([A-PR-UWYZ][0-9][A-HJKSTUW])|" .
 * "([A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVWXY]))))" .
 * "[0-9][ABD-HJLNP-UW-Z]{2})$", $postcode))
 * return $postcode;
 * else
 * return FALSE;
 * }
 * echo "<pre>";
 * $ed = new Entoken();
 *
 * checkNum(8);
 * checkNum(9);
 *
 * $number = pow($ed->base, 8) - 1;
 * echo number_format($number) . " translated into 9 characters: ";
 * echo $ed->enc($number, 9);
 * echo "<br />";
 *
 * doStr("badass01");
 * doStr("bad.ass-01");
 * doStr("banana01");
 * doStr("top bad ass");
 * doStr("top bad as");
 * doStr("arsehole");
 * doStr("imawhor");
 *
 * $china = 1321851888;
 * $world = 6900000000;
 * echo "====fire guard stuff=======================<br />";
 * $v = "aeiouy";
 * $c = "bdghkmnpstwz";
 * $c = "bcdfghkmnprstvwxz";
 * $pod = strlen($c . $v) * strlen($v) * strlen($c);
 * $x = pow($pod, 2); // * pow($ed->base,2);
 * echo "a pod consists of " . number_format($pod) . " combos of ";
 * echo number_format($x) . " values (total: " . number_format($x * $pod) . " - " . number_format(($x * $pod) / 1.75) . ")<br />";
 * echo "china needs " . ($china / $x) . " podpax<br/>";
 * echo "the world needs " . ($world / $x) . " podpax<br/>";
 * echo "====base 23 stuff=======================<br />";
 * // base 23 stuff
 * $pod = pow($ed->base, 2);
 * $pod2 = pow($ed->base, 3);
 * $x = pow($ed->base, 6);
 * echo "a pod consists of " . number_format($pod) . " combos of ";
 * echo number_format($x) . " values (total: " . number_format($x * $pod) . " - " . number_format(($x * $pod) / 1.75) . ")<br />";
 * echo "china needs " . ($china / $x) . " podpax<br/>";
 * echo "the world needs " . ($world / $x) . " podpax<br/>";
 *
 * for ($i = 0; $i < 10; $i++) {
 * echo $i . " --> " . $ed->enc($i) . "<br />";
 * }
 *
 * $s = "nd7zipgabksy4ro3wthemu";
 * $x = $ed->dec($s);
 * echo $s . " ==> " . $x . "<br />";
 * echo number_format($pod2) . " members before 'zip ga' changes <br />";
 *
 * print_r($ed->baseMap);
 *
 * echo "</pre>";
 */
function gtoken() {
	$t = floor ( my_microtime ( true ) * 10000 );
	// $t *= 2;
	$ed = new Entoken ();
	$ret = $ed->enc ( $t );
	$ret [0] = "3";
	$x = $ret [0];
	// echo "gtoken raw: $ret\n";
	if (is_numeric ( $x )) {
		// echo " got numeric first char: '$x'\n";
		$x = rand ( 0, 25 );
		// echo " replace with : chr('$x')\n";
		$x += ord ( 'a' );
		$ret [0] = chr ( $x );
	}
	// echo "returning gtoken: $ret\n";
	return $ret;
}

?>