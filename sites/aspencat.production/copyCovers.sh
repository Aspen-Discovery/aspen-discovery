#!/bin/bash
#
# copyCovers.sh
#
# author: Mark Noble
#
#-------------------------------------------------------------------------
# 19 Mar 14 - v0.0.2 - sml - updated for maintainablility
# 19 Mar 14 - v0.0.3 - sml - modified to copy files chgd in the last 90 min
# 31 Mar 14 - v0.0.4 - sml - add logging
# 01 Apr 14 - v0.0.5 - sml - add bail out if mount fails, change time to 61m
# 24 Apr 14 - v0.0.6 - mdn - update to use new data directory
#                            add optional all parameter to force copy of
#                            all covers
# 15 Sep 14 - v0.0.7 - plb - re-adjusted to 10 minute intervals
# 08 Nov 16          - plb - Refactored for easy plug-and-play on test servers

##-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------

FTPACCOUNT="aspencat/covers"
PIKASITENAME="aspencat.production"

REMOTE="10.1.2.7:/ftp"
LOCAL="/mnt/ftp"
SRC="/mnt/ftp/${FTPACCOUNT}"
DEST="/data/vufind-plus/${PIKASITENAME}/covers/original"
LOG="logger -t copyCovers "

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------
$LOG "~> starting copyCovers.sh ${PIKASITENAME}"

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

if [ -z "$1" ]
then
  #Grab Covers newer than 11 minutes
  $LOG "~> find $SRC -type f -mmin -11 -exec /bin/cp {} $DEST \;"
  find $SRC -type f -mmin 11 -exec /bin/cp {} $DEST \;
  $LOG "~> exit code $?"
	if [ ! -d "$SRC/processed/" ]; then
		mkdir $SRC/processed/
	fi
  $LOG "~> find $SRC -type f -mmin -11 -exec /bin/cp {} $SRC/processed/ \;"
  find $SRC -type f -mmin -11 -exec /bin/mv {} $SRC/processed/ \;
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

$LOG "~> finished copyCovers.sh ${PIKASITENAME}"
#-------------------------------------------------------------------------
#-- eof --
