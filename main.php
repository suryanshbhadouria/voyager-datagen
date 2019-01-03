<?php

require_once("lib.php");

// Constants
$journeyFailure = 0.01;
$errorPercent = 0.1;
$eventCount = 1;
$totalUsers = 10000000;
$exitProbability = 0.98;

$startDate = 1514745000;
$endDate = 1535537709;

// Query Params
$journeyDetails = getJourneyDetails();
$journey = $journeyDetails["journey"];
$journeyVer = $journeyDetails["journeyVer"];

$start = time();
$fileName = "data/" . $journey["uid"] . ".json";
$dataFile = fopen($fileName, "w");
for ( $i = 0 ; $i < $totalUsers ; $i ++ ) {
    $orgId = "all";
    $tenantId = $journey["tenantId"];
    $events = createUserJourneyExperience($journey, $journeyVer, $orgId, $tenantId, $journeyFailure, $errorPercent, $eventCount, $exitProbability, $startDate, $endDate);
    saveEventsToFile($dataFile, $events);
}

fclose($dataFile);
$end = time();
echo "Data generated in file: $fileName TimeTaken: $end-$start seconds";
?>
