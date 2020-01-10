#!/bin/sh
/usr/local/aspen-discovery/sites/arlington.production/arlington.production.sh restart

#Wait for solr to restart and warm up
sleep 10

#TODO: Check for which processes are included on the server, kill the old version, start the new version.
echo "Starting koha export"
cd /usr/local/aspen-discovery/code/sierra_api_export; java -jar sierra_api_export.jar arlington.production &
sleep 5
echo "Starting Overdrive export"
cd /usr/local/aspen-discovery/code/overdrive_api_extract; java -jar overdrive_extract.jar arlington.production &
sleep 5
#echo "Starting RBdigital export"
#cd /usr/local/aspen-discovery/code/rbdigital_export; java -jar rbdigital_export.jar arlington.production &
#sleep 5
echo "Starting User List Indexing"
cd /usr/local/aspen-discovery/code/user_list_indexer; java -jar user_list_indexer.jar arlington.production &

