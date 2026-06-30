#!/bin/bash

set -ev

# for safe ...
# uncomment exit to start...
exit 0

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
FILENAME=$DIR/"restore_list.txt"

BakDir=/backup
RESTORE_LIST=()

## Read list
while IFS='' read -r line || [[ -n "$line" ]]; do
    RESTORE_LIST+=( "$line" )
done < $FILENAME

## Unzip backup file
for FILE in ${RESTORE_LIST[@]}
do 
  #echo $FILE; 
  ## Delete folder if target exist
  FNAME=${FILE##*/}
  if [ -e $BakDir/data/$FNAME ]; then
    rm -rf $BakDir/data/$FNAME
  fi
  
  ## Escape if tar.gz file not exist
  if [ ! -e $BakDir/$FILE.tar.gz ]; then
    echo "Backup file: "$BakDir/$FILE.tar.gz" not found!"
    exit 0
  fi
  tar -xvzf $BakDir/$FILE.tar.gz -C /
done

## Prepare DB data
for i in ${RESTORE_LIST[@]}
do
    if [[ $BASE == "" ]]; then
      mariabackup --prepare --target-dir=$BakDir/data/$i
      BASE=$i 
    else
      mariabackup --prepare --target-dir=$BakDir/data/$BASE --incremental-dir=$BakDir/data/$i
    fi
done

## Clear origin DB space
rm -rf /var/lib/mysql/*
## Copy prepared DB data to DB space
mariabackup --move-back --target-dir=$BakDir/data/$BASE
## Setting mysql permission
chown -R mysql:mysql /var/lib/mysql/

## Recover full backup folder
rm -rf $BakDir/data/$BASE
tar -xvzf $BakDir/$BASE.tar.gz -C /

## Delete unuse file
for FILE in ${RESTORE_LIST[@]}
do
  ## Delete folder if target exist
  FNAME=${FILE##*/}
  if [ -e $BakDir/data/$FNAME ]; then
    rm -rf $BakDir/data/$FNAME
  fi
done

echo "Restore completed.\n exit and restart mysql container......"
