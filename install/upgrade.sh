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

sudo yum -y update
sudo apachectl graceful

cd /usr/local/aspen-discovery
git pull origin $2

cd /usr/local/aspen-discovery/install
if [ -f "/usr/local/aspen-discovery/install/upgrade_$2.sh" ]; then
  /usr/local/aspen-discovery/install/upgrade_$2.sh
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

service crond start

echo "Upgrade completed."

