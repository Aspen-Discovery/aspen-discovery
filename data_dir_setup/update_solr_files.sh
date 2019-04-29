#!/bin/sh
# Copies needed solr files to the server specified as a command line argument
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
fi
echo "Updating $1"
cp -r solr7 /data/aspen-discovery/$1

echo "cd ../sites/$1"
cd "../sites/$1"
echo "exec $1.sh restart"
exec $1.sh restart
echo "cd ../../data_dir_setup"
cd "../../data_dir_setup"