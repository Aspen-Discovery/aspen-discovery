#!/bin/bash
#
# setup_server.sh
#
# setup data and logs for a new aspen-discovery server by copying the appropriate 
# files from default
#
#-------------------------------------------------------------------------
# declare variables
#-------------------------------------------------------------------------
HOST=$1
WD=`pwd`

#-------------------------------------------------------------------------
# main loop
#-------------------------------------------------------------------------

if [ $# = 1 ];then
  echo ""
  echo "Working directory is: $WD"
  echo "Server name is: $HOST"
  echo ""
  #-----------------
  echo "setting up data directory"
  mkdir /data
  cd /data
  mkdir aspen-discovery
  cd aspen-discovery
  echo "creating accelerated reader data folder"
  mkdir accelerated_reader
  mkdir $HOST
  cd $HOST
  cp -rp $WD/data_dir_setup/* .
  #-----------------
  echo "setting group permissions to data directory for user apache"
  chgrp -v apache covers/*
  chmod -v g+w covers/*
  #-----------------
  echo "setting up logs directory"
  cd /var/log
  mkdir aspen-discovery
  cd aspen-discovery
  mkdir $HOST
  cd $HOST
  #-----------------
  echo "Creating symbolic link in /etc/httpd/conf.d to apache config file"
  ln -s $WD/sites/$HOST/httpd-$HOST.conf /etc/httpd/conf.d/httpd-$HOST.conf
  #-----------------
  echo "Installing Solr Files for $HOST"
  cd $WD/data_dir_setup/; ./update_solr_files.sh $HOST
  #-----------------

  echo ""
  cd $WD
  exit 0
else
  echo ""
  echo "Usage:  $0 {Sites Directory Name for this instance}"
  echo "eg: $0 aspen.demo"
  echo ""
  exit 1
fi
#
#--eof--
