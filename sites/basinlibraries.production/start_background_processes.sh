#!/bin/sh
# Copies needed solr files to the server specified as a command line argument
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
fi
echo "Starting background processes for $1"
/usr/local/aspen-discovery/sites/basinlibraries.production/basinlibraries.production.sh restart

#Wait for solr to restart
sleep 60
# Continuous Re-Indexing
echo "Starting koha export"
cd /usr/local/aspen-discovery/code/koha_export; java -jar koha_export.jar basinlibraries.production continuous &
echo "Starting Overdrive export"
cd /usr/local/aspen-discovery/code/overdrive_api_extract; java -jar overdrive_extract.jar basinlibraries.production continuous &
echo "Starting rbdigital export"
cd /usr/local/aspen-discovery/code/rbdigital_export; java -jar rbdigital_export.jar basinlibraries.production continuous &
echo "Starting User List Indexing"
cd /usr/local/aspen-discovery/code/user_list_indexer; java -jar user_list_indexer.jar basinlibraries.production continuous &

