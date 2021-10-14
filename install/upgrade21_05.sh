#!/bin/sh
#Updates a site to 21.05.00 from a previous version
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

# This gets done outside the script since this file is part of the release.
# cd /usr/local/aspen-discovery
# git pull origin 21.05.00

chown -R aspen:aspen_apache /usr/local/aspen-discovery/code/web/sitemaps
usermod -a -G aspen_apache aspen

service crond stop
echo "Run database maintenance, and then press return when done"
read waitOver

pkill java

apachectl restart

service crond start

echo "Upgrade completed"

