#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

printf "Updating cron configuration\n"
php /usr/local/aspen-discovery/install/updateCron_24_10.php $1
