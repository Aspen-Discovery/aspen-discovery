#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

apt-get install -y "php8.0-ldap"

php /usr/local/aspen-discovery/install/updateCron_23_05.php $1
php /usr/local/aspen-discovery/install/updateCron_23_06.php $1
php /usr/local/aspen-discovery/install/updateCron_23_09.php $1