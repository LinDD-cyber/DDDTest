#!/bin/bash

set -ev
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

BakDir=/backup
LogFile=bak.log

LogFilePath=$BakDir/$LogFile
Date=`date +%Y%m%d_%H%M%S`
Begin=`date +"%Y%m%d_%H:%M:%S "`
DumpPath=$BakDir/data/${Date}_incr/
GZDumpFile=$BakDir/${Date}_incr.tar.gz

## Check if backup zip file not exist do full backup
if ls $BakDir/*.tar.gz 1> /dev/null 2>&1 ; then
    echo "do incremental"
else
    echo "do full"
    bash $DIR/full_backup.sh
    exit 0
fi

## Get base from zip file
BASEZIP=$(ls -rd $BakDir/*.tar.gz | head -1)
BASEDIR=$BakDir/data/`basename $BASEZIP .tar.gz`/
if [ -e $BASEDIR ]; then
  rm -rf $BASEDIR
  echo "delete.."
fi
tar -xvzf $BASEZIP -C /


## Mysql backup
mariabackup --backup --target-dir=$DumpPath --incremental-basedir=$BASEDIR --user=root --password=12345678

## Zip file and remove nonzip file
tar pcf - $DumpPath | gzip > $GZDumpFile
rm -rf $DumpPath
rm -rf $BASEDIR

## File franster
Last=`date +"%Y%m%d_%H:%M:%S "`
if [ ! -e $LogFilePath ]; then
    touch $LogFilePath
fi
echo start: $Begin end: $Last $GZDumpFile base on:$BASEZIP  SUCC >> $LogFilePath

## Remove files
expect $DIR/upload.sh "$GZDumpFile"

## Log transfer done
Transfer=`date +"%Y%m%d_%H:%M:%S "`
echo Transfer file $GZDumpFile at: $Transfer  SUCC >> $LogFilePath
