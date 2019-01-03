<?php

const TIMESTAMP = "timestamp";
const IMSORGID = "IMSOrgId";
const ORGID = "OrgId";
const TENANT_ID = "tenantId";
const JOURNEY_UID = "journeyUID";
const JOURNEY_VER_UID = "journeyVerUID";
const ACTION_TYPE = "actionType";
const ACTION_UID = "actionUID";
const DATA_PROVIDER_TYPE = "dataProviderType";
const DATA_ENTITY_UID = "dataEntityUID";
const EVENT_ID = "eventId";
const FLOW_CONTROL_TYPE = "flowControlType";
const FLOW_CONTROL_ID = "flowControlId";

const JOURNEY_ENTERED = "EnteredJourneyInstance";
const JOURNEY_EXITED = "ExitedJourneyInstance";
const ERROR_IN_JOURNEY = "ErrorInJourneyInstance";
const ERROR_IN_EVENT = "ErrorInEventProcessing";
const ERROR_IN_ACTION = "ErrorInActionExecution";
const ERROR_IN_ENRICHMENT = "ErrorInEnrichment";
const EXECUTED_EVENT = "ExecutedEvent";
const EXECUTED_ACTION = "ExecutedAction";
const EXECUTED_FLOW_CONTROL = "ExecutedFlowControl";
const EXECUTED_ENRICHMENT = "ExecutedEnrichment";


function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}



function probability($p) {
    $n = mt_rand(0, 1/$p-1);
    return ($n == 1);
}



function getJourneyDetails() {
    $journeyDetails = file_get_contents("input.json");
    if ( empty($journeyDetails) ) {
        die("Couldn't find journey version information.");
    }
    return json_decode($journeyDetails, TRUE);
}



function getNode($journeyVer, $elementId) {
    $nodes = $journeyVer["ui"]["nodes"];
    if ( array_key_exists($elementId, $nodes) ) {
        return $nodes[$elementId];
    }

    return FALSE;
}



function getSourceEdgesForNode($edges, $nodeId) {
    $sEdges = array();
    foreach ($edges as $edgeId => $edge) {
        $source = $edge["source"];
        if ( $nodeId == $source["elementId"] ) {
            array_push($sEdges, $edgeId);
        }
    }
    return $sEdges;
}



function getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp) {
    $event = array();
    $event[JOURNEY_VER_UID] = $journeyVer["uid"];
    $event[JOURNEY_UID] = $journey["uid"];
    $event[ORGID] = $orgId;
    $event[TENANT_ID] = $tenantId;
    $event[TIMESTAMP] = $timestamp*1000 + mt_rand(0, 1000);
    return $event;
}



function createVoyagerJourneyEnteredEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp) {
    $event = getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
    $event[JOURNEY_ENTERED ] = 1;
    return $event;
}



function createVoyagerJourneyExitedEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp) {
    $event = getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
    $event[JOURNEY_EXITED  ] = 1;
    return $event;
}




function createVoyagerJourneyFailureEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp) {
    $event = getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
    $event[ERROR_IN_JOURNEY ] = 1;
    return $event;
}


function createVoyagerExecutedJumpActionEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $actionUID, $timestamp) {
    $event = getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
    $event[ACTION_UID] = $actionUID;
    $event[EXECUTED_JUMP_ACTION ] = 1;
    return $event;
}



function createVoyagerExecutedFlowControlEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $flowControlId, $flowControlType, $timestamp) {
    $event = getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
    $event[FLOW_CONTROL_ID] = $flowControlId;
    $event[FLOW_CONTROL_TYPE] = $flowControlType;
    $event[EXECUTED_FLOW_CONTROL] = 1;
    return $event;
}




function createVoyagerExecutedEventEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $eventId, $isError, $timestamp) {
    $event = getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
    $event[EVENT_ID] = $eventId;
    $event[EXECUTED_EVENT ] = 1;
    if ( $isError ) {
        $event[ERROR_IN_EVENT ] = 1;
    }
    return $event;
}


function createVoyagerExecutedActionEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $actionUID, $actionType, $isError, $timestamp) {
    $event = getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
    $event[ACTION_UID] = $actionUID;
    $event[ACTION_TYPE] = $actionType;
    #$event[IS_JUMP_ACTION] = false;
    $event[EXECUTED_ACTION] = 1;
    if ( $isError ) {
        $event[ERROR_IN_ACTION ] = 1;
    }
    return $event;
}



function createVoyagerExecutedEnrichmentEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $dataProviderType, $dataEntityUID, $isError, $timestamp) {
    $event = getBaseJourneyEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
    $event[DATA_PROVIDER_TYPE] = $dataProviderType;
    $event[DATA_ENTITY_UID] = $dataEntityUID;
    $event[EXECUTED_ENRICHMENT] = 1;
    if ( $isError ) {
        $event[ERROR_IN_ENRICHMENT ] = 1;
    }
    return $event;
}



function createUserJourneyExperience($journey, $journeyVer, $orgId, $tenantId, $journeyFailure, $errorPercent, $eventCount, $exitProbability, $startDate, $endDate) {

    $nodes = $journeyVer["ui"]["nodes"];
    $edges = $journeyVer["ui"]["edges"];
    $targetElementId = "start";
    $n = 1000 * (1 - $exitProbability);
    $isComplete = (mt_rand(0, 999) <= $n);
    $c = 9999;
    if ( $isComplete ) {
        $c = mt_rand(0, 5);
    }

    $eventsList = array();


    while($c-- > 0) {
        $edgesAvailable = getSourceEdgesForNode($edges, $targetElementId);
        $rd = mt_rand(0, count($edgesAvailable)-1);
        $edgeId = $edgesAvailable[$rd];
        $edge = $edges[$edgeId];

        $sourceElementId = $edge["source"]["elementId"];
        $targetElementId = $edge["target"]["elementId"];
        $sourceNode = $nodes[$sourceElementId];
        $targetNode = $nodes[$targetElementId];
        $sourceNodeType = $sourceNode["type"];
        $targetNodeType = $targetNode["type"];
        $isError = probability($errorPercent);

        $timestamp = mt_rand($startDate, $endDate);
        $startDate = $timestamp;

        switch ($sourceNodeType) {
            case "start":
                $event = createVoyagerJourneyEnteredEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
                break;

            case "event":
                $eventId = gen_uuid();
                $event = createVoyagerExecutedEventEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $eventId, $isError, $timestamp);
                break;

            case "condition":
                $flowControlId = $edgeId; // TO DO
                $flowControlType = "condition";
                $event = createVoyagerExecutedFlowControlEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $flowControlId, $flowControlType, $timestamp);
                break;


            case "enrichment":
                $flowControlId = $edgeId; // TO DO
                $flowControlType = "condition";
                $event = createVoyagerExecutedEnrichmentEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $flowControlId, $flowControlType, $isError, $timestamp);
                break;

            case "action":
                $actionUID = $sourceNode["id"];
                $actionType = $sourceNode["name"];
                $event = createVoyagerExecutedActionEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $actionUID, $actionType, $isError, $timestamp);
                //die("Unknown action type: " + $actionType);
                break;

            default:
                die("Unknown node type: " + $sourceNodeType);
        }

        array_push($eventsList, $event);

        if ( $targetNodeType == "end" ) {
            $event = createVoyagerJourneyExitedEvent($journeyVer, $journey, $orgId, $tenantId, $eventCount, $timestamp);
            array_push($eventsList, $event);
            break;
        }

        if ( $isError ) {
            break;
        }
    }

    return $eventsList;
}




function saveEventsToFile($dataFile, $events) {
    $len = count($events);
    for ( $i = 0 ; $i < $len ; $i ++ ) {
        $ch = PHP_EOL;
        if ( $i == $len - 1) {
            $ch = "";
        }
        fwrite($dataFile, json_encode($events[$i]) . $ch);
    }
}

?>