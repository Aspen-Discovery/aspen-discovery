#!/bin/bash
#
# copyCovers.sh
#
# author: Mark Noble
#   date:
#
# script is run by cron at 10 minutes after every hour
#
#-------------------------------------------------------------------------
# 19 Mar 14 - v0.0.2 - sml - updated for maintainablility
# 19 Mar 14 - v0.0.3 - sml - modified to copy files chgd in the last 90 min
# 31 Mar 14 - v0.0.4 - sml - add logging
# 01 Apr 14 - v0.0.5 - sml - add bail out if mount fails, change time to 61m
# 24 Apr 14 - v0.0.6 - mdn - update to use new data directory
#                            add optional all parameter to force copy of
#                            all covers
# 15 Sep 14 - v0.0.7 - plb - interval changes to correspond to current crontab
#                            settings
#-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------
REMOTE="10.1.2.7:/ftp"
LOCAL="/mnt/ftp_covers"
SRC="/mnt/ftp_covers/vufind_covers"
#DEST="/data/vufind-plus/opac.marmot.org/covers/original"
DEST="/data2/pika/covers/original"
LOG="logger -t copyCovers "

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------
$LOG "~> starting copyCovers.sh"

#------------------------------------------------
# mount external drive
#------------------------------------------------
$LOG "~> mount $REMOTE $LOCAL"
mount $REMOTE $LOCAL
EXITCODE=$?
$LOG "~> exit code $EXITCODE"
if [ $EXITCODE -ne 0 ];then
  $LOG "!! script terminated abnormally"
  exit 1
fi

#------------------------------------------------
# copy new files from SRC to DEST
#------------------------------------------------
# reset intervals to 11 minutes to reflect crontab intervals. pascal 9-15-2014
if [ -z "$1" ]
then
  #Grab Covers newer than 11 minutes
  $LOG "~> find $SRC -type f -exec /bin/cp {} $DEST \;"
  find $SRC -maxdepth 1 -type f -exec /bin/cp {} $DEST \;
  $LOG "~> exit code $?"
  if [ ! -d "$SRC/processed/" ]; then
     mkdir $SRC/processed/
  fi
  $LOG "~> find $SRC -type f -exec /bin/cp {} $SRC/processed/ \;"
  find $SRC -maxdepth 1 -type f -exec /bin/mv {} $SRC/processed/ \;
else
	#if a single parameter is passed this will copy over files without any time check.
  /bin/cp $SRC/* $DEST
	if [ ! -d "$SRC/processed/" ]; then
		mkdir $SRC/processed/
	fi
	/bin/mv $SRC/* $SRC/processed/
fi

#------------------------------------------------
# fix ownership/perms on all files in DEST
#------------------------------------------------
cd $DEST
$LOG "~> fix ownership"
chown -R root:apache *
$LOG "~> exit code $?"
$LOG "~> fix permissions"
chmod 660 *
$LOG "~> exit code $?"

#------------------------------------------------
# umount the external drive
#------------------------------------------------
$LOG "~> unmount $LOCAL"
umount $LOCAL
EXITCODE=$?
$LOG "~> exit code $EXITCODE"
if [ $EXITCODE -ne 0 ];then
  $LOG "!! script terminated abnormally"
  $LOG "!! $LOCAL needs UNMOUNTED BEFORE the next script execution"
  exit 3
fi

$LOG "~> finished copyCovers.sh"
#-------------------------------------------------------------------------
#-- eof --
