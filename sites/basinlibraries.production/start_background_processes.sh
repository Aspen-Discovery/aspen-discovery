#!/bin/sh
/usr/local/aspen-discovery/sites/basinlibraries.production/basinlibraries.production.sh restart

#Wait for solr to restart
sleep 60

#TODO: Check for which processes are included on the server, kill the old version, start the new version.
echo "Starting koha export"
cd /usr/local/aspen-discovery/code/koha_export; java -jar koha_export.jar basinlibraries.production &
echo "Starting Overdrive export"
cd /usr/local/aspen-discovery/code/overdrive_api_extract; java -jar overdrive_extract.jar basinlibraries.production &
echo "Starting rbdigital export"
cd /usr/local/aspen-discovery/code/rbdigital_export; java -jar rbdigital_export.jar basinlibraries.production &
echo "Starting User List Indexing"
cd /usr/local/aspen-discovery/code/user_list_indexer; java -jar user_list_indexer.jar basinlibraries.production continuous &

