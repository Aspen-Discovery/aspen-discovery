#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

cp /usr/local/aspen-discovery/install/limits.conf /etc/security/limits.conf
