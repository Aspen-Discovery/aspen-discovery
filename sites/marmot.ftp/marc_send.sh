#!/bin/bash
#
# marc_send
#
# send marc records tarball file via ftp
#
# Author:  Steve Lindemann
#   Date:  04 Oct 2012
#
#-------------------------------------------------------------------------
#
#  usage: marc_send <SERVICETOSENDTO>
#
#  uses expect scripts in directory /root/cron
#  ftp login credentials in file /root/.netrc
#  intended to be run by cron
#
#-------------------------------------------------------------------------
# 04 Oct 12 - sml - initial
# 10 Oct 12 - sml - incorporated an expect script & logger
# 12 Oct 12 - sml - fixed up to allow additional sites and worked
#                            with mark to change the source files available
# 15 Oct 12 - sml - add collectionhq
# 13 Nov 13 - sml - adjust for new file to upload and add compression
#                            of the file in this script
# 20 Nov 14 - sml - add aspencat
# 01 Dec 14 - sml - remove aspencat, add mackin, perma-bound, btsb
# 04 Dec 14 - sml - add follett
# 18 Dec 14 - sml - updated logging process
# 27 May 15 - sml - add booksite
# 17 Jul 15 - sml - add exit codes to log
#-------------------------------------------------------------------------

#-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------
LOG="logger -t marc_send -p local6.notice "

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------

$LOG " "
$LOG ">>>> FTP of marc record file starting <<<<"

#--------------------------------
case $1
  in
  ebsco)
    $LOG "~> send fullexport.marc to EBSCO using expect"
    /root/cron/ebsco_ftp.exp fullexport.marc
    $LOG "~> exit code $?"
    ;;
  collectionhq)
    $LOG "~> compress /ftp/sierra/fullexport.marc"
    gzip -c /ftp/sierra/fullexport.marc > /ftp/sierra/fullexport.marc.gz
    $LOG "~> exit code $?"
    $LOG "~> send fullexport.marc.gz to CollectionHQ using expect"
    /root/cron/collectionhq_ftp.exp fullexport.marc.gz
    $LOG "~> exit code $?"
    $LOG "~> delete /ftp/sierra/fullexport.marc.gz"
    rm -f /ftp/sierra/fullexport.marc.gz
    $LOG "~> exit code $?"
    ;;
  booksite)
    $LOG "~> compress /ftp/sierra/fullexport.marc"
    gzip -c /ftp/sierra/fullexport.marc > /ftp/sierra/7342.mrc.gz
    $LOG "~> exit code $?"
    $LOG "~> send 7342.mrc.gz to Booksite using expect"
    /root/cron/booksite_ftp.exp 7342.mrc.gz
    $LOG "~> exit code $?"
    $LOG "~> delete /ftp/sierra/7342.mrc.gz"
    rm -f /ftp/sierra/7342.mrc.gz
    $LOG "~> exit code $?"
    ;;
  mackin)
    $LOG "~> send sd51 records to mackin using expect"
    /root/cron/mackin.exp MCVSD_BIBS_CollectionAnalysis_81505
    $LOG "~> exit code $?"
    ;;
  perma-bound)
    $LOG "~> send sd51 records to perma-bound using expect"
    /root/cron/perma-bound.exp MCVSD_BIBS_CollectionAnalysis_81505
    $LOG "~> exit code $?"
    ;;
  btsb)
    $LOG "~> send sd51 records to btsb using expect"
    /root/cron/btsb.exp MCVSD_BIBS_CollectionAnalysis_81505
    $LOG "~> exit code $?"
    ;;
  follett)
    $LOG "~> send sd51 records to follett using curl"
    curl --ftp-ssl -k -n -s -T /ftp/sd51/MCVSD_BIBS_CollectionAnalysis_81505 ftp://ftp.fss.follett.com
    $LOG "~> exit code $?"
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
