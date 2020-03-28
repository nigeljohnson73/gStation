<?php

include_once (dirname ( __FILE__ ) . "/../functions.php");

function execCmd($cmd) {
	echo "Executing: ".$cmd."\n";
	exec($cmd);
}

function setupTrigger($i) {
	global $trigger_pin_1, $trigger_pin_2, $trigger_pin_3, $trigger_pin_4, $trigger_pin_5, $trigger_pin_6;
	$pin = "trigger_pin_".$i;

	$cmd = "gpio -g mode ".$$pin." out";
	execCmd($cmd);
}

function trigger($i, $tf) {
	global $trigger_pin_1, $trigger_pin_2, $trigger_pin_3, $trigger_pin_4, $trigger_pin_5, $trigger_pin_6;
	$pin = "trigger_pin_".$i;

	$cmd = "gpio -g write ".$$pin." ".($tf+0);
	execCmd($cmd);

}

setupGpio();

for($i = 0; $i < 6; $i++) {
	setupTrigger($i+1);
}

for($i = 0; $i < 6; $i++) {
	$delay = 0.1;
	trigger($i+1, 1);
	usleep($delay * 1000000);
}

for($i = 0; $i < 6; $i++) {
	$delay = 0.1;
	trigger($i+1, 0);
	usleep($delay * 1000000);
}

for($i = 0; $i < 6; $i++) {
	$delay = 0.1;
	trigger($i+1, 1);
	usleep($delay * 1000000);
	trigger($i+1, 0);
	usleep($delay * 1000000);
}

for($j=0; $j < 5; $j++) {
	$delay = 0.3;
	for($i = 0; $i < 6; $i++) {
		trigger($i+1, 1);
	}
	usleep($delay * 1000000);
	for($i = 0; $i < 6; $i++) {
		trigger($i+1, 0);
	}
	usleep($delay * 1000000);
}

?>
