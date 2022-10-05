#!/bin/sh
#Fix permissions for a sideload directory within data
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi
if [ -z "$2" ]
  then
    echo "Please provide the name of the sideload to update as the second argument."
    exit 1
fi
chown aspen:aspen /data/aspen-discovery/$1/$2
chmod -R o+r /data/aspen-discovery/$1/$2/
chmod o+x /data/aspen-discovery/$1/$2/

chown -R www-data:aspen_apache /data/aspen-discovery/$1/$2/marc
chmod o+x /data/aspen-discovery/$1/$2/marc
chown -R www-data:aspen_apache /data/aspen-discovery/$1/$2/marc_recs
chmod o+x /data/aspen-discovery/$1/$2/marc_recs