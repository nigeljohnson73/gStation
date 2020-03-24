<?php 

include_once (dirname ( __FILE__ ) . "/../functions.php");

function getVmStats() {
	$hdd = exec ( "df -k | grep '^\/dev\/root'" );
	// echo "free: '$free'\n";
	$bits = explode ( " ", preg_replace ( '/\s+/', " ", trim ( $hdd ) ) );
	// echo "bits: " . ob_print_r ( $bits ) . "\n";

	$keys = [ ];
	$keys [] = "fs";
	$keys [] = "blocks";
	$keys [] = "used";
	$keys [] = "available";
	$keys [] = "use";
	$keys [] = "mount";

	$hdd = new StdClass ();
	foreach ( $bits as $k => $v ) {
		$key = $keys [$k];
		$hdd->$key = $v;
	}
	// echo "HDD: ".ob_print_r($hdd)."\n";

	$free = exec ( "free | grep '^Mem:'" );
	// echo "free: '$free'\n";
	$bits = explode ( " ", preg_replace ( '/\s+/', " ", trim ( $free ) ) );
	// echo "bits: " . ob_print_r ( $bits ) . "\n";

	$keys = [ ];
	$keys [] = "dummy";
	$keys [] = "total";
	$keys [] = "used";
	$keys [] = "free";
	$keys [] = "shared";
	$keys [] = "cache";
	$keys [] = "available";

	$free = new StdClass ();
	foreach ( $bits as $k => $v ) {
		$key = $keys [$k];
		$free->$key = $v;
	}

	$vmstat = exec ( "vmstat 1 2" );
	// echo "vmstat: '$vmstat'\n";
	$bits = explode ( " ", preg_replace ( '/\s+/', " ", trim ( $vmstat ) ) );
	// echo "bits: " . ob_print_r ( $bits ) . "\n";

	$keys = [ ];
	$keys [] = "procs_r";
	$keys [] = "procs_b";
	$keys [] = "mem_swapd";
	$keys [] = "mem_free";
	$keys [] = "mem_buff";
	$keys [] = "mem_cache";
	$keys [] = "swap_si";
	$keys [] = "swap_so";
	$keys [] = "io_bi";
	$keys [] = "io_bo";
	$keys [] = "sys_in";
	$keys [] = "sys_cs";
	$keys [] = "procs_r";
	$keys [] = "cpu_us";
	$keys [] = "cpu_sy";
	$keys [] = "cpu_id";
	$keys [] = "cpu_wa";
	$keys [] = "cpu_st";

	$vmstat = new StdClass ();
	foreach ( $bits as $k => $v ) {
		$key = $keys [$k];
		$vmstat->$key = $v;
	}

	$throt = exec ( "vcgencmd get_throttled" );
	$throt = explode ( "0x", $throt ) [1];
	$throt = hexdec ( $throt );

	$temp = exec ( "vcgencmd measure_temp" );
	$temp = explode ( "=", $temp ) [1];
	$temp = explode ( "'", $temp ) [0];

	$ret = new StdClass ();
	$ret->sd_free = round ( 100 * $hdd->available / $hdd->blocks, 3 );
	$ret->cpu_wait = $vmstat->cpu_wa;
	$ret->cpu_load = $vmstat->cpu_sy + $vmstat->cpu_us;
	$ret->mem_total = $free->total;
	$ret->mem_avail = $free->available + $vmstat->mem_cache;
	$ret->mem_load = round ( 100 * ($ret->mem_total - $ret->mem_avail) / $ret->mem_total, 3 );
	$ret->temperature = $temp;
	$ret->under_voltage = bitCompare ( "UNDERVOLT", $throt, (1 << 0), (1 << 16) );
	$ret->frequency_capped = bitCompare ( "FREQCAP", $throt, (1 << 1), (1 << 17) );
	$ret->throttled = bitCompare ( "THROTTLED", $throt, (1 << 2), (1 << 18) );
	$ret->soft_temperature_limited = bitCompare ( "TEMPLIMIT", $throt, (1 << 3), (1 << 19) );

	return $ret;
}

//while(true) {
	$data = getVmStats();
	$data->event=time();
	$data->name="ZONE0";

	$msg = ob_print_r($data);
	logger(LL_SYSTEM, $msg);
	echo $msg;
	file_put_contents("/tmp/server_check.log", $msg);
	file_put_contents("/tmp/server_data.json", json_encode($data));
//	sleep (1);
//}

// Exit so monitor restarts us
exit(99);

?>
