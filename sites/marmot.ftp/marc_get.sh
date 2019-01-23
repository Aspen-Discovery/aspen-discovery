#!/bin/bash
#
# marc_get
#
# get marc records via ftp
#
# Author:  Steve Lindemann
#   Date:  20 Nov 14
#
#-------------------------------------------------------------------------
#
#  usage: marc_get <SERVICETOGETFROM>
#
#  uses expect scripts in directory /home/steve/cron
#  ftp login credentials in file /home/steve/.netrc
#  intended to be run by cron
#
#-------------------------------------------------------------------------
# 20 Nov 14 - sml - initial (copy from marc_send)
# 20 Nov 14 - sml - add aspencat
# 17 Dec 14 - sml - clean up logging, add deleted & updated files to get for aspencat
# 03 Apr 15 - mdn - change to pull based on today's date since we run right after
#                   the file is created.
# 17 Jul 15 - sml - add exit code to log
#-------------------------------------------------------------------------

#-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------
LOG="logger -t marc_get -p local6.notice "
DATE=`date +%Y%m%d --date="today"`

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------

$LOG " "
$LOG ">>>> FTP of marc record file starting <<<<"

#--------------------------------
case $1
  in
  aspencat)
    $LOG "~> get MARC update/delete records for AspenCat from CLIC using expect"
     /root/cron/aspencat.exp ascc-catalog-deleted.$DATE.marc ascc-catalog-updated.$DATE.marc
#     /root/cron/aspencat.exp ascc-catalog-full.marc.gz ascc-catalog-deleted.$DATE.marc ascc-catalog-updated.$DATE.marc
    $LOG "~> exit code $?"
    ;;
  aspencat_new)
    $LOG "~> get MARC Full Export records for AspenCat from CLIC using expect (if is new)"
     /root/cron/aspencat_newer_only.exp ascc-catalog-full.marc.gz
    $LOG "~> exit code $?"
#     /root/cron/aspencat.exp  ascc-catalog-deleted.$DATE.marc ascc-catalog-updated.$DATE.marc
#    $LOG "~> exit code $?"
    ;;
  *)
    $LOG "!!!> ERROR: $1 is not valid, no expect script run"
    ;;
  esac
#--------------------------------

$LOG ">>>> FTP of marc record file complete <<<<"
exit 0

#-------------------------------------------------------------------------
#--- eof ---
