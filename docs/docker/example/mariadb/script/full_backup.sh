#!/bin/bash

set -ev
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

BakDir=/backup
LogFile=bak.log

LogFilePath=$BakDir/$LogFile
Date=`date +%Y%m%d_%H%M%S`
Begin=`date +"%Y%m%d_%H:%M:%S "`
DumpPath=$BakDir/data/${Date}_full/
GZDumpFile=$BakDir/${Date}_full.tar.gz

## Collect the files want to remove
if [ ! -e $BakDir/want_to_delete ]; then
    mkdir $BakDir/want_to_delete
fi
find $BakDir -name '*.tar.gz' -exec mv {} $BakDir/want_to_delete \;


## Mysql backup
mariabackup --backup --target-dir=$DumpPath --user=root --password=12345678

## Zip file and remove nonzip file
tar pcf - $DumpPath | gzip > $GZDumpFile
rm -rf $DumpPath

## Log backup done
Last=`date +"%Y%m%d_%H:%M:%S "`
if [ ! -e $LogFilePath ]; then
    touch $LogFilePath
fi
echo start: $Begin end: $Last $GZDumpFile SUCC >> $LogFilePath

## File franster
expect $DIR/upload.sh "$GZDumpFile"

## Log transfer done
Transfer=`date +"%Y%m%d_%H:%M:%S "`
echo Transfer file $GZDumpFile at: $Transfer  SUCC >> $LogFilePath

## After Transfer remove the files want to delete
rm -rf $BakDir/want_to_delete/