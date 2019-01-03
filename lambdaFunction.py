import os
from dateutil.parser import parse
from datetime import datetime, timedelta
import requests
import json


def lambda_handler(event, context):
    s3location = os.environ['s3location']
    overlord1 = os.environ['overlord1']
    overlord2 = os.environ['overlord2']
    f = '{ "type" : "index_hadoop", "spec" : { "dataSchema" : { "dataSource" : "campaign_reporting_event_shared_ds", "parser" : { "type" : "hadoopyString", "parseSpec" : { "format" : "json", "timestampSpec" : { "column" : "timestamp", "format" : "millis" }, "dimensionsSpec" : { "dimensions": ["eventType","deliveryId","failureType","failureReason","domain","ip","isSeedMember","isQuarantine","variant","isABTest","device","platform","trackingUrl","trackingUrlType","IMSOrgId","instanceId","status","browser","messageType","isNegated","campaignId","senderDomain","mobileApp","pushPlatform","isBlacklisted","offerId","offerPlacementId","offerActivityId","offerType","trackingUrlLabel","trackingUrlCategory","birthDate","gender","city","countryCode","stateCode","zipCode","transactionalTemplateId","recurringDelId","broadlogId","cusField1","cusField2","cusField3","cusField4","cusField5","cusField6","cusField7","cusField8","cusField9","cusField10","cusField11","cusField12","cusField13","cusField14","cusField15","cusField16","cusField17","cusField18","cusField19","cusField20","OrgId","tenantId","journeyUID","journeyVerUID","actionType","actionUID","dataProviderType","dataEntityUID","eventId","flowControlId","flowControlType","externalEventType"], "dimensionExclusions" : [], "spatialDimensions" : [] } } }, "metricsSpec" : [{"type":"longMax","name":"eventCount","fieldName":"eventCount"},{"type":"longSum","name":"EnteredJourneyInstance","fieldName":"EnteredJourneyInstance"},{"type":"longSum","name":"ExitedJourneyInstance","fieldName":"ExitedJourneyInstance"},{"type":"longSum","name":"ErrorInJourneyInstance","fieldName":"ErrorInJourneyInstance"},{"type":"longSum","name":"ErrorInEventProcessing","fieldName":"ErrorInEventProcessing"},{"type":"longSum","name":"ErrorInActionExecution","fieldName":"ErrorInActionExecution"},{"type":"longSum","name":"ErrorInJumpActionExecution","fieldName":"ErrorInJumpActionExecution"},{"type":"longSum","name":"ErrorInEnrichment","fieldName":"ErrorInEnrichment"},{"type":"longSum","name":"ExecutedEvent","fieldName":"ExecutedEvent"},{"type":"longSum","name":"ExecutedAction","fieldName":"ExecutedAction"},{"type":"longSum","name":"ExecutedFlowControl","fieldName":"ExecutedFlowControl"},{"type":"longSum","name":"ExecutedJumpAction","fieldName":"ExecutedJumpAction"},{"type":"longSum","name":"ExecutedEnrichment","fieldName":"ExecutedEnrichment"},{"type":"longSum","name":"JumpToJourney","fieldName":"JumpToJourney"},{"type":"thetaSketch","name":"recipientId_sketch","fieldName":"recipientId"}], "granularitySpec" : { "type" : "uniform", "segmentGranularity" : "DAY", "queryGranularity" : "second", "intervals" : [ "'

    duration = int(os.environ.get('duration', '2'))
    if 'dateFrom' not in os.environ:
        pastDay = datetime.utcnow() - timedelta(duration)
        dateFrom = pastDay.replace(hour=0, minute=0, second=0)
    else:
        dateFrom = parse(os.environ['dateFrom'])
    if 'dateTo' not in os.environ:
        dateTo = dateFrom + timedelta(duration)
    else:
        dateTo = parse(os.environ['dateTo'])
    f += dateFrom.strftime("%Y-%m-%dT%H:%M:%SZ") + '/' + dateTo.strftime("%Y-%m-%dT%H:%M:%SZ")
    f += '" ] } }, "ioConfig" : { "type" : "hadoop", "inputSpec" : { "type" : "granularity", "inputPath" : "'
    f += s3location
    f += '", "dataGranularity" : "hour", "filePattern" : "'
    f += '.*\\\\.gz'
    f += '" } }, "tuningConfig" : { "type": "hadoop","partitionsSpec": {"type": "hashed", "targetPartitionSize": 10000000}, "jobProperties" : { "mapreduce.job.classloader": "true", "fs.s3.awsAccessKeyId" : "", "fs.s3.awsSecretAccessKey" : "", "fs.s3.impl" : "org.apache.hadoop.fs.s3a.S3AFileSystem", "fs.s3a.awsAccessKeyId" : "", "fs.s3a.awsSecretAccessKey" : "", "fs.s3a.impl" : "org.apache.hadoop.fs.s3a.S3AFileSystem", "io.compression.codecs" : "org.apache.hadoop.io.compress.Lz4Codec,org.apache.hadoop.io.compress.GzipCodec,org.apache.hadoop.io.compress.DefaultCodec,org.apache.hadoop.io.compress.BZip2Codec,org.apache.hadoop.io.compress.SnappyCodec" } } }, "hadoopDependencyCoordinates": ["org.apache.hadoop:hadoop-client:2.7.3"] }'
    payload = json.loads(f);
    urlpath = '/druid/indexer/v1/task'
    output = None
    r1response = None
    r2response = None
    try:
        r1 = requests.post(overlord1 + urlpath, json=payload)
        if r1.status_code == requests.codes.ok:
            output = r1.text
        else:
            r1response = 'Status Code: ' + str(r1.status_code) + ' ' + r1.text
    except requests.ConnectionError as e1:
        r1response = 'Exception: ' + str(e1)

    if not output is None:
        return output
    try:
        r2 = requests.post(overlord2 + urlpath, json=payload)
        if r2.status_code == requests.codes.ok:
            output = r2.text;
        else:
            r2response = 'Status Code: ' + str(r2.status_code) + ' ' + r2.text
    except requests.ConnectionError as e2:
        r2response = 'Exception: ' + str(e2)

    if not output is None:
        return output
    else:
        return 'Could Not Send Task. \nOverlord 1 returned: ', r1response, '\nOverlord 2 returned: ', r2response
