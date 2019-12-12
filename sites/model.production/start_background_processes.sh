#!/bin/sh
/usr/local/aspen-discovery/sites/model.production/model.production.sh restart

#Wait for solr to restart and warm up
sleep 10

#TODO: Check for which processes are included on the server, kill the old version, start the new version.
echo "Starting koha export"
cd /usr/local/aspen-discovery/code/koha_export; java -jar koha_export.jar model.production &
sleep 5
echo "Starting Overdrive export"
cd /usr/local/aspen-discovery/code/overdrive_api_extract; java -jar overdrive_extract.jar model.production &
sleep 5
echo "Starting Cloud Library export"
cd /usr/local/aspen-discovery/code/cloudlibrary_export; java -jar cloud_library_export.jar model.production &
sleep 5
echo "Starting User List Indexing"
cd /usr/local/aspen-discovery/code/user_list_indexer; java -jar user_list_indexer.jar model.production &
sleep 5
echo "Starting Side Load Processing"
cd /usr/local/aspen-discovery/code/sideload_processing; java -jar sideload_processing.jar model.production &

