#!/bin/bash
set -e

if [ "$#" -eq 0 ]; then
    
    # Adjust permissions if required
    if [[ ! -z "${LOCAL_USER_ID}" && "${LOCAL_USER_ID}" != "33" ]]; then
    	echo "%   Setting www-data to UID=${LOCAL_USER_ID}"
        usermod -o -u ${LOCAL_USER_ID} "www-data"
    fi
    chown -R www-data /usr/local/aspen-discovery
    exec /start.sh

elif [ "$1" = 'apache' ]; then

    # Adjust permissions if required
    if [[ ! -z "${LOCAL_USER_ID}" && "${LOCAL_USER_ID}" != "33" ]]; then
    	echo "%   Setting www-data to UID=${LOCAL_USER_ID}"
        usermod -o -u ${LOCAL_USER_ID} "www-data"
        # Fix permissions due to UID change
        chown -R "www-data" "/var/log/apache2"
    fi
    chown -R www-data /usr/local/aspen-discovery
    exec /apache.sh

elif [ "$1" = 'cron' ]; then
    
    # Adjust permissions if required
    if [[ ! -z "${LOCAL_USER_ID}" && "${LOCAL_USER_ID}" != "33" ]]; then
    	echo "%   Setting www-data to UID=${LOCAL_USER_ID}"
        usermod -o -u ${LOCAL_USER_ID} "www-data" 
    fi
    chown -R www-data /usr/local/aspen-discovery
    exec /cron.sh

else
    exec "$@"
fi
