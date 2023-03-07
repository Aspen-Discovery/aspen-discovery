#!/bin/bash

file="crontab_settings.txt "

search="10 0 * * *    root    /usr/local/aspen-discovery/code/cron/nightly_mysql_dump.sh $1 aspen 2>&1 >/dev/null"

replace="10 0 * * *    root    /usr/local/aspen-discovery/code/web/cron/backupAspen.php $1 aspen 2>&1 >/dev/null"

sed -i "s/$search/$replace/g" "$file"


