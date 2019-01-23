#!/bin/bash
# Copy Aspencat Extracts from ftp server
# runs after files are received on the ftp server
#-------------------------------------------------------------------------
# 19 Dec 14 sml expanded script to copy updated & deleted marc files from
#               ftp server and added variable/constants declarations
# 20 Dec 14 sml added output to track progression of the script as it runs
# 07 Apr 15 plb adjusted for use of production server
#-------------------------------------------------------------------------
# declare variables and constants
#-------------------------------------------------------------------------
REMOTE="10.1.2.7:/ftp"
LOCAL="/mnt/ftp"
DEST="/data/vufind-plus/aspencat.production/marc"
DATE=`date +%Y%m%d --date="yesterday"`
LOG="logger -t copyAspencatExport "

#-------------------------------------------------------------------------

$LOG "~> starting copyAspencatExport.sh"

#$LOG "~~ remove old deleted and updated marc record files"
##rm -f $DEST/ascc-catalog-deleted.* $DEST/ascc-catalog-updated.*
#$LOG "~~ exit code " $?
# Merging Process will move these to ../marc_backup pascal 5-9-2017

$LOG "~~ mount $REMOTE $LOCAL"
mount $REMOTE $LOCAL
$LOG "~~ exit code " $?

# Only grab the full export file if it is less that a day old.
FILE=$(find $LOCAL/aspencat/ -name ascc-catalog-full.marc.gz -mtime -1)
if [ -n "$FILE" ]; then
	$LOG "~~ unzip ascc-catalog-full marc file to fullexport.mrc"
	gunzip -c $LOCAL/aspencat/ascc-catalog-full.marc.gz > $DEST/fullexport.mrc
	$LOG "~~ exit code " $?
fi

$LOG "~~ copy ascc-catalog-deleted marc file"
cp $LOCAL/aspencat/ascc-catalog-deleted.$DATE.marc $DEST
$LOG "~~ exit code " $?

$LOG "~~ copy ascc-catalog-updated marc file"
cp $LOCAL/aspencat/ascc-catalog-updated.$DATE.marc $DEST
$LOG "~~ exit code " $?

$LOG "~~ umount $LOCAL"
umount $LOCAL
$LOG "~~ exit code " $?

$LOG "~> finished copyAspencatExport.sh"

#-------------------------------------------------------------------------
