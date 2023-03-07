#!/bin/bash

yum -y install php php-imagick

file="/usr/local/aspen-discovery/sites/$1/conf/crontab_settings.txt"

search="10 0 \* \* \*\s+root\s+\/usr\/local\/aspen-discovery\/code\/cron\/nightly_mysql_dump\.sh $1 aspen 2>&1 >\s*\/dev\/null"

replace="10 0 \* \* \*    root    php \/usr\/local\/aspen-discovery\/code\/web\/cron\/backupAspen\.php $1"

cp /usr/local/aspen-discovery/sites/$1/conf/crontab_settings.txt /usr/local/aspen-discovery/sites/$1/conf/crontab_settings.txt.bak
sed -i "s/$search/$replace/g" "$file"
