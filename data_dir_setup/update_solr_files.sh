#!/bin/sh
# Copies needed solr files to the server specified as a command line argument
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
fi
echo "Updating $1"

cp -r solr7 /data/aspen-discovery/$1
sudo chown -R solr:aspen /data/aspen-discovery/$1/solr7

sudo -u solr /usr/local/aspen-discovery/sites/$1/$1.sh restart
