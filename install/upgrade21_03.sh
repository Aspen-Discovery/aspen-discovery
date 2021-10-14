#!/bin/sh
#Updates a site to 21.03.00 from a previous version
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

yum -y install php-mbstring
cd /usr/local/aspen-discovery
git pull origin 21.03.00
cd install
./setup_aspen_user.sh

service crond stop
echo "Run database maintenance, and then press return when done"
read waitOver

echo "In a new window, update cron to run appropriate commands as the aspen user, and then press return when done"
read waitOver

pkill java

./updateSitePermissions.sh $1

apachectl restart

service crond start

echo "Upgrade completed, make sure that fields in the user table are encrypted and that all background tasks restart"

