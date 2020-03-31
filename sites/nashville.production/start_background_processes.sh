#!/bin/sh
/usr/local/aspen-discovery/sites/nashville.production/nashville.production.sh restart

#Wait for solr to restart and warm up
sleep 10

#TODO: Check for which processes are included on the server, kill the old version, start the new version.
echo "Starting carlx export"
cd /usr/local/aspen-discovery/code/carlx_export; java -jar carlx_export.jar nashville.production &
sleep 5
echo "Starting Overdrive export"
cd /usr/local/aspen-discovery/code/overdrive_extract; java -jar overdrive_extract.jar nashville.production &
sleep 5
echo "Starting rbdigital export"
cd /usr/local/aspen-discovery/code/rbdigital_export; java -jar rbdigital_export.jar nashville.production &
sleep 5
echo "Starting User List Indexing"
cd /usr/local/aspen-discovery/code/user_list_indexer; java -jar user_list_indexer.jar nashville.production &
sleep 5
echo "Starting Side Load Processing"
cd /usr/local/aspen-discovery/code/sideload_processing; java -jar sideload_processing.jar nashville.production &

