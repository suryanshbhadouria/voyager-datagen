<?php
set_time_limit(0);
ob_implicit_flush(1);

function unzipFile($file_name) {
	$buffer_size = 4096; // read 4kb at a time
	$out_file_name = str_replace('.gz', '', $file_name); 

	$file = gzopen($file_name, 'rb');
	$out_file = fopen($out_file_name, 'wb'); 

	while ( ! gzeof($file) ) {
	    fwrite($out_file, gzread($file, $buffer_size));
	}

	fclose($out_file);
	gzclose($file);
	return $out_file_name;
}


function getPath($ts, $imsOrgId) {
	$y = date("Y", $ts);
	$m = date("m", $ts);
	$d = date("d", $ts);
	$H = date("H", $ts);
	$modifiedImsOrgId = "campaign_reporting_event_" . str_replace("@", "_", $imsOrgId);
	$path = "y=$y/m=$m/d=$d/H=$H/$modifiedImsOrgId";
	//$path = "y=$y/m=$m/d=$d/H=$H";
	if ( ! file_exists($path) ) {
		mkdir($path, 0777, true);	
	} 
	return "${path}/file_" . microtime(true) . "_" . Thread::getCurrentThreadId() . ".json";
}



function getNormalizedTS($ts) {
	$y = date("Y", $ts);
	$m = date("m", $ts);
	$d = date("d", $ts);
	$H = date("H", $ts);
	return strtotime("$y-$m-$d $H:00:00");
}


function saveRecordsToFile($path, $arr) {
	$counter = 0;
	$size = count($arr);
	//$handle = fopen($path, "w");
	$handle = gzopen($path . ".gz", 'w9');
	while ( $counter < $size ) {
		//fwrite($handle, $arr[$counter++]);
		gzwrite($handle, $arr[$counter++]);
	}
	gzclose($handle);
	//fclose($handle);
	return $counter;
}


function dumpData(&$hash, $total, $t, $tmax, $tmin) {
	printf("\n[%s] Starting dumpData...", date("Y-m-d H:i:s"));
	$forceDump = ($total > $t);
	foreach ( $hash as $ts => $customerData ) {
		foreach ( $customerData as $imsOrgId => $arr ) {
			$len = count($arr);
			if ( $len > 0 && ($len >= $tmax) || ($forceDump && $len >= $tmin) ) {
				printf("\n[%s] Threshold reached. Dumping data...", date("Y-m-d H:i:s"));
				$path = getPath($ts, $imsOrgId);
				$c = saveRecordsToFile($path, $arr);
				unset($hash[$ts][$imsOrgId]);
				printf("\n[%s] Dumped %d records to %s", date("Y-m-d H:i:s"), $c, $path);
			}
		}
	}
	printf("\n[%s] Finished dumpData.", date("Y-m-d H:i:s"));
}


function scan($srcDir, $pattern, $mod) {
	printf("\n\n[%s] Starting scan...", date("Y-m-d H:i:s"));
	$hash = array();
	$readyToDump = false;
	$totalRecords = 0;
	$overallThreshold = 100000;
	$maxThreshold = 100000;
	$minThreshold = 5000;
	$count = 0;
	foreach (new DirectoryIterator($srcDir) as $fileInfo) {
	    if ( $fileInfo->isDot() ) continue;
	    $fileName = $fileInfo->getFilename();
	    $deliveryId = explode("-", $fileName, 2);
	    $deliveryId = $deliveryId[0];
	    if ( $deliveryId % $mod !== $pattern ) {
	    	continue;
	    }
	    $filePathName = $fileInfo->getPathname();
	    if (substr($filePathName, -3) != '.gz') continue;
	    $path = unzipFile($filePathName);
		print("\nDelivery num:".$deliveryId);
	    printf("\n[%s] Sweeping across file [%d] %s...", date("Y-m-d H:i:s"), $count++, $path);
	    $handle = fopen($path, "r");
	    while ( ! feof($handle) ) {
	    	$txtLine = fgets($handle);
	    	$line = json_decode ( $txtLine, true );
	    	$ts = (0 + $line["timestamp"])/1000;
	    	$normalizedTS = getNormalizedTS($ts); 
			$imsOrgId = $line["IMSOrgId"];
			if ( ! array_key_exists($normalizedTS, $hash) ) {
				$hash[$normalizedTS] = array();
			}

			if ( ! array_key_exists($imsOrgId, $hash[$normalizedTS]) ) {
				$hash[$normalizedTS][$imsOrgId] = array();
			}

			array_push($hash[$normalizedTS][$imsOrgId], $txtLine);
			$readyToDump = (count($hash[$normalizedTS][$imsOrgId]) > $maxThreshold);
			$totalRecords++;
	    }
	    fclose($handle);
	    unlink($path);
		
	    printf("\n[%s] Swept %s :: %d", date("Y-m-d H:i:s"), $path, $totalRecords);
		
		dumpData($hash, $totalRecords, 0, 0, 0);
	   /* if ( $readyToDump || $totalRecords >= $overallThreshold ) {
	    	dumpData($hash, $totalRecords, $overallThreshold, $maxThreshold, $minThreshold);
	    	$readyToDump = false;
	    	$totalRecords = 0;
	    }*/
	}

	printf("\n[%s] Swept through all the files. Dumping final records...", date("Y-m-d H:i:s"));
	//dumpData($hash, $totalRecords, 0, 0, 0);
	printf("\n[%s] Completed scan.", date("Y-m-d H:i:s"));
}


$srcDir = $argv[1];
$id = intVal($argv[2]);
$mod = intVal($argv[3]);
scan($srcDir, $id, $mod);
?>