<?php
include_once (dirname ( __FILE__ ) . "/../functions.php");

if ("" . @$argv [1] == "") {
	echo "You need to supply a board version on the command line\n";
	exit ( 0 );
}

file_put_contents ( dirname ( __FILE__ ) . "/../board.txt", $argv [1] );

setupTables ();
setupGpio ();
setupSensorsScript ();
setupTriggersScript ();
cleanFile("/boot/config.txt");
installFile(dirname ( __FILE__ ) . "/../res/install_boot_config.txt", "/boot/config.txt");

?>
