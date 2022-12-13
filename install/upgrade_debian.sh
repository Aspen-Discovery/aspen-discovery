#!/bin/sh
#Updates a site to a new version. This script is intended to be run as root
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

service cron stop

cd /usr/local/aspen-discovery
git pull origin $2

cd /usr/local/aspen-discovery/install
if [ -f "/usr/local/aspen-discovery/install/upgrade_debian_$2.sh" ]; then
  /usr/local/aspen-discovery/install/upgrade_debian_$2.sh
fi

echo "Run database maintenance, and then press return when done"
# shellcheck disable=SC2034
read waitOver

pkill java
sudo service mysqld restart
apachectl restart
cd /usr/local/aspen-discovery/data_dir_setup
/usr/local/aspen-discovery/data_dir_setup/update_solr_files.sh $1

cd /usr/local/aspen-discovery
git gc

service cron start

if [ -f "/usr/local/aspen-discovery/install/upgrade_complete_debian_$2.sh" ]; then
  /usr/local/aspen-discovery/install/upgrade_complete_debian_$2.sh
fi

echo "Upgrade completed."

