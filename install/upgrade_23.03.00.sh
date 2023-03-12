#!/bin/bash

yum -y install php php-imagick

file="/usr/local/aspen-discovery/sites/$1/conf/crontab_settings.txt"

search="/usr/local/aspen-discovery/code/cron/nightly_mysql_dump\.sh $1 aspen 2>&1 >\s*/dev/null"

replace="php /usr/local/aspen-discovery/code/web/cron/backupAspen\.php $1"

cp "$file" "$file.bak"
#echo "sed -i \"s#$search#$replace#g\" \"$file\""
sed -i "s#$search#$replace#g" "$file"
