#!/bin/bash
#
# author Pascal Brammeier
#
#-------------------------------------------------------------------------
#  FTP Data Directory Set up Script For Sideloads
#-------------------------------------------------------------------------
FTPACCOUNT=$1
SIDELOADCOLLECTION=$2
DIR=/ftp

if [ $# = 2 ] || [ $# = 3 ];then
  echo ""
  echo "Setting up new data directory for FTP account: $FTPACCOUNT"
  echo "For the collection: $SIDELOADCOLLECTION"
  if [ $# = 3 ];then
    LOCATION=$3
    echo "The Location is: $LOCATION"
  fi
  echo ""
  DIR+="/$FTPACCOUNT/$SIDELOADCOLLECTION"
  mkdir "$DIR"
  chown "$FTPACCOUNT:$FTPACCOUNT" "$DIR"
  if [ $# = 3 ];then
    DIR+="/$LOCATION"
    mkdir "$DIR"
    chown "$FTPACCOUNT:$FTPACCOUNT" "$DIR"
  fi

  mkdir "$DIR/completeCollection"
  mkdir "$DIR/addsAndUpdatesOnly"
  mkdir "$DIR/deletesOnly"
  chown "$FTPACCOUNT:$FTPACCOUNT" "$DIR" "$DIR/completeCollection" "$DIR/addsAndUpdatesOnly" "$DIR/deletesOnly"

else
  echo ""
  echo "Usage:  $0 {SideLoadFTPAccount} {SideLoadCollection} {Location (optional)}"
  echo "eg: $0 ccu gale"
  echo ""
  echo "Note: The ftp account must be created before running this"
  exit 1
fi
#
#--eof--
