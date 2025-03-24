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

systemctl stop cron
pkill -9 java

apt-get update

git config --global --add safe.directory /usr/local/aspen-discovery
cd /usr/local/aspen-discovery
git pull origin $2

cd /usr/local/aspen-discovery/install
if [ -f "/usr/local/aspen-discovery/install/upgrade_debian_$2.sh" ]; then
  echo "Running version updates"
  /usr/local/aspen-discovery/install/upgrade_debian_$2.sh $1
fi

if [ -f "/usr/local/aspen-discovery/install/updateCron_$2.php" ]; then
  echo "Updating cron configuration"
  php /usr/local/aspen-discovery/install/updateCron_$2.php $1
fi

echo "Run database maintenance, and then press return when done"
# shellcheck disable=SC2034
read waitOver

systemctl restart apache2 mysql
cd /usr/local/aspen-discovery/data_dir_setup
/usr/local/aspen-discovery/data_dir_setup/update_solr_files_debian.sh $1

cd /usr/local/aspen-discovery
git gc

systemctl start cron

echo "Upgrade completed."

