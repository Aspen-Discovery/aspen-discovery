#!/bin/bash
set -e

export CONFIG_DIRECTORY="/usr/local/aspen-discovery/sites/$SITE_NAME"

# Move to docker directory
cd "/usr/local/aspen-discovery/docker/files/scripts" || exit

# Check if site configuration exists
confSiteFile="$CONFIG_DIRECTORY/conf/config.ini"
if [ ! -f "$confSiteFile" ] ; then
  mkdir -p "$CONFIG_DIRECTORY"
  if ! php createConfig.php "$CONFIG_DIRECTORY" ; then
    echo "ERROR : FAILED TO CREATE ASPEN SETTINGS"
    exit 1
  fi

fi

# Initialize Aspen database
if ! php initDatabase.php ; then
  echo "ERROR : FAILED TO INITIALIZE DATABASE"
  exit 1
fi

# Initialize Koha Connection
if ! php initKohaLink.php ; then
  echo "ERROR : FAILED TO ESTABLISH A CONNECTION WITH KOHA"
  exit 1
fi

# Create missing dirs and fix ownership and permissions if needed
if ! php createDirs.php ; then
  echo "ERROR : FAILED TO CREATE DIRECTORIES OR TRY TO FIX OWNERSHIP AND PERMISSIONS"
  exit 1
fi

# Move and create temporarily sym-links to etc/cron directory
sanitizedSitename=$(echo "$SITE_NAME" | tr -dc '[:alnum:]_')
cp "$CONFIG_DIRECTORY/conf/crontab_settings.txt" "/etc/cron.d/$sanitizedSitename"

# Move and create temporarily sym-links to data directory
dataDir="/data/aspen-discovery/$SITE_NAME"
localDir="/usr/local/aspen-discovery/code/web"

ln -s "$dataDir/images" "$localDir/images"
ln -s "$dataDir/files" "$localDir/files"
ln -s "$dataDir/fonts" "$localDir/fonts"

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
