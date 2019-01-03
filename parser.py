import os, time, errno, logging, re, gzip, datetime


def parse(directory):
    for filename in os.listdir(directory):
        logging.info("reading file:" + str(filename))
        filepath = directory + '/' + filename
        with open(filepath) as fp:
            line = fp.readline()
            count = 0
            while line:
                count += 1
                # logging.info("Line {}: {}".format(count, line.strip()))
                split = line.split("}{")
                if (len(split) > 1):
                    for s in split:
                        parseEvent(s)
                else:
                    parseEvent(line)
                line = fp.readline()
    logging.info("Parsing completed")


def parseEvent(event):
    completeEvent = completeEventString(event)
    epoch = getEpoch(completeEvent)
    if epoch is not None:
        filepath = getFilePath(epoch)
        writeToFile(filepath, completeEvent)


def completeEventString(event):
    e = str(event)
    e = e.replace("{", "")
    e = e.replace("}", "")
    e = e.replace("\n", "")
    e = "{" + e + "}\n"
    return e


def getEpoch(event):
    e = str(event)
    split = e.split(",")
    t1 = None
    for s in split:
        if "timestamp" not in s:
            continue
        else:
            timeStr = s.split("\":")
            t1 = long(timeStr[1])
            break
    return t1


def getFilePath(epoch):
    timeStamp = time.gmtime(epoch / 1000)
    year = (str)(timeStamp.tm_year)
    month = timeStamp.tm_mon
    day = timeStamp.tm_mday
    hour = timeStamp.tm_hour
    if (month < 10):
        month = '0' + str(month)
    else:
        month = str(month)
    if (day < 10):
        day = '0' + str(day)
    else:
        day = str(day)
    if (hour < 10):
        hour = '0' + str(hour)
    else:
        hour = str(hour)
    filepath = 'y=' + year + '/m=' + month + '/d=' + day + '/h=' + hour + '/m1.json'
    return filepath


def writeToFile(filepath, event):
    if os.path.exists(filepath):
        append_write = 'a'  # append if already exists
    else:
        if not os.path.exists(os.path.dirname(filepath)):
            try:
                os.makedirs(os.path.dirname(filepath))
            except OSError as exc:  # Guard against race condition
                if exc.errno != errno.EEXIST:
                    raise
        append_write = 'w'  # make a new file if not

    f = open(filepath, append_write)
    f.write(event)
    f.close()


def compress():
    yearRegex = re.compile('y=[0-9]{4}')
    for y in os.listdir('.'):
        if os.path.isdir(y):
            dirName = str(y)
            if yearRegex.match(dirName):
                yearSplit = dirName.split('=')
                yearStr = yearSplit[1]
                year = int(yearStr)
                if year > 2015:
                    for m in os.listdir(dirName):
                        if os.path.isdir(dirName + '/' + m):
                            for d in os.listdir(dirName + '/' + m):
                                if os.path.isdir(dirName + '/' + m + '/' + d):
                                    for h in os.listdir(dirName + '/' + m + '/' + d):
                                        if os.path.isdir(dirName + '/' + m + '/' + d + '/' + h):
                                            for filename in os.listdir(dirName + '/' + m + '/' + d + '/' + h):
                                                if (filename.endswith("json")):
                                                    inF = file(dirName + '/' + m + '/' + d + '/' + h + '/' + filename,
                                                               'rb')
                                                    s = inF.read()
                                                    inF.close()
                                                    outF = gzip.GzipFile(
                                                        dirName + '/' + m + '/' + d + '/' + h + '/m1.json.gz', 'wb')
                                                    outF.write(s)
                                                    outF.close()
                                                    os.remove(dirName + '/' + m + '/' + d + '/' + h + "/" + filename)


logging.basicConfig(filename='dataParsing.log', level=logging.DEBUG)
start = datetime.datetime.now().timestamp()
parse('data')
compress()
end = datetime.datetime.now().timestamp()
logging.debug("time taken:" + (end - start) + "seconds")
