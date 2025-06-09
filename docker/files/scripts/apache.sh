#!/bin/bash

echo "%   * Starting Apache"

export CONFIG_DIRECTORY="/usr/local/aspen-discovery/sites/${SITE_NAME}"

# Check if site configuration exists
apacheConfFile="$CONFIG_DIRECTORY/httpd-${SITE_NAME}.conf"

tries=0

while [ ! -f "$apacheConfFile" ]; do
	sleep 5
	((tries++))

	if [ $tries -eq 10 ] ; then
		echo "%   ERROR: Site configuration not initialized. Skipping apache startup and waiting"
		exit 1
	fi

done

mkdir -p /var/run/apache2
chown -R www-data:www-data /var/run/apache2
source /etc/apache2/envvars

# Move to docker directory
cd "/usr/local/aspen-discovery/docker/files/apache2/" || exit

# Set Apache configurations 
echo "%   * Setting Apache configurations";
if ! php setApacheConf.php $apacheConfFile ; then
	echo "%   ERROR: Apache initialization failed"
	exit 1
fi

# Start Apache in the background
apache2 -D FOREGROUND &

# Wait for Apache to be ready
tries=0
until curl -sf http://"$SITE_NAME" > /dev/null; do
  echo "%   * Waiting for Apache..."
  sleep 5
	((tries++))

	if [ $tries -eq 10 ] ; then
		echo "ERROR: Apache could not initialize correctly"
		exit 1
	fi
done

# Run any pending database updates
echo "%   * Triggering pending database updates"
curl -k http://"$SITE_NAME"/API/SystemAPI?method=runPendingDatabaseUpdates

echo "%"
echo "%   Aspen Discovery ready to use!"
echo "%"
echo "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%"

# Bring Apache to the foreground
wait


