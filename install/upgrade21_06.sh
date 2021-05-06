#!/bin/sh
#Updates a site to 21.03.00 from a previous version
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

yum -y install php-soap
cd /usr/local/aspen-discovery
git pull origin 21.06.00

service crond stop
echo "Run database maintenance, and then press return when done"
read waitOver

pkill java
cd data_dir_setup
./update_solr_files.sh $1

apachectl restart

service crond start

echo "Upgrade completed"

