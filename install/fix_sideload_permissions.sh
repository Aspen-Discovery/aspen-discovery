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
chmod 775 /data/aspen-discovery/$1/$2/

chown -R apache:aspen_apache /data/aspen-discovery/$1/$2/marc
chmod 775 /data/aspen-discovery/$1/$2/marc
chown -R aspen:aspen_apache /data/aspen-discovery/$1/$2/marc_recs
chmod 775 /data/aspen-discovery/$1/$2/marc_recs