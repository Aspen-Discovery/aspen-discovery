#!/bin/bash
set -e

# Wait for 'db' service responses
while ! nc -z "$DATABASE_HOST" "$DATABASE_PORT"; do sleep 3; done

confDir="/usr/local/aspen-discovery/sites/$SITE_NAME"

# Move to docker directory
cd "/usr/local/aspen-discovery/docker/files/scripts" || exit

# Check if site configuration exists
confSiteFile="$confDir/conf/config.ini"
if [ ! -f "$confSiteFile" ] ; then
  mkdir -p "$confDir"
  if ! php createConfig.php "$confDir" ; then
    echo "Fail on createConfig.php"
  fi

fi

# Initialize Aspen database
php initDatabase.php

# Initialize Koha Connection
php initKohaLink.php

# Create missing dirs and fix ownership and permissions if needed
php createDirs.php

# Move and create temporarily sym-links to etc/cron directory
sanitizedSitename=$(echo "$SITE_NAME" | tr -dc '[:alnum:]_')
ln -s "$confDir/conf/crontab_settings.txt" "/etc/cron.d/$sanitizedSitename"

# Move and create temporarily sym-links to data directory
dataDir="/data/aspen-discovery/$SITE_NAME"
localDir="/usr/local/aspen-discovery/code/web"

ln -s "$dataDir/images" "$localDir/images"
ln -s "$dataDir/files" "$localDir/files"
ln -s "$dataDir/fonts" "$localDir/fonts"

# Wait for mysql startup
while ! nc -z "$DATABASE_HOST" "$DATABASE_PORT"; do sleep 1; done

# FIXME ENABLE_APACHE and ENABLE_CRON should be mutually exclusive
# and instead of using 'service' they should run in foreground as the last
# command to run, instead of the sleep infinity trick

# Turn on apache
if [ "$ASPEN_BACKEND" == "yes" ]; then
	service apache2 start
fi

# Run pending database updates
echo "127.0.0.1    $SITE_NAME" >> /etc/hosts
curl -k http://"$SITE_NAME"/API/SystemAPI?method=runPendingDatabaseUpdates

# Start Cron
if [ "$ASPEN_CRON" == "yes" ]; then
	service cron start
	php /usr/local/aspen-discovery/code/web/cron/checkBackgroundProcesses.php "$SITE_NAME" &
fi

# Infinite loop
/bin/bash -c "trap : TERM INT; sleep infinity & wait"