#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

echo "Updating cron\n"
php /usr/local/aspen-discovery/install/updateCron_24_01.php $1