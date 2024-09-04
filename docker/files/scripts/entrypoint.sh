#!/bin/sh
set -e

if [ "$#" -eq 0 ]; then
    exec /start.sh
elif [ "$1" = 'cron' ]; then
    exec /cron.sh
else
    exec "$@"
fi
