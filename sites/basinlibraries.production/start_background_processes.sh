#!/bin/sh
/usr/local/aspen-discovery/sites/basinlibraries.production/basinlibraries.production.sh restart

#Wait for solr to restart and warm up
sleep 300

#TODO: Check for which processes are included on the server, kill the old version, start the new version.
echo "Starting koha export"
cd /usr/local/aspen-discovery/code/koha_export; java -jar koha_export.jar basinlibraries.production &
sleep 20
echo "Starting Overdrive export"
cd /usr/local/aspen-discovery/code/overdrive_api_extract; java -jar overdrive_extract.jar basinlibraries.production &
sleep 20
echo "Starting rbdigital export"
cd /usr/local/aspen-discovery/code/rbdigital_export; java -jar rbdigital_export.jar basinlibraries.production &
sleep 20
echo "Starting User List Indexing"
cd /usr/local/aspen-discovery/code/user_list_indexer; java -jar user_list_indexer.jar basinlibraries.production continuous &
sleep 20
echo "Starting Side Load Processing"
cd /usr/local/aspen-discovery/code/sideload_processing; java -jar sideload_processing.jar basinlibraries.production &
