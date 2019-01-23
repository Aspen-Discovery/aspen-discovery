#!/bin/bash
#
# author Pascal Brammeier
#
#-------------------------------------------------------------------------
# Check Currently Installed crontab for differences from the site
# configuration file crontab_settings.txt
#-------------------------------------------------------------------------
PIKASERVER=$1

if [ $# = 1 ];then
   echo "Differences : "
   echo ""
   crontab -l| diff /usr/local/vufind-plus/sites/${PIKASERVER}/conf/crontab_settings.txt -
   echo ""
   echo "Please reconcile any differences that should be in the settings file before installing the new cronttab."
   echo ""
   echo "Command to install the new crontab: "
   echo "crontab < /usr/local/vufind-plus/sites/${PIKASERVER}/conf/crontab_settings.txt"
   exit 0
else
  echo ""
  echo "Usage:  $0 {PikaServer}"
  echo "eg: $0 marmot.test "
  echo ""
  exit 1
fi
#
#--eof--
