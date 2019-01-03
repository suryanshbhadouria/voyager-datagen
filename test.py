import time


def getFilePath1(epoch):
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
    filepath = 'y=' + year + '/m=' + month + '/d=' + day + '/h=' + hour + '/m1.json.gz'
    return filepath


filepath = getFilePath1(1535613110314)
print filepath
