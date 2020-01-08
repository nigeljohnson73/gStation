<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

if ("" . @$argv [2] == "") {
	echo $argv[0]." <file> <key>\n";
	exit ( 0 );
}

cleanFile($argv[1], $argv[2]);

function cleanFile($filename, $key) {
	if(!file_exists($filename)) {
		echo "File '".$filename."' cannot be accessed\n";
		return false;
	} else {
		$quiet = false;
		$output = "";

		$lines = file($filename);
		foreach($lines as $line) {
			if(strpos($line, $key) === 0) {
				$quiet = !$quiet;
			} else {
				if(!$quiet) {
					$output .= $line;
				}
			}	
		}

		//echo $output;
		$fn = "/tmp/cleansedata.".time().".txt";
		file_put_contents($fn, $output);

$cmd = "sudo rm -f ".$filename.".orig";
//echo "Removing .orig file: $cmd\n";
		exec($cmd);

$cmd =	"sudo cp ".$filename." ".$filename.".orig";
echo "Backing up original file to '".$filename.".orig'\n";
		exec($cmd);

$cmd = "sudo cp ".$fn." ".$filename;
//echo "Overwriting original file: $cmd\n";
		exec($cmd);

//echo "Removing temp file\n";
		@unlink($fn);
	}

	return true;
}
 
//GSTATION start ignore
// ignore this
//GSTATION end ignore
?>
