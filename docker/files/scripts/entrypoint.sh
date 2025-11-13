#!/bin/bash
set -e

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ENTRYPOINT] $1"
}

USER_NAME="www-data"
SOURCE_DIR="/usr/local/aspen-discovery"
APACHE_LOG_DIR="/var/log/apache2"

# Adjust user ID if needed
if [[ -n "${LOCAL_USER_ID}" && "${LOCAL_USER_ID}" != "33" ]]; then
	log "Setting UID for ${USER_NAME} to ${LOCAL_USER_ID}"
	usermod -o -u "$LOCAL_USER_ID" "$USER_NAME"
fi

# Determine service to run
if [ "$#" -eq 0 ]; then

	exec "/start.sh"

elif [ "$1" = 'apache' ]; then

	chown -R ${USER_NAME} "${APACHE_LOG_DIR}"
	exec /apache.sh

elif [ "$1" = 'cron' ]; then

	exec /cron.sh

else
	exec "$@"
fi
