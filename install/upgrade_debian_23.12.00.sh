#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

echo "Starting Upgrade 23.12.00.sh for $1"

service mysql stop
sleep 10

apt-get remove -y mariadb-server

cp mariadb.list /etc/apt/sources.list.d/mariadb.list
apt-get update
apt-get -y install mariadb-server

service mysql start
sleep 10

php /usr/local/aspen-discovery/install/runMariaDbUpgrade_23_12.php $1

apt-get install -y software-properties-common
add-apt-repository ppa:ondrej/php
apt-get update
apt-get install -y php8.3

service apache2 restart
sleep 10

echo "Finished Upgrade 23.12.00.sh for $1"