#!/bin/bash
#
# setup_server.sh
#
# author: Steve Lindemann
#   date: 22 Jul 14
#
# setup data and logs for a new vufind-plus server by copying the appropriate 
# files from default
#
#-------------------------------------------------------------------------
# 22 Jul 14 - v0.0.1 - sml - create initial script based on dos batch file
# 05 Dec 14 - v0.1.0 - plb - added permission setting for web user of data & logs
#-------------------------------------------------------------------------
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
  mkdir vufind-plus
  cd vufind-plus
  echo "creating accelerated reader data folder"
  mkdir accelerated_reader
  mkdir $HOST
  cd $HOST
  cp -rp $WD/data_dir_setup/* .
  #-----------------
  echo "setting group permissions to data directory for user apache"
  chgrp -v apache qrcodes
  chgrp -v apache covers/*
  chmod -v g+w qrcodes
  chmod -v g+w covers/*
  #-----------------
  echo "adding hoopla data directory"
  cd /data/vufind-plus
  mkdir hoopla hoopla/marc hoopla/marc_recs
  #-----------------
  echo "setting up logs directory"
  cd /var/log
  mkdir vufind-plus
  cd vufind-plus
  mkdir $HOST
  cd $HOST
  mkdir jetty
  #-----------------
  echo "setting group permissions to logs for user apache"
  touch error.log messages.log access.log
  chgrp apache error.log messages.log access.log
  chmod g+w error.log messages.log access.log
  #-----------------
  echo "installing Smarty Template engine in php shared"
  cp -r $WD/install/Smarty /usr/share/php
  echo "creating Smarty compile & cache directories"
  mkdir $WD/vufind/web/interface/compile $WD/vufind/web/interface/cache
  echo "set ownership & permissions for Smarty compile & cache directories"
  chgrp apache $WD/vufind/web/interface/compile $WD/vufind/web/interface/cache
  chmod g+w $WD/vufind/web/interface/compile $WD/vufind/web/interface/cache
  #-----------------
  echo "setting up Pika log rotation."
  cat $WD/install/pika |sed -r "s/\{servername\}/$HOST/" > /etc/logrotate.d/pika
  # The line above updates the log rotation file so that it doesn't need to be manually modifed
  #-----------------
  #echo "setting up Pika log rotation. Note: Servername must be manually set."
  #cp $WD/install/pika /etc/logrotate.d/
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
#  cat $WD/sites/default/pika_startup.sh |sed -r 's/\{servername\}/$HOST/' > /etc/init.d/pika.sh
  cat $WD/sites/default/pika_startup.sh |sed -r "s/\{servername\}/$HOST/"|sed -r "/mysqld/mariadb/" > /etc/init.d/pika.sh
  chmod u+x /etc/init.d/pika.sh
#  CentOS7 version that uses mariadb instead


  echo ""
  cd $WD
  exit 0
else
  echo ""
  echo "Usage:  $0 {Pika Sites Directory Name for this instance}"
  echo "eg: $0 pika.test"
  echo ""
  exit 1
fi
#
#--eof--
