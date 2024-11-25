#!/bin/bash

export CONFIG_DIRECTORY="/usr/local/aspen-discovery/sites/${SITE_NAME}"

# Check if site configuration exists
confSiteFile="$CONFIG_DIRECTORY/conf/config.ini"

tries=0

while [ ! -f "$confSiteFile" ]; do
	sleep 5
	((tries++))

	if [ $tries -eq 5 ] ; then
		echo "ERROR: Site configuration not initialized. Skipping cron startup and waiting"
		exit 1
	fi

done

# Set crontab to be executed
crontab -u root "$CONFIG_DIRECTORY/conf/crontab" >/proc/1/fd/1 2>/proc/1/fd/2

# Start cron daemon
cron -f -L 15 >/proc/1/fd/1 2>/proc/1/fd/2
