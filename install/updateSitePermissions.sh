#!/bin/sh
#Updates permissions for sites on a server to use aspen user, should be  run after setup_aspen_user
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi
chown root:root /usr/local/aspen-discovery/sites/$1/httpd-$1.conf
chown root:root /usr/local/aspen-discovery/sites/$1/conf/crontab_settings.txt
chown aspen:aspen /usr/local/aspen-discovery/sites/$1/$1.sh
chown aspen:aspen /usr/local/aspen-discovery/sites/$1/conf/log4j*
chown aspen:aspen_apache /usr/local/aspen-discovery/sites/$1/conf/config*
chown -R aspen:aspen_apache /data/aspen-discovery/$1/covers
chmod -R g+w /data/aspen-discovery/$1/covers
chown -R aspen:aspen_apache /data/aspen-discovery/$1/uploads
chmod -R g+w /data/aspen-discovery/$1/uploads
chown -R solr:aspen /data/aspen-discovery/$1/solr7
chown -R root:root /data/aspen-discovery/$1/sql_backup
chown aspen:aspen_apache /var/log/aspen-discovery/$1/*
chown -R aspen:aspen /var/log/aspen-discovery/$1/logs