#!/bin/bash

#Wait for 'db' service responses
while ! nc -z "$DATABASE_HOST" "$DATABASE_PORT"; do sleep 3; done

confDir="$CONFIG_DIRECTORY"

#Move to docker directory
cd "/usr/local/aspen-discovery/docker" || exit

#Check if site configuration exists
confSiteFile="$confDir/conf/config.ini"
if [ ! -f "$confSiteFile" ] ; then
  mkdir -p "$confDir"
  php createConfig.php "$confDir"
fi

#Create a user to manage Aspen database
if [ ! -z "$DATABASE_ROOT_PASSWORD" ] ; then
	mysql -u"$DATABASE_ROOT_USER" -p"$DATABASE_ROOT_PASSWORD" -h"$DATABASE_HOST" -P"$DATABASE_PORT" -e "CREATE USER '${DATABASE_USER}'@'%' IDENTIFIED BY '${DATABASE_PASSWORD}'; GRANT ALL PRIVILEGES ON ${DATABASE_NAME}.* TO '${DATABASE_USER}'@'%'; FLUSH PRIVILEGES;"
fi

#Create and initialize Aspen database
confDBFile="$confDir/conf/config.pwd.ini"
php initDatabase.php "$confDBFile"

#Initialize Koha Connection
php initKohaLink.php "$confSiteFile" "$confDBFile"

#Fix owners and permissions
php createDirs.php "$confSiteFile"

#Change the priority (for Aspen sign in purposes)
statement="UPDATE account_profiles SET weight=0 WHERE name='admin'; UPDATE account_profiles SET weight=1 WHERE name='ils';"
mysql -u"$DATABASE_USER" -p"$DATABASE_PASSWORD" -h"$DATABASE_HOST" -P"$DATABASE_PORT" "$DATABASE_NAME" -e "$statement"

#Move and create temporarily sym-links to etc/cron directory
sanitizedSitename=$(echo "$SITE_NAME" | tr -dc '[:alnum:]_')
ln -s "$confDir/conf/crontab_settings.txt" "/etc/cron.d/$sanitizedSitename"

#Move and create temporarily sym-links to data directory
dataDir="/data/aspen-discovery/$SITE_NAME"
localDir="/usr/local/aspen-discovery/code/web"

ln -s "$dataDir/images" "$localDir/images"
ln -s "$dataDir/files" "$localDir/files"
ln -s "$dataDir/fonts" "$localDir/fonts"

# Wait for mysql startup
while ! nc -z "$DATABASE_HOST" "$DATABASE_PORT"; do sleep 1; done

# FIXME ENABLE_APACHE and ENABLE_CRON should be mutually exclussive
# and instead of using 'service' they should run in foreground as the last
# command to run, instead of the sleep infinity trick

# Turn on apache
if [ "$ASPEN_BACKEND" == "yes" ]; then
	service apache2 start
fi

# Turn on Cron
if [ "$ASPEN_CRON" == "yes" ]; then
	service cron start
	php /usr/local/aspen-discovery/code/web/cron/checkBackgroundProcesses.php "$SITE_NAME" &
fi

#Infinite loop
/bin/bash -c "trap : TERM INT; sleep infinity & wait"