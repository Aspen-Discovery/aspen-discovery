#!/bin/sh
/usr/local/aspen-discovery/sites/model2.production/model2.production.sh restart

#Wait for solr to restart and warm up
sleep 10

#TODO: Check for which processes are included on the server, kill the old version, start the new version.
echo "Starting koha export"
cd /usr/local/aspen-discovery/code/koha_export; java -jar koha_export.jar model2.production &
sleep 5
echo "Starting User List Indexing"
cd /usr/local/aspen-discovery/code/user_list_indexer; java -jar user_list_indexer.jar model2.production &
