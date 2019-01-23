#!/bin/bash
#
# author Pascal Brammeier
#
#-------------------------------------------------------------------------
#  Fetch latest MARC from lynda.com
#-------------------------------------------------------------------------


LIBRARYDIR=$1

echo ""
echo "Fetching Lynda.com MARC"
echo "Marc file will be moved to /data/vufind-plus/lynda/$LIBRARYDIR/marc/fullexport.mrc"
echo ""


curl --output lynda_com_marc.zip --silent --show-error "https://www.lynda.com/courselist?marc=true&format=marc"
unzip -o lynda_com_marc.zip
mv lynda_marc_com.mrc /data/vufind-plus/lynda/$LIBRARYDIR/marc/fullexport.mrc

#
#--eof--
