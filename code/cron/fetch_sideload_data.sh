#!/bin/bash
#
# author Pascal Brammeier
#
#-------------------------------------------------------------------------
#  Fetch Any Kind of Update
#-------------------------------------------------------------------------
PIKASERVER=$1
FTPSOURCE=$2
PIKADATADIR=$3


if [ $# = 3 ];then
  echo ""
  echo "Fetching for $PIKADATADIR"

  # Fetch Full Export
  /usr/local/vufind-plus/vufind/cron/moveFullExport.sh $FTPSOURCE/completeCollection $PIKADATADIR $PIKASERVER

  # Fetch Adds
  /usr/local/vufind-plus/vufind/cron/moveSideloadAdds.sh $FTPSOURCE/addsAndUpdatesOnly $PIKADATADIR/merge $PIKASERVER

  # Fetch Deletes
  /usr/local/vufind-plus/vufind/cron/moveSideloadAdds.sh $FTPSOURCE/deletesOnly $PIKADATADIR/deletes $PIKASERVER

  if [ $(ls -lA /data/vufind-plus/$PIKADATADIR/marc |grep fullexport.mrc|wc -l) = 0 ];then
  # If there is no full export file in the main marc directory ....
  if [ $(ls -lA /data/vufind-plus/$PIKADATADIR/merge/marc/*.mrc|wc -l) -gt 0  ];then
  # ... and there is a marc file in the merge directory; move largest one and set it as the main full export file
    FILE=$(ls -rS /data/vufind-plus/$PIKADATADIR/merge/marc/*.mrc|tail -1)
    mv "$FILE" /data/vufind-plus/$PIKADATADIR/marc/fullexport.mrc
  fi
  fi

  # Merge Data
  /usr/local/vufind-plus/vufind/cron/mergeSideloadMarc.sh $PIKADATADIR

  # Delete mergeBackup file older than a month
  find /data/vufind-plus/$PIKADATADIR/mergeBackup -name "*.mrc" -mtime +30 -delete

   echo ""
else
  echo ""
  echo "Usage:  $0 {PikaServer} {FtpDataSource} {SideloadDataHomeDir}"
  echo "eg: $0 marmot.test adams/ebsco ebsco/adams"
  echo ""
  exit 1
fi
#
#--eof--
