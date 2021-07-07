#!/bin/sh
#Updates a site to a new version
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi
if [ -z "$2" ]
  then
    echo "Please provide the version number to upgrade to as the second argument."
    exit 1
fi

service crond stop

cd /usr/local/aspen-discovery/install
if [ -f "/usr/local/aspen-discovery/install/upgrade_$2.sh" ]; then
  /usr/local/aspen-discovery/install/upgrade_$2.sh
fi

echo "Run database maintenance, and then press return when done"
# shellcheck disable=SC2034
read waitOver

pkill java
cd /usr/local/aspen-discovery/data_dir_setup
/usr/local/aspen-discovery/data_dir_setup/update_solr_files.sh $1

service crond start

echo "Upgrade completed."

