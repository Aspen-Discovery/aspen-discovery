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
  echo "adding hoopla data directory"
  cd /data/aspen-discovery
  mkdir hoopla hoopla/marc hoopla/marc_recs
  #-----------------
  echo "setting up logs directory"
  cd /var/log
  mkdir aspen-discovery
  cd aspen-discovery
  mkdir $HOST
  cd $HOST
  #-----------------
  echo "installing Smarty Template engine in php shared"
  cp -r $WD/install/Smarty /usr/share/php
  echo "creating Smarty compile & cache directories"
  mkdir $WD/code/web/interface/compile $WD/code/web/interface/cache
  echo "set ownership & permissions for Smarty compile & cache directories"
  chgrp apache $WD/code/web/interface/compile $WD/code/web/interface/cache
  chmod g+w $WD/code/web/interface/compile $WD/code/web/interface/cache
  #-----------------
  echo "Creating symbolic link in /etc/httpd/conf.d to apache config file"
  ln -s $WD/sites/$HOST/httpd-$HOST.conf /etc/httpd/conf.d/httpd-$HOST.conf
  #-----------------
  echo "Copying mysql config file to /etc/my.cnf.d"
  # Probably centos 7/mariadb setups only
  cp $WD/install/my.cnf /etc/my.cnf.d/my.cnf
  #-----------------
#  echo "Creating symbolic link in /etc/my.cnf.d to mysql config file"
#  # Probably centos 7/mariadb setups only
#  ln -s $WD/install/my.cnf /etc/my.cnf.d/my.cnf
#  #-----------------
  #-----------------
  echo "Copying mysql credentials file to ~/.my.cnf (NOTE: this file will need manual editing.)"
  # Probably centos 7/mariadb setups only
  cp $WD/install/.my.cnf ~/.my.cnf
  #-----------------
  echo "Installing Solr Files for $HOST"
  cd $WD/data_dir_setup/; ./update_solr_files.sh $HOST
  #-----------------
  echo "Creating pika system service for $HOST"
#  cat $WD/sites/default/pika_startup.sh |sed -r "s/\{servername\}/$HOST/"|sed -r "/mysqld/mariadb/" > /etc/init.d/pika.sh
#  chmod u+x /etc/init.d/pika.sh
#  CentOS7 version that uses mariadb instead


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
