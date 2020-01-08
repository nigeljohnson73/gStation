<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

if ("" . @$argv [2] == "") {
	echo $argv[0]." <src file> <original file>\n";
	exit ( 0 );
}

installFile($argv[1], $argv[2]);

function installFile($src, $filename) {
	if(!file_exists($filename)) {
		echo "File '".$filename."' cannot be accessed\n";
		return false;
	} else {
setupGpio(true);
global $led_pin;
$repl = array();
$repl["[[LED_PIN]]"] = $led_pin;

$c = file_get_contents($src);
foreach($repl as $k => $v) {
	$c = str_replace($k, $v, $c);
}

//echo $c."\n";


		$fn = "/tmp/cleansedata.".time().".txt";
		file_put_contents($fn, $c);
$cmd = "sudo rm -f ".$filename.".orig";
echo "Backing up original file to '".$filename.".orig'\n";
		exec($cmd);

$cmd =  "sudo cp ".$filename." ".$filename.".orig";
//echo "Copying current file to .orig file: $cmd\n";
		exec($cmd);


$cmd = "sudo cat ".$fn ." | sudo tee -a ".$filename;
//echo "Adding update: $cmd\n";
		exec($cmd);

//echo "Removing temp file\n";
		@unlink($fn);
	}

	return true;
}
?>
